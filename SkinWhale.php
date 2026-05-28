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

		$mainColor = $optionMainColor ? $optionMainColor : $GLOBALS['wgWhaleMainColor'];
		// @codingStandardsIgnoreLine
		$tempSecondColor = isset( $GLOBALS['wgWhaleSecondColor'] ) ? $GLOBALS['wgWhaleSecondColor'] : '#' . strtoupper( dechex( hexdec( substr( $mainColor, 1, 6 ) ) - hexdec( '1A1415' ) ) );
		$secondColor = $optionSecondColor ? $optionSecondColor : $tempSecondColor;
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
			".Whale .nav-wrapper,
		.Whale .nav-wrapper .navbar .form-inline .btn:hover,
		.Whale .nav-wrapper .navbar .form-inline .btn:focus,
		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item .nav-link.active::before,
		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item .nav-link:hover::before,
		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item .nav-link:focus::before,
		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item .nav-link:active::before,
		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-footer .label,
		.Whale .content-wrapper .whale-content .whale-content-header .content-tools .tools-btn:hover,
		.Whale .content-wrapper .whale-content .whale-content-header .content-tools .tools-btn:focus,
		.Whale .content-wrapper .whale-content .whale-content-header .content-tools .tools-btn:active {
			background-color: $mainColor;
		}

		.Whale .nav-wrapper .navbar .form-inline .btn:hover,
		.Whale .nav-wrapper .navbar .form-inline .btn:focus {
			border-color: $secondColor;
		}

		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item .nav-link.active::before,
		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item .nav-link:hover::before,
		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item .nav-link:focus::before,
		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item .nav-link:active::before {
			border-bottom: 2px solid $mainColor;
		}

		.Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-footer .label:hover,
		.Whale .nav-wrapper .navbar .navbar-nav .nav-item .nav-link:hover,
		.Whale .nav-wrapper .navbar .navbar-nav .nav-item .nav-link:focus,
		.dropdown-menu .dropdown-item:hover {
			background-color: $secondColor;
		}


		.Whale .content-wrapper #whale-bottombtn,
		.Whale .content-wrapper #whale-bottombtn:hover {
			background-color: $mainColor;
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

		// Font settings
		$WhaleUserFontSettings = $userOptionsLookup->getOption( $user, 'whale-font' );
		if ( $WhaleUserFontSettings !== null ) {
			$out->addInlineStyle(
				"body, h1, h2, h3, h4, h5, h6, b {
					font-family: $WhaleUserFontSettings;
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

		$WhaleDarkCss = "body, .Whale, .dropdown-menu, .dropdown-item, .Whale .nav-wrapper .navbar .form-inline .btn, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item .nav-link.active, .Whale .content-wrapper .whale-content .whale-content-main table.wikitable tr > th, .Whale .content-wrapper .whale-content .whale-content-main table.wikitable tr > td, table.mw_metadata th, .Whale .content-wrapper .whale-content .whale-content-main table.infobox th, #preferences fieldset:not(.prefsection), #preferences div.mw-prefs-buttons, .navbox, .navbox-subgroup, .navbox > tbody > tr:nth-child(even) > .navbox-list {
			background-color: #000;
			color: #DDD;
		}

		.whale-content-header, .whale-footer, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-footer, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item, .Whale .content-wrapper .whale-content .whale-content-header, .Whale .content-wrapper .whale-footer, .editOptions, html .wikiEditor-ui-toolbar, #pagehistory li.selected, .mw-datatable td, .Whale .content-wrapper .whale-content .whale-content-main table.wikitable tr > td, table.mw_metadata td, .Whale .content-wrapper .whale-content .whale-content-main table.wikitable, .Whale .content-wrapper .whale-content .whale-content-main table.infobox, #preferences, .navbox-list, .dropdown-divider {
			background-color: #1F2023;
			color: #DDD;
		}

		.Whale .content-wrapper .whale-content .whale-content-main, .mw-datatable th, .mw-datatable tr:hover td, textarea, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-content, div.mw-warning-with-logexcerpt, div.mw-lag-warn-high, div.mw-cascadeprotectedwarning, div#mw-protect-cascadeon {
			background-color: #000;
		}

		.Whale .content-wrapper .whale-content .whale-content-header .title>h1, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-content .live-recent-list .recent-item, caption { color: #DDD; }

		.btn-secondary { background: transparent; color: #DDD; }

		#pagehistory li { border: 0; }

		.Whale .content-wrapper .whale-footer, .Whale .content-wrapper .whale-content .whale-content-header, .Whale .content-wrapper .whale-content .whale-content-main, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-footer, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-content, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-header .nav .nav-item + .nav-item, .Whale .content-wrapper .whale-content .whale-content-header .content-tools .tools-btn:hover, .Whale .content-wrapper .whale-content .whale-content-header .content-tools .tools-btn:focus, .Whale .content-wrapper .whale-content .whale-content-header .content-tools .tools-btn, .dropdown-menu, .dropdown-divider, .Whale .content-wrapper .whale-content .whale-content-main fieldset, hr, .Whale .content-wrapper .whale-sidebar .live-recent-wrapper .live-recent .live-recent-content .live-recent-list li, .mw-changeslist-legend, .Whale .content-wrapper .whale-content .whale-content-header .content-tools { border-color: #555; }

		.flow-post, .Whale .content-wrapper .whale-content .whale-content-main .toc .toctext { color: #DDD; }
		.flow-topic-titlebar { color: #000; }
		.flow-ui-navigationWidget { color: #FFF; }
		.Whale .content-wrapper .whale-content .whale-content-main .toccolours, .Whale .content-wrapper .whale-content .whale-content-main .toc ul, .Whale .content-wrapper .whale-content .whale-content-main .toc li { background-color: #000; }
		.Whale .content-wrapper .whale-content .whale-content-main .toc .toctitle { background-color: #1F2023; }";

		$WhaleUserDarkSetting = $userOptionsLookup->getOption( $user, 'whale-dark' );
		if ( $WhaleUserDarkSetting === 'dark' ) {
			$out->addInlineStyle( $WhaleDarkCss );
		} elseif ( $WhaleUserDarkSetting === null ) {
			$out->addInlineStyle( "@media (prefers-color-scheme: dark) { $WhaleDarkCss }" );
		}

		// @codingStandardsIgnoreEnd
		$this->setupCss( $out );
	}

	/**
	 * Setup skin CSS.
	 *
	 * @param OutputPage $out OutputPage
	 */
	public function setupCss( OutputPage $out ) {
		$out->addHeadItem(
			'font-awesome',
			// @codingStandardsIgnoreLine
			'<link rel="stylesheet" href="//use.fontawesome.com/releases/v5.13.1/css/all.css" />'
		);

		$out->addHeadItem(
			'font-awesome-shims',
			// @codingStandardsIgnoreLine
			'<link rel="stylesheet" href="//use.fontawesome.com/releases/v5.13.1/css/v4-shims.css" />'
		);

		$out->addHeadItem(
			'webfonts',
			// @codingStandardsIgnoreLine
			'<link href="https://fonts.googleapis.com/css?family=Dokdo|Gaegu|Nanum+Gothic|Nanum+Gothic+Coding|Nanum+Myeongjo|Noto+Serif+KR|Noto+Sans+KR&display=swap&subset=korean" rel="stylesheet">'
		);

		$out->addHeadItem(
			'share-api-polyfill',
			// @codingStandardsIgnoreLine
			'<script async src="https://unpkg.com/share-api-polyfill/dist/share-min.js"></script>'
		);
		$out->addModuleStyles( [ 'skins.whale.styles' ] );
	}
}
