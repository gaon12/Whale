import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const read = (path) => readFileSync(resolve(path), 'utf8');
const readLessWithImports = (path, seen = new Set()) => {
	const absolutePath = resolve(path);
	if (seen.has(absolutePath)) {
		return '';
	}

	seen.add(absolutePath);

	return readFileSync(absolutePath, 'utf8').replace(
		/@import\s+"([^"]+)";/g,
		(_match, importPath) =>
			readLessWithImports(resolve(dirname(absolutePath), importPath), seen),
	);
};
const assertIncludes = (source, needle, label) => {
	if (!source.includes(needle)) {
		throw new Error(`${label} should include ${needle}`);
	}
};

const skin = JSON.parse(read('skin.json'));
if (skin.config.WhaleEnableMobileFloatingToc !== true) {
	throw new Error(
		'Mobile floating TOC should be enabled by default in config.',
	);
}

if (skin.DefaultUserOptions['whale-layout-mobile-toc'] !== true) {
	throw new Error(
		'Mobile floating TOC should be enabled by default for users.',
	);
}

if (skin.config.WhaleAvatarStyle !== 'identicon') {
	throw new Error('DiceBear avatar style should default to identicon.');
}

if (skin.Hooks.BeforePageDisplay !== 'WhaleHooks::onBeforePageDisplay') {
	throw new Error('Whale client modules should load from BeforePageDisplay.');
}

if (
	typeof skin.config.WhaleAvatarOptions !== 'object' ||
	skin.config.WhaleAvatarOptions === null ||
	Array.isArray(skin.config.WhaleAvatarOptions)
) {
	throw new Error('DiceBear avatar options should default to an object.');
}

const removedAvatarConfigKeys = [
	['WhaleAvatar', 'Endpoint'].join(''),
	['WhaleUse', 'Grav', 'atar'].join(''),
];
if (removedAvatarConfigKeys.some((key) => key in skin.config)) {
	throw new Error('Avatar config should not depend on external avatar APIs.');
}

if (skin.AutoloadClasses.WhaleAvatar !== 'WhaleAvatar.php') {
	throw new Error(
		'WhaleAvatar should be registered for MediaWiki autoloading.',
	);
}

for (const locale of ['en', 'ja', 'ko', 'zh-hans', 'zh-hant']) {
	const messages = JSON.parse(read(`i18n/${locale}.json`));
	for (const key of [
		'whale-pref-layout-mobile-toc',
		'whale-pref-layout-mobile-toc-help',
	]) {
		if (!messages[key]) {
			throw new Error(`${locale} should define ${key}.`);
		}
	}
}

const indexButton = read('js/index-button.js');
assertIncludes(indexButton, 'whale:toggleFloatingToc', 'Floating TOC script');
assertIncludes(indexButton, 'MOBILE_SWIPE_DISTANCE_PX', 'Floating TOC script');
assertIncludes(indexButton, 'MOBILE_EDGE_SWIPE_PX = 64', 'Floating TOC script');
assertIncludes(
	indexButton,
	"event.pointerType === 'mouse'",
	'Floating TOC script',
);
assertIncludes(
	indexButton,
	'getFloatingTocItemsFromHeadings',
	'Floating TOC script',
);
if (indexButton.includes('whale-floating-toc-toolbar-hover')) {
	throw new Error(
		'Floating TOC script should not keep legacy toolbar hover state.',
	);
}
assertIncludes(
	indexButton,
	"item.classList.toggle('is-active'",
	'Floating TOC active dot state',
);

const layout = read('js/layout.js');
assertIncludes(layout, 'whale:toggleFloatingToc', 'Layout scroll TOC handler');
assertIncludes(layout, 'container?.classList.toggle', 'Section folding state');
assertIncludes(layout, 'folding?.classList.toggle', 'Folding block state');
assertIncludes(layout, 'initContentSkeleton', 'Content skeleton state');
assertIncludes(layout, 'handleDirectToggle', 'Mobile direct section toggle');
assertIncludes(layout, 'getHeadingToggle', 'Heading click section toggle');

const recovery = read('js/recovery.js');
assertIncludes(
	recovery,
	'live-recent-no-data-visual',
	'Recovery live recent empty state',
);
assertIncludes(
	recovery,
	'live-recent-no-data-paper',
	'Recovery live recent empty state',
);
assertIncludes(
	recovery,
	'live-recent-no-data-bubble',
	'Recovery live recent empty state',
);

const skinPhp = read('SkinWhale.php');
assertIncludes(
	skinPhp,
	"addMeta( 'color-scheme', 'light dark' )",
	'Color scheme meta',
);
if (skinPhp.includes("addMeta( 'viewport'")) {
	throw new Error('Whale should rely on MediaWiki viewport metadata.');
}
if (skinPhp.includes('maximum-scale=1')) {
	throw new Error('Viewport meta should not disable user zoom.');
}

const styles = readLessWithImports('less/default.less');
const wikiStyles = read('less/wiki.less');
const mediaWikiStyles = read('less/only-mw.less');
assertIncludes(styles, 'color-scheme: light dark', 'Stylesheet');
assertIncludes(styles, 'body.whale-dark,', 'Stylesheet');
assertIncludes(styles, '.whale-floating-toc.is-mobile', 'Stylesheet');
assertIncludes(styles, '.whale-content-no-sidebar', 'No-sidebar layout');
assertIncludes(styles, 'scrollbar-gutter: stable', 'Stylesheet');
assertIncludes(
	styles,
	'body.whale-scroll-buttons-vertical.whale-floating-toc-enabled #whale-bottombtn',
	'Fixed desktop scroll toolbar position',
);
assertIncludes(
	styles,
	'right: 10.55rem',
	'Fixed desktop scroll toolbar position',
);
assertIncludes(
	styles,
	'body.whale-floating-toc-hover .whale-floating-toc a',
	'Desktop floating TOC hover labels',
);
assertIncludes(
	styles,
	'pointer-events: none',
	'Desktop floating TOC dot-only default',
);
assertIncludes(
	styles,
	'.whale-floating-toc li.is-active::after',
	'Desktop floating TOC active dot',
);
assertIncludes(
	styles,
	'border-right: 2px solid currentColor',
	'Section collapse toggle style',
);
assertIncludes(
	styles,
	'.whale-section-heading .mw-editsection',
	'Section edit link alignment',
);
assertIncludes(
	styles,
	'border-bottom: 2px solid var(--whale-border-strong-color)',
	'Section heading divider',
);
assertIncludes(
	styles,
	'box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.7)',
	'Section heading divider',
);
assertIncludes(styles, 'margin-bottom: 1.25rem', 'Collapsed section spacing');
assertIncludes(
	styles,
	'background-color: transparent',
	'Section toggle should read as a heading affordance',
);
assertIncludes(styles, 'cursor: pointer', 'Clickable section heading');
if (/\.whale-section-toggle\s*\{[\s\S]*?border-radius:\s*999px;/.test(styles)) {
	throw new Error('Section toggles should not render as legacy round pills.');
}
assertIncludes(
	mediaWikiStyles,
	'min-width: 10.75rem',
	'Article TOC compact document box',
);
assertIncludes(
	mediaWikiStyles,
	'border: 1px solid var(--whale-border-color)',
	'Article TOC compact document box',
);
assertIncludes(
	mediaWikiStyles,
	'.toc .toctogglelabel::before',
	'Article TOC collapse chevron',
);
assertIncludes(
	mediaWikiStyles,
	'border-right: 1.5px solid currentColor',
	'Article TOC collapse chevron',
);
assertIncludes(
	mediaWikiStyles,
	'.toc .toctogglecheckbox:checked ~ ul',
	'Article TOC collapse state',
);
assertIncludes(mediaWikiStyles, 'display: none', 'Article TOC collapse state');
assertIncludes(
	mediaWikiStyles,
	'color: var(--whale-link-color)',
	'Article TOC link color',
);
assertIncludes(
	mediaWikiStyles,
	'.mw-heading h2.whale-section-heading',
	'MediaWiki heading rule should not override section toggles',
);
assertIncludes(
	mediaWikiStyles,
	'display: flex',
	'MediaWiki heading rule should preserve section toggle layout',
);
assertIncludes(
	mediaWikiStyles,
	'box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.7)',
	'MediaWiki heading rule should preserve section divider',
);
assertIncludes(
	styles,
	'whale-content-skeleton-loading',
	'Content skeleton style',
);
assertIncludes(
	styles,
	'.whale-heading-anchor-alert',
	'Heading link copy alert',
);
const rawLessCssFunction = styles
	.split('\n')
	.find((line) => /\b(?:min|max|clamp)\(/.test(line) && !line.includes('~"'));
if (rawLessCssFunction) {
	throw new Error(
		'CSS min/max/clamp functions in LESS should be escaped for MediaWiki less.php.',
	);
}
assertIncludes(styles, '~"min(82vw, 22rem)"', 'Mobile TOC CSS min escape');
assertIncludes(styles, 'gap: 0.65rem', 'Short URL copy row spacing');
assertIncludes(
	styles,
	'.Whale .whale-login-modal .whale-login-links',
	'Login modal link alignment',
);
assertIncludes(styles, 'display: grid', 'Login modal link alignment');
assertIncludes(styles, 'height: 2.75rem', 'Login modal button sizing');
assertIncludes(
	styles,
	'.Whale .content-wrapper .whale-content .whale-content-main p a:hover',
	'Content link hover underline',
);
assertIncludes(wikiStyles, 'a,\na:visited', 'Visited document link color');
assertIncludes(styles, 'color: var(--whale-link-color)', 'Document link color');
assertIncludes(
	wikiStyles,
	'a.new,\na.new:visited',
	'Missing document link color',
);
assertIncludes(styles, 'color: #b32424', 'Missing document link color');
assertIncludes(
	styles,
	'.whale-content-main p a:visited',
	'Content visited document link color',
);
assertIncludes(
	styles,
	'.whale-content-main p a.new:visited',
	'Content visited missing document link color',
);
if (
	/whale-content-main p a,\s*[\s\S]*?whale-content-main dd a\s*\{\s*text-decoration:\s*underline;/.test(
		styles,
	)
) {
	throw new Error('Content links should not be underlined before hover/focus.');
}

const shortUrlTemplate = read('templates/ShortUrlModal.mustache');
if (shortUrlTemplate.includes('whale-short-url-code')) {
	throw new Error('Short URL modal should not render the internal code pill.');
}

const headingAnchors = read('js/heading-anchors.js');
assertIncludes(headingAnchors, 'showCopyAlert', 'Heading anchor copy feedback');
assertIncludes(
	headingAnchors,
	"alert.setAttribute('role', 'status')",
	'Heading anchor copy feedback',
);

const searchTemplate = read('templates/SearchBox.mustache');
assertIncludes(searchTemplate, 'aria-label="{{go-label}}"', 'Search form');
assertIncludes(searchTemplate, 'aria-label="{{search-label}}"', 'Search form');

const navTemplate = read('templates/Nav.mustache');
assertIncludes(navTemplate, 'width="258" height="64"', 'Navbar logo');
assertIncludes(
	navTemplate,
	'whale-navbar-notifications',
	'Navbar notification placement',
);
assertIncludes(
	navTemplate,
	'{{>SearchBox}}',
	'Navbar search should precede right-side tools',
);
assertIncludes(
	styles,
	'.whale-navbar-notifications',
	'Navbar notification placement',
);
assertIncludes(styles, 'order: 29', 'Navbar notification placement');
assertIncludes(styles, 'height: 3.75rem', 'Navbar link height clamp');
assertIncludes(
	styles,
	'box-shadow: inset 0 -3px 0 transparent',
	'Navbar tab focus',
);
assertIncludes(styles, 'font-weight: 700', 'Navbar menu weight');
assertIncludes(styles, '.whale-icon-random', 'Navbar random icon sizing');

const rendererPhp = read('WhaleRenderer.php');
assertIncludes(rendererPhp, 'img/whale_footer_img.png', 'Footer badge image');
assertIncludes(rendererPhp, 'whale-footer-brand-img', 'Footer badge image');
assertIncludes(rendererPhp, "'width' => '78'", 'Footer badge image');
assertIncludes(rendererPhp, "'height' => '31'", 'Footer badge image');
assertIncludes(rendererPhp, 'parseSimpleNavbar', 'Simple navbar parser');
assertIncludes(
	rendererPhp,
	"'has-notifications'",
	'Navbar notification placement',
);
assertIncludes(
	rendererPhp,
	'$title->getLatestRevID()',
	'Navbar content cache key',
);
assertIncludes(
	rendererPhp,
	'WhaleAvatar::createDataUri',
	'Login avatar rendering',
);
assertIncludes(rendererPhp, 'profile-img-fallback', 'Login avatar fallback');
const removedAvatarRenderers = [
	['w', 'Avatar'].join(''),
	['Grav', 'atar'].join(''),
];
if (removedAvatarRenderers.some((needle) => rendererPhp.includes(needle))) {
	throw new Error('Login avatar rendering should use server-side DiceBear.');
}
const removedFooterImage = ['designed', 'by', 'libre.png'].join('');
if (rendererPhp.includes(removedFooterImage)) {
	throw new Error('Footer badge should not use the legacy footer image.');
}
const removedFooterClass = ['designed', 'by', 'libre'].join('');
if (
	rendererPhp.includes(removedFooterClass) ||
	styles.includes(removedFooterClass)
) {
	throw new Error('Footer badge should not keep legacy footer classes.');
}

assertIncludes(
	skinPhp,
	'WHALE_AD_POSITIONS',
	'Centralized AdSense position config',
);
assertIncludes(
	skinPhp,
	'pagead/js/adsbygoogle.js?client=',
	'Modern AdSense loader',
);
assertIncludes(
	skinPhp,
	"'crossorigin' => 'anonymous'",
	'Modern AdSense loader',
);
assertIncludes(skinPhp, 'normalizeAdBoolean', 'AdSense slot normalization');
const removedAdsenseLoader = ['src="//', 'pagead2.googlesyndication.com'].join(
	'',
);
if (skinPhp.includes(removedAdsenseLoader)) {
	throw new Error(
		'AdSense loader should not use protocol-relative legacy URLs.',
	);
}

const avatarPhp = read('WhaleAvatar.php');
assertIncludes(
	avatarPhp,
	"getInstallPath( 'dicebear/styles' )",
	'DiceBear PHP avatar',
);
assertIncludes(
	avatarPhp,
	'new Avatar( $style, $avatarOptions )',
	'DiceBear PHP avatar',
);

const externalLinkTemplate = read('templates/ExternalLinkModal.mustache');
assertIncludes(
	externalLinkTemplate,
	'href="#" data-whale-external-continue',
	'External link modal continue link',
);
if (
	externalLinkTemplate.indexOf('whale-modal-title') >
	externalLinkTemplate.indexOf('whale-modal-close')
) {
	throw new Error(
		'External link modal close button should sit after the title.',
	);
}

const skinTemplate = read('templates/skin.mustache');
assertIncludes(skinTemplate, 'whale-content-no-sidebar', 'No-sidebar layout');
assertIncludes(
	skinTemplate,
	'whale-content-wrapper-no-sidebar',
	'No-sidebar layout',
);
assertIncludes(
	skinTemplate,
	'{{#has-whale-section-tools}}',
	'Special-page section tool suppression',
);

const hooksPhp = read('WhaleHooks.php');
assertIncludes(
	hooksPhp,
	'public static function onBeforePageDisplay',
	'Client module loader hook',
);
assertIncludes(hooksPhp, 'getWhaleClientModules', 'Client module loader hook');
assertIncludes(hooksPhp, '$out->addModules', 'Client module loader hook');
assertIncludes(
	hooksPhp,
	"$preferences['whale-ads-belowarticle']",
	'Below-article ad preference',
);
const removedBelowArticleAdPreference = [
	'whale-ads',
	String.fromCharCode(109, 111, 114, 101, 97, 114, 116, 105, 99, 108, 101),
].join('-');
if (hooksPhp.includes(`$preferences['${removedBelowArticleAdPreference}']`)) {
	throw new Error('Below-article ad preference should use belowarticle key.');
}
assertIncludes(
	hooksPhp,
	'$wgWhaleEnableSectionCollapse ?? true',
	'Feature preference guards',
);
assertIncludes(
	hooksPhp,
	'shouldRenderSectionNavigation',
	'Special-page section navigation suppression',
);
assertIncludes(
	hooksPhp,
	'normalizeSectionMode',
	'Section collapse default normalization',
);

assertIncludes(skinPhp, 'NS_SPECIAL', 'Special-page sidebar suppression');

if (skin.config.WhaleEnableContentSkeleton !== false) {
	throw new Error('Content skeleton should be disabled by default.');
}

if (skin.DefaultUserOptions['whale-content-skeleton'] !== false) {
	throw new Error('Content skeleton user option should default to off.');
}

const shortUrlPhp = read('SpecialWhaleShortUrl.php');
assertIncludes(
	shortUrlPhp,
	"quickUserCan( 'read'",
	'Short URL permission check',
);
assertIncludes(
	shortUrlPhp,
	'ALLOWED_REDIRECT_STATUSES',
	'Short URL redirect status validation',
);

const readme = read('README.md');
assertIncludes(readme, 'children:', 'Simple navbar docs');
assertIncludes(readme, '- text: Beginner guide', 'Simple navbar docs');
