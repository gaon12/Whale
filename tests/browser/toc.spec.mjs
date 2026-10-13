import { expect, test } from '@playwright/test';
import { runtime, skinStyles } from './helpers.mjs';

export const setupToc = async (
	page,
	{ mobile = false, native = false, count = 317 } = {},
) => {
	await page.setViewportSize({
		width: mobile ? 390 : 1440,
		height: mobile ? 844 : 1000,
	});
	const sections = Array.from(
		{ length: count },
		(_, i) =>
			`<h2 id="section_${i + 1}">절 ${i + 1}</h2><p>긴 문서의 본문입니다.</p>`,
	).join('');
	const toc = native
		? `<div id="toc"><ol>${Array.from({ length: count }, (_, i) => `<li><a href="#section_${i + 1}"><span class="tocnumber">${i + 1}</span><span class="toctext">절 ${i + 1}</span></a></li>`).join('')}</ol></div>`
		: '';
	await page.setContent(`<body class="Whale whale-floating-toc-enabled whale-mobile-floating-toc-enabled">
		<button id="whale-scrolltoc">목차</button><main class="whale-content-main" id="mw-content-text">${toc}${sections}</main>
	</body>`);
	await page.addStyleTag({ content: await skinStyles() });
	await runtime(page);
	await page.addScriptTag({ path: 'js/toc-utils.js' });
	await page.addScriptTag({ path: 'js/index-button.js' });
	await expect(page.locator('.whale-floating-toc li')).toHaveCount(count);
};

for (const native of [false, true]) {
	for (const mobile of [false, true]) {
		test(`all 317 sections survive ${native ? 'native' : 'fallback'} TOC on ${mobile ? 'mobile' : 'desktop'}`, async ({
			page,
		}) => {
			await setupToc(page, { native, mobile });
			await expect(
				page.locator('.whale-floating-toc a').last(),
			).toHaveAttribute('href', '#section_317');
			if (mobile) {
				await page.evaluate(() =>
					document.dispatchEvent(new CustomEvent('whale:toggleFloatingToc')),
				);
				await page.locator('.whale-floating-toc a').last().click();
				await expect(page.locator('.whale-floating-toc')).toHaveAttribute(
					'aria-hidden',
					'true',
				);
				await expect(page.locator('#section_317')).toBeInViewport();
				await expect
					.poll(() => page.evaluate(() => window.scrollY))
					.toBeGreaterThan(0);
			}
		});
	}
}
