<?php

/**
 * Validate the folding block preprocessor output without booting MediaWiki.
 *
 * @license GPL-3.0-or-later
 */

if ( !function_exists( 'wfMessage' ) ) {
	function wfMessage( string $key ) {
		return new class( $key ) {
			private string $key;

			public function __construct( string $key ) {
				$this->key = $key;
			}

			public function text(): string {
				$messages = [
					'whale-folding-default-title' => '접기',
					'whale-folding-toggle-label' => '펼치기 · 접기',
				];

				return $messages[$this->key] ?? $this->key;
			}
		};
	}
}

require_once __DIR__ . '/../WhaleFoldingParser.php';
require_once __DIR__ . '/../WhaleArticleDecorator.php';
require_once __DIR__ . '/../WhaleHooks.php';

$manifest = json_decode( file_get_contents( __DIR__ . '/../skin.json' ), true, 512, JSON_THROW_ON_ERROR );
if ( ( $manifest['Hooks']['ParserBeforeInternalParse'] ?? null ) !== 'WhaleHooks::onParserBeforeInternalParse' ||
	isset( $manifest['Hooks']['ParserBeforePreprocess'] )
) {
	throw new RuntimeException( 'Folding must be registered on the article parsing hook, not preprocess-only expansion.' );
}
$wgWhaleEnableFoldingBlocks = true;
$hookText = "{{{#!folding Hook\nReadable\n}}}";
WhaleHooks::onParserBeforeInternalParse( null, $hookText, null );
if ( !str_contains( $hookText, 'class="whale-folding"' ) ) {
	throw new RuntimeException( 'The registered article parsing hook must convert folding syntax.' );
}

$method = new ReflectionMethod( WhaleArticleDecorator::class, 'convertFoldingSyntax' );
$method->setAccessible( true );

$input = "{{{#!folding 테스트 제목\n접힌 내용\n}}}";
$output = $method->invoke( null, $input );

$escapedInput = "{{{#!folding <script>alert(1)</script>\n내용\n}}}";
$escapedOutput = $method->invoke( null, $escapedInput );

$expectedSnippets = [
	'<div class="whale-folding">',
	'<div class="whale-folding-header"><span class="whale-folding-title">테스트 제목</span></div>',
	'<div class="whale-folding-body">',
	'접힌 내용',
	'</div></div>',
];

foreach ( $expectedSnippets as $snippet ) {
	if ( !str_contains( $output, $snippet ) ) {
		fwrite( STDERR, "Missing folding output snippet: $snippet\n" );
		exit( 1 );
	}
}

if ( str_contains( $output, 'hidden=' ) || str_contains( $output, '<button' ) ) {
	throw new RuntimeException( 'Shared parser output must stay readable without Whale scripts.' );
}

foreach ( [ 'nowiki', 'pre', 'source', 'syntaxhighlight', 'math' ] as $tag ) {
	$literal = "<$tag>\r\n{{{#!folding Example\r\nbody\r\n}}}\r\n</$tag>";
	if ( WhaleFoldingParser::convert( $literal ) !== $literal ) {
		throw new RuntimeException( "Folding syntax inside $tag must remain literal." );
	}
	$mixed = "{{{#!folding Real\n" . $literal . "\n}}}";
	$converted = WhaleFoldingParser::convert( $mixed );
	if ( substr_count( $converted, 'class="whale-folding"' ) !== 1 || !str_contains( $converted, $literal ) ) {
		throw new RuntimeException( 'Literal blocks must not open or close surrounding folding blocks.' );
	}
}
$comment = "<!--\n{{{#!folding Example\nbody\n}}}\n-->";
if ( WhaleFoldingParser::convert( $comment ) !== $comment ) {
	throw new RuntimeException( 'Folding examples in comments must remain unchanged.' );
}
foreach ( [ 'default' => false, 'open' => true, 'off' => true ] as $mode => $expanded ) {
	$decorated = WhaleArticleDecorator::decorateArticleHtml( $output, 'off', $mode );
	if ( !str_contains( $decorated, 'aria-expanded="' . ( $expanded ? 'true' : 'false' ) . '"' ) ||
		str_contains( $decorated, 'hidden=' ) === $expanded
	) {
		throw new RuntimeException( "Whale decoration must honor the $mode folding preference." );
	}
}
$legacy = '<div class="whale-folding is-collapsed"><button class="whale-folding-toggle">Toggle</button>' .
	'<div class="whale-folding-body" hidden="">Cached content</div></div>';
$readable = WhaleArticleDecorator::makeFoldingBlocksReadable( $legacy );
if ( str_contains( $readable, 'hidden=' ) || str_contains( $readable, '<button' ) ||
	!str_contains( $readable, 'Cached content' )
) {
	throw new RuntimeException( 'Other skins must be able to read older cached folding content.' );
}

$nested = '<div class="whale-folding"><div class="whale-folding-header">Outer</div>' .
	'<div class="whale-folding-body"><div class="whale-folding">' .
	'<div class="whale-folding-header">Inner<button class="whale-folding-toggle">Toggle</button></div>' .
	'<div class="whale-folding-body">Inner body</div></div></div></div>';
$decorated = WhaleArticleDecorator::decorateArticleHtml( $nested, 'off', 'default', false );
$dom = new DOMDocument();
$dom->loadHTML( $decorated );
$xpath = new DOMXPath( $dom );
$toggles = $xpath->query( '//button[@class="whale-folding-toggle"]' );
if ( $toggles === false || $toggles->length !== 2 ) {
	throw new RuntimeException( 'Nested folding blocks must retain independent controls.' );
}
foreach ( $toggles as $toggle ) {
	if ( !$toggle instanceof DOMElement ||
		!$toggle->nextSibling instanceof DOMElement ||
		$toggle->nextSibling->getAttribute( 'id' ) !== $toggle->getAttribute( 'aria-controls' )
	) {
		throw new RuntimeException( 'Each folding control must sit below its title and directly before its own body.' );
	}
}

if ( str_contains( $escapedOutput, '<script>' ) ) {
	fwrite( STDERR, "Folding title was not escaped.\n" );
	exit( 1 );
}

if ( !str_contains( $escapedOutput, '&lt;script&gt;alert(1)&lt;/script&gt;' ) ) {
	fwrite( STDERR, "Escaped folding title was not preserved.\n" );
	exit( 1 );
}
