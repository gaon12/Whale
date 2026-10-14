<?php // @codingStandardsIgnoreLine

use MediaWiki\Content\Content;
use MediaWiki\Content\TextContent;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;

if ( !class_exists( Html::class ) ) {
	class_alias( \Html::class, Html::class );
}

if ( !class_exists( Linker::class ) ) {
	class_alias( \Linker::class, Linker::class );
}

class WhaleRenderer {
	private SkinWhale $skin;
	private ?WhaleAccountRenderer $accountRenderer = null;

	public function __construct( SkinWhale $skin ) {
		$this->skin = $skin;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getNavData(): array {
		global $wgSitename;

		$notifications = $this->getNotificationItems();

		return [
			'brand-href' => Title::newMainPage()->getLocalURL(),
			'brand-logo' => $this->getNavBarLogoUrl(),
			'brand-alt' => is_string( $wgSitename ?? null ) ? $wgSitename : 'Whale',
			'navigation-label' => $this->skin->msg( 'whale-navigation-menu' )->text(),
			'html-navigation-icon' => $this->renderIcon( 'list' ),
			'items' => array_merge( $this->getDefaultNavItems(), $this->getPortalItems( $this->parseNavbar() ) ),
			'theme-toggle' => $this->getThemeToggleData(),
			'data-login' => $this->getLoginData(),
			'has-notifications' => count( $notifications ) > 0,
			'notifications' => $notifications,
			'data-search' => $this->getSearchData(),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getSearchData(): array {
		$skin = $this->skin;
		$request = $skin->getRequest();

		return [
			'action' => $skin->getConfig()->get( 'Script' ),
			'title-value' => SpecialPage::getTitleFor( 'Search' )->getPrefixedDBkey(),
			'placeholder' => $skin->msg( 'searchsuggest-search' )->text(),
			'input-title' => Linker::titleAttrib( 'search' ),
			'accesskey' => Linker::accesskey( 'search' ),
			'value' => $request->getText( 'search' ),
			'go-label' => $skin->msg( 'go' )->text(),
			'search-label' => $skin->msg( 'searchbutton' )->text(),
			'html-eye-icon' => $this->renderIcon( 'eye' ),
			'html-search-icon' => $this->renderIcon( 'search' ),
		];
	}

	/** @return array<string,mixed> */
	public function getLoginData(): array {
		return $this->getAccountRenderer()->getLoginData();
	}

	/**
	 * @return array<string,mixed>|false
	 */
	public function getThemeToggleData() {
		if ( ( $GLOBALS['wgWhaleEnableAnonThemeToggle'] ?? true ) === false ) {
			return false;
		}

		$mode = $this->skin->getRequest()->getCookie( 'whale-dark-mode' );
		$isDark = $mode === 'dark';

		return [
			'label' => $this->skin->msg( $isDark ? 'whale-theme-toggle-light' : 'whale-theme-toggle-dark' )->text(),
			'aria-pressed' => $isDark ? 'true' : 'false',
			'html-moon-icon' => $this->renderIcon( 'moon' ),
			'html-sun-icon' => $this->renderIcon( 'sun' ),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getContentToolsData(): array {
		$skin = $this->skin;
		$user = $skin->getUser();
		$services = MediaWikiServices::getInstance();
		$title = $skin->getTitle();
		$action = $skin->getRequest()->getVal( 'action', 'view' );

		if ( !$title || $title->getNamespace() === NS_SPECIAL ) {
			return [ 'has-content-tools' => false ];
		}

		$linkRenderer = $services->getLinkRenderer();
		$permissionManager = $services->getPermissionManager();
		$watchlistManager = $services->getWatchlistManager();
		$revid = $skin->getRequest()->getText( 'oldid' );
		$editable = $permissionManager->quickUserCan( 'edit', $user, $title );
		$watched = $watchlistManager->isWatchedIgnoringRights( $user, $skin->getRelevantTitle() ) ? 'unwatch' : 'watch';
		$companionTitle = $title->isTalkPage() ? $title->getSubjectPage() : $title->getTalkPage();
		$buttons = [];
		if ( $action === 'view' ) {
			$buttons[] = [
				'html' => $linkRenderer->makeKnownLink(
					$title,
					new HtmlArmor( $this->renderIcon( 'star' ) . Html::element(
						'span',
						[ 'class' => 'whale-sr-only' ],
						$skin->msg( $watched )->text()
					) ),
					[
						'class' => 'whale-btn whale-btn-secondary tools-btn tools-watch',
						'id' => 'ca-' . $watched,
						'title' => Linker::titleAttrib( 'ca-' . $watched, 'withaccess' ),
						'accesskey' => Linker::accesskey( 'ca-' . $watched ),
					],
					[ 'action' => $watched ]
				),
			];
		}

		if ( $action !== 'edit' ) {
			$buttons[] = [
				'html' => $linkRenderer->makeKnownLink(
					$title,
					new HtmlArmor( $this->renderIcon( $editable ? 'edit' : 'lock' ) . ' ' . $skin->msg( 'edit' )->escaped() ),
					[
						'class' => 'whale-btn whale-btn-primary tools-btn tools-edit',
						'id' => 'ca-edit',
						'title' => Linker::titleAttrib( 'ca-edit', 'withaccess' ),
						'accesskey' => Linker::accesskey( 'ca-edit' ),
					],
					$revid ? [ 'action' => 'edit', 'oldid' => $revid ] : [ 'action' => 'edit' ]
				),
			];
		}

		if ( $action === 'edit' || $action === 'history' ) {
			$buttons[] = [
				'html' => $linkRenderer->makeKnownLink(
					$title,
					$skin->msg( 'article' )->plain(),
					[
						'class' => 'whale-btn whale-btn-secondary tools-btn',
						'title' => Linker::titleAttrib( 'ca-nstab-main', 'withaccess' ),
						'accesskey' => Linker::accesskey( 'ca-nstab-main' ),
					]
				),
			];
		}

		if ( $action !== 'edit' ) {
			$isTalk = $title->isTalkPage() && $action !== 'history';
			$buttons[] = [
				'html' => $linkRenderer->makeKnownLink(
					$companionTitle,
					$skin->msg( $isTalk ? 'nstab-main' : 'talk' )->plain(),
					[
						'class' => 'whale-btn whale-btn-secondary tools-btn',
						'title' => Linker::titleAttrib( $isTalk ? 'ca-nstab-main' : 'ca-talk', 'withaccess' ),
						'accesskey' => Linker::accesskey( $isTalk ? 'ca-nstab-main' : 'ca-talk' ),
					]
				),
			];
		}

		if ( $action !== 'history' ) {
			$buttons[] = [
				'html' => $linkRenderer->makeKnownLink(
					$title,
					$skin->msg( 'history' )->plain(),
					[
						'class' => 'whale-btn whale-btn-secondary tools-btn',
						'title' => Linker::titleAttrib( 'ca-history', 'withaccess' ),
						'accesskey' => Linker::accesskey( 'ca-history' ),
					],
					[ 'action' => 'history' ]
				),
			];
		}

		$dropdownItems = [];
		if ( $title->inNamespaces( NS_USER, NS_USER_TALK ) ) {
			$dropdownItems[] = [
				'html' => $linkRenderer->makeKnownLink(
					SpecialPage::getTitleFor( 'Contributions', $title->getText() ),
					$skin->msg( 'contributions' )->escaped(),
					[
						'class' => 'whale-dropdown-item',
						'title' => Linker::titleAttrib( 't-contributions', 'withaccess' ),
						'accesskey' => Linker::accesskey( 't-contributions' ),
					]
				),
			];
		}

		$dropdownItems[] = [
			'html' => $linkRenderer->makeKnownLink(
				$title,
				$skin->msg( 'whale-purge' )->plain(),
				[
					'class' => 'whale-dropdown-item',
					'title' => $skin->msg( 'whale-tooltip-purge' )->plain() . ' [alt+shift+p]',
					'accesskey' => 'p',
				],
				[ 'action' => 'purge' ]
			),
		];
		$dropdownItems[] = [
			'html' => $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'Whatlinkshere', $title->getPrefixedDBkey() ),
				$skin->msg( 'whatlinkshere' )->plain(),
				[
					'class' => 'whale-dropdown-item',
					'title' => Linker::titleAttrib( 't-whatlinkshere', 'withaccess' ),
					'accesskey' => Linker::accesskey( 't-whatlinkshere' ),
				]
			),
		];
		$dropdownItems[] = [
			'html' => $linkRenderer->makeKnownLink(
				$title,
				$skin->msg( 'whale-info' )->plain(),
				[
					'class' => 'whale-dropdown-item',
					'title' => $skin->msg( 'whale-tooltip-info' )->plain(),
				],
				[ 'action' => 'info' ]
			),
		];

		if ( $permissionManager->quickUserCan( 'move', $user, $title ) && $title->exists() ) {
			$dropdownItems[] = [
				'html' => $linkRenderer->makeKnownLink(
					SpecialPage::getTitleFor( 'Movepage', $title->getPrefixedDBkey() ),
					$skin->msg( 'move' )->plain(),
					[
						'class' => 'whale-dropdown-item',
						'title' => Linker::titleAttrib( 'ca-move', 'withaccess' ),
						'accesskey' => Linker::accesskey( 'ca-move' ),
					]
				),
			];
		}

		if ( $permissionManager->quickUserCan( 'protect', $user, $title ) ) {
			$protectionMsg = $this->isProtectedTitle( $title ) ? 'unprotect' : 'protect';
			$dropdownItems[] = [ 'is-divider' => true ];
			$dropdownItems[] = [
				'html' => $linkRenderer->makeKnownLink(
					$title,
					$skin->msg( $protectionMsg )->plain(),
					[
						'class' => 'whale-dropdown-item',
						'title' => Linker::titleAttrib( 'ca-' . $protectionMsg, 'withaccess' ),
						'accesskey' => Linker::accesskey( 'ca-' . $protectionMsg ),
					],
					[ 'action' => 'protect' ]
				),
			];
		}

		if ( $permissionManager->quickUserCan( 'delete', $user, $title ) && $title->exists() ) {
			$dropdownItems[] = [ 'is-divider' => true ];
			$dropdownItems[] = [
				'html' => $linkRenderer->makeKnownLink(
					$title,
					$skin->msg( 'delete' )->plain(),
					[
						'class' => 'whale-dropdown-item',
						'title' => Linker::titleAttrib( 'ca-delete', 'withaccess' ),
						'accesskey' => Linker::accesskey( 'ca-delete' ),
					],
					[ 'action' => 'delete' ]
				),
			];
		}

		return [
			'has-content-tools' => true,
			'buttons' => $buttons,
			'show-share' => $action === 'view',
			'share-label' => $skin->msg( 'whale-share' )->text(),
			'html-share-icon' => $this->renderIcon( 'share-square' ),
			'more-label' => $skin->msg( 'whale-more-tools' )->text(),
			'dropdown-items' => $dropdownItems,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getLiveRecentData( string $mode = 'desktop' ): array {
		global $wgWhaleEnableLiveRC,
			$wgWhaleMaxRecent,
			$wgWhaleLiveRecentFixedHeight,
			$wgWhaleLiveRCRefreshInterval,
			$wgWhaleLiveRCArticleNamespaces,
			$wgWhaleLiveRCTalkNamespaces;

		if ( !$wgWhaleEnableLiveRC ) {
			return [ 'has-live-recent' => false ];
		}

		$skin = $this->skin;
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$fixedHeight = ( $wgWhaleLiveRecentFixedHeight ?? false ) === true &&
			$userOptionsLookup->getOption( $skin->getUser(), 'whale-live-recent-fixed-height' ) !== false;
		$refreshRaw = $wgWhaleLiveRCRefreshInterval ?? 60;
		$refreshInterval = max( 10, is_numeric( $refreshRaw ) ? (int)$refreshRaw : 60 );
		$isMobile = $mode === 'mobile';
		$articleNamespaces = $this->normalizeRecentChangeNamespaces(
			$wgWhaleLiveRCArticleNamespaces ?? null,
			[ 0, 4, 10, 12, 14 ]
		);
		$talkNamespaces = $this->normalizeRecentChangeNamespaces(
			$wgWhaleLiveRCTalkNamespaces ?? null,
			[ 1, 3, 5, 7, 9, 11, 13, 15 ]
		);
		$feeds = [
			$this->getLiveRecentFeedData(
				$mode . '-live-recent-article-list',
				$skin->msg( 'recentchanges' )->text(),
				'sync',
				implode( '|', $articleNamespaces ),
				true
			),
		];

		if ( !$isMobile ) {
			$feeds[] = $this->getLiveRecentFeedData(
				$mode . '-live-recent-talk-list',
				$skin->msg( 'whale-recent-discussions' )->text(),
				'comments',
				implode( '|', $talkNamespaces ),
				false
			);
		}

		$maxRecent = is_numeric( $wgWhaleMaxRecent ?? null ) ? (int)$wgWhaleMaxRecent : 10;

		return [
			'has-live-recent' => true,
			'classes' => 'live-recent' . ( $fixedHeight ? ' live-recent-fixed-height' : '' ),
			'mode' => $mode,
			'limit' => $maxRecent,
			'refresh-ms' => $refreshInterval * 1000,
			'style' => '--whale-live-recent-limit: ' . $maxRecent .
				'; --whale-live-recent-refresh: ' . $refreshInterval . 's;',
			'feeds' => $feeds,
		];
	}

	/**
	 * @param mixed $namespaces
	 * @param array<int,int> $fallback
	 * @return array<int,int>
	 */
	private function normalizeRecentChangeNamespaces( mixed $namespaces, array $fallback ): array {
		if ( !is_array( $namespaces ) ) {
			return $fallback;
		}

		$normalized = array_values( array_unique( $this->normalizeIntegerList( $namespaces ) ) );
		return count( $normalized ) > 0 ? $normalized : $fallback;
	}

	/**
	 * @param array<mixed> $items
	 * @return array<int,int>
	 */
	private function normalizeIntegerList( array $items ): array {
		$normalized = [];
		foreach ( $items as $item ) {
			if ( is_int( $item ) || is_float( $item ) || is_string( $item ) || is_bool( $item ) ) {
				$normalized[] = (int)$item;
			}
		}

		return $normalized;
	}

	/**
	 * @return array<string,string|bool>
	 */
	private function getLiveRecentFeedData(
		string $listId,
		string $heading,
		string $icon,
		string $namespaces,
		bool $isActive
	): array {
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$tabId = $listId . '-tab';
		$panelId = $listId . '-panel';

		return [
			'list-id' => $listId,
			'tab-id' => $tabId,
			'panel-id' => $panelId,
			'heading' => $heading,
			'namespaces' => $namespaces,
			'is-active' => $isActive,
			'aria-selected' => $isActive ? 'true' : 'false',
			'tab-index' => $isActive ? '0' : '-1',
			'html-icon' => $this->renderIcon( $icon ),
			'html-more-link' => $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'Recentchanges' ),
				new HtmlArmor( '<span>' .
					$this->skin->msg( 'whale-view-more' )->escaped() .
					'</span>' . $this->renderIcon( 'angle-right' ) ),
				[ 'class' => 'live-recent-more' ]
			),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getFooterData(): array {
		$footerData = $this->skin->getWhaleFooterData();
		$categories = [];
		$lastModifiedHtml = '';

		foreach ( $footerData as $category => $categoryData ) {
			$links = is_array( $categoryData ) ? ( $categoryData['array-items'] ?? [] ) : [];
			if ( !is_array( $links ) || !$links ) {
				continue;
			}
			$categoryName = preg_replace( '/^data-/', '', $category );
			if ( $categoryName === 'icons' ) {
				continue;
			}

			$items = [];
			foreach ( $links as $link ) {
				if ( !is_array( $link ) ) {
					continue;
				}
				$rawName = $link['name'] ?? null;
				$name = is_string( $rawName ) ? $rawName : '';
				$rawHtml = $link['html'] ?? '';
				if ( $name === 'lastmod' ) {
					$lastModifiedHtml = is_string( $rawHtml ) ? $rawHtml : '';
					continue;
				}
				$items[] = [
					'class' => 'footer-' . $categoryName . '-' . Sanitizer::escapeClass( $name ),
					'html' => is_string( $rawHtml ) ? $rawHtml : '',
				];
			}

			$categories[] = [
				'class' => 'footer-' . Sanitizer::escapeClass( $categoryName ?? '' ),
				'items' => $items,
			];
		}

		$iconBlocks = [];
		foreach ( $this->skin->getWhaleFooterIcons() as $blockName => $footerIcons ) {
			$html = '';
			foreach ( $footerIcons as $icon ) {
				$html .= $this->skin->makeWhaleFooterIcon( $icon );
			}
			$iconBlocks[] = [
				'class' => 'footer-' . Sanitizer::escapeClass( $blockName ) . 'ico',
				'html' => $html,
			];
		}

		$stylePath = $this->skin->getConfig()->get( 'StylePath' );
		$stylePath = is_string( $stylePath ) ? $stylePath : '';

		$iconBlocks[] = [
			'class' => 'whale-footer-brand',
			'html' => Html::rawElement( 'a', [ 'href' => 'https://github.com/gaon12/Whale' ], Html::element( 'img', [
				'src' => $stylePath . '/Whale/img/whale_footer_img.png',
				'class' => 'whale-footer-brand-img',
				'alt' => 'Designed by Whale',
				'width' => '78',
				'height' => '31',
				'decoding' => 'async',
			] ) ),
		];

		return [
			'categories' => $categories,
			'icon-blocks' => $iconBlocks,
			'html-last-modified' => $this->skin->getWhaleRevisionMetadata() ?: $lastModifiedHtml,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getShortUrlData(): array {
		global $wgWhaleEnableShortUrls;

		$skin = $this->skin;
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$title = $skin->getTitle();
		$enabled = ( $wgWhaleEnableShortUrls ?? true ) !== false &&
			$userOptionsLookup->getOption( $skin->getUser(), 'whale-short-url', true ) !== false;

		if ( !$enabled || !$title || $title->getNamespace() === NS_SPECIAL ) {
			return [ 'has-short-url' => false ];
		}

		$revisionId = $this->getLatestRevisionId( $title );
		if ( $revisionId <= 0 ) {
			return [ 'has-short-url' => false ];
		}

		$code = WhaleShortUrl::encode( $revisionId );
		$url = WhaleShortUrl::buildUrl( $code );

		return [
			'has-short-url' => true,
			'url' => $url,
			'html-icon' => $this->renderIcon( 'link' ),
			'button-label' => $skin->msg( 'whale-short-url-button' )->text(),
			'modal-title' => $skin->msg( 'whale-short-url-title' )->text(),
			'description' => $skin->msg( 'whale-short-url-description' )->text(),
			'copy-label' => $skin->msg( 'whale-short-url-copy' )->text(),
			'close-label' => $skin->msg( 'whale-close' )->text(),
		];
	}

	/**
	 * @return array<string,string|bool>
	 */
	public function getExternalLinkWarningData(): array {
		if ( ( $GLOBALS['wgWhaleEnableExternalLinkWarning'] ?? false ) !== true ) {
			return [];
		}
		$skin = $this->skin;
		$trusted = $GLOBALS['wgWhaleExternalLinkTrustedOrigins'] ?? [];
		$trusted = is_array( $trusted ) ? array_values( array_filter( $trusted, 'is_string' ) ) : [];

		return [
			'enabled' => true,
			'trusted-origins' => json_encode( $trusted ) ?: '[]',
			'title' => $skin->msg( 'whale-external-link-title' )->text(),
			'description' => $skin->msg( 'whale-external-link-description' )->text(),
			'cancel-label' => $skin->msg( 'cancel' )->text(),
			'continue-label' => $skin->msg( 'whale-external-link-continue' )->text(),
			'show-decoded-label' => $skin->msg( 'whale-external-link-show-decoded' )->text(),
			'show-original-label' => $skin->msg( 'whale-external-link-show-original' )->text(),
			'close-label' => $skin->msg( 'whale-close' )->text(),
		];
	}

	/**
	 * @return array<string,string>
	 */
	public function getReferenceModalData(): array {
		$skin = $this->skin;

		return [
			'title' => $skin->msg( 'whale-reference-title' )->text(),
			'jump-label' => $skin->msg( 'whale-reference-jump' )->text(),
			'close-label' => $skin->msg( 'whale-close' )->text(),
			'html-icon' => $this->renderIcon( 'file' ),
		];
	}

	/** @return array<string,mixed> */
	public function getLoginModalData(): array {
		return $this->getAccountRenderer()->getLoginModalData();
	}

	private function getAccountRenderer(): WhaleAccountRenderer {
		$this->accountRenderer ??= new WhaleAccountRenderer( $this->skin );
		return $this->accountRenderer;
	}

	public function getIcon( string $icon ): string {
		return $this->renderIcon( $icon );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getUserContributionGraphData(): array {
		global $wgWhaleEnableUserContributionGraph,
			$wgWhaleContributionGraphDays,
			$wgWhaleContributionGraphNamespaces,
			$wgWhaleContributionGraphCacheTTL,
			$wgWhaleContributionGraphLevels;

		$skin = $this->skin;
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$title = $skin->getTitle();
		$enabled = ( $wgWhaleEnableUserContributionGraph ?? true ) !== false &&
			$userOptionsLookup->getOption( $skin->getUser(), 'whale-user-contribution-graph', true ) !== false;

		if (
			!$enabled ||
			!$title ||
			$title->getNamespace() !== NS_USER ||
			strpos( $title->getDBkey(), '/' ) !== false
		) {
			return [ 'has-user-contribution-graph' => false ];
		}

		$daysRaw = $wgWhaleContributionGraphDays ?? 365;
		$days = max( 7, min( 730, is_numeric( $daysRaw ) ? (int)$daysRaw : 365 ) );
		$levels = is_array( $wgWhaleContributionGraphLevels ?? null )
			? array_values( array_filter( $this->normalizeIntegerList( $wgWhaleContributionGraphLevels ), static function ( int $level ) {
				return $level > 0;
			} ) )
			: [ 1, 3, 6, 10 ];
		sort( $levels );
		if ( count( $levels ) === 0 ) {
			$levels = [ 1, 3, 6, 10 ];
		}

		$namespaces = is_array( $wgWhaleContributionGraphNamespaces ?? null )
			? array_values( array_unique( $this->normalizeIntegerList( $wgWhaleContributionGraphNamespaces ) ) )
			: null;
		$ttlRaw = $wgWhaleContributionGraphCacheTTL ?? 3600;
		$ttl = max( 60, is_numeric( $ttlRaw ) ? (int)$ttlRaw : 3600 );
		$userName = $title->getText();
		$graph = new WhaleContributionGraph( $this->skin );
		$counts = $graph->getCounts( $userName, $days, $namespaces, $ttl );
		$graphData = $graph->buildGraph( $counts, $days, $levels );
		$total = array_sum( $counts );

		return [
			'has-user-contribution-graph' => true,
			'title' => $skin->msg( 'whale-contrib-graph-title', $userName )->text(),
			'summary' => $skin->msg( 'whale-contrib-graph-summary', $skin->getLanguage()->formatNum( $total ), $skin->getLanguage()->formatNum( $days ) )->text(),
			'weeks' => $graphData['weeks'],
			'legend' => $graphData['legend'],
		];
	}

	private function getNavBarLogoUrl(): string {
		global $wgResourceBasePath, $wgWhaleNavBarLogoImage;

		if ( is_string( $wgWhaleNavBarLogoImage ?? null ) && trim( $wgWhaleNavBarLogoImage ) !== '' ) {
			return $wgWhaleNavBarLogoImage;
		}

		$resourceBasePath = is_string( $wgResourceBasePath ?? null )
			? rtrim( $wgResourceBasePath, '/' )
			: '';

		return $resourceBasePath . '/skins/Whale/img/logo.png';
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function getDefaultNavItems(): array {
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$skin = $this->skin;

		return [
			[
				'classes' => 'whale-navbar-item',
				'html-link' => $linkRenderer->makeKnownLink(
					new TitleValue( NS_SPECIAL, 'Recentchanges' ),
					new HtmlArmor( $this->renderIcon( 'sync' ) . '<span class="hide-title">' . $skin->msg( 'recentchanges' )->escaped() . '</span>' ),
					[
						'class' => 'whale-navbar-link',
						'title' => Linker::titleAttrib( 'n-recentchanges', 'withaccess' ),
						'accesskey' => Linker::accesskey( 'n-recentchanges' ),
					]
				),
			],
			[
				'classes' => 'whale-navbar-item',
				'html-link' => $linkRenderer->makeKnownLink(
					new TitleValue( NS_SPECIAL, 'Randompage' ),
					new HtmlArmor( $this->renderIcon( 'random' ) . '<span class="hide-title">' . $skin->msg( 'randompage' )->escaped() . '</span>' ),
					[
						'class' => 'whale-navbar-link',
						'title' => Linker::titleAttrib( 'n-randompage', 'withaccess' ),
						'accesskey' => Linker::accesskey( 'n-randompage' ),
					]
				),
			],
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $contents
	 * @return array<int,array<string,mixed>>
	 */
	private function getPortalItems( array $contents ): array {
		$skin = $this->skin;
		$user = $skin->getUser();
		$services = MediaWikiServices::getInstance();
		$userGroups = $services->getUserGroupManager()->getUserGroups( $user );
		$userRights = $services->getPermissionManager()->getUserPermissions( $user );
		$items = [];

		foreach ( $contents as $content ) {
			if ( !$this->canShowPortalItem( $content, $userGroups, $userRights ) ) {
				continue;
			}

			$children = [];
			foreach ( self::normalizePortalItemList( $content['children'] ?? null ) as $child ) {
				if ( !$this->canShowPortalItem( $child, $userGroups, $userRights ) ) {
					continue;
				}

				$subItems = [];
				foreach ( self::normalizePortalItemList( $child['children'] ?? null ) as $sub ) {
					if ( $this->canShowPortalItem( $sub, $userGroups, $userRights ) ) {
						$subItems[] = $this->buildPortalChildItem( $sub );
					}
				}

				$children[] = $this->buildPortalChildItem( $child, $subItems );
			}

			$classes = array_merge(
				[ 'whale-dropdown', 'whale-navbar-item' ],
				self::normalizeClassList( $content['wrapperClasses'] ?? null )
			);
			$linkClasses = array_merge(
				self::normalizeClassList( $content['classes'] ?? null ),
				[ 'whale-navbar-link' ]
			);
			$hasChildren = count( $children ) > 0;
			if ( $hasChildren ) {
				$linkClasses[] = 'whale-dropdown-toggle';
				$linkClasses[] = 'whale-dropdown-toggle-fix';
			}

			$items[] = [
				'classes' => implode( ' ', array_map( [ Sanitizer::class, 'escapeClass' ], $classes ) ),
				'html-link' => $this->buildPortalLink( $content, $linkClasses, $hasChildren ),
				'has-children' => $hasChildren,
				'children' => $children,
			];
		}

		return $items;
	}

	/**
	 * @param array<string,mixed> $item
	 * @param array<int,string> $userGroups
	 * @param array<int,string> $userRights
	 */
	private function canShowPortalItem( array $item, array $userGroups, array $userRights ): bool {
		return !(
			( !empty( $item['right'] ) && !in_array( $item['right'], $userRights ) ) ||
			( !empty( $item['group'] ) && !in_array( $item['group'], $userGroups ) )
		);
	}

	/**
	 * @param array<string,mixed> $item
	 * @param array<int,array<string,mixed>> $children
	 * @return array<string,mixed>
	 */
	private function buildPortalChildItem( array $item, array $children = [] ): array {
		$classes = array_merge( self::normalizeClassList( $item['classes'] ?? null ), [ 'whale-dropdown-item' ] );
		$hasChildren = count( $children ) > 0;
		$wrapperClasses = [ 'whale-dropdown-child' ];
		if ( $hasChildren ) {
			$classes[] = 'whale-dropdown-toggle';
			$classes[] = 'whale-dropdown-toggle-sub';
			$wrapperClasses[] = 'whale-dropdown-subitem';
		}

		return [
			'classes' => implode( ' ', array_map( [ Sanitizer::class, 'escapeClass' ], $wrapperClasses ) ),
			'html-link' => $this->buildPortalLink( $item, $classes, $hasChildren, true ),
			'has-children' => $hasChildren,
			'children' => $children,
		];
	}

	/**
	 * @param array<string,mixed> $item
	 * @param array<int,string> $classes
	 */
	private function buildPortalLink( array $item, array $classes, bool $hasChildren, bool $isChild = false ): string {
		global $wgWhaleNavbarParentLinks;

		$useParentLink = !$isChild && $hasChildren && !empty( $wgWhaleNavbarParentLinks );
		$isToggleButton = $hasChildren && !$useParentLink;
		$attrs = [
			'class' => implode( ' ', array_map( [ Sanitizer::class, 'escapeClass' ], $classes ) ),
			'title' => $item['title'] ?? '',
		];
		if ( !$isToggleButton ) {
			$attrs['href'] = $item['href'] ?? '#';
		}
		if ( !empty( $item['access'] ) ) {
			$attrs['accesskey'] = $item['access'];
		}
		if ( $hasChildren ) {
			if ( !$useParentLink ) {
				$attrs['data-whale-toggle'] = $isChild ? 'submenu' : 'dropdown';
				$attrs['role'] = 'button';
				$attrs['aria-expanded'] = 'false';
			}
			$attrs['aria-haspopup'] = 'true';
		}
		if ( $isToggleButton ) {
			$attrs['type'] = 'button';
		}

		$html = '';
		$icon = $item['icon'] ?? null;
		if ( is_string( $icon ) ) {
			$html .= $this->renderIcon( $icon );
		}
		$text = $item['text'] ?? null;
		if ( is_string( $text ) && $text !== '' ) {
			$html .= Html::element( 'span', [ 'class' => $isChild ? '' : 'hide-title' ], $text );
		}

		return Html::rawElement( $isToggleButton ? 'button' : 'a', $attrs, $html );
	}

	/**
	 * Normalize a parsed navbar 'children' value into a list of items with
	 * string keys, dropping anything that is not an array.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function normalizePortalItemList( mixed $value ): array {
		if ( !is_array( $value ) ) {
			return [];
		}

		$items = [];
		foreach ( $value as $entry ) {
			if ( !is_array( $entry ) ) {
				continue;
			}
			$item = [];
			foreach ( $entry as $key => $entryValue ) {
				$item[(string)$key] = $entryValue;
			}
			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Normalize a parsed navbar class list into non-empty strings.
	 *
	 * @return array<int,string>
	 */
	private static function normalizeClassList( mixed $classes ): array {
		if ( !is_array( $classes ) ) {
			return [];
		}

		$list = [];
		foreach ( $classes as $class ) {
			if ( is_string( $class ) && $class !== '' ) {
				$list[] = $class;
			}
		}

		return $list;
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	private function getNotificationItems(): array {
		$personalTools = $this->skin->getWhalePersonalTools();
		$items = [];
		foreach ( [ 'notifications-alert', 'notifications-notice' ] as $key ) {
			if (
				isset( $personalTools[$key] ) &&
				WhalePersonalTools::getFirstLinkCounter( $personalTools[$key] ) > 0
			) {
				$items[] = [ 'html' => $this->skin->makeWhaleListItem( $key, $personalTools[$key] ) ];
			}
		}

		return $items;
	}

	/**
	 * Parse [[MediaWiki:Whale-Navbar]].
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function parseNavbar(): array {
		$skin = $this->skin;
		$user = $skin->getUser();
		$userLang = $skin->getLanguage()->getCode();
		$globalData = $this->getCachedContentText( Title::newFromText( 'Whale-Navbar', NS_MEDIAWIKI ) );
		$globalLangData = $this->getCachedContentText( Title::newFromText( 'Whale-Navbar/' . $userLang, NS_MEDIAWIKI ) );
		$userData = $user->isRegistered()
			? $this->getCachedContentText( Title::newFromText( $user->getName() . '/Whale-Navbar', NS_USER ) )
			: '';
		$data = $userData !== '' ? $userData : ( $globalLangData !== '' ? $globalLangData : $globalData );
		if ( trim( $data ) === '' ) {
			return [];
		}

		return ( new WhaleNavbarParser( $this->skin ) )->parse( $data );
	}

	private function getLatestRevisionId( Title $title ): int {
		$revision = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionByTitle( $title );

		return $revision?->getId() ?? 0;
	}

	private function renderIcon( ?string $icon ): string {
		return WhaleIcon::render( $icon );
	}

	private function getContentText( ?Content $content = null ): ?string {
		if ( $content === null ) {
			return '';
		}

		if ( $content instanceof TextContent ) {
			return $content->getText();
		}

		return null;
	}

	private function getCachedContentText( ?Title $title ): string {
		if ( $title === null ) {
			return '';
		}

		$services = MediaWikiServices::getInstance();
		$cache = $services->getMainWANObjectCache();
		$cacheKey = $cache->makeKey(
			'whale',
			'whale-nav-content',
			$title->getNamespace(),
			$title->getDBkey(),
			$title->getLatestRevID()
		);

		return $cache->getWithSetCallback(
			$cacheKey,
			6 * 60 * 60,
			function () use ( $title ) {
				return $this->getContentText( $this->getContentOfTitle( $title ) ) ?: '';
			}
		);
	}

	private function getContentOfTitle( Title $title ): ?Content {
		$wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
		$page = $wikiPageFactory->newFromTitle( $title );

		return $page->getContent( RevisionRecord::RAW );
	}

	private function isProtectedTitle( Title $title ): bool {
		return MediaWikiServices::getInstance()->getRestrictionStore()->isProtected( $title );
	}
}
