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
assertIncludes(
	styles,
	':not(.whale-floating-toc-toolbar-hover) #whale-bottombtn',
	'Toolbar hover guard style',
);
assertIncludes(styles, 'content: ">"', 'Section collapse toggle style');

const shortUrlTemplate = read('templates/ShortUrlModal.mustache');
if (shortUrlTemplate.includes('whale-short-url-code')) {
	throw new Error('Short URL modal should not render the internal code pill.');
}

const searchTemplate = read('templates/SearchBox.mustache');
assertIncludes(searchTemplate, 'aria-label="{{go-label}}"', 'Search form');
assertIncludes(searchTemplate, 'aria-label="{{search-label}}"', 'Search form');

const navTemplate = read('templates/Nav.mustache');
assertIncludes(navTemplate, 'width="258" height="64"', 'Navbar logo');

const rendererPhp = read('WhaleRenderer.php');
assertIncludes(rendererPhp, "'width' => '174'", 'Footer badge image');
assertIncludes(rendererPhp, "'height' => '62'", 'Footer badge image');

const externalLinkTemplate = read('templates/ExternalLinkModal.mustache');
assertIncludes(
	externalLinkTemplate,
	'href="#" data-whale-external-continue',
	'External link modal continue link',
);
