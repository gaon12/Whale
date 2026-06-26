<?php // @codingStandardsIgnoreLine

use MediaWiki\MediaWikiServices;

class WhaleContributionGraph {
	private SkinWhale $skin;

	public function __construct( SkinWhale $skin ) {
		$this->skin = $skin;
	}

	/**
	 * @param array<string,int> $counts
	 * @param int $days
	 * @param array<int,int> $levels
	 * @return array{weeks:array<int,array<string,mixed>>,legend:array<int,array<string,string>>}
	 */
	public function buildGraph( array $counts, int $days, array $levels ): array {
		$today = new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) );
		$start = $today->modify( '-' . ( $days - 1 ) . ' days' );
		$weeks = [];
		$currentWeek = [ 'days' => [] ];
		$weekday = (int)$start->format( 'w' );

		for ( $i = 0; $i < $weekday; $i++ ) {
			$currentWeek['days'][] = [ 'is-empty' => true ];
		}

		for ( $i = 0; $i < $days; $i++ ) {
			$date = $start->modify( '+' . $i . ' days' );
			$key = $date->format( 'Ymd' );
			$count = $counts[$key] ?? 0;
			$currentWeek['days'][] = [
				'date' => $date->format( 'Y-m-d' ),
				'count' => (string)$count,
				'level' => 'whale-contrib-level-' . $this->getContributionLevel( $count, $levels ),
				'label' => $this->skin->msg( 'whale-contrib-graph-day', $count, $date->format( 'Y-m-d' ) )->text(),
			];

			if ( count( $currentWeek['days'] ) === 7 ) {
				$weeks[] = $currentWeek;
				$currentWeek = [ 'days' => [] ];
			}
		}

		if ( count( $currentWeek['days'] ) > 0 ) {
			while ( count( $currentWeek['days'] ) < 7 ) {
				$currentWeek['days'][] = [ 'is-empty' => true ];
			}
			$weeks[] = $currentWeek;
		}

		return [
			'weeks' => $weeks,
			'legend' => [
				[ 'level' => 'whale-contrib-level-0' ],
				[ 'level' => 'whale-contrib-level-1' ],
				[ 'level' => 'whale-contrib-level-2' ],
				[ 'level' => 'whale-contrib-level-3' ],
				[ 'level' => 'whale-contrib-level-4' ],
			],
		];
	}

	/**
	 * @param int $count
	 * @param array<int,int> $levels
	 */
	private function getContributionLevel( int $count, array $levels ): int {
		$level = 0;
		foreach ( $levels as $index => $threshold ) {
			if ( $count >= $threshold ) {
				$level = min( 4, $index + 1 );
			}
		}

		return $level;
	}

	/**
	 * @param string $userName
	 * @param int $days
	 * @param array<int,int>|null $namespaces
	 * @param int $ttl
	 * @return array<string,int>
	 */
	public function getCounts( string $userName, int $days, ?array $namespaces, int $ttl ): array {
		$services = MediaWikiServices::getInstance();
		$cache = $services->getMainWANObjectCache();
		$cacheKey = $cache->makeKey(
			'whale',
			'contrib-graph',
			$userName,
			$days,
			$namespaces === null ? 'all' : implode( ',', $namespaces )
		);

		try {
			return $cache->getWithSetCallback(
				$cacheKey,
				$ttl,
				static function () use ( $userName, $days, $namespaces ) {
					$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
					$db = $lb->getConnection( DB_REPLICA );
					$start = gmdate( 'Ymd000000', time() - ( $days - 1 ) * 86400 );
					$tables = [ 'revision', 'actor' ];
					$joins = [ 'actor' => [ 'JOIN', 'rev_actor = actor_id' ] ];
					$conds = [
						'actor_name' => $userName,
						'rev_deleted' => 0,
						'rev_timestamp >= ' . $db->addQuotes( $start ),
					];

					if ( $namespaces !== null ) {
						$tables[] = 'page';
						$joins['page'] = [ 'JOIN', 'rev_page = page_id' ];
						$conds['page_namespace'] = $namespaces;
					}

					$rows = $db->select(
						$tables,
						[
							'day' => 'SUBSTR(rev_timestamp,1,8)',
							'edits' => 'COUNT(*)',
						],
						$conds,
						WhaleContributionGraph::class . '::getCounts',
						[
							'GROUP BY' => 'SUBSTR(rev_timestamp,1,8)',
							'ORDER BY' => 'day ASC',
						],
						$joins
					);
					$counts = [];
					foreach ( $rows as $row ) {
						$counts[$row->day] = (int)$row->edits;
					}

					return $counts;
				}
			);
		} catch ( Throwable $exception ) {
			return [];
		}
	}
}
