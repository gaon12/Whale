<?php

/** Validate presentation delegation and structured optional personal tools. */
require_once __DIR__ . '/../tests/phpstan/mediawiki-stubs.php';
$manifest = json_decode( file_get_contents( __DIR__ . '/../skin.json' ), true, 512, JSON_THROW_ON_ERROR );
spl_autoload_register( static function ( string $name ) use ( $manifest ): void {
	if ( isset( $manifest['AutoloadClasses'][$name] ) ) {
		require_once __DIR__ . '/../' . $manifest['AutoloadClasses'][$name];
	}
} );

$skin = ( new ReflectionClass( SkinWhale::class ) )->newInstanceWithoutConstructor();
$renderer = new WhaleRenderer( $skin );
$account = new WhaleAccountRenderer( $skin );
if ( $renderer->getLoginData() !== $account->getLoginData() ||
	$renderer->getLoginModalData() !== $account->getLoginModalData()
) {
	throw new RuntimeException( 'The renderer must delegate account presentation without changing its contract.' );
}
foreach ( [ null, [], [ 'links' => [] ], [ 'links' => [ 'invalid' ] ] ] as $tool ) {
	if ( WhalePersonalTools::getFirstLinkField( $tool, 'href' ) !== null ||
		WhalePersonalTools::getFirstLinkCounter( $tool ) !== 0
	) {
		throw new RuntimeException( 'Malformed optional personal tools must not invent fields or counters.' );
	}
}
$tool = [ 'links' => [ [ 'href' => '/logout', 'data' => [ 'counter-num' => '4' ] ] ] ];
if ( WhalePersonalTools::getFirstLinkField( $tool, 'href' ) !== '/logout' ||
	WhalePersonalTools::getFirstLinkCounter( $tool ) !== 4
) {
	throw new RuntimeException( 'Structured core personal tools must retain their link and notification count.' );
}
if ( WhaleIcon::render( 'invalid"><script>' ) !== '' || WhaleIcon::render( 'unknown-icon' ) !== '' ) {
	throw new RuntimeException( 'Only names from the icon allowlist may render SVG.' );
}
