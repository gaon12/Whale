import { expect, test } from '@playwright/test';
import { renderSkin, runtime, skinStyles } from './helpers.mjs';

const columns = Array.from(
	{ length: 6 },
	(_, index) =>
		`<td style="white-space:nowrap">${index === 5 ? '마지막 열' : `${index + 1}번째 열`}의 긴 내용을 끝까지 읽습니다</td>`,
).join('');

const fixture = async (page, width = 390, dark = false) => {
	await page.setViewportSize({ width, height: 844 });
	const markup = await renderSkin({
		'html-title': '가로로 긴 표',
		'html-body-content': `<div id="mw-content-text"><div class="mw-parser-output">
		<table id="wide" class="wikitable"><tr>${columns}</tr></table>
		<table id="fixed" class="wikitable" style="width:1100px"><tr>${columns}</tr></table>
		<table id="plain" style="border-collapse:collapse"><tr>${columns}</tr></table>
		<table id="nested" class="wikitable"><tr><td><table><tr>${columns}</tr></table></td></tr></table>
		<table id="resize" class="wikitable" style="width:700px"><tr><td>첫 열</td><td>마지막 열</td></tr></table>
		<div class="whale-folding is-collapsed"><div class="whale-folding-header">접힌 표</div><button class="whale-folding-toggle" type="button" aria-expanded="false" aria-controls="folded" data-expand-label="펼치기" data-collapse-label="접기">펼치기 · 접기</button><div class="whale-folding-body" id="folded" hidden><table id="fold-table" class="wikitable"><tr>${columns}</tr></table></div></div>
		<table id="narrow" class="wikitable"><tr><td>짧은 표</td></tr></table>
		</div></div>`,
	});
	await page.setContent(
		`<body class="Whale whale-responsive-tables-enabled ${dark ? 'whale-dark' : ''}">${markup}</body>`,
	);
	// MediaWiki's native mobile table rule must be present to reproduce the clipping.
	await page.addStyleTag({
		content: '@media(max-width:639px){.mw-parser-output table{display:block}}',
	});
	await page.addStyleTag({ content: await skinStyles() });
	await runtime(page);
	await page.evaluate(() => {
		delete window.whale;
	});
	await page.addScriptTag({ path: 'js/common.js' });
	await page.addScriptTag({ path: 'js/table.js' });
	await page.addScriptTag({ path: 'js/layout.js' });
};

const scrollToLastColumn = async (page, id) => {
	const table = page.locator(`#${id}`);
	const wrapper = table.locator('..');
	await expect(wrapper).toHaveClass('whale-table-wrapper');
	await expect(wrapper).toHaveCSS('overflow-x', 'auto');
	const dimensions = await wrapper.evaluate((el) => ({
		client: el.clientWidth,
		scroll: el.scrollWidth,
	}));
	const viewport = await page.evaluate(
		() => document.documentElement.clientWidth,
	);
	expect(dimensions.client).toBeLessThan(viewport);
	expect(dimensions.scroll).toBeGreaterThan(dimensions.client + 100);
	await expect(table).toHaveCSS('display', 'table');
	await expect(table).toHaveCSS('clip-path', 'none');
	await wrapper.hover();
	await page.mouse.wheel(10000, 0);
	await expect
		.poll(() => wrapper.evaluate((el) => el.scrollLeft))
		.toBeGreaterThan(100);
	const result = await wrapper.evaluate((el) => {
		const last = el
			.querySelector('table tr')
			.lastElementChild.getBoundingClientRect();
		const frame = el.getBoundingClientRect();
		return { left: el.scrollLeft, end: last.right, frame: frame.right };
	});
	expect(result.left).toBeGreaterThan(100);
	expect(result.end).toBeLessThanOrEqual(result.frame + 1);
	return wrapper;
};

for (const width of [320, 390, 768]) {
	for (const dark of [false, true]) {
		test(`wide table columns stay reachable at ${width}px in ${dark ? 'dark' : 'light'}`, async ({
			page,
		}) => {
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));
			await fixture(page, width, dark);
			for (const id of ['wide', 'fixed', 'plain', 'nested']) {
				await scrollToLastColumn(page, id);
			}
			await expect(page.locator('#narrow').locator('..')).not.toHaveClass(
				'whale-table-wrapper',
			);
			await page.locator('.whale-folding-toggle').click();
			await expect(page.locator('#folded')).toBeVisible();
			await expect
				.poll(() =>
					page.locator('#fold-table').locator('..').getAttribute('class'),
				)
				.toBe('whale-table-wrapper');
			await scrollToLastColumn(page, 'fold-table');
			expect(
				await page.evaluate(
					() =>
						document.body.scrollWidth > document.documentElement.clientWidth,
				),
			).toBe(false);
			expect(errors).toEqual([]);
		});
	}
}

test('resizing and repeated content hooks keep exactly one usable scroll frame', async ({
	page,
}) => {
	await fixture(page, 390);
	await scrollToLastColumn(page, 'resize');
	await page.setViewportSize({ width: 1800, height: 1000 });
	await expect
		.poll(() => page.locator('#resize').locator('..').getAttribute('class'))
		.not.toBe('whale-table-wrapper');
	await page.setViewportSize({ width: 390, height: 844 });
	await scrollToLastColumn(page, 'resize');
	await page.evaluate(() =>
		window.mw
			.hook('wikipage.content')
			.fire([document.getElementById('mw-content-text')]),
	);
	await expect(
		page.locator('.whale-table-wrapper > .whale-table-wrapper'),
	).toHaveCount(0);
	await scrollToLastColumn(page, 'resize');
});
