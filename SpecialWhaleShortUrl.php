<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

if (
	!class_exists( SpecialPage::class ) &&
	class_exists( MediaWiki\SpecialPage\SpecialPage::class )
) {
	class_alias( MediaWiki\SpecialPage\SpecialPage::class, SpecialPage::class );
}

class SpecialWhaleShortUrl extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WhaleShortUrl' );
	}

	/**
	 * @param string|null $subPage Encoded revision identifier
	 */
	public function execute( $subPage ) {
		$this->setHeaders();
		$out = $this->getOutput();
		$config = $this->getConfig();

		if ( $config->get( 'WhaleEnableShortUrls' ) === false ) {
			$out->showErrorPage( 'error', 'whale-short-url-disabled' );
			return;
		}

		$code = trim( (string)$subPage );
		$revisionId = WhaleShortUrl::decode( $code );
		if ( $revisionId === null ) {
			$out->showErrorPage( 'error', 'whale-short-url-invalid' );
			return;
		}

		$title = $this->getTitleFromRevisionId( $revisionId );
		if ( !$title || !$title->exists() ) {
			$out->showErrorPage( 'error', 'whale-short-url-missing' );
			return;
		}

		$this->getRequest()->response()->header(
			'Location: ' . $title->getFullURL(),
			true,
			max( 300, min( 308, (int)$config->get( 'WhaleShortUrlRedirectStatus' ) ) )
		);
		$out->disable();
	}

	private function getTitleFromRevisionId( int $revisionId ): ?Title {
		$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$row = $db->selectRow(
			[ 'revision', 'page' ],
			[ 'page_namespace', 'page_title' ],
			[
				'rev_id' => $revisionId,
				'rev_deleted' => 0,
			],
			__METHOD__,
			[],
			[ 'page' => [ 'JOIN', 'rev_page = page_id' ] ]
		);

		if ( !$row ) {
			return null;
		}

		return Title::makeTitleSafe( (int)$row->page_namespace, $row->page_title );
	}
}
