<?php // @codingStandardsIgnoreLine
if ( function_exists( 'wfLoadSkin' ) ) {
	wfLoadSkin( 'Whale' );
	$wgMessagesDirs['Whale'] = __DIR__ . '/i18n';
	return true;
} else {
	die( 'This version of the Whale skin requires MediaWiki 1.25+' );
}
