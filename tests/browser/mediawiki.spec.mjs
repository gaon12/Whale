import { expect, test } from '@playwright/test';

const base = process.env.WHALE_MEDIAWIKI_BASE_URL;
test.describe('MediaWiki integration', () => {
	test.skip(!base, 'Requires an isolated MediaWiki installation.');
	test.setTimeout(60000);
	test.beforeEach(async ({ page }) => {
		// Fixtures use only core and skin assets, without external dependencies.
		await page.route(/^https?:/, (route) =>
			new URL(route.request().url()).origin === new URL(base).origin
				? route.continue()
				: route.fulfill({ body: '', contentType: 'text/javascript' }),
		);
	});

	test('the real skin renders and ResourceLoader initializes without runtime errors', async ({
		page,
	}) => {
		const errors = [];
		page.on('pageerror', (error) => errors.push(error.message));
		await page.goto(`${base}/index.php?title=대문&useskin=whale`, {
			waitUntil: 'domcontentloaded',
		});
		await expect(page.locator('#firstHeading')).toBeVisible();
		await expect(page.locator('#mw-content-text')).toBeVisible();
		await expect(page.locator('.whale-document-metadata')).toBeVisible();
		await expect(page.locator('.whale-document-metadata')).toContainText(
			'최근 수정 시각:',
		);
		await expect(
			page.locator('#whale-login-modal .whale-modal-close'),
		).toHaveAttribute('aria-label', '닫기');
		await expect
			.poll(
				() =>
					page.evaluate(() =>
						Object.fromEntries(
							[
								'mediawiki.api',
								'skins.whale.layoutjs',
								'skins.whale.liverc',
								'skins.whale.headingAnchors',
							].map((name) => [name, window.mw?.loader.getState(name)]),
						),
					),
				{ timeout: 30000 },
			)
			.toEqual({
				'mediawiki.api': 'ready',
				'skins.whale.layoutjs': 'ready',
				'skins.whale.liverc': 'ready',
				'skins.whale.headingAnchors': 'ready',
			});
		await expect(page.locator('.whale-home-section')).toHaveCount(4);
		await expect(page.locator('.whale-home .whale-heading-anchor')).toHaveCount(
			0,
		);
		await expect(page.locator('.whale-home-title')).toHaveText(
			'가온 위키에 오신 것을 환영합니다',
		);
		await expect(
			page.locator('.whale-sidebar .recent-item-title').first(),
		).toBeVisible({ timeout: 10000 });
		expect(errors).toEqual([]);
	});

	test('normal API parsing invokes folding hooks while preserving literal examples', async ({
		request,
	}) => {
		const response = await request.post(`${base}/api.php`, {
			form: {
				action: 'parse',
				format: 'json',
				prop: 'text',
				contentmodel: 'wikitext',
				text: '{{{#!folding Fold\nBody\n}}}\n<nowiki>{{{#!folding Literal}}}</nowiki>',
			},
		});
		expect(response.ok()).toBe(true);
		const data = await response.json();
		const html = data.parse?.text?.['*'];
		expect(html).toContain('whale-folding');
		expect(html).toContain('#!folding Literal');
		expect(html).not.toContain(' hidden');
		expect(html).not.toContain('<button');
	});

	test('registered theme changes persist through the actual authenticated options API', async ({
		page,
	}) => {
		const tokens = await page.request.get(`${base}/api.php`, {
			params: {
				action: 'query',
				meta: 'tokens',
				type: 'login',
				format: 'json',
			},
		});
		const token = (await tokens.json()).query.tokens.logintoken;
		const login = await page.request.post(`${base}/api.php`, {
			form: {
				action: 'clientlogin',
				username: 'FixtureAdmin',
				password: 'Whale-local-fixture-2026',
				logintoken: token,
				loginreturnurl: `${base}/index.php`,
				format: 'json',
			},
		});
		expect((await login.json()).clientlogin.status).toBe('PASS');
		const csrf = await page.request.get(`${base}/api.php`, {
			params: { action: 'query', meta: 'tokens', format: 'json' },
		});
		const reset = await page.request.post(`${base}/api.php`, {
			form: {
				action: 'options',
				token: (await csrf.json()).query.tokens.csrftoken,
				optionname: 'whale-dark',
				optionvalue: 'light',
				format: 'json',
			},
		});
		expect((await reset.json()).options).toBe('success');
		await page.goto(`${base}/index.php?title=대문&useskin=whale`, {
			waitUntil: 'domcontentloaded',
		});
		await expect(page.locator('body')).toHaveAttribute(
			'data-whale-theme-storage',
			'user',
		);
		await page.waitForFunction(
			() => window.mw?.loader.getState('skins.whale.themeToggle') === 'ready',
		);
		await page.locator('.whale-tools-toggle').click();
		await page.locator('[data-whale-theme-toggle]').click();
		await expect(page.locator('body')).toHaveClass(/\bwhale-dark\b/);
		await expect(page.locator('body')).toHaveAttribute(
			'data-whale-theme-mode',
			'dark',
		);
		await expect
			.poll(async () => {
				const response = await page.request.get(`${base}/api.php`, {
					params: {
						action: 'query',
						meta: 'userinfo',
						uiprop: 'options',
						format: 'json',
					},
				});
				return (await response.json()).query.userinfo.options['whale-dark'];
			})
			.toBe('dark');
		await page.reload({ waitUntil: 'domcontentloaded' });
		await expect(page.locator('body')).toHaveAttribute(
			'data-whale-theme-mode',
			'dark',
		);
	});
});
