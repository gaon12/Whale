<?php

/**
 * Validate the simplified Whale-Navbar parser without booting MediaWiki.
 *
 * @license GPL-3.0-or-later
 */

require_once __DIR__ . '/../tests/phpstan/mediawiki-stubs.php';

class TestNavbarMessage extends Message {
	private string $key;

	public function __construct( string $key ) {
		$this->key = $key;
	}

	public function text(): string {
		return $this->key;
	}

	public function isDisabled(): bool {
		return true;
	}
}

if ( !class_exists( 'SkinWhale' ) ) {
	class SkinWhale extends Skin {
		public function msg( string $key, mixed ...$params ): Message {
			return new TestNavbarMessage( $key );
		}
	}
}

require_once __DIR__ . '/../WhaleRenderer.php';

$wgArticlePath = '/wiki/$1';

class TestNavbarRenderer extends WhaleRenderer {
	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function parseForTest( string $text ): array {
		return $this->parseNavbarContent( $text );
	}
}

$renderer = new TestNavbarRenderer( new SkinWhale() );
$simple = $renderer->parseForTest( <<<NAV
- text: Help
  link: Help:Contents
  icon: book
  title: Help pages
  class: primary pinned
  children:
    - Beginner -> Help:Beginner
      icon: link
      children:
        - text: Syntax
          link: Help:Syntax
NAV );

if ( count( $simple ) !== 1 ) {
	fwrite( STDERR, "Simple navbar should produce one top-level item.\n" );
	exit( 1 );
}

if (
	$simple[0]['text'] !== 'Help' ||
	$simple[0]['href'] !== '/wiki/Help:Contents' ||
	$simple[0]['icon'] !== 'book' ||
	$simple[0]['classes'] !== [ 'primary', 'pinned' ]
) {
	fwrite( STDERR, "Simple navbar top-level fields were not parsed correctly.\n" );
	exit( 1 );
}

if (
	$simple[0]['children'][0]['text'] !== 'Beginner' ||
	$simple[0]['children'][0]['href'] !== '/wiki/Help:Beginner' ||
	$simple[0]['children'][0]['children'][0]['href'] !== '/wiki/Help:Syntax'
) {
	fwrite( STDERR, "Simple navbar nested items were not parsed correctly.\n" );
	exit( 1 );
}

$legacy = $renderer->parseForTest( '* icon=sync | display=recentchanges | link=Special:RecentChanges' );
if (
	count( $legacy ) !== 1 ||
	$legacy[0]['text'] !== 'recentchanges' ||
	$legacy[0]['href'] !== '/wiki/Special:RecentChanges'
) {
	fwrite( STDERR, "Legacy navbar fallback was not preserved.\n" );
	exit( 1 );
}
