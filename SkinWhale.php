<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;

if (
	!class_exists( SkinMustache::class ) &&
	class_exists( MediaWiki\Skin\SkinMustache::class )
) {
	class_alias( MediaWiki\Skin\SkinMustache::class, SkinMustache::class );
}

class SkinWhale extends SkinMustache {
	// @codingStandardsIgnoreStart
	public $skinname = 'whale';
	public $stylename = 'Whale';
	public $template = 'skin';
	// @codingStandardsIgnoreEnd

	/**
	 * Page initialize.
	 *
	 * @param OutputPage $out OutputPage
	 */
	public function initPage( OutputPage $out ): void {
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

		// Only load LiveRC JS when the sidebar can render.
		if ( $wgWhaleEnableLiveRC && $this->shouldRenderSidebar() ) {
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

	/**
	 * Get template data for Mustache rendering.
	 *
	 * @return array<string,mixed>
	 */
	public function getTemplateData(): array {
		global $wgWhaleAdSetting, $wgWhaleMobileReplaceAd;

		$data = parent::getTemplateData();
		$renderer = new WhaleRenderer( $this );
		$request = $this->getRequest();
		$hasAds = isset( $wgWhaleAdSetting['client'] ) && $wgWhaleAdSetting['client'];
		$hasSidebar = $this->shouldRenderSidebar();
		$siteNoticeHtml = $request->getCookie( 'disable-notice' )
			? ''
			: $this->getVisibleSiteNoticeHtml( $data['html-site-notice'] ?? '' );

		$data['html-title'] = $this->getOutput()->getPageTitle();
		$data['html-whale-nav-menu'] = $renderer->getNavMenu();
		$data['has-whale-site-notice'] = $siteNoticeHtml !== '';
		$data['html-whale-site-notice'] = $siteNoticeHtml;
		$data['html-whale-contents-toolbox'] = $renderer->getContentsToolbox();
		$data['has-whale-sidebar'] = $hasSidebar;
		$data['html-whale-live-recent'] = $hasSidebar ? $renderer->getLiveRecent() : '';
		$data['html-whale-right-ad'] =
			$hasSidebar && isset( $wgWhaleAdSetting['right'] ) && $wgWhaleAdSetting['right']
				? $renderer->getAd( 'right' )
				: '';
		$data['html-whale-header-ad'] =
			isset( $wgWhaleAdSetting['header'] ) && $wgWhaleAdSetting['header']
				? $renderer->getAd( 'header' )
				: '';
		$data['html-whale-belowarticle-ad'] =
			isset( $wgWhaleAdSetting['belowarticle'] ) && $wgWhaleAdSetting['belowarticle']
				? $renderer->getAd( 'belowarticle' )
				: '';
		$data['html-whale-bottom-ad'] =
			isset( $wgWhaleAdSetting['bottom'] ) && $wgWhaleAdSetting['bottom']
				? $renderer->getAd( 'bottom' )
				: '';
		$data['has-whale-mobile-ad'] =
			isset( $wgWhaleMobileReplaceAd ) && $wgWhaleMobileReplaceAd &&
			isset( $wgWhaleAdSetting['right'] ) && $wgWhaleAdSetting['right'];
		$data['html-whale-footer'] = $renderer->getFooter();
		$data['html-whale-scroll-up-icon'] = $renderer->getIcon( 'angle-up' );
		$data['html-whale-scroll-down-icon'] = $renderer->getIcon( 'angle-down' );
		$data['html-whale-adsense-script'] = $hasAds
			? '<script async defer src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>'
			: '';
		$data['html-whale-login-modal'] = $renderer->getLoginModal();
		$data['html-whale-debughtml'] = class_exists( MWDebug::class ) ? MWDebug::getHTMLDebugLog() : '';

		return $data;
	}

	private function getVisibleSiteNoticeHtml( mixed $siteNoticeHtml ): string {
		if ( !is_string( $siteNoticeHtml ) ) {
			return '';
		}

		$siteNoticeHtml = trim( $siteNoticeHtml );
		if ( $siteNoticeHtml === '' ) {
			return '';
		}

		$withoutComments = preg_replace( '/<!--.*?-->/s', '', $siteNoticeHtml ) ?? '';
		$visibleText = trim(
			str_replace(
				"\xc2\xa0",
				' ',
				html_entity_decode( strip_tags( $withoutComments ), ENT_QUOTES | ENT_HTML5, 'UTF-8' )
			)
		);
		$hasVisibleMedia = preg_match( '/<(?:img|picture|svg|video|iframe|object|embed)\b/i', $withoutComments ) === 1;

		return $visibleText !== '' || $hasVisibleMedia ? $siteNoticeHtml : '';
	}

	private function shouldRenderSidebar(): bool {
		$request = $this->getRequest();

		if (
			$request->getCheck( 'handheld' ) ||
			$request->getVal( 'useformat' ) === 'mobile' ||
			$request->getCheck( 'mobileaction' )
		) {
			return false;
		}

		$viewportWidth = $request->getHeader( 'Sec-CH-Viewport-Width' );
		if ( is_string( $viewportWidth ) && ctype_digit( $viewportWidth ) ) {
			return (int)$viewportWidth >= 992;
		}

		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		return !$userOptionsLookup->getOption( $this->getUser(), 'whale-layout-sidebar' );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getWhaleFooterData(): array {
		return $this->getComponent( 'footer' )->getTemplateData();
	}

	/**
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function getWhaleFooterIcons(): array {
		return $this->getFooterIcons();
	}

	/**
	 * @param array<string,mixed> $icon
	 */
	public function makeWhaleFooterIcon( array $icon ): string {
		return $this->makeFooterIcon( $icon );
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public function getWhalePersonalTools(): array {
		return $this->getStructuredPersonalTools();
	}

	/**
	 * @param array<string,mixed> $item
	 */
	public function makeWhaleListItem( string $key, array $item ): string {
		return $this->makeListItem( $key, $item );
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
