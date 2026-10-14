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

$breadcrumbs = '<div class="subpages">&lt; <a href="/w/상위_문서">상위 문서</a></div>';
$sourceHref = '/w/index.php?title=3%ED%83%B1_3%ED%9E%90&redirect=no';
$redirect = '<span class="mw-redirectedfrom custom-note" lang="ko">' .
	'<a href="/w/도움말:넘겨주기">넘겨주기</a>&nbsp;: ' .
	'<a class="mw-redirect" href="' . htmlspecialchars( $sourceHref, ENT_QUOTES, 'UTF-8' ) .
	'" title="3탱 &quot;3힐&quot; &amp; 구버전"><bdi>3탱 3힐</bdi></a></span>';
$split = WhaleArticleDecorator::splitSubtitleHtml(
	'<div id="mw-content-subtitle">' . $breadcrumbs . $redirect . '</div>'
);
if ( str_contains( $split['subtitle'], 'mw-redirectedfrom' ) ||
	!str_contains( $split['subtitle'], 'class="subpages"' ) || $split['redirect'] === ''
) {
	throw new RuntimeException( 'Redirect origins must leave breadcrumbs in the ordinary subtitle.' );
}
$subtitleDom = new DOMDocument();
$subtitleDom->loadHTML( '<?xml encoding="utf-8" ?><div>' . $split['subtitle'] . '</div>' );
$parentLink = $subtitleDom->getElementsByTagName( 'a' )->item( 0 );
if ( !$parentLink instanceof DOMElement || $parentLink->textContent !== '상위 문서' ||
	rawurldecode( $parentLink->getAttribute( 'href' ) ) !== '/w/상위_문서'
) {
	throw new RuntimeException( 'The breadcrumb label and destination must survive subtitle separation.' );
}
$redirectDom = new DOMDocument();
$redirectDom->loadHTML(
	'<?xml encoding="utf-8" ?><div>' . $split['redirect'] . '</div>',
	LIBXML_NOERROR | LIBXML_NOWARNING
);
$sourceLink = $redirectDom->getElementsByTagName( 'a' )->item( 1 );
if ( !$sourceLink instanceof DOMElement || $sourceLink->getAttribute( 'href' ) !== $sourceHref ||
	$sourceLink->textContent !== '3탱 3힐' ||
	$sourceLink->getAttribute( 'title' ) !== '3탱 "3힐" & 구버전' ||
	$redirectDom->getElementsByTagName( 'span' )->item( 0 )?->getAttribute( 'lang' ) !== 'ko' ||
	rawurldecode( $redirectDom->getElementsByTagName( 'a' )->item( 0 )?->getAttribute( 'href' ) ?? '' ) !== '/w/도움말:넘겨주기'
) {
	throw new RuntimeException( 'Separating redirect notices must preserve Unicode, entities, URLs and attributes.' );
}
foreach ( [ '', $breadcrumbs, 'mw-redirectedfrom', '<span class="mw-redirectedfrom-extra">다른 부제</span>' ] as $subtitle ) {
	if ( WhaleArticleDecorator::splitSubtitleHtml( $subtitle ) !== [ 'subtitle' => $subtitle, 'redirect' => '' ] ) {
		throw new RuntimeException( 'Non-redirect subtitles must remain unchanged.' );
	}
}
$onlyRedirect = WhaleArticleDecorator::splitSubtitleHtml( '<div id="mw-content-subtitle">' . $redirect . '</div>' );
if ( $onlyRedirect['subtitle'] !== '' || $onlyRedirect['redirect'] === '' ) {
	throw new RuntimeException( 'A redirect-only subtitle must not leave an empty breadcrumb row.' );
}
$multipleRedirects = WhaleArticleDecorator::splitSubtitleHtml( $redirect . $redirect );
if ( $multipleRedirects['subtitle'] !== '' || substr_count( $multipleRedirects['redirect'], 'mw-redirectedfrom' ) !== 2 ) {
	throw new RuntimeException( 'Every native redirect notice must survive extraction once.' );
}
