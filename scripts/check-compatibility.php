<?php

/** Validate the legacy footer boundary independently of core internals. */
require_once __DIR__ . '/../WhaleCompatibility.php';
$footer = WhaleCompatibility::normalizeLegacyFooter( [
	'info' => [ 'lastmod' => 'Revision time', 'copyright' => '<a>License</a>', 'empty' => null ],
	'places' => [ 'about' => '<a>About</a>', 'invalid' => [], 0 => 'Unnamed' ],
	'invalid' => false,
] );
if ( $footer !== [
	'data-info' => [ 'array-items' => [
		[ 'name' => 'lastmod', 'html' => 'Revision time' ],
		[ 'name' => 'copyright', 'html' => '<a>License</a>' ],
	] ],
	'data-places' => [ 'array-items' => [ [ 'name' => 'about', 'html' => '<a>About</a>' ] ] ],
] ) {
	throw new RuntimeException( 'Legacy footer conversion must preserve named HTML while excluding absent or malformed links.' );
}
