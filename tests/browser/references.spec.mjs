import { expect, test } from '@playwright/test';
import { createServer } from 'node:http';
import { renderSkin, skinStyles } from './helpers.mjs';

let fixtureMarkup = '';
let fixtureBase;
const server = createServer((_request, response) => {
	response.setHeader('Content-Type', 'text/html; charset=utf-8');
	response.end(fixtureMarkup);
});

test.beforeAll(async () => {
	await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
	fixtureBase = `http://127.0.0.1:${server.address().port}`;
});

test.afterAll(async () => {
	await new Promise((resolve) => server.close(resolve));
});

const fixture = async (page, width = 1440, dark = false, enhanced = true) => {
	await page.setViewportSize({ width, height: 844 });
	const markup = await renderSkin({
		'html-title': '각주 예시',
		'data-whale-reference-modal': { title: '각주', 'close-label': '닫기' },
		'html-body-content': `<div id="mw-content-text"><div class="mw-parser-output">
		<p style="margin-top:180px;display:flex;justify-content:space-between">
		<span>첫째<sup class="reference" id="cite_ref-first"><a href="#cite_note-first">[1]</a></sup></span>
		<span>둘째<sup class="mw-ref" id="cite_ref-second"><a href="./Page#cite_note-second">[2]</a></sup></span></p>
		<p>반복<sup class="reference" id="cite_ref-repeat"><a href="#cite_note-first">[1]</a></sup></p>
		<p><a id="outside" href="#unrelated">일반 링크</a></p>
		<div style="height:900px"></div><p id="citation-source">문서 내 출처</p>
		<ol class="references"><li id="cite_note-first"><span class="mw-cite-backlink"><a href="#cite_ref-first">↑</a></span>
		<span class="reference-text" id="original-note" aria-labelledby="original-label">첫 번째 각주입니다. <b id="original-label">굵은 내용</b> <a href="#citation-source">내부 출처</a> <a class="external text" href="https://www.mediawiki.org/wiki/Extension:Cite">외부 출처</a></span></li></ol>
		<ol class="mw-references"><li id="cite_note-second"><span class="mw-reference-text">두 번째 각주는 다른 내용을 보여줍니다.</span></li></ol>
		</div></div>`,
	});
	fixtureMarkup = `<body class="Whale ${dark ? 'whale-dark' : ''}">${markup}</body>`;
	await page.goto(`${fixtureBase}/wiki/Page`);
	await page.addStyleTag({ content: await skinStyles() });
	if (enhanced) {
		await page.evaluate(() => {
			window.mw = {
				message: (key, number) => ({
					text: () =>
						key === 'whale-reference-title-numbered'
							? `각주 ${number}`
							: '각주',
				}),
				cookie: { set: () => {} },
			};
		});
		for (const path of [
			'js/common.js',
			'js/layout.js',
			'js/reference-modal.js',
		]) {
			await page.addScriptTag({ path });
		}
	}
};

for (const width of [320, 390, 768, 1440]) {
	for (const dark of [false, true]) {
		test(`each reference opens its own readable preview at ${width}px in ${dark ? 'dark' : 'light'}`, async ({
			page,
		}) => {
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));
			await fixture(page, width, dark);
			const mobile = width < 1024;
			const preview = page.locator(
				mobile ? '#whale-reference-modal' : '#whale-reference-popover',
			);
			const content = preview.locator('.whale-reference-content');
			const first = page.locator('#cite_ref-first a');
			await first.click();
			await expect(preview).toBeVisible();
			await expect(content).toContainText('[1] 첫 번째 각주입니다.');
			await expect(
				content.getByRole('link', { name: '외부 출처' }),
			).toHaveAttribute(
				'href',
				'https://www.mediawiki.org/wiki/Extension:Cite',
			);
			await expect(content.locator('[id], [aria-labelledby]')).toHaveCount(0);
			await expect(page.locator('#original-note')).toHaveAttribute(
				'aria-labelledby',
				'original-label',
			);
			expect(new URL(page.url()).hash).toBe('');
			if (mobile) {
				await expect
					.poll(async () => {
						const box = await preview
							.locator('.whale-reference-modal-content')
							.boundingBox();
						return Math.abs(box.y + box.height / 2 - 422);
					})
					.toBeLessThan(2);
			}
			const bounds = await preview
				.locator(
					mobile
						? '.whale-reference-modal-content'
						: '.whale-reference-content',
				)
				.boundingBox();
			expect(bounds.x).toBeGreaterThanOrEqual(0);
			expect(bounds.x + bounds.width).toBeLessThanOrEqual(width);
			expect(bounds.y).toBeGreaterThanOrEqual(0);
			expect(bounds.y + bounds.height).toBeLessThanOrEqual(844);
			if (mobile) {
				await expect(
					preview.getByRole('button', { name: '닫기', exact: true }),
				).toBeVisible();
				await expect(preview.locator('.whale-modal-header')).toHaveCount(0);
				expect(Math.abs(bounds.y + bounds.height / 2 - 422)).toBeLessThan(2);
				await preview
					.getByRole('button', { name: '닫기', exact: true })
					.click();
			} else {
				await page.keyboard.press('Escape');
				await expect(page.locator('.whale-modal-backdrop')).toHaveCount(0);
			}
			await expect(preview).toBeHidden();
			await expect(first).toBeFocused();
			await page.locator('#cite_ref-second a').click();
			await expect(preview).toBeVisible();
			await expect(content).toHaveText(
				'[2] 두 번째 각주는 다른 내용을 보여줍니다.',
			);
			await expect(content).not.toContainText('첫 번째 각주');
			await page.keyboard.press('Escape');
			await expect(preview).toBeHidden();
			await page.locator('#cite_ref-repeat a').click();
			await expect(content).toContainText('[1] 첫 번째 각주입니다.');
			expect(errors).toEqual([]);
		});
	}
}

test('desktop hover allows entering the popup and keyboard activation exposes its links', async ({
	page,
}) => {
	await fixture(page);
	const first = page.locator('#cite_ref-first a');
	const preview = page.locator('#whale-reference-popover');
	await first.hover();
	await expect(preview).toBeVisible();
	await preview.getByRole('link', { name: '내부 출처' }).hover();
	await expect(preview).toBeVisible();
	await page.locator('#outside').hover();
	await expect(preview).toBeHidden();
	await first.focus();
	await expect(preview).toBeVisible();
	await first.press('Enter');
	await expect(preview).toBeFocused();
	await page.keyboard.press('Tab');
	await expect(
		preview.getByRole('link', { name: '[1]', exact: true }),
	).toBeFocused();
	await page.keyboard.press('Escape');
	await expect(preview).toBeHidden();
	await expect(first).toBeFocused();
});

test('large notes stay scrollable and a responsive switch closes the old preview', async ({
	page,
}) => {
	await fixture(page);
	await page
		.locator('#original-note')
		.evaluate((node) =>
			node.append(document.createTextNode(' 길고공백없는내용'.repeat(500))),
		);
	const first = page.locator('#cite_ref-first a');
	await first.click();
	const preview = page.locator('#whale-reference-popover');
	await expect(preview).toBeVisible();
	const dimensions = await preview
		.locator('.whale-reference-content')
		.evaluate((node) => ({
			height: node.clientHeight,
			scrollHeight: node.scrollHeight,
			width: node.clientWidth,
			scrollWidth: node.scrollWidth,
		}));
	expect(dimensions.scrollHeight).toBeGreaterThan(dimensions.height);
	expect(dimensions.scrollWidth).toBeLessThanOrEqual(dimensions.width);
	await page.setViewportSize({ width: 390, height: 844 });
	await expect(preview).toBeHidden();
	await first.click();
	await expect(page.locator('#whale-reference-modal')).toBeVisible();
	await page.keyboard.press('Escape');
	await expect(first).toBeFocused();
});

test('unresolved notes and missing enhancement retain native anchor navigation', async ({
	page,
}) => {
	await fixture(page);
	await page
		.locator('#cite_ref-first a')
		.evaluate((node) => node.setAttribute('href', '#unresolved'));
	await page.locator('#cite_ref-first a').click();
	expect(new URL(page.url()).hash).toBe('#unresolved');
	await expect(page.locator('#whale-reference-popover')).toBeHidden();
	await fixture(page, 390, false, false);
	await page.locator('#cite_ref-first a').click();
	expect(new URL(page.url()).hash).toBe('#cite_note-first');
	await expect(page.locator('#whale-reference-modal')).toBeHidden();
});

test('modified clicks preserve opening the original footnote in a new tab', async ({
	page,
}) => {
	await fixture(page);
	const [opened] = await Promise.all([
		page.context().waitForEvent('page'),
		page.locator('#cite_ref-first a').click({ modifiers: ['Control'] }),
	]);
	await opened.waitForLoadState();
	await expect(opened).toHaveURL(`${fixtureBase}/wiki/Page#cite_note-first`);
	await opened.close();
});

test('the inline number jumps to the currently displayed reference', async ({
	page,
}) => {
	await fixture(page);
	await page.evaluate(() => {
		const scroll = window.whale.scrollToTarget;
		window.referenceJumps = [];
		window.whale.scrollToTarget = (target) => {
			window.referenceJumps.push(target.id);
			scroll(target);
		};
	});
	await page.locator('#cite_ref-second a').click();
	await page
		.locator('#whale-reference-popover')
		.getByRole('link', { name: '[2]', exact: true })
		.click();
	await expect(page.locator('#whale-reference-popover')).toBeHidden();
	expect(await page.evaluate(() => window.referenceJumps)).toEqual([
		'cite_note-second',
	]);
});

test('mobile focus stays in the modal and dismissing the backdrop restores the source', async ({
	page,
}) => {
	await fixture(page, 390);
	const source = page.locator('#cite_ref-first a');
	const modal = page.locator('#whale-reference-modal');
	await source.click();
	await expect(modal.locator('[data-whale-reference-content]')).toBeFocused();
	await page.keyboard.press('Shift+Tab');
	await expect(
		modal.getByRole('button', { name: '닫기', exact: true }),
	).toBeFocused();
	await page.keyboard.press('Tab');
	await expect(modal.locator('[data-whale-reference-content]')).toBeFocused();
	await page.mouse.click(4, 4);
	await expect(modal).toBeHidden();
	await expect(source).toBeFocused();
});
