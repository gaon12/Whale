import { expect, test } from '@playwright/test';

const blockedScript = async (page, { source, src } = {}) => {
	await page.evaluate(
		({ source, src }) => {
			const script = document.createElement('script');
			script.type = 'text/rocketscript';
			if (src) script.src = src;
			if (source) script.textContent = source;
			document.head.append(script);
		},
		{ source, src },
	);
};

test('slow or failed modules cannot replace an existing runtime or callback queue', async ({
	page,
}) => {
	await page.clock.install();
	await page.setContent('<main>본문</main>');
	await page.evaluate(() => {
		window.originalRuntime = window.mw = {
			loader: { getState: () => 'loading' },
		};
		window.mediaWiki = window.mw;
		window.originalQueue = window.RLQ = [() => {}];
	});
	await blockedScript(page, {
		source: 'window.RLCONF = {}; window.replayed = true;',
	});
	await page.addScriptTag({ path: 'js/recovery.js' });
	await page.clock.runFor(7000);
	await expect
		.poll(() =>
			page.evaluate(
				() =>
					window.mw === window.originalRuntime &&
					window.RLQ === window.originalQueue &&
					!window.replayed,
			),
		)
		.toBe(true);
});

test('ordinary scripts are not replayed merely because startup is slow', async ({
	page,
}) => {
	await page.clock.install();
	await page.setContent(
		'<script>window.RLCONF = {}; window.executions = 1;</script><main>본문</main>',
	);
	await page.addScriptTag({ path: 'js/recovery.js' });
	await page.clock.runFor(7000);
	await expect.poll(() => page.evaluate(() => window.executions)).toBe(1);
	await expect(page.locator('html')).not.toHaveAttribute(
		'data-whale-resource-loader-recovery',
	);
});

test('blocked startup is recovered once while preserving queued callbacks', async ({
	page,
}) => {
	await page.clock.install();
	await page.route('http://whale.test/**', async (route) => {
		if (route.request().url().includes('load.php')) {
			await route.fulfill({
				contentType: 'text/javascript',
				body: `window.startups = (window.startups || 0) + 1;
				window.mw = { loader: { using: async (modules) => { window.requestedModules = modules; } } };
				window.mediaWiki = window.mw;
				window.RLQ.forEach(callback => callback());`,
			});
		} else {
			await route.fulfill({
				body: '<main>본문</main>',
				contentType: 'text/html',
			});
		}
	});
	await page.goto('http://whale.test/');
	await page.evaluate(() => {
		window.RLQ = [
			() => {
				window.callbackRuns = (window.callbackRuns || 0) + 1;
			},
		];
	});
	await blockedScript(page, { source: 'window.RLCONF = {};' });
	await blockedScript(page, {
		src: 'http://whale.test/load.php?modules=startup&only=scripts',
	});
	await page.addScriptTag({ path: 'js/recovery.js' });
	await page.clock.runFor(3500);
	await expect(page.locator('html')).toHaveAttribute(
		'data-whale-resource-loader-recovery',
		'complete',
	);
	await page.clock.runFor(7000);
	await expect
		.poll(() =>
			page.evaluate(() => ({
				startups: window.startups,
				callbacks: window.callbackRuns,
				modules: window.requestedModules,
			})),
		)
		.toEqual({ startups: 1, callbacks: 1, modules: ['skins.whale.layoutjs'] });
});

test('a startup that finishes during a recovery fetch keeps ownership', async ({
	page,
}) => {
	await page.clock.install();
	await page.route('http://whale.test/**', async (route) => {
		if (route.request().url().includes('load.php')) {
			await page.evaluate(() => {
				window.originalRuntime = window.mw = { loader: {} };
			});
			await route.fulfill({
				body: 'window.mw = {}; window.replayed = true;',
				contentType: 'text/javascript',
			});
		} else
			await route.fulfill({
				body: '<main>본문</main>',
				contentType: 'text/html',
			});
	});
	await page.goto('http://whale.test/');
	await blockedScript(page, {
		src: 'http://whale.test/load.php?modules=startup&only=scripts',
	});
	await page.addScriptTag({ path: 'js/recovery.js' });
	await page.clock.runFor(3500);
	await expect(page.locator('html')).toHaveAttribute(
		'data-whale-resource-loader-recovery',
		'runtime-started',
	);
	await expect
		.poll(() =>
			page.evaluate(
				() => window.mw === window.originalRuntime && !window.replayed,
			),
		)
		.toBe(true);
});
