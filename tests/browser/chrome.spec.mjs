import { expect, test } from '@playwright/test';
import { renderSkin, skinStyles } from './helpers.mjs';

for (const width of [320, 390, 768, 1024, 1440]) {
	test(`wiki chrome keeps content clear of fixed navigation at ${width}px`, async ({
		page,
	}) => {
		const pageErrors = [];
		page.on('pageerror', (error) => pageErrors.push(error.message));
		await page.setViewportSize({ width, height: 1000 });
		const markup = await renderSkin({
			'has-whale-sidebar': true,
			'html-title': '문서 제목',
			'html-whale-last-modified': '최근 수정 시각: 2026년 10월 13일',
			'has-whale-site-notice': true,
			'html-whale-site-notice':
				'<p>위키 공지사항: 서비스 안내와 편집 규칙을 확인해 주세요.</p>',
			'html-body-content': '<div id="mw-content-text"><p>문서 본문</p></div>',
			'data-whale-nav': {
				'brand-logo':
					'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="140" height="32"%3E%3Crect width="140" height="32" fill="white"/%3E%3C/svg%3E',
				'brand-alt': '가온 위키',
				items: [
					{
						classes: 'whale-navbar-item',
						'html-link':
							'<a class="whale-navbar-link"><span class="hide-title">최근 바뀜</span></a>',
					},
				],
			},
			'data-search': { placeholder: '가온 위키 검색' },
		});
		await page.setContent(
			`<body class="Whale whale-floating-toc-enabled whale-scroll-buttons-vertical">${markup}<nav class="whale-floating-toc"></nav></body>`,
		);
		await page.addStyleTag({ content: await skinStyles() });
		await page.evaluate(() => {
			window.noticeCookies = [];
			window.mw = {
				message: (key) => ({ text: () => key }),
				cookie: {
					set: (name, value) => window.noticeCookies.push({ name, value }),
				},
			};
		});
		await page.addScriptTag({ path: 'js/common.js' });
		await page.addScriptTag({ path: 'js/layout.js' });
		const measure = () =>
			page.evaluate(() => {
				const bounds = (selector) =>
					document.querySelector(selector).getBoundingClientRect();
				return {
					navBottom: bounds('.whale-nav-wrapper').bottom,
					contentTop: bounds('.content-wrapper').top,
					noticeTop: bounds('.whale-site-notice-bar').top,
					noticeBottom: bounds('.whale-site-notice-bar').bottom,
					documentTop: bounds('.whale-content').top,
					noticeInsideDocument: !!document
						.querySelector('.whale-site-notice-bar')
						.closest('.whale-content'),
					sidebarTop: bounds('.whale-sidebar-column').top,
					toolbarRight: bounds('#whale-bottombtn').right,
					markersLeft: bounds('.whale-floating-toc').left,
					sidebarWidth: bounds('.whale-sidebar-column').width,
					headerBorder: getComputedStyle(
						document.querySelector('.whale-content-header'),
					).borderBottomWidth,
					overflow:
						document.body.scrollWidth > document.documentElement.clientWidth,
				};
			});
		await expect
			.poll(async () => {
				const measurements = await measure();
				return measurements.contentTop >= measurements.navBottom;
			})
			.toBe(true);
		const measurements = await measure();
		expect(measurements.noticeInsideDocument).toBe(false);
		expect(measurements.noticeTop).toBeGreaterThanOrEqual(
			measurements.navBottom,
		);
		expect(measurements.noticeBottom).toBeLessThan(measurements.documentTop);
		expect(measurements.headerBorder).toBe('0px');
		expect(measurements.overflow).toBe(false);
		if (width >= 1024) {
			expect(measurements.sidebarTop).toBe(measurements.documentTop);
			expect(measurements.toolbarRight).toBeLessThan(measurements.markersLeft);
			expect(measurements.sidebarWidth).toBe(width < 1200 ? 260 : 320);
		}
		await page.setViewportSize({
			width: width >= 1024 ? 390 : 1440,
			height: 1000,
		});
		await expect
			.poll(async () => {
				const measurements = await measure();
				return (
					measurements.noticeTop >= measurements.navBottom &&
					measurements.noticeBottom < measurements.documentTop
				);
			})
			.toBe(true);
		const noticeDocumentTop = (await measure()).documentTop;
		await page.locator('.whale-notice-close').click();
		await expect(page.locator('.whale-site-notice-bar')).toHaveCount(0);
		const dismissed = await page.evaluate(() => ({
			documentTop: document
				.querySelector('.whale-content')
				.getBoundingClientRect().top,
			wrapperTop: document
				.querySelector('.content-wrapper')
				.getBoundingClientRect().top,
		}));
		expect(dismissed.documentTop).toBeLessThan(noticeDocumentTop);
		expect(dismissed.documentTop).toBe(dismissed.wrapperTop);
		expect(await page.evaluate(() => window.noticeCookies)).toEqual([
			{ name: 'disable-notice', value: true },
		]);
		expect(pageErrors).toEqual([]);
	});
}
