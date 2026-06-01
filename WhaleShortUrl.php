<?php

class WhaleShortUrl {
	private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

	public static function encode( int $value ): string {
		if ( $value <= 0 ) {
			return '';
		}

		$output = '';
		$base = strlen( self::ALPHABET );
		while ( $value > 0 ) {
			$output = self::ALPHABET[$value % $base] . $output;
			$value = intdiv( $value, $base );
		}

		return $output;
	}

	public static function decode( string $code ): ?int {
		if ( !preg_match( '/^[0-9A-Za-z]+$/', $code ) ) {
			return null;
		}

		$value = 0;
		$base = strlen( self::ALPHABET );
		$length = strlen( $code );
		for ( $i = 0; $i < $length; $i++ ) {
			$position = strpos( self::ALPHABET, $code[$i] );
			if ( $position === false ) {
				return null;
			}
			$value = $value * $base + $position;
		}

		return $value > 0 ? $value : null;
	}

	public static function buildUrl( string $code ): string {
		global $wgServer, $wgWhaleShortUrlPathPrefix;

		$prefix = is_string( $wgWhaleShortUrlPathPrefix ?? null )
			? '/' . trim( $wgWhaleShortUrlPathPrefix, '/' )
			: '/s';
		$server = is_string( $wgServer ?? null ) ? rtrim( $wgServer, '/' ) : '';

		return $server . $prefix . '/' . rawurlencode( $code );
	}
}
