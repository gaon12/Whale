import { expect, test } from '@playwright/test';
import { runtime, skinStyles } from './helpers.mjs';

export const setupToc = async (
	page,
	{ mobile = false, native = false, count = 317, sidebar = true } = {},
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
		<button id="whale-scrolltoc">목차</button><div class="content-wrapper"><div class="whale-content"><main class="whale-content-main" id="mw-content-text">${toc}${sections}</main></div>
		${sidebar ? '<aside class="whale-sidebar-column"><div class="whale-sidebar"></div></aside>' : ''}</div>
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

test('desktop markers follow exact document positions beside the scrollbar', async ({
	page,
}) => {
	await setupToc(page);
	await expect(page.locator('body > .whale-floating-toc')).toHaveCount(1);
	await expect(page.locator('.whale-sidebar .whale-floating-toc')).toHaveCount(
		0,
	);
	const positions = await page.evaluate(() => {
		const documentHeight = document.documentElement.scrollHeight;
		return [...document.querySelectorAll('.whale-floating-toc-marker')].map(
			(item, index) => {
				const heading = document.getElementById(`section_${index + 1}`);
				return {
					actual: Number.parseFloat(item.style.top),
					expected:
						((heading.getBoundingClientRect().top + scrollY) / documentHeight) *
						100,
				};
			},
		);
	});
	for (const item of positions)
		expect(item.actual).toBeCloseTo(item.expected, 3);
	const marker = await page
		.locator('.whale-floating-toc-marker')
		.nth(100)
		.boundingBox();
	await page.mouse.move(
		marker.x + marker.width / 2,
		marker.y + marker.height / 2,
	);
	// Approaching the rail opens the full list, not just the nearest label.
	await expect(page.locator('.whale-floating-toc')).toHaveClass(/is-expanded/);
	const panel = page.locator('.whale-floating-toc-panel');
	await expect(panel).toBeVisible();
	await expect(panel.locator('a')).toHaveCount(317);
	await expect(panel.locator('li').nth(100)).toHaveClass(/is-hovered/);
	await expect(panel.locator('a.is-active')).toHaveCount(1);
	await page.mouse.click(
		marker.x + marker.width / 2,
		marker.y + marker.height / 2,
	);
	await expect(page.locator('#section_101')).toBeInViewport();
});

test('mobile drawer keeps keyboard focus inside and returns it on Escape', async ({
	page,
}) => {
	await setupToc(page, { mobile: true, count: 24 });
	await page.evaluate(() =>
		document.dispatchEvent(new CustomEvent('whale:toggleFloatingToc')),
	);
	const close = page.locator('.whale-floating-toc-close');
	await expect(close).toBeFocused();
	await page.keyboard.press('Shift+Tab');
	await expect(page.locator('.whale-floating-toc a').last()).toBeFocused();
	await page.keyboard.press('Tab');
	await expect(close).toBeFocused();
	await page.keyboard.press('Escape');
	await expect(page.locator('#whale-scrolltoc')).toBeFocused();
	await expect(page.locator('#whale-scrolltoc')).toHaveAttribute(
		'aria-expanded',
		'false',
	);
});

test('pages without a sidebar retain document-position navigation', async ({
	page,
}) => {
	await setupToc(page, { sidebar: false, count: 24 });
	await expect(page.locator('body > .whale-floating-toc')).toBeVisible();
	await expect(page.locator('.whale-floating-toc a')).toHaveCount(24);
});
