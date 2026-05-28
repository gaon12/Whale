<?php

use MediaWiki\MediaWikiServices;

class SkinWhale extends SkinTemplate {
	// @codingStandardsIgnoreStart
	public $skinname = 'whale';
	public $stylename = 'Whale';
	public $template = 'WhaleTemplate';
	// @codingStandardsIgnoreEnd

	/**
	 * Page initialize.
	 *
	 * @param OutputPage $out OutputPage
	 */
	public function initPage( OutputPage $out ) {
		// @codingStandardsIgnoreLine
		global $wgSitename, $wgTwitterAccount, $wgLanguageCode, $wgNaverVerification, $wgLogo, $wgWhaleEnableLiveRC, $wgWhaleAdSetting, $wgWhaleAdGroup, $wgWhaleNavBarLogoImage;

		$user = $this->getUser();
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		/* uncomment if needs to use UserGroupManager
		$userGroupManager = $services->getUserGroupManager();
		$userGroups = $userGroupManager->getUserGroups( $user );
		*/

		$optionMainColor = $userOptionsLookup->getOption( $user, 'whale-color-main' );
		$optionSecondColor = $userOptionsLookup->getOption( $user, 'whale-color-second' );

		$mainColor = $this->normalizeCssColor( $optionMainColor ?: $GLOBALS['wgWhaleMainColor'], '#4188F1' );
		// @codingStandardsIgnoreLine
		$tempSecondColor = isset( $GLOBALS['wgWhaleSecondColor'] ) ? $GLOBALS['wgWhaleSecondColor'] : '#' . strtoupper( dechex( hexdec( substr( $mainColor, 1, 6 ) ) - hexdec( '1A1415' ) ) );
		$secondColor = $this->normalizeCssColor( $optionSecondColor ?: $tempSecondColor, '#2774DC' );
		$ogLogo = isset( $GLOBALS['wgWhaleOgLogo'] ) ? $GLOBALS['wgWhaleOgLogo'] : $wgLogo;
		if ( !preg_match( '/^((?:(?:http(?:s)?)?:)?\/\/(?:.{4,}))$/i', $ogLogo ) ) {
			$ogLogo = $GLOBALS['wgServer'] . $GLOBALS['wgLogo'];
		}

		$skin = $this->getSkin();

		parent::initPage( $out );

		$out->addMeta( 'viewport', 'width=device-width, initial-scale=1, maximum-scale=1' );

		if (
			!class_exists( ArticleMetaDescription::class ) ||
			!class_exists( Description2::class )
		) {
			// The validator complains if there's more than one description,
			// so output this here only if none of the aforementioned SEO
			// extensions aren't installed
			$out->addMeta( 'description', strip_tags(
				preg_replace( '/<table[^>]*>([\s\S]*?)<\/table[^>]*>/', '', $out->mBodytext ),
				'<br>'
			) );
		}
		$out->addMeta( 'keywords', $wgSitename . ',' . $skin->getTitle() );

		/* 네이버 웹마스터 도구 */
		if ( isset( $wgNaverVerification ) ) {
			$out->addMeta( 'naver-site-verification', $wgNaverVerification );
		}

		/* IOS 기기 및 모바일 크롬에서의 웹앱 옵션 켜기 및 상단바 투명화 */
		$out->addMeta( 'apple-mobile-web-app-capable', 'Yes' );
		$out->addMeta( 'apple-mobile-web-app-status-bar-style', 'black-translucent' );
		$out->addMeta( 'mobile-web-app-capable', 'Yes' );

		/* 모바일에서의 테마 컬러 적용 */
		// 크롬, 파이어폭스 OS, 오페라
		$out->addMeta( 'theme-color', $mainColor );
		// 윈도우 폰
		$out->addMeta( 'msapplication-navbutton-color', $mainColor );

		/* 트위터 카드 */
		$out->addMeta( 'twitter:card', 'summary' );
		if ( isset( $wgTwitterAccount ) ) {
			$out->addMeta( 'twitter:site', "@$wgTwitterAccount" );
			$out->addMeta( 'twitter:creator', "@$wgTwitterAccount" );
		}

		$modules = [
			'skins.whale.layoutjs'
		];

		// Only load ad-related JS if ads are enabled in site configuration
		if ( isset( $wgWhaleAdSetting['client'] ) && $wgWhaleAdSetting['client'] ) {
			$modules[] = 'skins.whale.ads';
		}

		// Only load LiveRC JS is we have enabled that feature in site config
		if ( $wgWhaleEnableLiveRC ) {
			$modules[] = 'skins.whale.liverc';
		}

		// Only load modal login JS for anons, no point in loading it for logged-in
		// users since the modal HTML isn't even rendered for them.
		if ( $skin->getUser()->isAnon() ) {
			$modules[] = 'skins.whale.loginjs';
		}

		$out->addModules( $modules );

		// @codingStandardsIgnoreStart
		$out->addInlineStyle(
			".Whale {
			--whale-main-color: $mainColor;
			--whale-second-color: $secondColor;
		}"
		);

		// navbar image settings
		if ( isset( $wgWhaleNavBarLogoImage ) ) {
			$out->addInlineStyle(
				".Whale .nav-wrapper .navbar .navbar-brand {
					background: transparent url($wgWhaleNavBarLogoImage) no-repeat scroll left center/auto 1.9rem;
				}
				@media screen and (max-width: 397px){
					.Whale .nav-wrapper .navbar .navbar-brand {
						background: transparent url($wgWhaleNavBarLogoImage) no-repeat scroll left center/auto 1.5rem;
					}
				}"
			);
		}

		// layout settings
		$WhaleUserWidthSettings = $userOptionsLookup->getOption( $user, 'whale-layout-width' );
		$WhaleUserSidebarSettings = $userOptionsLookup->getOption( $user, 'whale-layout-sidebar' );
		$WhaleUserNavbarSettings = $userOptionsLookup->getOption( $user, 'whale-layout-navfix' );
		$WhaleUsercontrolbarSettings = $userOptionsLookup->getOption( $user, 'whale-layout-controlbar' );

		if ( isset( $WhaleUserNavbarSettings ) && $WhaleUserNavbarSettings ) {
			$out->addInlineStyle(
				".navbar-fixed-top {
					position: absolute;
				}"
			);
		}

		if ( isset( $WhaleUserSidebarSettings ) && $WhaleUserSidebarSettings ) {
			$out->addInlineStyle(
				".Whale .content-wrapper .whale-content {
					margin-right: 0;
				}"
			);
		}

		if ( $WhaleUserWidthSettings !== null ) {
			$out->addInlineStyle(
				".Whale .content-wrapper {
					max-width: $WhaleUserWidthSettings;
				}

				.Whale .nav-wrapper .navbar {
					max-width: $WhaleUserWidthSettings;
				}"
			);
		}

		if ( isset( $WhaleUsercontrolbarSettings ) && $WhaleUsercontrolbarSettings ) {
			$out->addInlineStyle(
				".Whale .content-wrapper #whale-bottombtn {
					display: none;
				}"
			);
		}

		// Ads setting
		if ( isset( $wgWhaleAdSetting['client'] ) && $wgWhaleAdSetting['client'] ) {
			// change ads option by rights
			if ( isset( $wgWhaleAdGroup ) && $wgWhaleAdGroup == 'differ' ) {
				if (
					isset( $wgWhaleAdSetting['header'] ) && $wgWhaleAdSetting['header'] &&
					$userOptionsLookup->getOption( $user, 'whale-ads-header' )
				) {
					$wgWhaleAdSetting['header'] = null;
				}
				if (
					isset( $wgWhaleAdSetting['right'] ) && $wgWhaleAdSetting['right'] &&
					$userOptionsLookup->getOption( $user, 'whale-ads-right' )
				) {
					$wgWhaleAdSetting['right'] = null;
				}
				if (
					isset( $wgWhaleAdSetting['bottom'] ) && $wgWhaleAdSetting['bottom'] &&
					$userOptionsLookup->getOption( $user, 'whale-ads-bottom' )
				) {
					$wgWhaleAdSetting['bottom'] = null;
				}
				if (
					isset( $wgWhaleAdSetting['belowarticle'] ) && $wgWhaleAdSetting['belowarticle'] &&
					$userOptionsLookup->getOption( $user, 'whale-ads-belowarticle' )
				) {
					$wgWhaleAdSetting['belowarticle'] = null;
				}
			}
		}

		// @codingStandardsIgnoreEnd
		$this->setupCss( $out );
	}

	private function normalizeCssColor( ?string $color, string $fallback ): string {
		return $color !== null && preg_match( '/^#[0-9a-f]{6}$/i', $color ) ? $color : $fallback;
	}

	/**
	 * Setup skin CSS.
	 *
	 * @param OutputPage $out OutputPage
	 */
	public function setupCss( OutputPage $out ) {
		$out->addModuleStyles( [ 'skins.whale.styles' ] );
	}
}
