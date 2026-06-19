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

	public function __construct( SkinWhale $skin ) {
		$this->skin = $skin;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getNavData(): array {
		global $wgSitename;

		return [
			'brand-href' => Title::newMainPage()->getLocalURL(),
			'brand-logo' => $this->getNavBarLogoUrl(),
			'brand-alt' => is_string( $wgSitename ?? null ) ? $wgSitename : 'Whale',
			'items' => array_merge( $this->getDefaultNavItems(), $this->getPortalItems( $this->parseNavbar() ) ),
			'theme-toggle' => $this->getThemeToggleData(),
			'data-login' => $this->getLoginData(),
			'notifications' => $this->getNotificationItems(),
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

	/**
	 * @return array<string,mixed>
	 */
	public function getLoginData(): array {
		global $wgWhaleAvatarOptions, $wgWhaleAvatarStyle;

		$skin = $this->skin;
		$user = $skin->getUser();
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$isRegistered = $user->isRegistered();
		$data = [
			'is-registered' => $isRegistered,
			'html-sign-in-icon' => $this->renderIcon( 'sign-in' ),
			'html-sign-out-icon' => $this->renderIcon( 'sign-out' ),
			'login-title' => $skin->msg( 'whale-login' )->text(),
		];

		if ( !$isRegistered ) {
			return $data;
		}

		$personalTools = $skin->getWhalePersonalTools();
		$avatar = Html::rawElement( 'span', [
			'class' => 'profile-img profile-img-fallback',
			'aria-hidden' => 'true',
		], $this->renderIcon( 'user' ) );
		if ( class_exists( WhaleAvatar::class ) ) {
			$avatarStyle = is_string( $wgWhaleAvatarStyle ?? null ) ? $wgWhaleAvatarStyle : 'identicon';
			$avatarSrc = WhaleAvatar::createDataUri(
				WhaleAvatar::getSeedForUser( $user ),
				$avatarStyle,
				$this->getAvatarOptions( $wgWhaleAvatarOptions ?? [] )
			);

			if ( $avatarSrc !== null ) {
				$avatar = Html::element( 'img', [
					'class' => 'profile-img',
					'src' => $avatarSrc,
					'alt' => '',
					'width' => '64',
					'height' => '64',
					'decoding' => 'async',
				] );
			}
		}

		$links = [
			[
				'html' => $linkRenderer->makeKnownLink(
					Title::makeTitle( NS_USER, $user->getName() ),
					$user->getName(),
					[
						'id' => 'pt-userpage',
						'class' => 'whale-dropdown-item',
						'title' => Linker::titleAttrib( 'pt-userpage', 'withaccess' ),
						'accesskey' => Linker::accesskey( 'pt-userpage' ),
					]
				),
			],
			[ 'is-divider' => true ],
		];

		if ( class_exists( 'EchoEvent' ) ) {
			$notiCount = 0;
			if (
				isset( $personalTools['notifications-alert'] ) &&
				isset( $personalTools['notifications-notice'] )
			) {
				$notiCount =
					( $personalTools['notifications-alert']['links'][0]['data']['counter-num'] ?? 0 ) +
					( $personalTools['notifications-notice']['links'][0]['data']['counter-num'] ?? 0 );
			}
			$links[] = [
				'html' => $linkRenderer->makeKnownLink(
					new TitleValue( NS_SPECIAL, 'Notifications' ),
					$skin->msg( 'notifications' )->plain() . ( $notiCount ? " ($notiCount)" : '' ),
					[
						'class' => 'whale-dropdown-item',
						'title' => $skin->msg( 'tooltip-pt-notifications-notice' )->text(),
					]
				),
			];
		}

		$links[] = [
			'html' => $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'Contributions', $user->getName() ),
				$skin->msg( 'mycontris' )->plain(),
				[
					'class' => 'whale-dropdown-item',
					'title' => Linker::titleAttrib( 'pt-mycontris', 'withaccess' ),
					'accesskey' => Linker::accesskey( 'pt-mycontris' ),
				]
			),
		];
		$links[] = [
			'html' => $linkRenderer->makeKnownLink(
				Title::makeTitle( NS_USER_TALK, $user->getName() ),
				$skin->msg( 'mytalk' )->plain(),
				[
					'class' => 'whale-dropdown-item',
					'title' => Linker::titleAttrib( 'pt-mytalk', 'withaccess' ),
					'accesskey' => Linker::accesskey( 'pt-mytalk' ),
				]
			),
		];
		$links[] = [
			'html' => $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'Watchlist' ),
				$skin->msg( 'watchlist' )->plain(),
				[
					'class' => 'whale-dropdown-item',
					'title' => Linker::titleAttrib( 'pt-watchlist', 'withaccess' ),
					'accesskey' => Linker::accesskey( 'pt-watchlist' ),
				]
			),
		];
		$links[] = [ 'is-divider' => true ];
		$links[] = [
			'html' => $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'Preferences' ),
				$skin->msg( 'preferences' )->plain(),
				[
					'class' => 'whale-dropdown-item',
					'title' => Linker::titleAttrib( 'pt-preferences', 'withaccess' ),
					'accesskey' => Linker::accesskey( 'pt-preferences' ),
				]
			),
		];

		$logoutHref = $personalTools['logout']['links'][0]['href'] ?? '';
		$logoutTitle = Linker::titleAttrib( 'pt-logout', 'withaccess' );

		$data += [
			'html-avatar' => $avatar,
			'user-name' => $user->getName(),
			'links' => $links,
			'logout-href' => $logoutHref,
			'logout-title' => $logoutTitle,
			'logout-label' => $skin->msg( 'logout' )->text(),
		];

		return $data;
	}

	/**
	 * @param mixed $options
	 * @return array<string,mixed>
	 */
	private function getAvatarOptions( $options ): array {
		if ( !is_array( $options ) ) {
			return [];
		}

		$avatarOptions = [];
		foreach ( $options as $name => $value ) {
			if ( is_string( $name ) ) {
				$avatarOptions[$name] = $value;
			}
		}

		return $avatarOptions;
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

		if ( $action !== 'edit' ) {
			$buttons[] = [
				'html' => $linkRenderer->makeKnownLink(
					$title,
					new HtmlArmor( $this->renderIcon( $editable ? 'edit' : 'lock' ) . ' ' . $skin->msg( 'edit' )->escaped() ),
					[
						'class' => 'whale-btn whale-btn-secondary tools-btn',
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

		if ( $companionTitle && $action !== 'edit' ) {
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
				$title,
				$skin->msg( $watched )->plain(),
				[
					'class' => 'whale-dropdown-item',
					'title' => Linker::titleAttrib( 'ca-' . $watched, 'withaccess' ),
					'accesskey' => Linker::accesskey( 'ca-' . $watched ),
				],
				[ 'action' => $watched ]
			),
		];
		$dropdownItems[] = [
			'html' => $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'Whatlinkshere', $title ),
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
					SpecialPage::getTitleFor( 'Movepage', $title ),
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
			'more-label' => $skin->msg( 'more' )->text(),
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
		$fixedHeight = ( $wgWhaleLiveRecentFixedHeight ?? true ) !== false &&
			$userOptionsLookup->getOption( $skin->getUser(), 'whale-live-recent-fixed-height' ) !== false;
		$refreshInterval = max( 10, (int)( $wgWhaleLiveRCRefreshInterval ?? 60 ) );
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
				implode( '|', $articleNamespaces )
			),
		];

		if ( !$isMobile ) {
			$feeds[] = $this->getLiveRecentFeedData(
				$mode . '-live-recent-talk-list',
				$skin->msg( 'whale-recent-discussions' )->text(),
				'comments',
				implode( '|', $talkNamespaces )
			);
		}

		return [
			'has-live-recent' => true,
			'classes' => 'live-recent' . ( $fixedHeight ? ' live-recent-fixed-height' : '' ),
			'mode' => $mode,
			'limit' => (int)$wgWhaleMaxRecent,
			'refresh-ms' => $refreshInterval * 1000,
			'style' => '--whale-live-recent-limit: ' . (int)$wgWhaleMaxRecent .
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
	 * @return array<string,string>
	 */
	private function getLiveRecentFeedData(
		string $listId,
		string $heading,
		string $icon,
		string $namespaces
	): array {
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

		return [
			'list-id' => $listId,
			'heading' => $heading,
			'namespaces' => $namespaces,
			'html-icon' => $this->renderIcon( $icon ),
			'html-more-link' => $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'Recentchanges' ),
				new HtmlArmor( '<span class="whale-sr-only">' .
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

		foreach ( $footerData as $category => $categoryData ) {
			$links = $categoryData['array-items'] ?? [];
			if ( !$links ) {
				continue;
			}
			$categoryName = preg_replace( '/^data-/', '', $category );
			if ( $categoryName === 'icons' ) {
				continue;
			}

			$items = [];
			foreach ( $links as $link ) {
				$name = $link['name'] ?? '';
				$items[] = [
					'class' => 'footer-' . $categoryName . '-' . Sanitizer::escapeClass( $name ),
					'html' => $link['html'] ?? '',
				];
			}

			$categories[] = [
				'class' => 'footer-' . Sanitizer::escapeClass( $categoryName ),
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
			'close-label' => $skin->msg( 'close' )->text(),
		];
	}

	/**
	 * @return array<string,string|bool>
	 */
	public function getExternalLinkWarningData(): array {
		$skin = $this->skin;

		return [
			'enabled' => true,
			'title' => $skin->msg( 'whale-external-link-title' )->text(),
			'description' => $skin->msg( 'whale-external-link-description' )->text(),
			'cancel-label' => $skin->msg( 'cancel' )->text(),
			'continue-label' => $skin->msg( 'whale-external-link-continue' )->text(),
			'show-decoded-label' => $skin->msg( 'whale-external-link-show-decoded' )->text(),
			'show-original-label' => $skin->msg( 'whale-external-link-show-original' )->text(),
			'close-label' => $skin->msg( 'close' )->text(),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getLoginModalData(): array {
		$skin = $this->skin;
		return [
			'show-login-modal' => $skin->getUser()->isAnon(),
			'title' => $skin->msg( 'whale-login' )->text(),
			'alert-label' => $skin->msg( 'error' )->text(),
			'name-placeholder' => $skin->msg( 'userlogin-yourname-ph' )->text(),
			'password-label' => $skin->msg( 'userlogin-yourpassword' )->text(),
			'password-placeholder' => $skin->msg( 'userlogin-yourpassword-ph' )->text(),
			'remember-label' => $skin->msg( 'whale-remember' )->text(),
			'login-label' => $skin->msg( 'whale-login-btn' )->text(),
			'join-html' => MediaWikiServices::getInstance()->getLinkRenderer()->makeKnownLink(
				SpecialPage::getTitleFor( 'Userlogin' ),
				$skin->msg( 'userlogin-joinproject' ),
				[ 'class' => 'whale-login-join' ],
				[ 'type' => 'signup' ]
			),
			'forgot-html' => MediaWikiServices::getInstance()->getLinkRenderer()->makeKnownLink(
				SpecialPage::getTitleFor( 'PasswordReset' ),
				$skin->msg( 'whale-forgot-pw' )->plain(),
				[ 'class' => 'whale-login-help' ]
			),
			'alternate-html' => MediaWikiServices::getInstance()->getLinkRenderer()->makeKnownLink(
				SpecialPage::getTitleFor( 'Userlogin' ),
				$skin->msg( 'whale-login-alter' )->plain(),
				[ 'class' => 'whale-login-help' ]
			),
			'close-label' => $skin->msg( 'close' )->text(),
		];
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

		$days = max( 7, min( 730, (int)( $wgWhaleContributionGraphDays ?? 365 ) ) );
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
		$ttl = max( 60, (int)( $wgWhaleContributionGraphCacheTTL ?? 3600 ) );
		$userName = $title->getText();
		$counts = $this->getContributionCounts( $userName, $days, $namespaces, $ttl );
		$graph = $this->buildContributionGraph( $counts, $days, $levels );
		$total = array_sum( $counts );

		return [
			'has-user-contribution-graph' => true,
			'title' => $skin->msg( 'whale-contrib-graph-title', $userName )->text(),
			'summary' => $skin->msg( 'whale-contrib-graph-summary', $skin->getLanguage()->formatNum( $total ), $skin->getLanguage()->formatNum( $days ) )->text(),
			'weeks' => $graph['weeks'],
			'legend' => $graph['legend'],
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
			foreach ( $content['children'] ?? [] as $child ) {
				if ( !$this->canShowPortalItem( $child, $userGroups, $userRights ) ) {
					continue;
				}

				$subItems = [];
				foreach ( $child['children'] ?? [] as $sub ) {
					if ( $this->canShowPortalItem( $sub, $userGroups, $userRights ) ) {
						$subItems[] = $this->buildPortalChildItem( $sub );
					}
				}

				$children[] = $this->buildPortalChildItem( $child, $subItems );
			}

			$classes = array_merge( [ 'whale-dropdown', 'whale-navbar-item' ], $content['wrapperClasses'] ?? [] );
			$linkClasses = array_merge( $content['classes'] ?? [], [ 'whale-navbar-link' ] );
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
		$classes = array_merge( $item['classes'] ?? [], [ 'whale-dropdown-item' ] );
		$hasChildren = count( $children ) > 0;
		if ( $hasChildren ) {
			$classes[] = 'whale-dropdown-toggle';
			$classes[] = 'whale-dropdown-toggle-sub';
		}

		return [
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
		$attrs = [
			'class' => implode( ' ', array_map( [ Sanitizer::class, 'escapeClass' ], $classes ) ),
			'href' => $item['href'] ?? '#',
			'title' => $item['title'] ?? '',
		];
		if ( !empty( $item['access'] ) ) {
			$attrs['accesskey'] = $item['access'];
		}
		if ( $hasChildren ) {
			$attrs['data-whale-toggle'] = $isChild ? '' : 'dropdown';
			$attrs['role'] = 'button';
			$attrs['aria-haspopup'] = 'true';
			$attrs['aria-expanded'] = 'false';
		}

		$html = '';
		if ( isset( $item['icon'] ) ) {
			$html .= $this->renderIcon( $item['icon'] );
		}
		if ( isset( $item['text'] ) && $item['text'] !== '' ) {
			$html .= Html::element( 'span', [ 'class' => $isChild ? '' : 'hide-title' ], $item['text'] );
		}

		return Html::rawElement( 'a', $attrs, $html );
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
				( $personalTools[$key]['links'][0]['data']['counter-num'] ?? 0 )
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

		return $this->parseNavbarContent( $data );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function parseNavbarContent( string $data ): array {
		return $this->parseSimpleNavbar( $data );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function parseSimpleNavbar( string $data ): array {
		$items = [];
		$stack = [];

		foreach ( explode( "\n", $data ) as $line ) {
			$line = rtrim( $line, "\r" );
			if ( trim( $line ) === '' || preg_match( '/^\s*#/', $line ) ) {
				continue;
			}

			if ( preg_match( '/^([ \t]*)-\s*(.*?)\s*$/', $line, $matches ) ) {
				$level = $this->getNavbarIndentLevel( $matches[1], true );
				if ( $level < 1 || $level > 3 ) {
					continue;
				}

				$item = $this->buildNavbarItemFromFields( $this->parseNavbarInlineFields( $matches[2] ) );
				if ( $item === null ) {
					continue;
				}

				$item['children'] = [];
				if ( $level === 1 ) {
					$items[] = $item;
					$stack = [ 1 => &$items[count( $items ) - 1] ];
					continue;
				}

				if ( !isset( $stack[$level - 1] ) ) {
					continue;
				}

				$stack[$level - 1]['children'][] = $item;
				$childIndex = count( $stack[$level - 1]['children'] ) - 1;
				$stack[$level] = &$stack[$level - 1]['children'][$childIndex];
				foreach ( array_keys( $stack ) as $stackLevel ) {
					if ( $stackLevel > $level ) {
						unset( $stack[$stackLevel] );
					}
				}
				continue;
			}

			if ( preg_match( '/^([ \t]+)([a-z][a-z0-9_-]*)\s*:\s*(.*?)\s*$/i', $line, $matches ) ) {
				$level = $this->getNavbarIndentLevel( $matches[1], false );
				if ( !isset( $stack[$level] ) || strtolower( $matches[2] ) === 'children' ) {
					continue;
				}

				$this->applyNavbarField( $stack[$level], $matches[2], $matches[3] );
			}
		}

		return $items;
	}

	private function getNavbarIndentLevel( string $indent, bool $isItem ): int {
		$spaces = strlen( str_replace( "\t", '  ', $indent ) );
		if ( $isItem ) {
			return intdiv( $spaces, 4 ) + 1;
		}

		return intdiv( max( 0, $spaces - 2 ), 4 ) + 1;
	}

	/**
	 * @return array<string,string>
	 */
	private function parseNavbarInlineFields( string $line ): array {
		if ( preg_match( '/^(.+?)\s*->\s*(.+)$/', $line, $matches ) ) {
			return [
				'text' => trim( $matches[1] ),
				'link' => trim( $matches[2] ),
			];
		}

		if ( preg_match( '/^([a-z][a-z0-9_-]*)\s*:\s*(.*?)\s*$/i', $line, $matches ) ) {
			return [ strtolower( $matches[1] ) => trim( $matches[2] ) ];
		}

		return [ 'text' => trim( $line ) ];
	}

	/**
	 * @param array<string,string> $data
	 * @return array<string,mixed>|null
	 */
	private function buildNavbarItemFromFields( array $data ): ?array {
		$data = $this->normalizeNavbarFields( $data );
		$text = $this->messageOrRaw( $this->getNavbarField( $data, [ 'text', 'label', 'name', 'display' ] ) );
		$icon = isset( $data['icon'] ) && preg_match( '/^[a-z0-9-]+$/i', $data['icon'] )
			? strtolower( $data['icon'] )
			: null;
		if ( $icon === null && $text === '' ) {
			return null;
		}

		return [
			'access' => isset( $data['access'] ) && preg_match( '/^[0-9a-z]$/i', $data['access'] ) ? $data['access'] : null,
			'classes' => $this->getNavbarClasses( $this->getNavbarField( $data, [ 'class', 'classes' ] ) ),
			'href' => $this->getNavbarHref( $this->getNavbarField( $data, [ 'link', 'url', 'href' ] ) ),
			'icon' => $icon,
			'text' => $text,
			'title' => $this->messageOrRaw( $this->getNavbarField( $data, [ 'title', 'tooltip' ] ) ) ?: $text,
			'group' => $this->safeToken( $data['group'] ?? null ),
			'right' => $this->safeToken( $data['right'] ?? null ),
			'_fields' => $data,
		];
	}

	/**
	 * @param array<string,mixed> &$item
	 */
	private function applyNavbarField( array &$item, string $field, string $value ): void {
		$fields = $this->getRawNavbarFields( $item['_fields'] ?? null );
		$fields[strtolower( $field )] = $value;
		$updated = $this->buildNavbarItemFromFields( $fields );

		if ( $updated === null ) {
			return;
		}

		$children = $item['children'] ?? [];
		$item = $updated;
		$item['children'] = is_array( $children ) ? $children : [];
	}

	/**
	 * @param mixed $fields
	 * @return array<string,string>
	 */
	private function getRawNavbarFields( $fields ): array {
		if ( !is_array( $fields ) ) {
			return [];
		}

		$result = [];
		foreach ( $fields as $key => $value ) {
			if ( is_string( $key ) && is_string( $value ) ) {
				$result[$key] = $value;
			}
		}

		return $result;
	}

	/**
	 * @param array<string,string> $data
	 * @return array<string,string>
	 */
	private function normalizeNavbarFields( array $data ): array {
		$normalized = [];
		foreach ( $data as $key => $value ) {
			$normalized[strtolower( trim( $key ) )] = trim( $value );
		}

		return $normalized;
	}

	/**
	 * @param array<string,string> $data
	 * @param array<int,string> $names
	 */
	private function getNavbarField( array $data, array $names ): string {
		foreach ( $names as $name ) {
			if ( isset( $data[$name] ) ) {
				return trim( $data[$name] );
			}
		}

		return '';
	}

	private function getNavbarHref( string $link ): string {
		global $wgArticlePath;

		if ( $link === '' ) {
			return '#';
		}

		if ( $this->isSafeExternalNavbarHref( $link ) ) {
			return $link;
		}

		$encoded = str_replace( '%3A', ':', urlencode( $link ) );
		return str_replace( '$1', $encoded, $wgArticlePath );
	}

	private function isSafeExternalNavbarHref( string $link ): bool {
		if ( preg_match( '/[\x00-\x20\x7F]/', $link ) ) {
			return false;
		}

		if ( !preg_match( '/^(?:https?:)?\/\//i', $link ) ) {
			return false;
		}

		$url = str_starts_with( $link, '//' ) ? 'https:' . $link : $link;
		$parts = parse_url( $url );

		if ( !is_array( $parts ) ) {
			return false;
		}

		$scheme = strtolower( (string)( $parts['scheme'] ?? '' ) );
		$host = (string)( $parts['host'] ?? '' );

		return ( $scheme === 'http' || $scheme === 'https' ) && $host !== '';
	}

	/**
	 * @return array<int,string>
	 */
	private function getNavbarClasses( string $classes ): array {
		$result = [];
		foreach ( preg_split( '/[\s,]+/', $classes ) ?: [] as $class ) {
			if ( preg_match( '/^[a-z0-9_-]+$/i', $class ) ) {
				$result[] = $class;
			}
		}

		return $result;
	}

	private function messageOrRaw( string $value ): string {
		if ( $value === '' ) {
			return '';
		}

		$message = $this->skin->msg( $value );
		return $message->isDisabled() ? $value : $message->text();
	}

	private function safeToken( ?string $value ): ?string {
		if ( !is_string( $value ) || $value === '' || !preg_match( '/^[a-z0-9_-]+$/i', $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * @param array<string,int> $counts
	 * @param int $days
	 * @param array<int,int> $levels
	 * @return array{weeks:array<int,array<string,mixed>>,legend:array<int,array<string,string>>}
	 */
	private function buildContributionGraph( array $counts, int $days, array $levels ): array {
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
	private function getContributionCounts( string $userName, int $days, ?array $namespaces, int $ttl ): array {
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
						WhaleRenderer::class . '::getContributionCounts',
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

	private function getLatestRevisionId( Title $title ): int {
		$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$row = $db->selectRow(
			'page',
			[ 'page_latest' ],
			[
				'page_namespace' => $title->getNamespace(),
				'page_title' => $title->getDBkey(),
			],
			__METHOD__
		);
		if ( $row && (int)$row->page_latest > 0 ) {
			return (int)$row->page_latest;
		}

		$wikiPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		if ( method_exists( $wikiPage, 'getLatest' ) ) {
			return (int)$wikiPage->getLatest();
		}

		return method_exists( $title, 'getLatestRevID' ) ? (int)$title->getLatestRevID() : 0;
	}

	private function renderIcon( ?string $icon ): string {
		if ( $icon === null || !preg_match( '/^[a-z0-9-]+$/i', $icon ) ) {
			return '';
		}

		$iconPaths = [
			'angle-down' => '<path d="m6 9 6 6 6-6"/>',
			'angle-right' => '<path d="m9 18 6-6-6-6"/>',
			'angle-up' => '<path d="m18 15-6-6-6 6"/>',
			'bell' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
			'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>',
			'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/>',
			'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.1 2.1-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V20h-3v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-2.1-2.1.1-.1A1.7 1.7 0 0 0 5 15a1.7 1.7 0 0 0-1.5-1H3v-3h.2A1.7 1.7 0 0 0 5 10a1.7 1.7 0 0 0-.3-1.9l-.1-.1 2.1-2.1.1.1A1.7 1.7 0 0 0 8.7 6a1.7 1.7 0 0 0 1-1.5V4h3v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 2.1 2.1-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1H21v3h-.2a1.7 1.7 0 0 0-1.4 1z"/>',
			'comment' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>',
			'comments' => '<path d="M21 14a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M7 9h10"/><path d="M7 13h6"/>',
			'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
			'envelope' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
			'external-link' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
			'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
			'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/>',
			'folder' => '<path d="M3 6a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
			'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20"/><path d="M12 2a15 15 0 0 0 0 20"/>',
			'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/>',
			'home' => '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
			'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
			'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
			'list' => '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>',
			'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
			'minus' => '<path d="M5 12h14"/>',
			'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
			'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
			'question' => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.3 2c-.8.7-1.4 1.2-1.4 2.5"/><path d="M12 17h.01"/>',
			'link' => '<path d="M7.05025 1.53553C8.03344 0.552348 9.36692 0 10.7574 0C13.6528 0 16 2.34721 16 5.24264C16 6.63308 15.4477 7.96656 14.4645 8.94975L12.4142 11L11 9.58579L13.0503 7.53553C13.6584 6.92742 14 6.10264 14 5.24264C14 3.45178 12.5482 2 10.7574 2C9.89736 2 9.07258 2.34163 8.46447 2.94975L6.41421 5L5 3.58579L7.05025 1.53553Z"/><path d="M7.53553 13.0503L9.58579 11L11 12.4142L8.94975 14.4645C7.96656 15.4477 6.63308 16 5.24264 16C2.34721 16 0 13.6528 0 10.7574C0 9.36693 0.552347 8.03344 1.53553 7.05025L3.58579 5L5 6.41421L2.94975 8.46447C2.34163 9.07258 2 9.89736 2 10.7574C2 12.5482 3.45178 14 5.24264 14C6.10264 14 6.92742 13.6584 7.53553 13.0503Z"/><path d="M5.70711 11.7071L11.7071 5.70711L10.2929 4.29289L4.29289 10.2929L5.70711 11.7071Z"/>',
			'random' => '<path d="M0 24q0 0.832 0.576 1.44t1.44 0.576h1.984q2.048 0 3.904-0.8t3.168-2.144 2.144-3.2 0.8-3.872q0-2.464 1.728-4.224t4.256-1.76h4v1.984q0 0.672 0.384 1.152t0.864 0.704 1.12 0.128 1.056-0.544l4-4q0.608-0.64 0.576-1.44t-0.576-1.408l-4-4q-0.48-0.448-1.088-0.544t-1.12 0.128-0.864 0.704-0.352 1.12v2.016h-4q-2.016 0-3.872 0.8t-3.2 2.112-2.144 3.2-0.768 3.872q0 2.496-1.76 4.256t-4.256 1.76h-1.984q-0.832 0-1.44 0.576t-0.576 1.408zM0 8.032q0 0.832 0.576 1.408t1.44 0.576h1.984q1.408 0 2.592 0.608t2.080 1.664q0.672-2.048 1.984-3.68-2.912-2.592-6.656-2.592h-1.984q-0.832 0-1.44 0.608t-0.576 1.408zM13.376 23.456q2.848 2.56 6.624 2.56h4v2.016q0 0.64 0.384 1.152t0.864 0.704 1.12 0.096 1.056-0.544l4-4q0.608-0.608 0.576-1.44t-0.576-1.376l-4-4q-0.48-0.48-1.088-0.576t-1.12 0.128-0.864 0.736-0.352 1.12v1.984h-4q-1.376 0-2.592-0.576t-2.048-1.664q-0.704 2.048-1.984 3.68z"/>',
			'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
			'share-square' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4"/><path d="m8.6 13.5 6.8 4"/>',
			'sign-in' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
			'sign-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
			'star' => '<path d="m12 2 3 6 6.5.9-4.7 4.6 1.1 6.5L12 17l-5.9 3 1.1-6.5-4.7-4.6L9 8z"/>',
			'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/>',
			'sync' => '<path d="M18.4721 16.7023C17.3398 18.2608 15.6831 19.3584 13.8064 19.7934C11.9297 20.2284 9.95909 19.9716 8.25656 19.0701C6.55404 18.1687 5.23397 16.6832 4.53889 14.8865C3.84381 13.0898 3.82039 11.1027 4.47295 9.29011C5.12551 7.47756 6.41021 5.96135 8.09103 5.02005C9.77184 4.07875 11.7359 3.77558 13.6223 4.16623C15.5087 4.55689 17.1908 5.61514 18.3596 7.14656C19.5283 8.67797 20.1052 10.5797 19.9842 12.5023M19.9842 12.5023L21.4842 11.0023M19.9842 12.5023L18.4842 11.0023"/><path d="M12 8V12L15 15"/>',
			'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><path d="M7.5 7.5h.01"/>',
			'tags' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><path d="M7.5 7.5h.01"/><path d="M17 7 21 11"/>',
			'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8 12 3 7 8"/><path d="M12 3v12"/>',
			'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
			'users' => '<path d="M16 21a6 6 0 0 0-12 0"/><circle cx="10" cy="8" r="4"/><path d="M22 21a5 5 0 0 0-4-4.9"/><path d="M17 4.3a4 4 0 0 1 0 7.4"/>',
			'wrench' => '<path d="M6 6L10.5 10.5M6 6H3L2 3L3 2L6 3V6ZM19.259 2.74101L16.6314 5.36863C16.2354 5.76465 16.0373 5.96265 15.9632 6.19098C15.8979 6.39183 15.8979 6.60817 15.9632 6.80902C16.0373 7.03735 16.2354 7.23535 16.6314 7.63137L16.8686 7.86863C17.2646 8.26465 17.4627 8.46265 17.691 8.53684C17.8918 8.6021 18.1082 8.6021 18.309 8.53684C18.5373 8.46265 18.7354 8.26465 19.1314 7.86863L21.5893 5.41072C21.854 6.05488 22 6.76039 22 7.5C22 10.5376 19.5376 13 16.5 13C16.1338 13 15.7759 12.9642 15.4298 12.8959C14.9436 12.8001 14.7005 12.7521 14.5532 12.7668C14.3965 12.7824 14.3193 12.8059 14.1805 12.8802C14.0499 12.9501 13.919 13.081 13.657 13.343L6.5 20.5C5.67157 21.3284 4.32843 21.3284 3.5 20.5C2.67157 19.6716 2.67157 18.3284 3.5 17.5L10.657 10.343C10.919 10.081 11.0499 9.95005 11.1198 9.81949C11.1941 9.68068 11.2176 9.60347 11.2332 9.44681C11.2479 9.29945 11.1999 9.05638 11.1041 8.57024C11.0358 8.22406 11 7.86621 11 7.5C11 4.46243 13.4624 2 16.5 2C17.5055 2 18.448 2.26982 19.259 2.74101ZM12.0001 14.9999L17.5 20.4999C18.3284 21.3283 19.6716 21.3283 20.5 20.4999C21.3284 19.6715 21.3284 18.3283 20.5 17.4999L15.9753 12.9753C15.655 12.945 15.3427 12.8872 15.0408 12.8043C14.6517 12.6975 14.2249 12.7751 13.9397 13.0603L12.0001 14.9999Z"/>',
		];

		$iconKey = strtolower( $icon );
		if ( !isset( $iconPaths[$iconKey] ) ) {
			return '';
		}

		$solidIcons = [
			'link' => '0 0 16 16',
			'random' => '0 0 32 32',
		];
		$classes = [ 'whale-icon', 'whale-icon-' . $iconKey ];
		if ( isset( $solidIcons[$iconKey] ) ) {
			$classes[] = 'whale-icon-solid';
		}

		return Html::rawElement( 'svg', [
			'aria-hidden' => 'true',
			'class' => $classes,
			'focusable' => 'false',
			'viewBox' => $solidIcons[$iconKey] ?? '0 0 24 24',
			'xmlns' => 'http://www.w3.org/2000/svg',
		], $iconPaths[$iconKey] );
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

	private function getCachedContentText( Title $title ): string {
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
