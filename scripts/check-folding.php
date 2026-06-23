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

require_once __DIR__ . '/../WhaleArticleDecorator.php';

$method = new ReflectionMethod( WhaleArticleDecorator::class, 'convertFoldingSyntax' );
$method->setAccessible( true );

$input = "{{{#!folding 테스트 제목\n접힌 내용\n}}}";
$output = $method->invoke( null, $input );

$escapedInput = "{{{#!folding <script>alert(1)</script>\n내용\n}}}";
$escapedOutput = $method->invoke( null, $escapedInput );

$expectedSnippets = [
	'<div class="whale-folding is-collapsed">',
	'<div class="whale-folding-header"><span class="whale-folding-title">테스트 제목</span></div>',
	'<button type="button" class="whale-folding-toggle" aria-expanded="false">펼치기 · 접기</button>',
	'<div class="whale-folding-body" hidden="">',
	'접힌 내용',
	'</div></div>',
];

foreach ( $expectedSnippets as $snippet ) {
	if ( !str_contains( $output, $snippet ) ) {
		fwrite( STDERR, "Missing folding output snippet: $snippet\n" );
		exit( 1 );
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
