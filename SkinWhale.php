<?php

use MediaWiki\Html\Html;
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

	private const LEGACY_THEME_COLORS = [
		'light' => [ 'primary' => '#4188F1', 'secondary' => '#2774DC' ],
		'dark' => [ 'primary' => '#4188F1', 'secondary' => '#2774DC' ],
	];

	private const THEME_PALETTES = [
		'han-river-blue' => [
			'light' => [ 'primary' => '#336699', 'secondary' => '#003366' ],
			'dark' => [ 'primary' => '#99CCFF', 'secondary' => '#6699FF' ],
		],
		'hanbat-forest' => [
			'light' => [ 'primary' => '#006633', 'secondary' => '#336633' ],
			'dark' => [ 'primary' => '#99CC99', 'secondary' => '#66CC66' ],
		],
		'milk-vetch-purple' => [
			'light' => [ 'primary' => '#663399', 'secondary' => '#993366' ],
			'dark' => [ 'primary' => '#CCCCFF', 'secondary' => '#CC99FF' ],
		],
		'clay-roof' => [
			'light' => [ 'primary' => '#993300', 'secondary' => '#666633' ],
			'dark' => [ 'primary' => '#FFCC99', 'secondary' => '#CCCC99' ],
		],
		'jeju-teal' => [
			'light' => [ 'primary' => '#006666', 'secondary' => '#336666' ],
			'dark' => [ 'primary' => '#99CCCC', 'secondary' => '#66CCCC' ],
		],
		'camellia-red' => [
			'light' => [ 'primary' => '#993333', 'secondary' => '#663333' ],
			'dark' => [ 'primary' => '#FF9999', 'secondary' => '#CC9999' ],
		],
		'ginkgo-gold' => [
			'light' => [ 'primary' => '#666600', 'secondary' => '#663300' ],
			'dark' => [ 'primary' => '#FFCC33', 'secondary' => '#CCCC66' ],
		],
	];

	/**
	 * Page initialize.
	 *
	 * @param OutputPage $out OutputPage
	 */
	public function initPage( OutputPage $out ): void {
		// @codingStandardsIgnoreLine
		global $wgSitename, $wgTwitterAccount, $wgLanguageCode, $wgNaverVerification, $wgLogo, $wgWhaleEnableLiveRC, $wgWhaleAdSetting;

		$user = $this->getUser();
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		/* uncomment if needs to use UserGroupManager
		$userGroupManager = $services->getUserGroupManager();
		$userGroups = $userGroupManager->getUserGroups( $user );
		*/

		$optionTheme = $userOptionsLookup->getOption( $user, 'whale-theme' );

		$themeColors = $this->resolveThemeColors( $optionTheme );
		$mainColor = $themeColors['light']['primary'];
		$secondColor = $themeColors['light']['secondary'];
		$darkMainColor = $themeColors['dark']['primary'];
		$darkSecondColor = $themeColors['dark']['secondary'];
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

		// Only load LiveRC JS when a desktop or mobile recent feed can render.
		$hasSidebar = $this->shouldRenderSidebar();
		if (
			$wgWhaleEnableLiveRC &&
			(
				( $hasSidebar && $this->shouldRenderLiveRecent() ) ||
				$this->shouldRenderMobileLiveRecent( $hasSidebar )
			)
		) {
			$modules[] = 'skins.whale.liverc';
		}

		// Only load modal login JS for anons, no point in loading it for logged-in
		// users since the modal HTML isn't even rendered for them.
		if ( $skin->getUser()->isAnon() ) {
			$modules[] = 'skins.whale.loginjs';
		}

		if ( $this->isWhaleFeatureEnabled( 'WhaleEnableHeadingAnchors', 'whale-heading-anchors' ) ) {
			$modules[] = 'skins.whale.headingAnchors';
		}

		if (
			$this->isWhaleFeatureEnabled( 'WhaleEnableResponsiveTables', 'whale-responsive-tables' ) ||
			$this->isWhaleFeatureEnabled( 'WhaleEnableSortableTables', 'whale-sortable-tables' )
		) {
			$modules[] = 'skins.whale.tables';
		}

		if ( $this->isWhaleFeatureEnabled( 'WhaleEnableShortUrls', 'whale-short-url' ) ) {
			$modules[] = 'skins.whale.shortUrl';
		}

		if ( ( $GLOBALS['wgWhaleEnableAnonThemeToggle'] ?? true ) !== false ) {
			$modules[] = 'skins.whale.themeToggle';
		}

		if ( $this->isWhaleFeatureEnabled( 'WhaleEnableImageLazyLoad', 'whale-lazy-images' ) ) {
			$modules[] = 'skins.whale.lazyImages';
		}

		$out->addModules( $modules );

		// @codingStandardsIgnoreStart
		$out->addInlineStyle(
			".Whale {
			--whale-main-color: $mainColor;
			--whale-second-color: $secondColor;
		}

		body.whale-dark .Whale {
			--whale-main-color: $darkMainColor;
			--whale-second-color: $darkSecondColor;
		}

		@media (prefers-color-scheme: dark) {
			body.whale-auto-dark .Whale {
				--whale-main-color: $darkMainColor;
				--whale-second-color: $darkSecondColor;
			}

			body.whale-auto-dark.Whale {
				--whale-main-color: $darkMainColor;
				--whale-second-color: $darkSecondColor;
			}
		}

		body.whale-dark.Whale {
			--whale-main-color: $darkMainColor;
			--whale-second-color: $darkSecondColor;
		}"
		);

		// layout settings
		$WhaleUserWidthSettings = $userOptionsLookup->getOption( $user, 'whale-layout-width' );
		$WhaleUserSidebarSettings = $userOptionsLookup->getOption( $user, 'whale-layout-sidebar' );
		$WhaleUserNavbarSettings = $userOptionsLookup->getOption( $user, 'whale-layout-navfix' );
		$WhaleUsercontrolbarSettings = $userOptionsLookup->getOption( $user, 'whale-layout-controlbar' );

		if ( isset( $WhaleUserNavbarSettings ) && $WhaleUserNavbarSettings ) {
			$out->addInlineStyle(
				".Whale .whale-nav-wrapper.whale-navbar-fixed {
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

				.Whale .whale-nav-wrapper .whale-navbar {
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

		// @codingStandardsIgnoreEnd
		$this->setupCss( $out );
	}

	/**
	 * Get template data for Mustache rendering.
	 *
	 * @return array<string,mixed>
	 */
	public function getTemplateData(): array {
		global $wgWhaleAdSetting, $wgWhaleEnableLiveRC, $wgWhaleMobileReplaceAd;

		$data = parent::getTemplateData();
		$renderer = new WhaleRenderer( $this );
		$request = $this->getRequest();
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$hasAds = isset( $wgWhaleAdSetting['client'] ) && $wgWhaleAdSetting['client'];
		$hasSidebar = $this->shouldRenderSidebar();
		$hasLiveRecent = $wgWhaleEnableLiveRC && $this->shouldRenderLiveRecent();
		$hasDesktopLiveRecent = $hasLiveRecent && $hasSidebar;
		$hasMobileLiveRecent = $wgWhaleEnableLiveRC && $this->shouldRenderMobileLiveRecent( $hasSidebar );
		$siteNoticeHtml = $request->getCookie( 'disable-notice' )
			? ''
			: $this->getVisibleSiteNoticeHtml( $data['html-site-notice'] ?? '' );
		$categoryBlur = $userOptionsLookup->getOption( $this->getUser(), 'whale-content-category-blur' );

		$categoriesHtml = $data['html-categories'] ?? '';
		$data['html-title'] = $this->getOutput()->getPageTitle();
		$data['html-categories'] = is_string( $categoriesHtml ) ? WhaleHooks::decorateCategoryHtml(
			$categoriesHtml,
			$categoryBlur !== false
		) : $categoriesHtml;
		$data['data-whale-not-found'] = $this->getNotFoundPageData();
		$data['has-whale-not-found'] = !empty( $data['data-whale-not-found']['has-not-found'] );
		$data['data-whale-nav'] = $renderer->getNavData();
		$data['data-whale-theme-toggle'] = $data['data-whale-nav']['theme-toggle'] ?? false;
		$data['has-whale-site-notice'] = $siteNoticeHtml !== '';
		$data['html-whale-site-notice'] = $siteNoticeHtml;
		$data['data-whale-content-tools'] = $renderer->getContentToolsData();
		$data['has-whale-sidebar'] = $hasSidebar;
		$data['has-whale-live-recent'] = $hasDesktopLiveRecent;
		$data['has-whale-mobile-live-recent'] = $hasMobileLiveRecent;
		$data['data-whale-live-recent'] = $hasDesktopLiveRecent ? $renderer->getLiveRecentData() : [];
		$data['data-whale-mobile-live-recent'] = $hasMobileLiveRecent ? $renderer->getLiveRecentData( 'mobile' ) : [];
		$data['html-whale-right-ad'] =
			$hasSidebar && $this->shouldRenderAd( 'right', 'whale-ads-right' )
				? $this->renderAdHtml( $renderer->getAdData( 'right' ) )
				: '';
		$data['html-whale-header-ad'] =
			$this->shouldRenderAd( 'header', 'whale-ads-header' )
				? $this->renderAdHtml( $renderer->getAdData( 'header' ) )
				: '';
		$data['html-whale-belowarticle-ad'] =
			$this->shouldRenderAd( 'belowarticle', 'whale-ads-belowarticle' )
				? $this->renderAdHtml( $renderer->getAdData( 'belowarticle' ) )
				: '';
		$data['html-whale-bottom-ad'] =
			$this->shouldRenderAd( 'bottom', 'whale-ads-bottom' )
				? $this->renderAdHtml( $renderer->getAdData( 'bottom' ) )
				: '';
		$data['has-whale-mobile-ad'] =
			isset( $wgWhaleMobileReplaceAd ) && $wgWhaleMobileReplaceAd &&
			isset( $wgWhaleAdSetting['right'] ) && $wgWhaleAdSetting['right'];
		$shortUrlData = $renderer->getShortUrlData();
		$footerData = $renderer->getFooterData();
		$footerData['short-url'] = $shortUrlData;
		$footerData['has-short-url'] = !empty( $shortUrlData['has-short-url'] );
		$footerData['short-url-button-label'] = $shortUrlData['button-label'] ?? '';
		$data['data-whale-footer'] = $footerData;
		$data['data-whale-short-url'] = $shortUrlData;
		$data['data-whale-external-link-warning'] = $renderer->getExternalLinkWarningData();
		$data['data-whale-login-modal'] = $renderer->getLoginModalData();
		$data['data-whale-user-contribution-graph'] = $renderer->getUserContributionGraphData();
		$data['has-whale-user-contribution-graph'] =
			!empty( $data['data-whale-user-contribution-graph']['has-user-contribution-graph'] );
		$data['html-whale-scroll-up-icon'] = $renderer->getIcon( 'angle-up' );
		$data['html-whale-scroll-down-icon'] = $renderer->getIcon( 'angle-down' );
		$data['html-whale-scroll-toc-icon'] = $renderer->getIcon( 'list' );
		$data['html-whale-tools-icon'] = $renderer->getIcon( 'wrench' );
		$data['whale-tools-label'] = $this->msg( 'whale-tools' )->text();
		$data['html-whale-adsense-script'] = $hasAds
			? '<script async defer src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>'
			: '';
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

	/**
	 * @return array<string,mixed>
	 */
	private function getNotFoundPageData(): array {
		$title = $this->getTitle();
		$request = $this->getRequest();

		if (
			!$title ||
			$title->exists() ||
			$title->getNamespace() === NS_SPECIAL ||
			$request->getVal( 'action', 'view' ) !== 'view' ||
			$request->getCheck( 'oldid' )
		) {
			return [ 'has-not-found' => false ];
		}

		$pageName = $title->getPrefixedText();
		$searchUrl = SpecialPage::getTitleFor( 'Search' )->getLocalURL( [
			'search' => $pageName,
		] );
		$mainPage = Title::newMainPage();

		return [
			'has-not-found' => true,
			'html-icon' => ( new WhaleRenderer( $this ) )->getIcon( 'question' ),
			'title' => $this->msg( 'whale-not-found-title', $pageName )->text(),
			'description' => $this->msg( 'whale-not-found-description' )->text(),
			'html-actions' => implode( '', [
				Html::element( 'a', [
					'class' => 'whale-btn whale-btn-primary whale-not-found-action',
					'href' => $title->getLocalURL( [ 'action' => 'edit' ] ),
				], $this->msg( 'whale-not-found-create' )->text() ),
				Html::element( 'a', [
					'class' => 'whale-btn whale-btn-secondary whale-not-found-action',
					'href' => $searchUrl,
				], $this->msg( 'search' )->text() ),
				Html::element( 'a', [
					'class' => 'whale-btn whale-btn-secondary whale-not-found-action',
					'href' => $mainPage->getLocalURL(),
				], $this->msg( 'mainpage' )->text() ),
			] ),
		];
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

	private function shouldRenderLiveRecent(): bool {
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		return !$userOptionsLookup->getOption( $this->getUser(), 'whale-layout-sidebar' );
	}

	private function shouldRenderMobileLiveRecent( bool $hasSidebar ): bool {
		if ( !$this->shouldRenderLiveRecent() ) {
			return false;
		}

		$title = $this->getTitle();
		if ( !$hasSidebar && $title && $title->getNamespace() === NS_SPECIAL ) {
			return false;
		}

		return true;
	}

	private function isWhaleFeatureEnabled( string $configKey, string $optionKey ): bool {
		$configValue = $GLOBALS['wg' . $configKey] ?? true;
		if ( $configValue === false ) {
			return false;
		}

		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		return $userOptionsLookup->getOption( $this->getUser(), $optionKey, true ) !== false;
	}

	private function shouldRenderAd( string $position, string $optionKey ): bool {
		global $wgWhaleAdSetting, $wgWhaleAdGroup;

		if (
			!isset( $wgWhaleAdSetting['client'], $wgWhaleAdSetting[$position] ) ||
			!$wgWhaleAdSetting['client'] ||
			!$wgWhaleAdSetting[$position]
		) {
			return false;
		}

		if ( isset( $wgWhaleAdGroup ) && $wgWhaleAdGroup === 'differ' ) {
			$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
			if ( $userOptionsLookup->getOption( $this->getUser(), $optionKey ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array<string,mixed> $adData
	 */
	private function renderAdHtml( array $adData ): string {
		return Html::rawElement(
			'div',
			[ 'class' => $adData['class'] ?? '' ],
			Html::rawElement( 'ins', [
				'class' => 'adsbygoogle',
				'data-full-width-responsive' => $adData['full-width-responsive'] ?? 'true',
				'data-ad-client' => $adData['client'] ?? '',
				'data-ad-slot' => $adData['slot'] ?? '',
				'data-ad-format' => $adData['format'] ?? 'auto',
			], '' )
		);
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

	/**
	 * @return array{light:array{primary:string,secondary:string},dark:array{primary:string,secondary:string}}
	 */
	private function resolveThemeColors( mixed $userTheme ): array {
		$userThemeSlug = is_string( $userTheme ) && isset( self::THEME_PALETTES[$userTheme] )
			? $userTheme
			: null;
		$siteThemeSlug = isset( $GLOBALS['wgWhaleTheme'] ) && is_string( $GLOBALS['wgWhaleTheme'] )
			? strtolower( $GLOBALS['wgWhaleTheme'] )
			: null;
		$themeSlug = $userThemeSlug ?? $siteThemeSlug;
		$hasTheme = $themeSlug !== null && isset( self::THEME_PALETTES[$themeSlug] );
		$colors = $hasTheme ? self::THEME_PALETTES[$themeSlug] : self::LEGACY_THEME_COLORS;
		$primary = $userThemeSlug === null
			? $this->normalizeOptionalCssColor( $GLOBALS['wgWhalePrimaryColor'] ?? null )
			: null;
		$secondary = $userThemeSlug === null
			? $this->normalizeOptionalCssColor( $GLOBALS['wgWhaleSecondaryColor'] ?? null )
			: null;
		$legacyPrimary = $userThemeSlug === null
			? $this->normalizeOptionalCssColor( $GLOBALS['wgWhaleMainColor'] ?? null )
			: null;
		$legacySecondary = $userThemeSlug === null
			? $this->normalizeOptionalCssColor( $GLOBALS['wgWhaleSecondColor'] ?? null )
			: null;

		if ( $primary === null && ( !$hasTheme || $legacyPrimary !== self::LEGACY_THEME_COLORS['light']['primary'] ) ) {
			$primary = $legacyPrimary;
		}

		if ( $secondary === null ) {
			$secondary = $legacySecondary;
		}

		$hasPrimaryOverride = $primary !== null;
		$lightPrimary = $primary ?? $colors['light']['primary'];
		$darkPrimary = $primary ?? $colors['dark']['primary'];

		return [
			'light' => [
				'primary' => $lightPrimary,
				'secondary' => $secondary ?? ( $hasPrimaryOverride ? $this->deriveSecondaryColor(
					$lightPrimary,
					$colors['light']['secondary']
				) : $colors['light']['secondary'] ),
			],
			'dark' => [
				'primary' => $darkPrimary,
				'secondary' => $secondary ?? ( $hasPrimaryOverride ? $this->deriveSecondaryColor(
					$darkPrimary,
					$colors['dark']['secondary']
				) : $colors['dark']['secondary'] ),
			],
		];
	}

	private function normalizeOptionalCssColor( mixed $color ): ?string {
		return is_string( $color ) && preg_match( '/^#[0-9a-f]{6}$/i', $color )
			? strtoupper( $color )
			: null;
	}

	private function deriveSecondaryColor( string $primary, string $fallback ): string {
		$value = hexdec( substr( $primary, 1 ) ) - hexdec( '1A1415' );
		if ( !is_int( $value ) || $value < 0 ) {
			return $fallback;
		}

		return sprintf( '#%06X', $value );
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
