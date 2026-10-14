import { expect, test } from '@playwright/test';
import { renderSkin, skinStyles } from './helpers.mjs';

function contrastRatio(foreground, background) {
	const luminance = (color) => {
		const channels = color.match(/\d+/g).slice(0, 3).map(Number);
		const linear = channels.map((channel) => {
			const value = channel / 255;
			return value <= 0.04045
				? value / 12.92
				: ((value + 0.055) / 1.055) ** 2.4;
		});
		return linear[0] * 0.2126 + linear[1] * 0.7152 + linear[2] * 0.0722;
	};
	const values = [luminance(foreground), luminance(background)].sort(
		(a, b) => b - a,
	);
	return (values[0] + 0.05) / (values[1] + 0.05);
}

for (const width of [320, 390, 768, 1440]) {
	for (const dark of [false, true]) {
		test(`redirect origins stay distinct and readable at ${width}px in ${dark ? 'dark' : 'light'} mode`, async ({
			page,
		}) => {
			await page.setViewportSize({ width, height: 1000 });
			const origin = '길고_공백이_없는_넘겨주기_문서_이름_'.repeat(5);
			const originalHref = `/w/index.php?title=${encodeURIComponent(origin)}&redirect=no`;
			const markup = await renderSkin({
				'html-title': '도착 문서',
				'html-whale-last-modified': '최근 수정 시각: 2026년 10월 14일',
				'html-subtitle':
					'<div id="mw-content-subtitle"><div class="subpages">&lt; <a href="#parent">상위 문서</a></div></div>',
				'has-whale-redirect-notice': true,
				'html-whale-redirect-notice': `
				<span class="mw-redirectedfrom"><a href="#help">넘겨주기</a>&nbsp;: <a href="${originalHref}" class="mw-redirect">${origin}</a></span>
				<span class="mw-redirectedfrom">(<a href="/w/index.php?title=3.1절&amp;redirect=no" class="mw-redirect">3.1절</a>에서 넘어옴)</span>`,
				'html-categories':
					'<div class="catlinks" id="catlinks"><a href="#categories">분류</a>: 예시</div>',
				'data-whale-content-tools': {
					'has-content-tools': true,
					buttons: [
						{ html: '<a class="whale-btn tools-btn" href="#edit">편집</a>' },
					],
				},
				'html-body-content': '<div id="mw-content-text"><p>본문</p></div>',
			});
			await page.setContent(
				`<body class="Whale ${dark ? 'whale-dark' : ''}">${markup}</body>`,
			);
			await page.addStyleTag({ content: await skinStyles() });
			const notices = page.locator('.mw-redirectedfrom');
			await expect(notices).toHaveCount(2);
			await expect(page.locator('.contentSub .mw-redirectedfrom')).toHaveCount(
				0,
			);
			const placement = await page.evaluate(() => {
				const heading = document
					.querySelector('.whale-content-heading')
					.getBoundingClientRect();
				const notice = document
					.querySelector('.whale-redirect-notice')
					.getBoundingClientRect();
				const categories = document
					.querySelector('#catlinks')
					.getBoundingClientRect();
				return {
					gap: notice.top - heading.bottom,
					widthDifference: Math.abs(notice.width - categories.width),
					leftDifference: Math.abs(notice.left - categories.left),
					beforeCategories: notice.bottom <= categories.top,
				};
			});
			expect(placement.gap).toBeGreaterThanOrEqual(12);
			expect(placement.widthDifference).toBeLessThan(1);
			expect(placement.leftDifference).toBeLessThan(1);
			expect(placement.beforeCategories).toBe(true);
			await expect(notices.first().locator('a.mw-redirect')).toHaveAttribute(
				'href',
				originalHref,
			);
			await expect(notices.last()).toHaveText('(3.1절에서 넘어옴)');
			// Link colors transition when the fixture stylesheet is attached.
			await expect
				.poll(async () => {
					const colors = await notices.evaluateAll((elements) =>
						elements.map((element) => ({
							background: getComputedStyle(element).backgroundColor,
							foreground: [element, ...element.querySelectorAll('a')].map(
								(node) => getComputedStyle(node).color,
							),
						})),
					);
					return Math.min(
						...colors.flatMap(({ foreground, background }) =>
							foreground.map((color) => contrastRatio(color, background)),
						),
					);
				})
				.toBeGreaterThanOrEqual(4.5);
			const geometry = await notices.evaluateAll((elements) =>
				elements.map((element) => {
					const style = getComputedStyle(element);
					const bounds = element.getBoundingClientRect();
					const source = element.querySelector('a.mw-redirect');
					return {
						left: bounds.left,
						right: bounds.right,
						height: bounds.height,
						background: style.backgroundColor,
						borders: ['Top', 'Right', 'Bottom', 'Left'].map(
							(side) => style[`border${side}Width`],
						),
						sourceWeight: getComputedStyle(source).fontWeight,
						iconContent: getComputedStyle(element, '::before').content,
					};
				}),
			);
			for (const notice of geometry) {
				expect(notice.background).not.toBe('rgba(0, 0, 0, 0)');
				expect(new Set(notice.borders).size).toBe(1);
				expect(notice.sourceWeight).toBe('600');
				expect(notice.iconContent).toBe('""');
				expect(notice.left).toBeGreaterThanOrEqual(0);
				expect(notice.right).toBeLessThanOrEqual(width);
				expect(notice.height).toBeGreaterThan(30);
			}
			await expect(page.locator('.subpages')).toHaveCSS(
				'background-color',
				'rgba(0, 0, 0, 0)',
			);
			await expect(page.locator('.contentSub')).toHaveCSS(
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
