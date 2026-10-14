<?php

/** Validate compact cells without changing authored table contents. */
require_once __DIR__ . '/../WhaleArticleDecorator.php';

$input = '<table class="wikitable"><tr><td id="empty" style="background:green">' . " \n\t" .
	'</td></tr><tr><td id="space">&nbsp;<!-- separator --></td></tr>' .
	'<tr><td id="sized" style="height:30px;padding:8px"></td></tr>' .
	'<tr><th id="label">제목</th><td id="image"><img src="example.png"></td></tr>' .
	'<tr><td id="break"><br></td><td id="nested"><table><tr><td>값</td></tr></table></td></tr></table>';
$output = WhaleArticleDecorator::decorateArticleHtml( $input, 'off', 'default', false );
$dom = new DOMDocument();
$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $output );
foreach ( [ 'empty', 'space', 'sized' ] as $id ) {
	if ( !str_contains( $dom->getElementById( $id )?->getAttribute( 'class' ) ?? '', 'whale-empty-cell' ) ) {
		throw new RuntimeException( "Whitespace-only cell $id was not compacted." );
	}
}
foreach ( [ 'label', 'image', 'break', 'nested' ] as $id ) {
	if ( str_contains( $dom->getElementById( $id )?->getAttribute( 'class' ) ?? '', 'whale-empty-cell' ) ) {
		throw new RuntimeException( "Meaningful cell $id must retain its normal height." );
	}
}
if ( $dom->getElementById( 'empty' )?->getAttribute( 'style' ) !== 'background:green' ||
	$dom->getElementById( 'sized' )?->getAttribute( 'style' ) !== 'height:30px;padding:8px'
) {
	throw new RuntimeException( 'Authored colors, heights and padding must be preserved.' );
}
if ( WhaleArticleDecorator::decorateArticleHtml( $output, 'off', 'default', false ) !== $output ) {
	throw new RuntimeException( 'Repeated decoration must be idempotent.' );
}
