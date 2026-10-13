import { expect, test } from '@playwright/test';
import { runtime } from './helpers.mjs';

test('homepage portal headings stay clean while article link controls survive repeated content hooks', async ({
	page,
}) => {
	await page.setContent(
		'<body class="whale-heading-anchors-enabled"><div id="mw-content-text"><div class="whale-home"><h2 id="portal">공지사항</h2></div><h2 id="article">문서의 문단</h2></div></body>',
	);
	await runtime(page);
	await page.evaluate(() => {
		window.mw.msg = (key) => key;
	});
	await page.addScriptTag({ path: 'js/heading-anchors.js' });
	await expect(page.locator('#article .whale-heading-anchor')).toHaveCount(1);
	await expect(page.locator('.whale-home .whale-heading-anchor')).toHaveCount(
		0,
	);
	await page.evaluate(() => {
		const portal = document.createElement('div');
		portal.className = 'whale-home';
		portal.innerHTML = '<h2 id="new-portal">새 안내</h2>';
		const content = document.getElementById('mw-content-text');
		content.append(portal);
		mw.hook('wikipage.content').fire([portal]);
		mw.hook('wikipage.content').fire([content]);
	});
	await expect(page.locator('#article .whale-heading-anchor')).toHaveCount(1);
	await expect(page.locator('.whale-home .whale-heading-anchor')).toHaveCount(
		0,
	);
});
