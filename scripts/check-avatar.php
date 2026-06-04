<?php

/**
 * Validate server-side DiceBear avatar generation without booting MediaWiki.
 *
 * @license GPL-3.0-or-later
 */

require_once __DIR__ . '/../WhaleAvatar.php';

$avatar = WhaleAvatar::createDataUri( 'whale-test-user', 'identicon' );
if ( !is_string( $avatar ) || !str_starts_with( $avatar, 'data:image/svg+xml;charset=utf-8,' ) ) {
	fwrite( STDERR, "DiceBear avatar was not rendered as an SVG data URI.\n" );
	exit( 1 );
}

if ( !str_contains( rawurldecode( $avatar ), '<svg' ) ) {
	fwrite( STDERR, "DiceBear avatar data URI does not contain SVG markup.\n" );
	exit( 1 );
}

$customAvatar = WhaleAvatar::createDataUri( 'whale-test-user', 'identicon', [
	'backgroundColor' => [ 'f8fafc' ],
	'borderRadius' => 12,
	'size' => 32,
] );
if ( !is_string( $customAvatar ) || !str_contains( rawurldecode( $customAvatar ), 'width="32"' ) ) {
	fwrite( STDERR, "DiceBear avatar options from LocalSettings-style config were not applied.\n" );
	exit( 1 );
}

$fallbackAvatar = WhaleAvatar::createDataUri( 'whale-test-user', '../../bad-style' );
if ( !is_string( $fallbackAvatar ) || !str_contains( rawurldecode( $fallbackAvatar ), '<svg' ) ) {
	fwrite( STDERR, "DiceBear avatar did not fall back to the default style.\n" );
	exit( 1 );
}
