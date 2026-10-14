import { expect, test } from '@playwright/test';
import { renderSkin, skinStyles } from './helpers.mjs';

// 틀:인용 uses a full-width blockquote to center a smaller, independently painted table.
const templateQuote = `<blockquote style="padding:0;border:0;font-size:inherit;margin:1em 40px;text-align:center">
<table style="padding:0 1rem;border-collapse:collapse;border-style:none;background-color:#F8F8F8;display:inline-block;width:auto">
<tbody><tr><td style="color:#707070;font-size:40px;font-family:Georgia">“</td>
<td style="padding:1rem;text-align:left"><p>팀 대항, 페어플레이</p></td>
<td style="color:#707070;font-size:40px;font-family:Georgia">”</td></tr></tbody>
</table></blockquote>`;

for (const width of [320, 390, 1440]) {
	for (const dark of [false, true]) {
		test(`quotation templates paint only their table at ${width}px in ${dark ? 'dark' : 'light'}`, async ({
			page,
		}) => {
			await page.setViewportSize({ width, height: 1000 });
			const markup = await renderSkin({
				'html-title': '인용',
				'html-body-content': `<div id="mw-content-text"><div class="mw-parser-output">
				${templateQuote}<blockquote id="plain-quote"><p>일반 인용</p></blockquote>
				<div id="authored-quote">${templateQuote.replace('padding:0;border:0;', 'background-color:#ffeedd;padding:0;border:0;')}</div>
				</div></div>`,
			});
			await page.setContent(
				`<body class="Whale ${dark ? 'whale-dark' : ''}">${markup}</body>`,
			);
			await page.addStyleTag({ content: await skinStyles() });
			const quote = page.locator('.mw-parser-output > blockquote').first();
			await expect(quote).toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
			await expect(quote.locator('table')).toHaveCSS(
				'background-color',
				'rgb(248, 248, 248)',
			);
			await expect(page.locator('#authored-quote blockquote')).toHaveCSS(
				'background-color',
				'rgb(255, 238, 221)',
			);
			const sizes = await quote.evaluate((element) => ({
				wrapper: element.getBoundingClientRect().width,
				table: element.querySelector('table').getBoundingClientRect().width,
			}));
			if (width >= 1024) {
				expect(sizes.wrapper).toBeGreaterThan(sizes.table * 2);
			}
			await expect(page.locator('#plain-quote')).not.toHaveCSS(
				'background-color',
				'rgba(0, 0, 0, 0)',
			);
			expect(
				await page.evaluate(
					() =>
						document.body.scrollWidth > document.documentElement.clientWidth,
				),
			).toBe(false);
		});
	}
}
