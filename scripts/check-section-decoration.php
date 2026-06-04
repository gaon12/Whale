<?php

/**
 * Validate section folding decoration without booting MediaWiki.
 *
 * @license GPL-3.0-or-later
 */

if ( !function_exists( 'wfMessage' ) ) {
	function wfMessage( string $key ): object {
		return new class( $key ) {
			public function __construct( private string $key ) {
			}

			public function text(): string {
				$messages = [
					'whale-section-expand' => '문단 펼치기',
					'whale-section-collapse' => '문단 접기',
				];

				return $messages[$this->key] ?? $this->key;
			}
		};
	}
}

require_once __DIR__ . '/../WhaleHooks.php';

$method = new ReflectionMethod( WhaleHooks::class, 'decorateArticleHtml' );
$method->setAccessible( true );

$html = implode( '', [
	'<h2><span class="mw-headline" id="parent"># Parent #</span></h2>',
	'<p>Parent text</p>',
	'<h3><span class="mw-headline" id="child">Child</span></h3>',
	'<p>Child text</p>',
	'<h2><span class="mw-headline" id="empty">Empty</span></h2>',
] );
$output = $method->invoke( null, $html, 'all', 'default' );

$dom = new DOMDocument();
$previous = libxml_use_internal_errors( true );
$loaded = $dom->loadHTML(
	'<?xml encoding="utf-8" ?><div id="root">' . $output . '</div>',
	LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
);
libxml_clear_errors();
libxml_use_internal_errors( $previous );

if ( !$loaded ) {
	fwrite( STDERR, "Decorated section HTML could not be parsed.\n" );
	exit( 1 );
}

$xpath = new DOMXPath( $dom );

$parentHeading = $xpath->query( '//*[@id="parent"]/parent::h2' )->item( 0 );
if ( !$parentHeading instanceof DOMElement ) {
	fwrite( STDERR, "Parent heading was not preserved.\n" );
	exit( 1 );
}

if ( trim( $xpath->query( '//*[@id="parent"]' )->item( 0 )?->textContent ?? '' ) !== 'Parent' ) {
	fwrite( STDERR, "Marked section title should remove surrounding # markers.\n" );
	exit( 1 );
}

$parentToggle = $xpath->query( './/button[contains(@class, "whale-section-toggle")]', $parentHeading )->item( 0 );
if ( !$parentToggle instanceof DOMElement ) {
	fwrite( STDERR, "Parent section toggle was not added.\n" );
	exit( 1 );
}

$parentBodyId = $parentToggle->getAttribute( 'aria-controls' );
$parentBody = $dom->getElementById( $parentBodyId );
if (
	!$parentBody instanceof DOMElement ||
	!$parentBody->hasAttribute( 'hidden' ) ||
	strpos( $parentBody->textContent, 'Child text' ) === false
) {
	fwrite( STDERR, "Collapsed parent body should exist, be hidden, and contain child sections.\n" );
	exit( 1 );
}

$emptyHeading = $xpath->query( '//*[@id="empty"]/parent::h2' )->item( 0 );
if ( !$emptyHeading instanceof DOMElement ) {
	fwrite( STDERR, "Empty heading was not preserved.\n" );
	exit( 1 );
}

$emptyToggle = $xpath->query( './/button[contains(@class, "whale-section-toggle")]', $emptyHeading )->item( 0 );
if ( !$emptyToggle instanceof DOMElement ) {
	fwrite( STDERR, "Empty section toggle was not added.\n" );
	exit( 1 );
}

$emptyBody = $dom->getElementById( $emptyToggle->getAttribute( 'aria-controls' ) );
if ( !$emptyBody instanceof DOMElement ) {
	fwrite( STDERR, "Every section toggle should control an existing section body.\n" );
	exit( 1 );
}
