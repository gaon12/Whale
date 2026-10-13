import { expect, test } from '@playwright/test';
import { renderSkin, skinStyles } from './helpers.mjs';

export const documentFixture = async (page, width = 1440) => {
	await page.setViewportSize({ width, height: width < 1024 ? 844 : 1000 });
	const markup = await renderSkin({
		'has-whale-sidebar': true,
		'has-whale-site-notice': true,
		'html-whale-site-notice': '자주자주 공지를 확인하세요!',
		'whale-close-label': '닫기',
		'html-title': '라이믹스',
		'html-whale-last-modified': '최근 수정 시각: 2026년 8월 21일',
		'data-whale-content-tools': {
			'has-content-tools': true,
			'more-label': '문서 도구 더 보기',
			buttons: ['편집', '토론', '문서 역사'].map((label) => ({
				html: `<a class="whale-btn tools-btn" href="#">${label}</a>`,
			})),
		},
		'html-body-content': `<div id="mw-content-text"><div class="mw-parser-output">
			<table class="infobox" style="width:26em"><tr><th colspan="2">라이믹스</th></tr>
			<tr><th scope="row" style="width:1%">원작자</th><td>XE 개발팀 및 기여자</td></tr>
			<tr><th scope="row" style="width:1%">최신(안정화) 버전</th><td>2.1.36 / 2026년 8월 12일</td></tr></table>
			<p>영어 문서는 Rhymix 문서를 참고할 수 있다.</p><h2 id="overview">개요</h2>
			<p>라이믹스는 PHP로 개발된 오픈 소스 콘텐츠 관리 시스템입니다.</p>
		</div></div>`,
	});
	await page.setContent(`<body class="Whale">${markup}</body>`);
	await page.addStyleTag({ content: await skinStyles() });
};

for (const width of [320, 390, 768, 1024, 1440]) {
	test(`document hierarchy and infobox remain readable at ${width}px`, async ({
		page,
	}) => {
		await documentFixture(page, width);
		await expect(page.locator('#firstHeading')).toHaveText('라이믹스');
		await expect(page.locator('.whale-document-metadata')).toBeVisible();
		const measurements = await page.evaluate(() => {
			const title = document
				.getElementById('firstHeading')
				.getBoundingClientRect();
			const metadata = document
				.querySelector('.whale-document-metadata')
				.getBoundingClientRect();
			const content = document.querySelector('.whale-content-main');
			const labels = [...document.querySelectorAll('.infobox th[scope="row"]')];
			return {
				metadataBelowTitle: metadata.top >= title.bottom,
				fontSize: getComputedStyle(content).fontSize,
				lineHeight: Number.parseFloat(getComputedStyle(content).lineHeight),
				labels: labels.map((label) => ({
					width: label.clientWidth,
					height: label.clientHeight,
				})),
				overflow:
					document.body.scrollWidth > document.documentElement.clientWidth,
			};
		});
		expect(measurements.metadataBelowTitle).toBe(true);
		expect(measurements.fontSize).toBe('16px');
		expect(measurements.lineHeight).toBeCloseTo(27.2);
		expect(
			measurements.labels.every(
				(label) => label.width >= 110 && label.height < 80,
			),
		).toBe(true);
		expect(measurements.overflow).toBe(false);
	});
}
