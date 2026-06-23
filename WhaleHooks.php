<?php

//phpcs:ignore
class WhaleHooks {
	private const SECTION_COLLAPSE_MARKED = 'marked';
	private const SECTION_COLLAPSE_ALL = 'all';
	private const SECTION_COLLAPSE_OFF = 'off';
	private const FOLDING_MODE_DEFAULT = 'default';
	private const FOLDING_MODE_OPEN = 'open';
	private const FOLDING_MODE_OFF = 'off';
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
					wfMessage( 'whale-section-collapse-marked' )->text() => self::SECTION_COLLAPSE_MARKED,
					wfMessage( 'whale-section-collapse-all' )->text() => self::SECTION_COLLAPSE_ALL,
					wfMessage( 'whale-section-collapse-off' )->text() => self::SECTION_COLLAPSE_OFF,
				],
				'help-message' => 'whale-pref-content-section-collapse-help',
				'default' => self::SECTION_COLLAPSE_ALL
			];
		}

		if ( ( $wgWhaleEnableFoldingBlocks ?? true ) !== false ) {
			$preferences['whale-content-folding'] = [
				'type' => 'select',
				'label-message' => 'whale-pref-content-folding',
				'section' => 'whale/content',
				'options' => [
					wfMessage( 'whale-folding-mode-default' )->text() => self::FOLDING_MODE_DEFAULT,
					wfMessage( 'whale-folding-mode-open' )->text() => self::FOLDING_MODE_OPEN,
					wfMessage( 'whale-folding-mode-off' )->text() => self::FOLDING_MODE_OFF,
				],
				'help-message' => 'whale-pref-content-folding-help',
				'default' => self::FOLDING_MODE_DEFAULT
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

		$text = self::convertFoldingSyntax( $text );

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
		global $wgWhaleEnableSectionCollapse, $wgWhaleEnableFoldingBlocks;

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
			? self::SECTION_COLLAPSE_OFF
			: $userOptionsLookup->getOption( $user, 'whale-content-section-collapse' );
		$foldingMode = ( $wgWhaleEnableFoldingBlocks ?? true ) === false
			? self::FOLDING_MODE_OFF
			: $userOptionsLookup->getOption( $user, 'whale-content-folding' );

		$text = self::decorateArticleHtml(
			$text,
			self::normalizeSectionMode( $sectionMode ),
			is_string( $foldingMode ) ? $foldingMode : self::FOLDING_MODE_DEFAULT
		);

		return true;
	}

	private static function normalizeSectionMode( mixed $sectionMode ): string {
		if ( in_array( $sectionMode, [
			self::SECTION_COLLAPSE_MARKED,
			self::SECTION_COLLAPSE_ALL,
			self::SECTION_COLLAPSE_OFF,
		], true ) ) {
			return $sectionMode;
		}

		return self::SECTION_COLLAPSE_ALL;
	}

	/**
	 * Decorate category links generated outside html-body-content.
	 *
	 * @param string $html Category HTML
	 * @param bool $enableBlur Whether to blur #blur categories
	 * @return string
	 */
	public static function decorateCategoryHtml( string $html, bool $enableBlur ): string {
		global $wgWhaleEnableBlurredCategories;

		if (
			!$enableBlur ||
			( $wgWhaleEnableBlurredCategories ?? true ) === false ||
			( strpos( $html, '#blur' ) === false && stripos( $html, '%23blur' ) === false )
		) {
			return $html;
		}

		return self::withHtmlFragment( $html, function ( DOMDocument $dom, DOMElement $root ) {
			$xpath = new DOMXPath( $dom );
			foreach ( $xpath->query( './/a', $root ) as $link ) {
				if ( !$link instanceof DOMElement ) {
					continue;
				}

				$href = $link->getAttribute( 'href' );
				$text = $link->textContent;
				if (
					stripos( $href, '#blur' ) === false &&
					stripos( $href, '%23blur' ) === false &&
					strpos( $text, '#blur' ) === false
				) {
					continue;
				}

				$link->setAttribute( 'href', preg_replace( '/(?:#blur|%23blur)$/i', '', $href ) ?? $href );
				self::replaceNodeText( $link, str_replace( '#blur', '', $text ) );
				self::addClass( $link, 'whale-category-blur' );
			}
		} );
	}

	private static function convertFoldingSyntax( string $text ): string {
		$lines = preg_split( "/(\r\n|\n|\r)/", $text );
		if ( $lines === false ) {
			return $text;
		}

		$output = [];
		$stackDepth = 0;

		foreach ( $lines as $line ) {
			if ( preg_match( '/^\{\{\{#!folding(?:[ \t]+(.*))?$/u', $line, $matches ) ) {
				$title = trim( $matches[1] ?? '' );
				if ( $title === '' ) {
					$title = wfMessage( 'whale-folding-default-title' )->text();
				}

				$output[] = '<div class="whale-folding is-collapsed">';
				$output[] = '<div class="whale-folding-header">' .
					'<span class="whale-folding-title">' .
					htmlspecialchars( $title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) .
					'</span></div>';
				$output[] = '<button type="button" class="whale-folding-toggle" aria-expanded="false">' .
					htmlspecialchars( wfMessage( 'whale-folding-toggle-label' )->text(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) .
					'</button>';
				$output[] = '<div class="whale-folding-body" hidden="">';
				$stackDepth++;
				continue;
			}

			if ( $stackDepth > 0 && trim( $line ) === '}}}' ) {
				$output[] = '</div></div>';
				$stackDepth--;
				continue;
			}

			$output[] = $line;
		}

		while ( $stackDepth > 0 ) {
			$output[] = '</div></div>';
			$stackDepth--;
		}

		return implode( "\n", $output );
	}

	private static function decorateArticleHtml(
		string $html,
		string $sectionMode,
		string $foldingMode
	): string {
		if ( $sectionMode === self::SECTION_COLLAPSE_OFF && strpos( $html, 'whale-folding' ) === false ) {
			return $html;
		}

		return self::withHtmlFragment( $html, function ( DOMDocument $dom, DOMElement $root ) use (
			$sectionMode,
			$foldingMode
		) {
			if ( $sectionMode !== self::SECTION_COLLAPSE_OFF ) {
				self::decorateSectionHeadings( $dom, $root, $sectionMode );
			}

			self::decorateFoldingBlocks( $dom, $root, $foldingMode );
		} );
	}

	private static function decorateSectionHeadings(
		DOMDocument $dom,
		DOMElement $root,
		string $sectionMode
	): void {
		$xpath = new DOMXPath( $dom );
		$headings = [];
		foreach ( $xpath->query( './/h1|.//h2|.//h3|.//h4|.//h5|.//h6', $root ) as $heading ) {
			if ( $heading instanceof DOMElement ) {
				$headings[] = $heading;
			}
		}

		$counter = 0;
		foreach ( $headings as $heading ) {
			if ( self::isNavigationHeading( $heading ) ) {
				continue;
			}

			$level = self::getHeadingLevel( $heading );
			if ( $level === null ) {
				continue;
			}

			$label = self::getHeadingLabelNode( $heading );
			$title = trim( $label->textContent );
			$isMarkedCollapsed = preg_match( '/^#\s*(.*?)\s*#$/u', $title, $matches ) === 1;
			if ( !$isMarkedCollapsed && $sectionMode !== self::SECTION_COLLAPSE_ALL ) {
				continue;
			}

			if ( $isMarkedCollapsed ) {
				self::replaceNodeText( $label, trim( $matches[1] ) );
			}

			$counter++;
			$bodyId = 'whale-section-body-' . $counter;
			$collapsed = $isMarkedCollapsed;
			$container = self::getHeadingContainer( $heading );
			self::addClass( $heading, 'whale-section-heading' );
			self::addClass( $container, 'whale-section-container' );
			if ( $collapsed ) {
				self::addClass( $heading, 'is-collapsed' );
				self::addClass( $container, 'is-collapsed' );
			}

			$button = self::createToggleButton(
				$dom,
				'whale-section-toggle',
				$bodyId,
				!$collapsed,
				wfMessage( 'whale-section-expand' )->text(),
				wfMessage( 'whale-section-collapse' )->text()
			);
			$heading->insertBefore( $button, $heading->firstChild );

			self::wrapSectionBody( $dom, $container, $level, $bodyId, $collapsed );
		}
	}

	private static function decorateFoldingBlocks(
		DOMDocument $dom,
		DOMElement $root,
		string $foldingMode
	): void {
		$xpath = new DOMXPath( $dom );
		$counter = 0;

		foreach ( $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " whale-folding ")]', $root ) as $folding ) {
			if ( !$folding instanceof DOMElement ) {
				continue;
			}

			$toggle = self::firstElementByClass( $folding, 'whale-folding-toggle' );
			$body = self::firstElementByClass( $folding, 'whale-folding-body' );
			if ( !$toggle || !$body ) {
				continue;
			}

			$counter++;
			$bodyId = 'whale-folding-body-' . $counter;
			$body->setAttribute( 'id', $bodyId );

			if ( strtolower( $toggle->tagName ) !== 'button' ) {
				$button = $dom->createElement( 'button' );
				while ( $toggle->firstChild ) {
					$button->appendChild( $toggle->firstChild );
				}
				foreach ( [ 'class', 'aria-expanded' ] as $attribute ) {
					if ( $toggle->hasAttribute( $attribute ) ) {
						$button->setAttribute( $attribute, $toggle->getAttribute( $attribute ) );
					}
				}
				$toggle->parentNode->replaceChild( $button, $toggle );
				$toggle = $button;
			}

			$toggle->setAttribute( 'type', 'button' );
			$toggle->setAttribute( 'aria-controls', $bodyId );
			$toggle->setAttribute( 'data-expand-label', wfMessage( 'whale-folding-expand' )->text() );
			$toggle->setAttribute( 'data-collapse-label', wfMessage( 'whale-folding-collapse' )->text() );

			if ( $foldingMode === self::FOLDING_MODE_OPEN || $foldingMode === self::FOLDING_MODE_OFF ) {
				self::removeClass( $folding, 'is-collapsed' );
				$body->removeAttribute( 'hidden' );
				$toggle->setAttribute( 'aria-expanded', 'true' );
			}

			if ( $foldingMode === self::FOLDING_MODE_OFF ) {
				self::addClass( $folding, 'is-disabled' );
				$toggle->setAttribute( 'disabled', 'disabled' );
			}
		}
	}

	private static function wrapSectionBody(
		DOMDocument $dom,
		DOMElement $container,
		int $level,
		string $bodyId,
		bool $collapsed
	): void {
		$parent = $container->parentNode;
		if ( !$parent ) {
			return;
		}

		$body = $dom->createElement( 'div' );
		$body->setAttribute( 'id', $bodyId );
		$body->setAttribute( 'class', 'whale-section-body' );
		if ( $collapsed ) {
			$body->setAttribute( 'hidden', '' );
		}

		$cursor = $container->nextSibling;
		while ( $cursor ) {
			if (
				$cursor instanceof DOMElement &&
				self::isHeadingBoundary( $cursor, $level )
			) {
				break;
			}

			$next = $cursor->nextSibling;
			$body->appendChild( $cursor );
			$cursor = $next;
		}

		$parent->insertBefore( $body, $cursor );
	}

	private static function createToggleButton(
		DOMDocument $dom,
		string $class,
		string $controls,
		bool $expanded,
		string $expandLabel,
		string $collapseLabel
	): DOMElement {
		$button = $dom->createElement( 'button' );
		$button->setAttribute( 'type', 'button' );
		$button->setAttribute( 'class', $class );
		$button->setAttribute( 'aria-controls', $controls );
		$button->setAttribute( 'aria-expanded', $expanded ? 'true' : 'false' );
		$button->setAttribute( 'aria-label', $expanded ? $collapseLabel : $expandLabel );
		$button->setAttribute( 'data-expand-label', $expandLabel );
		$button->setAttribute( 'data-collapse-label', $collapseLabel );

		return $button;
	}

	private static function isNavigationHeading( DOMElement $heading ): bool {
		if ( $heading->getAttribute( 'id' ) === 'mw-toc-heading' ) {
			return true;
		}

		$node = $heading->parentNode;
		while ( $node instanceof DOMElement ) {
			foreach ( [ 'toc', 'navbox', 'metadata', 'mw-editsection' ] as $class ) {
				if ( self::hasClass( $node, $class ) ) {
					return true;
				}
			}
			$node = $node->parentNode;
		}

		return false;
	}

	private static function getHeadingLevel( DOMElement $heading ): ?int {
		if ( preg_match( '/^h([1-6])$/i', $heading->tagName, $matches ) ) {
			return (int)$matches[1];
		}

		return null;
	}

	private static function getHeadingContainer( DOMElement $heading ): DOMElement {
		$parent = $heading->parentNode;
		if (
			$parent instanceof DOMElement &&
			self::hasClass( $parent, 'mw-heading' )
		) {
			return $parent;
		}

		return $heading;
	}

	private static function getHeadingLabelNode( DOMElement $heading ): DOMElement {
		foreach ( $heading->getElementsByTagName( '*' ) as $node ) {
			if ( $node instanceof DOMElement && self::hasClass( $node, 'mw-headline' ) ) {
				return $node;
			}
		}

		return $heading;
	}

	private static function isHeadingBoundary( DOMElement $element, int $currentLevel ): bool {
		$heading = null;
		if ( preg_match( '/^h[1-6]$/i', $element->tagName ) ) {
			$heading = $element;
		} elseif ( self::hasClass( $element, 'mw-heading' ) ) {
			foreach ( $element->childNodes as $child ) {
				if (
					$child instanceof DOMElement &&
					preg_match( '/^h[1-6]$/i', $child->tagName )
				) {
					$heading = $child;
					break;
				}
			}
		}

		if ( !$heading ) {
			return false;
		}

		$level = self::getHeadingLevel( $heading );
		return $level !== null && $level <= $currentLevel;
	}

	private static function firstElementByClass( DOMElement $root, string $class ): ?DOMElement {
		foreach ( $root->getElementsByTagName( '*' ) as $node ) {
			if ( $node instanceof DOMElement && self::hasClass( $node, $class ) ) {
				return $node;
			}
		}

		return null;
	}

	private static function withHtmlFragment( string $html, callable $callback ): string {
		if ( $html === '' || !class_exists( DOMDocument::class ) ) {
			return $html;
		}

		$dom = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$loaded = $dom->loadHTML(
			'<?xml encoding="utf-8" ?><div id="whale-fragment-root">' . $html . '</div>',
			LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( !$loaded ) {
			return $html;
		}

		$root = $dom->getElementById( 'whale-fragment-root' );
		if ( !$root instanceof DOMElement ) {
			return $html;
		}

		$callback( $dom, $root );

		$result = '';
		foreach ( $root->childNodes as $child ) {
			$result .= $dom->saveHTML( $child );
		}

		return $result;
	}

	private static function replaceNodeText( DOMElement $node, string $text ): void {
		while ( $node->firstChild ) {
			$node->removeChild( $node->firstChild );
		}

		$node->appendChild( $node->ownerDocument->createTextNode( $text ) );
	}

	private static function hasClass( DOMElement $element, string $class ): bool {
		return preg_match(
			'/(^|\s)' . preg_quote( $class, '/' ) . '(\s|$)/',
			$element->getAttribute( 'class' )
		) === 1;
	}

	private static function addClass( DOMElement $element, string $class ): void {
		if ( self::hasClass( $element, $class ) ) {
			return;
		}

		$current = trim( $element->getAttribute( 'class' ) );
		$element->setAttribute( 'class', trim( $current . ' ' . $class ) );
	}

	private static function removeClass( DOMElement $element, string $class ): void {
		$classes = preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) );
		if ( $classes === false ) {
			return;
		}

		$classes = array_filter( $classes, static function ( string $item ) use ( $class ) {
			return $item !== '' && $item !== $class;
		} );
		$element->setAttribute( 'class', implode( ' ', $classes ) );
	}

	private static function getAnonDarkMode( Skin $sk ): ?string {
		$mode = $sk->getRequest()->getCookie( 'whale-dark-mode' );
		return in_array( $mode, [ 'dark', 'light' ], true ) ? $mode : null;
	}
}
