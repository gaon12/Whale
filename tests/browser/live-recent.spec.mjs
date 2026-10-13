import { expect, test } from '@playwright/test';
import { runtime, skinStyles } from './helpers.mjs';

const setup = async (page) => {
	await page.clock.install();
	await page.setContent(`<body class="Whale"><main class="content-wrapper">
		<aside class="whale-sidebar"><div class="live-recent" data-refresh-interval="60000">
			<button role="tab" aria-controls="articles">문서</button>
			<button role="tab" aria-controls="talk">토론</button>
			<div id="articles" class="live-recent-feed" data-namespaces="0">
				<div class="live-recent-progress"><span class="live-recent-progress-bar"></span></div>
				<ul class="live-recent-list"></ul>
			</div>
			<div id="talk" class="live-recent-feed" data-namespaces="1" hidden>
				<div class="live-recent-progress"><span class="live-recent-progress-bar"></span></div>
				<ul class="live-recent-list"></ul>
			</div>
		</div></aside></main></body>`);
	await page.addStyleTag({ content: await skinStyles() });
	await runtime(page);
	await page.evaluate(() => {
		window.requests = [];
		window.whale.getApi = async () => ({
			get: async (params) => {
				window.requests.push(params);
				if (window.failRequest) throw new Error('offline');
				if (window.slowRequest)
					await new Promise((resolve) => {
						window.resolveRequest = resolve;
					});
				return {
					query: {
						recentchanges: window.emptyResult
							? []
							: [
									{
										title: '유지되어야 할 문서',
										timestamp: new Date().toISOString(),
										type: 'edit',
									},
								],
					},
				};
			},
		});
	});
	await page.addScriptTag({ path: 'js/live-recent.js' });
	await expect(page.locator('#articles .recent-item-title')).toHaveText(
		'유지되어야 할 문서',
	);
};

for (const reducedMotion of ['no-preference', 'reduce']) {
	test(`polling uses elapsed time with ${reducedMotion} motion`, async ({
		page,
	}) => {
		await page.emulateMedia({ reducedMotion });
		await setup(page);
		await page.clock.runFor(3000);
		await expect
			.poll(() => page.evaluate(() => window.requests.length))
			.toBe(1);
		// Even an animation event fired by CSS must have no network side effect.
		await page
			.locator('#articles .live-recent-progress-bar')
			.dispatchEvent('animationend');
		await expect
			.poll(() => page.evaluate(() => window.requests.length))
			.toBe(1);
		await page.clock.runFor(60000);
		await expect
			.poll(() => page.evaluate(() => window.requests.length))
			.toBe(2);
	});
}

test('failed refresh preserves data, backs off and allows an explicit retry', async ({
	page,
}) => {
	await setup(page);
	await page.evaluate(() => {
		window.failRequest = true;
	});
	await page.clock.runFor(61000);
	await expect(page.locator('#articles .live-recent-status')).toBeVisible();
	await expect(page.locator('#articles .recent-item-title')).toHaveText(
		'유지되어야 할 문서',
	);
	await expect(page.locator('#articles')).not.toContainText(
		'표시할 내용이 없습니다',
	);
	await page.clock.runFor(60000);
	await expect.poll(() => page.evaluate(() => window.requests.length)).toBe(2);
	await page.evaluate(() => {
		window.failRequest = false;
	});
	await page.getByRole('button', { name: '다시 시도' }).click();
	await expect(page.locator('#articles .live-recent-status')).toBeHidden();
	await expect.poll(() => page.evaluate(() => window.requests.length)).toBe(3);
});

test('switching tabs during a slow request keeps one request and one timer', async ({
	page,
}) => {
	await setup(page);
	await page.evaluate(() => {
		window.slowRequest = true;
	});
	await page.getByRole('tab', { name: '토론' }).click();
	await page.getByRole('tab', { name: '문서' }).click();
	await page.getByRole('tab', { name: '토론' }).click();
	await expect.poll(() => page.evaluate(() => window.requests.length)).toBe(2);
	await page.evaluate(() => {
		window.slowRequest = false;
		window.resolveRequest();
	});
	await expect(page.locator('#talk .recent-item-title')).toHaveText(
		'유지되어야 할 문서',
	);
	await page.clock.runFor(61000);
	await expect.poll(() => page.evaluate(() => window.requests.length)).toBe(3);
});

test('a truly empty response has a different state from an initial failure', async ({
	page,
}) => {
	await setup(page);
	await page.evaluate(() => {
		window.failRequest = true;
	});
	await page.getByRole('tab', { name: '토론' }).click();
	await expect(page.locator('#talk .live-recent-status')).toBeVisible();
	await expect(page.locator('#talk')).not.toContainText(
		'표시할 내용이 없습니다',
	);
	await page.evaluate(() => {
		window.failRequest = false;
		window.emptyResult = true;
	});
	await page.getByRole('button', { name: '다시 시도' }).click();
	await expect(page.locator('#talk .live-recent-no-data')).toHaveText(
		'표시할 내용이 없습니다',
	);
	await expect(page.locator('#talk .live-recent-status')).toBeHidden();
});
