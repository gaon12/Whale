<?php // @codingStandardsIgnoreLine
if ( function_exists( 'wfLoadSkin' ) ) {
	wfLoadSkin( 'Freedom' );
	$wgMessagesDirs['Freedom'] = __DIR__ . '/i18n';
	return true;
} else {
	die( 'This version of the Freedom skin requires MediaWiki 1.39+' );
}
