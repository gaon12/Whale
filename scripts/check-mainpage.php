<?php

/** Exercise the semantic contract and the legacy portal using the real DOM. */
require_once __DIR__ . '/../WhaleMainPage.php';
require_once __DIR__ . '/../WhaleArticleDecorator.php';

$fixture = file_get_contents( __DIR__ . '/../tests/fixtures/mainpage-legacy.html' );
if ( $fixture === false ) {
	throw new RuntimeException( 'Missing legacy main-page fixture.' );
}
$welcome = '가온 위키에 오신 것을 환영합니다';
$html = WhaleArticleDecorator::decorateArticleHtml( $fixture, 'all', 'default', true, true, $welcome );
if ( in_array( '--render', $argv, true ) ) {
	echo $html;
	exit;
}
foreach ( [ 'whale-home', 'whale-home-intro', 'whale-home-title', 'whale-home-section', $welcome ] as $expected ) {
	if ( !str_contains( $html, $expected ) ) {
		throw new RuntimeException( "Missing semantic homepage content: $expected" );
	}
}
foreach ( [ '베타에', 'whale-section-toggle', 'whale-heading-number', '📢' ] as $unexpected ) {
	if ( str_contains( $html, $unexpected ) ) {
		throw new RuntimeException( "Legacy presentation remains: $unexpected" );
	}
}
foreach ( [ '공지사항', '주요_분류', '소개', '시작하기' ] as $id ) {
	if ( !str_contains( $html, 'id="' . $id . '"' ) ) {
		throw new RuntimeException( 'Migrating presentation must preserve bookmarked heading IDs.' );
	}
}
if ( substr_count( $html, 'class="whale-home-section"' ) !== 4 || substr_count( $html, '<a ' ) !== substr_count( $fixture, '<a ' ) ) {
	throw new RuntimeException( 'All portal sections and links must survive migration.' );
}
$document = new DOMDocument();
$document->loadHTML( '<?xml encoding="UTF-8">' . $html );
$xpath = new DOMXPath( $document );
$columns = $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " whale-home-column ")]' );
if ( $columns === false || $columns->length !== 2 ) {
	throw new RuntimeException( 'Migrating the homepage must retain both authored columns.' );
}
foreach ( $columns as $index => $column ) {
	$ids = $xpath->query( './/span[@id]', $column );
	$expectedIds = $index === 0 ? [ '공지사항', '주요_분류' ] : [ '소개', '시작하기' ];
	if ( !$column instanceof DOMElement || $column->getAttribute( 'style' ) !== 'flex:1' || $ids === false ) {
		throw new RuntimeException( 'Authored column layout styles must survive decoration.' );
	}
	$actualIds = [];
	foreach ( $ids as $label ) {
		if ( $label instanceof DOMElement ) {
			$actualIds[] = $label->getAttribute( 'id' );
		}
	}
	if ( $actualIds !== $expectedIds ) {
		throw new RuntimeException( 'Sections must stay in their original left or right column.' );
	}
}
$again = WhaleArticleDecorator::decorateArticleHtml( $html, 'all', 'default', true, true, $welcome );
if ( $html !== $again ) {
	throw new RuntimeException( 'Homepage decoration must be idempotent.' );
}
$semantic = '<div class="whale-home"><div class="whale-home-intro"><span class="whale-home-title">Old branding</span></div>' .
	'<div class="whale-home-section"><h2>Notice</h2><div style="color:red">Authored warning</div></div></div>';
$output = WhaleArticleDecorator::decorateArticleHtml( $semantic, 'all', 'default', true, true, $welcome );
if ( !str_contains( $output, $welcome ) || !str_contains( $output, 'style="color:red"' ) ) {
	throw new RuntimeException( 'Semantic templates must use site branding while preserving authored warning colors.' );
}
