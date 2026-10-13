<?php

/** Semantic homepage presentation, including the original two-column portal. */
class WhaleMainPage {
	public static function decorate( DOMElement $root, string $welcome ): void {
		$document = $root->ownerDocument;
		if ( !$document instanceof DOMDocument ) {
			return;
		}
		$xpath = new DOMXPath( $document );
		$parserNodes = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " mw-parser-output ")]', $root );
		$parser = $parserNodes !== false ? $parserNodes->item( 0 ) : null;
		$parser = $parser instanceof DOMElement ? $parser : $root;
		$homeNodes = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " whale-home ")]', $root );
		$home = $homeNodes !== false ? $homeNodes->item( 0 ) : null;
		if ( $home instanceof DOMElement ) {
			$parser = $home;
		}
		if ( !self::hasClass( $parser, 'whale-home' ) && !self::migrateLegacyLayout( $parser ) ) {
			return;
		}
		$titles = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " whale-home-title ")]', $parser );
		if ( $welcome !== '' && $titles !== false ) {
			foreach ( $titles as $title ) {
				if ( $title instanceof DOMElement ) {
					$title->textContent = $welcome;
				}
			}
		}
	}

	/** Match the documented portal structure, never its authored color values. */
	private static function migrateLegacyLayout( DOMElement $parser ): bool {
		$blocks = array_values( array_filter( self::children( $parser ), static fn ( DOMElement $node ): bool => $node->tagName === 'div' ) );
		if ( count( $blocks ) !== 2 ) {
			return false;
		}
		[ $intro, $grid ] = $blocks;
		$introChildren = self::children( $intro );
		$columns = self::children( $grid );
		if ( count( $introChildren ) !== 1 || $introChildren[0]->tagName !== 'p' || count( $columns ) !== 2 ) {
			return false;
		}
		$spans = array_values( array_filter( self::children( $introChildren[0] ), static fn ( DOMElement $node ): bool => $node->tagName === 'span' ) );
		if ( count( $spans ) !== 2 ) {
			return false;
		}
		$panels = [];
		foreach ( $columns as $column ) {
			$cards = self::children( $column );
			if ( $column->tagName !== 'div' || count( $cards ) !== 2 ) {
				return false;
			}
			foreach ( $cards as $card ) {
				if ( $card->tagName !== 'div' || !self::heading( $card ) ) {
					return false;
				}
				$panels[] = $card;
			}
		}
		self::mark( $parser, 'whale-home', false );
		self::mark( $intro, 'whale-home-intro' );
		self::mark( $introChildren[0], 'whale-home-intro-copy' );
		self::mark( $spans[0], 'whale-home-title' );
		self::mark( $spans[1], 'whale-home-description' );
		self::mark( $grid, 'whale-home-sections' );
		foreach ( $panels as $panel ) {
			self::mark( $panel, 'whale-home-section' );
			$heading = self::heading( $panel );
			if ( !$heading ) {
				continue;
			}
			$heading->removeAttribute( 'style' );
			$labels = $heading->getElementsByTagName( 'span' );
			$label = $heading;
			foreach ( $labels as $span ) {
				if ( self::hasClass( $span, 'mw-headline' ) ) {
					$label = $span;
					break;
				}
			}
			// Keep the existing heading ID so bookmarked links remain valid.
			foreach ( $label->childNodes as $node ) {
				if ( $node instanceof DOMText ) {
					$node->nodeValue = preg_replace( '/^[\s\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]+/u', '', $node->nodeValue ?? '' ) ?? $node->nodeValue;
					break;
				}
			}
			$grid->appendChild( $panel );
		}
		foreach ( $columns as $column ) {
			$grid->removeChild( $column );
		}
		foreach ( self::children( $parser ) as $child ) {
			if ( $child->tagName === 'p' && trim( $child->textContent ) === '' ) {
				$parser->removeChild( $child );
			}
		}
		return true;
	}

	private static function heading( DOMElement $panel ): ?DOMElement {
		$first = self::children( $panel )[0] ?? null;
		if ( $first?->tagName === 'h2' ) {
			return $first;
		}
		if ( $first && self::hasClass( $first, 'mw-heading' ) ) {
			$heading = $first->getElementsByTagName( 'h2' )->item( 0 );
			return $heading instanceof DOMElement ? $heading : null;
		}
		return null;
	}

	/** @return list<DOMElement> */
	private static function children( DOMNode $node ): array {
		$elements = [];
		foreach ( $node->childNodes as $child ) {
			if ( $child instanceof DOMElement ) {
				$elements[] = $child;
			}
		}
		return $elements;
	}

	private static function hasClass( DOMElement $element, string $class ): bool {
		return in_array( $class, preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) ) ?: [], true );
	}

	private static function mark( DOMElement $element, string $class, bool $removeStyle = true ): void {
		if ( !self::hasClass( $element, $class ) ) {
			$element->setAttribute( 'class', trim( $element->getAttribute( 'class' ) . ' ' . $class ) );
		}
		if ( $removeStyle ) {
			$element->removeAttribute( 'style' );
		}
	}
}
