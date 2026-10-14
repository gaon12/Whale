import { expect, test } from '@playwright/test';
import { renderSkin, runtime, skinStyles } from './helpers.mjs';

const fixture = async (page, width, dark = false) => {
	await page.setViewportSize({ width, height: 844 });
	const markup = await renderSkin({
		'html-title': '표 예시',
		'html-body-content': `<div id="mw-content-text">
		<table id="license" class="wikitable"><tr><td class="whale-empty-cell" style="background:green">\n</td></tr>
		<tr><td><big><b>이 파일은 퍼블릭 도메인으로 제공됩니다.</b></big><ul><li>이 저작물이 CC0가 아니라면 이 틀을 달지 마세요!</li></ul></td></tr></table>
		<table id="plain" style="border:1px solid #888;border-collapse:collapse"><tr><td class="whale-empty-cell" style="background:green">&nbsp;</td></tr><tr><td>일반 표</td></tr></table>
		<table id="sized" class="wikitable"><tr><td class="whale-empty-cell" style="height:30px;padding:8px"></td></tr><tr><td>명시적인 높이</td></tr></table>
		<table id="navigation" class="wikitable mw-collapsible"><tr><th colspan="3" style="background:#a3f4f9"><span class="mw-collapsible-toggle mw-collapsible-toggle-default" role="button" tabindex="0" aria-expanded="true">접기</span><b id="nav-title">A20엔터테인먼트</b></th></tr><tr><td>A20 MAY</td><td>A20 LITE</td><td>A20 SOUL</td></tr></table>
		<div class="whale-folding is-collapsed"><div class="whale-folding-header">상세 정보</div><button type="button" class="whale-folding-toggle" aria-expanded="false" aria-controls="fold-body" data-expand-label="펼치기" data-collapse-label="접기">펼치기 · 접기</button><div id="fold-body" class="whale-folding-body" hidden>접힌 본문</div></div>
		</div>`,
	});
	await page.setContent(
		`<body class="Whale ${dark ? 'whale-dark' : ''}">${markup}</body>`,
	);
	await page.addStyleTag({ content: await skinStyles() });
	await runtime(page);
	await page.addScriptTag({ path: 'js/common.js' });
	await page.addScriptTag({ path: 'js/table.js' });
	await page.addScriptTag({ path: 'js/layout.js' });
};

for (const width of [320, 390, 1440]) {
	for (const dark of [false, true]) {
		test(`compact separators and title controls at ${width}px in ${dark ? 'dark' : 'light'}`, async ({
			page,
		}) => {
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));
			await fixture(page, width, dark);
			for (const id of ['license', 'plain']) {
				const cell = page.locator(`#${id} tr`).first().locator('td');
				expect((await cell.boundingBox()).height).toBeLessThanOrEqual(6);
				await expect(cell).toHaveCSS('background-color', 'rgb(0, 128, 0)');
				await expect(page.locator(`#${id}`)).toHaveCSS(
					'border-top-left-radius',
					'4px',
				);
				await expect(page.locator(`#${id}`)).toHaveCSS(
					'border-top-right-radius',
					'4px',
				);
			}
			expect(
				(await page.locator('#sized tr').first().boundingBox()).height,
			).toBeGreaterThanOrEqual(30);
			const bar = await page
				.locator('#navigation .whale-table-toggle-bar')
				.boundingBox();
			const title = await page.locator('#nav-title').boundingBox();
			const table = await page.locator('#navigation').boundingBox();
			expect(bar.y).toBeGreaterThan(title.y + title.height);
			expect(bar.width).toBeGreaterThanOrEqual(table.width - 2);
			await expect(page.locator('#navigation tr')).toHaveCount(2);
			await expect(page.locator('#navigation th')).toHaveAttribute(
				'colspan',
				'3',
			);
			await expect(page.locator('#navigation th')).toHaveCSS(
				'background-color',
				'rgb(163, 244, 249)',
			);
			await page.evaluate(() =>
				window.mw
					.hook('wikipage.content')
					.fire([document.getElementById('mw-content-text')]),
			);
			await expect(
				page.locator('#navigation .whale-table-toggle-bar'),
			).toHaveCount(1);
			const toggle = page.locator('.whale-folding-toggle');
			const fold = await page.locator('.whale-folding').boundingBox();
			const control = await toggle.boundingBox();
			expect(control.width).toBeGreaterThanOrEqual(fold.width - 2);
			await toggle.press('Enter');
			await expect(page.locator('#fold-body')).toBeVisible();
			await toggle.press('Space');
			await expect(page.locator('#fold-body')).toBeHidden();
			expect(errors).toEqual([]);
		});
	}
}
