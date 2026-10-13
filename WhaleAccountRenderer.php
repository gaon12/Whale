<?php

use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

/** Build login, avatar and personal-menu presentation data. */
class WhaleAccountRenderer {
	public function __construct( private SkinWhale $skin ) {
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
					WhalePersonalTools::getFirstLinkCounter( $personalTools['notifications-alert'] ) +
					WhalePersonalTools::getFirstLinkCounter( $personalTools['notifications-notice'] );
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

		$logoutHrefValue = WhalePersonalTools::getFirstLinkField( $personalTools['logout'] ?? null, 'href' );
		$logoutHref = is_string( $logoutHrefValue ) ? $logoutHrefValue : '';
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
	 * @return array<string,mixed>
	 */
	public function getLoginModalData(): array {
		$skin = $this->skin;
		return [
			'show-login-modal' => $skin->getUser()->isAnon(),
			'title' => $skin->msg( 'whale-login' )->text(),
			'subtitle' => $skin->msg( 'whale-login-subtitle' )->text(),
			'alert-label' => $skin->msg( 'error' )->text(),
			'error-message' => $skin->msg( 'whale-login-error' )->text(),
			'name-label' => $skin->msg( 'userlogin-yourname' )->text(),
			'name-placeholder' => $skin->msg( 'userlogin-yourname-ph' )->text(),
			'password-label' => $skin->msg( 'userlogin-yourpassword' )->text(),
			'password-placeholder' => $skin->msg( 'userlogin-yourpassword-ph' )->text(),
			'remember-label' => $skin->msg( 'whale-remember' )->text(),
			'login-label' => $skin->msg( 'whale-login-btn' )->text(),
			'working-label' => $skin->msg( 'whale-login-working' )->text(),
			'show-password-label' => $skin->msg( 'whale-login-show-password' )->text(),
			'hide-password-label' => $skin->msg( 'whale-login-hide-password' )->text(),
			'form-action' => SpecialPage::getTitleFor( 'Userlogin' )->getLocalURL(),
			'full-login-url' => SpecialPage::getTitleFor( 'Userlogin' )->getLocalURL( [
				'returnto' => $skin->getTitle()?->getPrefixedDBkey() ?? '',
			] ),
			'html-user-icon' => $this->renderIcon( 'user' ),
			'html-lock-icon' => $this->renderIcon( 'lock' ),
			'html-eye-icon' => $this->renderIcon( 'eye' ),
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
			'close-label' => $skin->msg( 'whale-close' )->text(),
		];
	}

	private function renderIcon( ?string $icon ): string {
		return WhaleIcon::render( $icon );
	}
}
