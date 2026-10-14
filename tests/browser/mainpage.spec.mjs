import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { renderSkin, skinStyles } from './helpers.mjs';

const html = execFileSync('php', ['scripts/check-mainpage.php', '--render'], {
	encoding: 'utf8',
});

for (const width of [320, 390, 640, 641, 768, 1440]) {
	for (const dark of [false, true]) {
		test(`main page uses flat semantic sections at ${width}px in ${dark ? 'dark' : 'light'}`, async ({
			page,
		}) => {
			await page.setViewportSize({ width, height: width < 1024 ? 844 : 1000 });
			const markup = await renderSkin({
				'html-title': '가온 위키:대문',
				'html-body-content': `<div id="mw-content-text">${html}</div>`,
				'html-whale-last-modified': '최근 수정 시각: 2026년 7월 10일',
			});
			await page.setContent(
				`<body class="Whale page-Main_Page ${dark ? 'whale-dark' : ''}">${markup}</body>`,
			);
			await page.addStyleTag({ content: await skinStyles() });
			await expect(page.locator('.whale-home-title')).toHaveText(
				'가온 위키에 오신 것을 환영합니다',
			);
			await expect(page.locator('.whale-home-section')).toHaveCount(4);
			await expect(page.locator('.whale-home-column')).toHaveCount(2);
			await expect(
				page.locator('.whale-home-column').first().locator('h2'),
			).toHaveText(['공지사항', '주요 분류']);
			await expect(
				page.locator('.whale-home-column').last().locator('h2'),
			).toHaveText(['이 사이트는...', '위키 시작하기']);
			await expect(page.locator('.whale-home-section h2').first()).toHaveText(
				'공지사항',
			);
			await expect(page.locator('.whale-section-toggle')).toHaveCount(0);
			const layout = await page.evaluate(() => ({
				introHeight: document
					.querySelector('.whale-home-intro')
					.getBoundingClientRect().height,
				sections: [...document.querySelectorAll('.whale-home-section')].map(
					(e) => ({
						shadow: getComputedStyle(e).boxShadow,
						background: getComputedStyle(e).backgroundColor,
					}),
				),
				columns: [...document.querySelectorAll('.whale-home-column')].map(
					(element) => {
						const rect = element.getBoundingClientRect();
						return {
							x: rect.x,
							top: rect.top,
							right: rect.right,
							bottom: rect.bottom,
						};
					},
				),
				overflow:
					document.body.scrollWidth > document.documentElement.clientWidth,
			}));
			expect(layout.introHeight).toBeLessThan(150);
			expect(
				layout.sections.every(
					(e) => e.shadow === 'none' && e.background === 'rgba(0, 0, 0, 0)',
				),
			).toBe(true);
			expect(layout.overflow).toBe(false);
			const [left, right] = layout.columns;
			if (width > 640) {
				expect(left.right).toBeLessThan(right.x);
				expect(left.top).toBeCloseTo(right.top);
			} else {
				expect(left.x).toBeCloseTo(right.x);
				expect(left.bottom).toBeLessThan(right.top);
			}
		});
	}
}
