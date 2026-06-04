<?php

/**
 * Validate short URL base62 helpers without booting MediaWiki.
 *
 * @license GPL-3.0-or-later
 */

require_once __DIR__ . '/../WhaleShortUrl.php';

$roundTrips = [
	1,
	61,
	62,
	3843,
	PHP_INT_MAX,
];

foreach ( $roundTrips as $value ) {
	$code = WhaleShortUrl::encode( $value );
	$decoded = WhaleShortUrl::decode( $code );

	if ( $decoded !== $value ) {
		fwrite( STDERR, "Short URL round trip failed for $value via $code.\n" );
		exit( 1 );
	}
}

$invalidCodes = [
	'',
	'0',
	'abc!',
	str_repeat( 'z', 12 ),
	'zzzzzzzzzzz',
];

foreach ( $invalidCodes as $code ) {
	if ( WhaleShortUrl::decode( $code ) !== null ) {
		fwrite( STDERR, "Invalid short URL code was accepted: $code\n" );
		exit( 1 );
	}
}
