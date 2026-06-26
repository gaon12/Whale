<?php

//phpcs:ignore
class WhaleHooks {
	private const SCROLL_BUTTONS_VERTICAL = 'vertical';
	private const SCROLL_BUTTONS_HORIZONTAL = 'horizontal';
	private const FONT_SCALE_SMALL = 'small';
	private const FONT_SCALE_NORMAL = 'normal';
	private const FONT_SCALE_LARGE = 'large';
	private const FONT_SCALE_XLARGE = 'x-large';

	/**
	 * @param OutputPage $out
	 * @param Skin $sk
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $sk ): void {
		if ( $sk->getSkinName() !== 'whale' ) {
			return;
		}

		if ( method_exists( $sk, 'getWhaleClientModules' ) ) {
			$modules = call_user_func( [ $sk, 'getWhaleClientModules' ] );
			if ( is_array( $modules ) ) {
				$out->addModules( array_values( array_filter( $modules, 'is_string' ) ) );
			}
		}
	}

	/**
	 * @since 1.17.0
	 * @param OutputPage $out
	 * @param Skin $sk
	 * @param array &$bodyAttrs
	 */
	public static function onOutputPageBodyAttributes( OutputPage $out, Skin $sk, array &$bodyAttrs ): void {
		global $wgWhaleEnableFloatingToc, $wgWhaleEnableReadingProgress, $wgWhaleEnableHeadingAnchors,
			$wgWhaleEnableResponsiveTables, $wgWhaleEnableSortableTables, $wgWhaleEnableContentFontScale,
			$wgWhaleEnableMobileFloatingToc, $wgWhaleMobileUserToolsPosition, $wgWhaleEnableContentSkeleton;

		if ( $sk->getSkinName() === 'whale' ) {
			$bodyAttrs['class'] .= ' Whale width-size';
			$userOptionsLookup = MediaWiki\MediaWikiServices::getInstance()->getUserOptionsLookup();
			$darkMode = $userOptionsLookup->getOption( $sk->getUser(), 'whale-dark' );
			if ( $sk->getUser()->isAnon() ) {
				$cookieDarkMode = self::getAnonDarkMode( $sk );
				if ( $cookieDarkMode !== null ) {
					$darkMode = $cookieDarkMode;
				}
			}

			if ( $darkMode === 'dark' ) {
				$bodyAttrs['class'] .= ' whale-dark';
			} elseif ( $darkMode === null ) {
				$bodyAttrs['class'] .= ' whale-auto-dark';
			}

			if ( $userOptionsLookup->getOption( $sk->getUser(), 'whale-content-reduce-motion' ) ) {
				$bodyAttrs['class'] .= ' whale-reduce-motion';
			}

			$scrollButtons = $userOptionsLookup->getOption( $sk->getUser(), 'whale-layout-scroll-buttons' );
			if ( $scrollButtons === self::SCROLL_BUTTONS_HORIZONTAL ) {
				$bodyAttrs['class'] .= ' whale-scroll-buttons-horizontal';
			} else {
				$bodyAttrs['class'] .= ' whale-scroll-buttons-vertical';
			}

			$sectionNavigationEnabled = self::shouldRenderSectionNavigation( $out );
			if (
				$sectionNavigationEnabled &&
				( $wgWhaleEnableFloatingToc ?? true ) !== false &&
				$userOptionsLookup->getOption( $sk->getUser(), 'whale-layout-floating-toc' ) !== false
			) {
				$bodyAttrs['class'] .= ' whale-floating-toc-enabled';
			}

			if (
				$sectionNavigationEnabled &&
				( $wgWhaleEnableMobileFloatingToc ?? true ) !== false &&
				$userOptionsLookup->getOption( $sk->getUser(), 'whale-layout-mobile-toc' ) !== false
			) {
				$bodyAttrs['class'] .= ' whale-mobile-floating-toc-enabled';
			}

			if (
				( $wgWhaleEnableReadingProgress ?? true ) !== false &&
				$userOptionsLookup->getOption( $sk->getUser(), 'whale-reading-progress' ) !== false
			) {
				$bodyAttrs['class'] .= ' whale-reading-progress-enabled';
			}

			if (
				( $wgWhaleEnableHeadingAnchors ?? true ) !== false &&
				$userOptionsLookup->getOption( $sk->getUser(), 'whale-heading-anchors' ) !== false
			) {
				$bodyAttrs['class'] .= ' whale-heading-anchors-enabled';
			}

			if (
				( $wgWhaleEnableResponsiveTables ?? true ) !== false &&
				$userOptionsLookup->getOption( $sk->getUser(), 'whale-responsive-tables' ) !== false
			) {
				$bodyAttrs['class'] .= ' whale-responsive-tables-enabled';
			}

			if (
				( $wgWhaleEnableSortableTables ?? true ) !== false &&
				$userOptionsLookup->getOption( $sk->getUser(), 'whale-sortable-tables' ) !== false
			) {
				$bodyAttrs['class'] .= ' whale-sortable-tables-enabled';
			}

			if (
				( $wgWhaleMobileUserToolsPosition ?? 'right' ) === 'right' &&
				$userOptionsLookup->getOption( $sk->getUser(), 'whale-mobile-user-tools-right' ) !== false
			) {
				$bodyAttrs['class'] .= ' whale-mobile-user-tools-right';
			}

			if ( ( $wgWhaleEnableContentFontScale ?? true ) !== false ) {
				$fontScale = $userOptionsLookup->getOption( $sk->getUser(), 'whale-content-font-scale' );
				if ( !in_array( $fontScale, [
					self::FONT_SCALE_SMALL,
					self::FONT_SCALE_LARGE,
					self::FONT_SCALE_XLARGE
				] ) ) {
					$fontScale = self::FONT_SCALE_NORMAL;
				}
				$bodyAttrs['class'] .= ' whale-font-scale-' . $fontScale;
			}

			if (
				( $wgWhaleEnableContentSkeleton ?? false ) !== false &&
				$userOptionsLookup->getOption( $sk->getUser(), 'whale-content-skeleton' ) === true
			) {
				$bodyAttrs['class'] .= ' whale-content-skeleton-enabled whale-content-skeleton-loading';
			}
		}
	}

	private static function shouldRenderSectionNavigation( object $out ): bool {
		$getTitle = [ $out, 'getTitle' ];
		if ( !is_callable( $getTitle ) ) {
			return false;
		}

		$title = $getTitle();
		if ( !is_object( $title ) ) {
			return false;
		}

		$getNamespace = [ $title, 'getNamespace' ];
		if ( !is_callable( $getNamespace ) || $getNamespace() === NS_SPECIAL ) {
			return false;
		}

		$getRequest = [ $out, 'getRequest' ];
		if ( !is_callable( $getRequest ) ) {
			return true;
		}

		$request = $getRequest();
		if ( !is_object( $request ) ) {
			return true;
		}

		$getVal = [ $request, 'getVal' ];
		if ( !is_callable( $getVal ) ) {
			return true;
		}

		$action = $getVal( 'action', 'view' );
		return $action === 'view';
	}

	/**
	 * Set up user preferences specific to the Whale skin.
	 *
	 * @param User $user user
	 * @param array<string,mixed> &$preferences preferences
	 */
	public static function onGetPreferences( \User $user, array &$preferences ): void {
		global $wgWhaleAdSetting, $wgWhaleAdGroup, $wgWhaleEnableUserContributionGraph,
			$wgWhaleEnableShortUrls, $wgWhaleEnableHeadingAnchors, $wgWhaleEnableReadingProgress,
			$wgWhaleEnableResponsiveTables, $wgWhaleEnableSortableTables, $wgWhaleEnableContentFontScale,
			$wgWhaleEnableMobileFloatingToc, $wgWhaleMobileUserToolsPosition, $wgWhaleEnableFloatingToc,
			$wgWhaleEnableLiveRC, $wgWhaleEnableSectionCollapse, $wgWhaleEnableFoldingBlocks,
			$wgWhaleEnableBlurredCategories;

		$service = MediaWiki\MediaWikiServices::getInstance();
		$userGroupManager = $service->getUserGroupManager();
		$userGroups = $userGroupManager->getUserGroups( $user );
		$permissionManager = $service->getPermissionManager();

		$preferences['whale-layout-width'] = [
			'type' => 'select',
			'label-message' => 'whale-pref-layout-width',
			'section' => 'whale/layout',
			'options' => [
				wfMessage( 'whale-layout-select-1000' )->text() => '1000px',
				wfMessage( 'whale-layout-select-1100' )->text() => '1100px',
				wfMessage( 'whale-layout-select-1200' )->text() => '1200px',
				wfMessage( 'whale-layout-select-1300' )->text() => '1300px',
				wfMessage( 'whale-layout-select-1400' )->text() => null,
				wfMessage( 'whale-layout-select-1500' )->text() => '1500px',
				wfMessage( 'whale-layout-select-1600' )->text() => '1600px',
			],
			'help-message' => 'whale-pref-layout-width-help',
			'default' => null
		];

		$preferences['whale-layout-navfix'] = [
			'type' => 'toggle',
			'label-message' => 'whale-pref-layout-navfix',
			'section' => 'whale/layout',
		];

		$preferences['whale-layout-sidebar'] = [
			'type' => 'toggle',
			'label-message' => 'whale-pref-layout-sidebar',
			'section' => 'whale/layout',
		];

		$preferences['whale-layout-controlbar'] = [
			'type' => 'toggle',
			'label-message' => 'whale-pref-layout-controlbar',
			'section' => 'whale/layout',
		];

		$preferences['whale-layout-scroll-buttons'] = [
			'type' => 'select',
			'label-message' => 'whale-pref-layout-scroll-buttons',
			'section' => 'whale/layout',
			'options' => [
				wfMessage( 'whale-scroll-buttons-vertical' )->text() => self::SCROLL_BUTTONS_VERTICAL,
				wfMessage( 'whale-scroll-buttons-horizontal' )->text() => self::SCROLL_BUTTONS_HORIZONTAL,
			],
			'help-message' => 'whale-pref-layout-scroll-buttons-help',
			'default' => self::SCROLL_BUTTONS_VERTICAL
		];

		if ( ( $wgWhaleEnableFloatingToc ?? true ) !== false ) {
			$preferences['whale-layout-floating-toc'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-layout-floating-toc',
				'section' => 'whale/layout',
				'default' => true
			];
		}

		if ( ( $wgWhaleEnableMobileFloatingToc ?? true ) !== false ) {
			$preferences['whale-layout-mobile-toc'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-layout-mobile-toc',
				'section' => 'whale/layout',
				'help-message' => 'whale-pref-layout-mobile-toc-help',
				'default' => true
			];
		}

		if ( ( $wgWhaleEnableLiveRC ?? true ) !== false ) {
			$preferences['whale-live-recent-fixed-height'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-live-recent-fixed-height',
				'section' => 'whale/layout',
				'help-message' => 'whale-pref-live-recent-fixed-height-help',
				'default' => true
			];
		}

		if ( ( $wgWhaleEnableSectionCollapse ?? true ) !== false ) {
			$preferences['whale-content-section-collapse'] = [
				'type' => 'select',
				'label-message' => 'whale-pref-content-section-collapse',
				'section' => 'whale/content',
				'options' => [
					wfMessage( 'whale-section-collapse-marked' )->text() => WhaleArticleDecorator::SECTION_COLLAPSE_MARKED,
					wfMessage( 'whale-section-collapse-all' )->text() => WhaleArticleDecorator::SECTION_COLLAPSE_ALL,
					wfMessage( 'whale-section-collapse-off' )->text() => WhaleArticleDecorator::SECTION_COLLAPSE_OFF,
				],
				'help-message' => 'whale-pref-content-section-collapse-help',
				'default' => WhaleArticleDecorator::SECTION_COLLAPSE_ALL
			];
		}

		if ( ( $wgWhaleEnableFoldingBlocks ?? true ) !== false ) {
			$preferences['whale-content-folding'] = [
				'type' => 'select',
				'label-message' => 'whale-pref-content-folding',
				'section' => 'whale/content',
				'options' => [
					wfMessage( 'whale-folding-mode-default' )->text() => WhaleArticleDecorator::FOLDING_MODE_DEFAULT,
					wfMessage( 'whale-folding-mode-open' )->text() => WhaleArticleDecorator::FOLDING_MODE_OPEN,
					wfMessage( 'whale-folding-mode-off' )->text() => WhaleArticleDecorator::FOLDING_MODE_OFF,
				],
				'help-message' => 'whale-pref-content-folding-help',
				'default' => WhaleArticleDecorator::FOLDING_MODE_DEFAULT
			];
		}

		if ( ( $wgWhaleEnableBlurredCategories ?? true ) !== false ) {
			$preferences['whale-content-category-blur'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-content-category-blur',
				'section' => 'whale/content',
				'default' => true
			];
		}

		$preferences['whale-content-reduce-motion'] = [
			'type' => 'toggle',
			'label-message' => 'whale-pref-content-reduce-motion',
			'section' => 'whale/content',
		];

		if ( ( $wgWhaleEnableReadingProgress ?? true ) !== false ) {
			$preferences['whale-reading-progress'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-reading-progress',
				'section' => 'whale/content',
				'default' => true
			];
		}

		if ( ( $wgWhaleEnableHeadingAnchors ?? true ) !== false ) {
			$preferences['whale-heading-anchors'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-heading-anchors',
				'section' => 'whale/content',
				'default' => true
			];
		}

		if ( ( $wgWhaleEnableResponsiveTables ?? true ) !== false ) {
			$preferences['whale-responsive-tables'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-responsive-tables',
				'section' => 'whale/content',
				'default' => true
			];
		}

		if ( ( $wgWhaleEnableSortableTables ?? true ) !== false ) {
			$preferences['whale-sortable-tables'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-sortable-tables',
				'section' => 'whale/content',
				'default' => true
			];
		}

		if ( ( $wgWhaleEnableContentFontScale ?? true ) !== false ) {
			$preferences['whale-content-font-scale'] = [
				'type' => 'select',
				'label-message' => 'whale-pref-content-font-scale',
				'section' => 'whale/content',
				'options' => [
					wfMessage( 'whale-font-scale-small' )->text() => self::FONT_SCALE_SMALL,
					wfMessage( 'whale-font-scale-normal' )->text() => self::FONT_SCALE_NORMAL,
					wfMessage( 'whale-font-scale-large' )->text() => self::FONT_SCALE_LARGE,
					wfMessage( 'whale-font-scale-x-large' )->text() => self::FONT_SCALE_XLARGE,
				],
				'default' => self::FONT_SCALE_NORMAL
			];
		}

		if ( ( $wgWhaleEnableUserContributionGraph ?? true ) !== false ) {
			$preferences['whale-user-contribution-graph'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-user-contribution-graph',
				'section' => 'whale/content',
				'default' => true
			];
		}

		if ( ( $wgWhaleEnableShortUrls ?? true ) !== false ) {
			$preferences['whale-short-url'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-short-url',
				'section' => 'whale/layout',
				'default' => true
			];
		}

		if ( ( $GLOBALS['wgWhaleEnableImageLazyLoad'] ?? true ) !== false ) {
			$preferences['whale-lazy-images'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-lazy-images',
				'section' => 'whale/content',
				'default' => true
			];
		}

		if ( ( $GLOBALS['wgWhaleEnableContentSkeleton'] ?? false ) !== false ) {
			$preferences['whale-content-skeleton'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-content-skeleton',
				'section' => 'whale/content',
				'help-message' => 'whale-pref-content-skeleton-help',
				'default' => false
			];
		}

		if ( ( $wgWhaleMobileUserToolsPosition ?? 'right' ) === 'right' ) {
			$preferences['whale-mobile-user-tools-right'] = [
				'type' => 'toggle',
				'label-message' => 'whale-pref-mobile-user-tools-right',
				'section' => 'whale/layout',
				'default' => true
			];
		}

		if (
			isset( $wgWhaleAdSetting['client'] ) && $wgWhaleAdSetting['client'] &&
			isset( $wgWhaleAdGroup ) && $wgWhaleAdGroup == 'differ'
		) {
			if (
				isset( $wgWhaleAdSetting['belowarticle'] ) && $wgWhaleAdSetting['belowarticle'] &&
				$permissionManager->userHasRight( $user, 'blockads-belowarticle' )
			) {
				$preferences['whale-ads-belowarticle'] = [
					'type' => 'toggle',
					'label-message' => 'whale-pref-ads-belowarticle',
					'section' => 'whale/ads',
				];
			}

			if (
				isset( $wgWhaleAdSetting['header'] ) && $wgWhaleAdSetting['header'] &&
				$permissionManager->userHasRight( $user, 'blockads-header' )
			) {
				$preferences['whale-ads-header'] = [
					'type' => 'toggle',
					'label-message' => 'whale-pref-ads-header',
					'section' => 'whale/ads',
				];
			}

			if (
				isset( $wgWhaleAdSetting['right'] ) && $wgWhaleAdSetting['right'] &&
				$permissionManager->userHasRight( $user, 'blockads-right' )
			) {
				$preferences['whale-ads-right'] = [
					'type' => 'toggle',
					'label-message' => 'whale-pref-ads-right',
					'section' => 'whale/ads',
				];
			}

			if (
				isset( $wgWhaleAdSetting['bottom'] ) && $wgWhaleAdSetting['bottom'] &&
				$permissionManager->userHasRight( $user, 'blockads-bottom' )
			) {
				$preferences['whale-ads-bottom'] = [
					'type' => 'toggle',
					'label-message' => 'whale-pref-ads-bottom',
					'section' => 'whale/ads',
				];
			}
		}

		$preferences['whale-theme'] = [
			'type' => 'select',
			'label-message' => 'whale-pref-theme',
			'section' => 'whale/color',
			'options' => [
				wfMessage( 'whale-theme-default' )->text() => null,
				wfMessage( 'whale-theme-han-river-blue' )->text() => 'han-river-blue',
				wfMessage( 'whale-theme-hanbat-forest' )->text() => 'hanbat-forest',
				wfMessage( 'whale-theme-milk-vetch-purple' )->text() => 'milk-vetch-purple',
				wfMessage( 'whale-theme-clay-roof' )->text() => 'clay-roof',
				wfMessage( 'whale-theme-jeju-teal' )->text() => 'jeju-teal',
				wfMessage( 'whale-theme-camellia-red' )->text() => 'camellia-red',
				wfMessage( 'whale-theme-ginkgo-gold' )->text() => 'ginkgo-gold',
			],
			'help-message' => 'whale-pref-theme-help',
			'default' => null
		];

		$preferences['whale-dark'] = [
			'type' => 'select',
			'label-message' => 'whale-pref-dark',
			'section' => 'whale/color',
			'options' => [
				wfMessage( 'whale-dark-default' )->text() => null,
				wfMessage( 'whale-dark-dark' )->text() => 'dark',
				wfMessage( 'whale-dark-light' )->text() => 'light'
			],
			'help-message' => 'whale-pref-dark-help',
			'default' => null
		];
	}

	/**
	 * Convert NamuWiki-style folding blocks before MediaWiki parses the inner
	 * wikitext. Syntax:
	 *
	 * {{{#!folding [ title ]
	 * content
	 * }}}
	 *
	 * @param Parser $parser Parser
	 * @param string &$text Wikitext
	 * @param StripState $stripState Strip state
	 * @return bool
	 */
	public static function onParserBeforePreprocess( $parser, &$text, $stripState ) {
		global $wgWhaleEnableFoldingBlocks;

		if ( $wgWhaleEnableFoldingBlocks === false || strpos( $text, '{{{#!folding' ) === false ) {
			return true;
		}

		$text = WhaleArticleDecorator::convertFoldingSyntax( $text );

		return true;
	}

	/**
	 * Decorate parsed article HTML with Whale-only controls.
	 *
	 * @param OutputPage $out OutputPage
	 * @param string &$text Article HTML
	 * @return bool
	 */
	public static function onOutputPageBeforeHTML( $out, &$text ) {
		global $wgWhaleEnableSectionCollapse, $wgWhaleEnableFoldingBlocks, $wgWhaleEnableHeadingAnchors;

		$skin = method_exists( $out, 'getSkin' ) ? $out->getSkin() : null;
		if ( !$skin || $skin->getSkinName() !== 'whale' ) {
			return true;
		}

		if ( !self::shouldRenderSectionNavigation( $out ) ) {
			return true;
		}

		$userOptionsLookup = MediaWiki\MediaWikiServices::getInstance()->getUserOptionsLookup();
		$user = $skin->getUser();
		$sectionMode = ( $wgWhaleEnableSectionCollapse ?? true ) === false
			? WhaleArticleDecorator::SECTION_COLLAPSE_OFF
			: $userOptionsLookup->getOption( $user, 'whale-content-section-collapse' );
		$foldingMode = ( $wgWhaleEnableFoldingBlocks ?? true ) === false
			? WhaleArticleDecorator::FOLDING_MODE_OFF
			: $userOptionsLookup->getOption( $user, 'whale-content-folding' );
		$headingAnchorsEnabled = ( $wgWhaleEnableHeadingAnchors ?? true ) !== false &&
			$userOptionsLookup->getOption( $user, 'whale-heading-anchors' ) !== false;

		$text = WhaleArticleDecorator::decorateArticleHtml(
			$text,
			WhaleArticleDecorator::normalizeSectionMode( $sectionMode ),
			is_string( $foldingMode ) ? $foldingMode : WhaleArticleDecorator::FOLDING_MODE_DEFAULT,
			$headingAnchorsEnabled
		);

		return true;
	}

	/**
	 * Decorate category links generated outside html-body-content.
	 *
	 * @param string $html Category HTML
	 * @param bool $enableBlur Whether to blur #blur categories
	 * @return string
	 */
	public static function decorateCategoryHtml( string $html, bool $enableBlur ): string {
		return WhaleArticleDecorator::decorateCategoryHtml( $html, $enableBlur );
	}

	private static function getAnonDarkMode( Skin $sk ): ?string {
		$mode = $sk->getRequest()->getCookie( 'whale-dark-mode' );
		return in_array( $mode, [ 'dark', 'light' ], true ) ? $mode : null;
	}
}
