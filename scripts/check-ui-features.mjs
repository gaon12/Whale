import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const read = (path) => readFileSync(resolve(path), 'utf8');
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

if (
	typeof skin.config.WhaleAvatarOptions !== 'object' ||
	skin.config.WhaleAvatarOptions === null ||
	Array.isArray(skin.config.WhaleAvatarOptions)
) {
	throw new Error('DiceBear avatar options should default to an object.');
}

if ('WhaleAvatarEndpoint' in skin.config || 'WhaleUseGravatar' in skin.config) {
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
assertIncludes(
	indexButton,
	'whale-floating-toc-toolbar-hover',
	'Floating TOC script',
);

const layout = read('js/layout.js');
assertIncludes(layout, 'whale:toggleFloatingToc', 'Layout scroll TOC handler');
assertIncludes(layout, 'container?.classList.toggle', 'Section folding state');
assertIncludes(layout, 'folding?.classList.toggle', 'Folding block state');

const skinPhp = read('SkinWhale.php');
assertIncludes(
	skinPhp,
	"addMeta( 'viewport', 'width=device-width, initial-scale=1' )",
	'Viewport meta',
);
if (skinPhp.includes('maximum-scale=1')) {
	throw new Error('Viewport meta should not disable user zoom.');
}

const styles = read('less/default.less');
assertIncludes(styles, '.whale-floating-toc.is-mobile', 'Stylesheet');
assertIncludes(styles, 'scrollbar-gutter: stable', 'Stylesheet');
assertIncludes(
	styles,
	':not(.whale-floating-toc-toolbar-hover) #whale-bottombtn',
	'Toolbar hover guard style',
);
assertIncludes(styles, 'content: ">"', 'Section collapse toggle style');
assertIncludes(
	styles,
	'.whale-heading-anchor-alert',
	'Heading link copy alert',
);
assertIncludes(styles, 'gap: 0.65rem', 'Short URL copy row spacing');

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

const rendererPhp = read('WhaleRenderer.php');
assertIncludes(rendererPhp, "'width' => '174'", 'Footer badge image');
assertIncludes(rendererPhp, "'height' => '62'", 'Footer badge image');
assertIncludes(rendererPhp, 'parseSimpleNavbar', 'Simple navbar parser');
assertIncludes(
	rendererPhp,
	'WhaleAvatar::createDataUri',
	'Login avatar rendering',
);
if (
	rendererPhp.includes('secure.gravatar.com') ||
	rendererPhp.includes('wAvatar')
) {
	throw new Error('Login avatar rendering should use server-side DiceBear.');
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

const readme = read('README.md');
assertIncludes(readme, 'children:', 'Simple navbar docs');
assertIncludes(readme, '- text: Beginner guide', 'Simple navbar docs');
