<?php // @codingStandardsIgnoreLine
if ( function_exists( 'wfLoadSkin' ) ) {
	wfLoadSkin( 'Whale' );
	if ( !isset( $wgMessagesDirs ) || !is_array( $wgMessagesDirs ) ) {
		$wgMessagesDirs = [];
	}
	$wgMessagesDirs['Whale'] = __DIR__ . '/i18n';
	return true;

}

die( 'This version of the Whale skin requires MediaWiki 1.39+' );
