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
	private const ALLOWED_REDIRECT_STATUSES = [ 301, 302, 303, 307, 308 ];

	public function __construct() {
		parent::__construct( 'WhaleShortUrl' );
	}

	/**
	 * @param string|null $subPage Encoded revision identifier
	 */
	public function execute( $subPage ): void {
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

		$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		if ( !$permissionManager->quickUserCan( 'read', $this->getUser(), $title ) ) {
			$out->showErrorPage( 'badaccess', 'badaccess-group0' );
			return;
		}

		$this->getRequest()->response()->header(
			'Location: ' . $title->getFullURL(),
			true,
			$this->getRedirectStatus( $config->get( 'WhaleShortUrlRedirectStatus' ) )
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

		$namespace = $row->page_namespace ?? null;
		$titleText = $row->page_title ?? null;
		if ( !is_numeric( $namespace ) || !is_string( $titleText ) ) {
			return null;
		}

		return Title::makeTitleSafe( (int)$namespace, $titleText );
	}

	private function getRedirectStatus( mixed $status ): int {
		$status = is_numeric( $status ) ? (int)$status : 302;

		return in_array( $status, self::ALLOWED_REDIRECT_STATUSES, true ) ? $status : 302;
	}
}
