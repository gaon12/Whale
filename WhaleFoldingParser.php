<?php

/** Convert folding syntax to readable, skin-independent parser output. */
class WhaleFoldingParser {
	public static function convert( string $text ): string {
		$protected = [];
		$prefix = 'WHALE_LITERAL_';
		while ( str_contains( $text, $prefix ) ) {
			$prefix .= '_';
		}
		$masked = preg_replace_callback(
			'/<!--.*?(?:-->|\z)|<(nowiki|pre|source|syntaxhighlight|math)\b(?![^>]*\/\s*>)[^>]*>.*?(?:<\/\1\s*>|\z)/is',
			static function ( array $match ) use ( &$protected, $prefix ): string {
				$key = $prefix . count( $protected ) . '_END';
				$protected[$key] = $match[0];
				return $key;
			},
			$text
		);
		if ( $masked === null ) {
			return $text;
		}

		$lines = preg_split( '/(\r\n|\n|\r)/', $masked, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( $lines === false ) {
			return $text;
		}
		$output = [];
		$depth = 0;
		$converted = false;
		foreach ( $lines as $line ) {
			if ( preg_match( '/^\{\{\{#!folding(?:[ \t]+(.*))?$/u', $line, $matches ) ) {
				$title = trim( $matches[1] ?? '' );
				if ( $title === '' ) {
					$title = wfMessage( 'whale-folding-default-title' )->text();
				}
				$title = strtr( $title, $protected );
				$output[] = '<div class="whale-folding">' . "\n" .
					'<div class="whale-folding-header"><span class="whale-folding-title">' .
					htmlspecialchars( $title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) .
					'</span></div>' . "\n" . '<div class="whale-folding-body">';
				$depth++;
				$converted = true;
			} elseif ( $depth > 0 && trim( $line ) === '}}}' ) {
				$output[] = '</div></div>';
				$depth--;
			} else {
				$output[] = $line;
			}
		}
		if ( !$converted ) {
			return $text;
		}
		while ( $depth-- > 0 ) {
			$output[] = "\n" . '</div></div>';
		}
		return strtr( implode( '', $output ), $protected );
	}
}
