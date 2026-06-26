<?php

//phpcs:ignore
class WhaleArticleDecorator {
	public const SECTION_COLLAPSE_MARKED = 'marked';
	public const SECTION_COLLAPSE_ALL = 'all';
	public const SECTION_COLLAPSE_OFF = 'off';
	public const FOLDING_MODE_DEFAULT = 'default';
	public const FOLDING_MODE_OPEN = 'open';
	public const FOLDING_MODE_OFF = 'off';

	public static function normalizeSectionMode( mixed $sectionMode ): string {
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
			$links = $xpath->query( './/a', $root );
			if ( $links === false ) {
				return;
			}
			foreach ( $links as $link ) {
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

	public static function convertFoldingSyntax( string $text ): string {
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

	public static function decorateArticleHtml(
		string $html,
		string $sectionMode,
		string $foldingMode,
		bool $headingAnchorsEnabled = true
	): string {
		if (
			$html === '' ||
			(
				$sectionMode === self::SECTION_COLLAPSE_OFF &&
				strpos( $html, 'whale-folding' ) === false &&
				!preg_match( '/<h[1-6]\b/i', $html )
			)
		) {
			return $html;
		}

		return self::withHtmlFragment( $html, function ( DOMDocument $dom, DOMElement $root ) use (
			$sectionMode,
			$foldingMode,
			$headingAnchorsEnabled
		) {
			if ( $sectionMode !== self::SECTION_COLLAPSE_OFF ) {
				self::decorateSectionHeadings( $dom, $root, $sectionMode );
			}

			self::decorateHeadingNumbers( $dom, $root );
			if ( $headingAnchorsEnabled ) {
				self::decorateHeadingAnchors( $dom, $root );
			}
			self::decorateFoldingBlocks( $dom, $root, $foldingMode );
		} );
	}

	private static function decorateHeadingAnchors( DOMDocument $dom, DOMElement $root ): void {
		foreach ( self::getDecoratableHeadings( $root ) as $heading ) {
			$id = $heading->getAttribute( 'id' );
			$label = self::getHeadingLabelNode( $heading );
			if ( $id === '' ) {
				$id = $label->getAttribute( 'id' );
			}

			if ( $id === '' || self::firstElementByClass( $heading, 'whale-heading-anchor' ) ) {
				continue;
			}

			$button = $dom->createElement( 'button', '#' );
			$button->setAttribute( 'type', 'button' );
			$button->setAttribute( 'class', 'whale-heading-anchor' );
			$button->setAttribute( 'aria-label', wfMessage( 'whale-heading-link-copy' )->text() );
			$button->setAttribute( 'title', wfMessage( 'whale-heading-link-copy' )->text() );
			$button->setAttribute( 'data-heading', self::getHeadingText( $label ) );
			$heading->appendChild( $button );
		}
	}

	private static function decorateHeadingNumbers( DOMDocument $dom, DOMElement $root ): void {
		$headings = self::getDecoratableHeadings( $root );
		if ( count( $headings ) === 0 ) {
			return;
		}

		$baseLevel = min( array_map( static function ( DOMElement $heading ): int {
			return self::getHeadingLevel( $heading ) ?? 2;
		}, $headings ) );
		$counters = [];

		foreach ( $headings as $heading ) {
			$level = ( self::getHeadingLevel( $heading ) ?? $baseLevel ) - $baseLevel + 1;
			$level = max( 1, min( 6, $level ) );
			$counters[$level - 1] = ( $counters[$level - 1] ?? 0 ) + 1;
			$counters = array_slice( $counters, 0, $level );
			$number = self::formatHeadingNumber( implode( '.', array_map( 'strval', $counters ) ) );
			$label = self::getHeadingLabelNode( $heading );

			if ( self::firstElementByClass( $label, 'whale-heading-number' ) ) {
				continue;
			}

			$numberNode = $dom->createElement( 'span', $number . ' ' );
			$numberNode->setAttribute( 'class', 'whale-heading-number' );
			$numberNode->setAttribute( 'aria-hidden', 'true' );
			$label->insertBefore( $numberNode, $label->firstChild );
		}
	}

	private static function formatHeadingNumber( string $number ): string {
		$number = rtrim( trim( $number ), '.' );
		if ( $number === '' ) {
			return '';
		}

		return strpos( $number, '.' ) === false ? $number . '.' : $number;
	}

	private static function decorateSectionHeadings(
		DOMDocument $dom,
		DOMElement $root,
		string $sectionMode
	): void {
		$headings = self::getDecoratableHeadings( $root );

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
		$foldingsResult = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " whale-folding ")]', $root );
		if ( $foldingsResult === false ) {
			return;
		}

		foreach ( $foldingsResult as $folding ) {
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
				$toggleParent = $toggle->parentNode;
				if ( $toggleParent === null ) {
					continue;
				}
				$toggleParent->replaceChild( $button, $toggle );
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

	/**
	 * @return array<int,DOMElement>
	 */
	private static function getDecoratableHeadings( DOMElement $root ): array {
		$document = $root->ownerDocument;
		if ( !$document instanceof DOMDocument ) {
			return [];
		}

		$xpath = new DOMXPath( $document );
		$headings = [];
		$headingsResult = $xpath->query( './/h1|.//h2|.//h3|.//h4|.//h5|.//h6', $root );
		if ( $headingsResult === false ) {
			return $headings;
		}

		foreach ( $headingsResult as $heading ) {
			if (
				$heading instanceof DOMElement &&
				!self::isNavigationHeading( $heading ) &&
				self::getHeadingLevel( $heading ) !== null
			) {
				$headings[] = $heading;
			}
		}

		return $headings;
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
			if ( self::hasClass( $node, 'mw-headline' ) ) {
				return $node;
			}
		}

		return $heading;
	}

	private static function getHeadingText( DOMElement $heading ): string {
		$clone = $heading->cloneNode( true );
		if ( !$clone instanceof DOMElement ) {
			return trim( $heading->textContent );
		}

		foreach ( [ 'whale-heading-anchor', 'whale-heading-number', 'mw-editsection' ] as $class ) {
			while ( true ) {
				$node = self::firstElementByClass( $clone, $class );
				if ( !$node || !$node->parentNode ) {
					break;
				}
				$node->parentNode->removeChild( $node );
			}
		}

		return trim( preg_replace( '/\s+/', ' ', $clone->textContent ) ?? $clone->textContent );
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
			if ( self::hasClass( $node, $class ) ) {
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

		$doc = $node->ownerDocument;
		if ( $doc === null ) {
			return;
		}
		$node->appendChild( $doc->createTextNode( $text ) );
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
}
