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
		await expect(page.locator('.whale-home-column')).toHaveCount(2);
		const columns = await page
			.locator('.whale-home-column')
			.evaluateAll((elements) =>
				elements.map((element) => {
					const rect = element.getBoundingClientRect();
					return { top: rect.top, right: rect.right, left: rect.left };
				}),
			);
		expect(columns[0].top).toBeCloseTo(columns[1].top);
		expect(columns[0].right).toBeLessThan(columns[1].left);
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

	test('the native mobile bar stays compact while its menu and search remain usable', async ({
		page,
	}) => {
		await page.setViewportSize({ width: 390, height: 844 });
		await page.goto(`${base}/index.php?title=대문&useskin=whale`, {
			waitUntil: 'domcontentloaded',
		});
		await expect(page.locator('.whale-mobile-nav-toggle')).toHaveAttribute(
			'aria-label',
			'탐색 메뉴',
		);
		await page.waitForFunction(
			() => window.mw?.loader.getState('skins.whale.layoutjs') === 'ready',
		);
		expect(
			(await page.locator('.whale-nav-wrapper').boundingBox()).height,
		).toBeLessThanOrEqual(56);
		await page.locator('.whale-mobile-nav-toggle').click();
		await expect(page.locator('.whale-navbar-menu')).toBeVisible();
		await page.keyboard.press('Escape');
		await page.locator('.whale-mobile-search-toggle').click();
		await expect(page.locator('#searchInput')).toBeFocused();
		expect(
			(await page.locator('.whale-nav-wrapper').boundingBox()).height,
		).toBeLessThanOrEqual(56);
		await page.locator('.whale-mobile-search-close').click();
		await expect(page.locator('.whale-mobile-search-toggle')).toBeFocused();
		expect(
			await page.evaluate(
				() => document.body.scrollWidth > document.documentElement.clientWidth,
			),
		).toBe(false);
	});

	test('a real redirect notice preserves access to the original redirect page', async ({
		page,
	}) => {
		await page.goto(
			`${base}/index.php?title=${encodeURIComponent('넘겨주기 예시')}&useskin=whale`,
			{ waitUntil: 'domcontentloaded' },
		);
		await expect(page.locator('#firstHeading')).toHaveText(
			'상위 문서/넘겨주기 도착',
		);
		const notice = page.locator('.mw-redirectedfrom');
		await expect(notice).toBeVisible();
		await expect(notice).toHaveCSS('border-top-style', 'solid');
		await expect(page.locator('.contentSub .mw-redirectedfrom')).toHaveCount(0);
		await expect(page.locator('.contentSub .subpages')).toContainText(
			'상위 문서',
		);
		expect(
			await page.evaluate(() => {
				const heading = document
					.querySelector('.whale-content-heading')
					.getBoundingClientRect();
				const notice = document
					.querySelector('.whale-redirect-notice')
					.getBoundingClientRect();
				const categories = document
					.querySelector('#catlinks')
					.getBoundingClientRect();
				return (
					notice.top >= heading.bottom + 12 &&
					notice.bottom <= categories.top &&
					Math.abs(notice.left - categories.left) < 1 &&
					Math.abs(notice.width - categories.width) < 1
				);
			}),
		).toBe(true);
		const origin = notice.getByRole('link', {
			name: '넘겨주기 예시',
			exact: true,
		});
		expect(
			new URL(await origin.getAttribute('href'), base).searchParams.get(
				'redirect',
			),
		).toBe('no');
		await origin.click();
		await expect(page.locator('#firstHeading')).toHaveText('넘겨주기 예시');
		await expect(page.locator('.mw-redirectedfrom')).toHaveCount(0);
		await page
			.locator('#mw-content-text')
			.getByRole('link', { name: '상위 문서/넘겨주기 도착', exact: true })
			.click();
		await expect(page.locator('#firstHeading')).toHaveText(
			'상위 문서/넘겨주기 도착',
		);
		await expect(page.locator('.mw-redirectedfrom')).toHaveCount(0);
		await expect(page.locator('.whale-redirect-notice')).toHaveCount(0);
		await expect(page.locator('.contentSub .subpages')).toContainText(
			'상위 문서',
		);
	});

	for (const width of [1440, 390]) {
		test(`native Cite notes show distinct content in the ${width < 1024 ? 'mobile modal' : 'desktop popup'}`, async ({
			page,
		}) => {
			await page.setViewportSize({ width, height: 900 });
			await page.goto(
				`${base}/index.php?title=${encodeURIComponent('각주 예시')}&useskin=whale`,
				{ waitUntil: 'domcontentloaded' },
			);
			await page.waitForFunction(
				() => window.mw?.loader.getState('skins.whale.articlejs') === 'ready',
			);
			const markers = page.locator(
				'#mw-content-text .reference, #mw-content-text .mw-ref',
			);
			const preview = page.locator(
				width < 1024 ? '#whale-reference-modal' : '#whale-reference-popover',
			);
			await markers
				.getByRole('link', { name: '[1]', exact: true })
				.first()
				.click();
			await expect(preview).toBeVisible();
			await expect(preview.locator('.whale-reference-content')).toContainText(
				'[1] 첫 번째 각주 내용입니다.',
			);
			await expect(
				preview.getByRole('link', { name: '출처', exact: true }),
			).toHaveAttribute(
				'href',
				'https://www.mediawiki.org/wiki/Extension:Cite',
			);
			expect(new URL(page.url()).hash).toBe('');
			await page.keyboard.press('Escape');
			await expect(preview).toBeHidden();
			await markers.getByRole('link', { name: '[2]', exact: true }).click();
			await expect(preview.locator('.whale-reference-content')).toHaveText(
				'[2] 두 번째 각주는 다른 내용을 보여줍니다.',
			);
			if (width < 1024) {
				await preview
					.getByRole('button', { name: '닫기', exact: true })
					.click();
			} else {
				await page.keyboard.press('Escape');
			}
			await expect(preview).toBeHidden();
			await markers
				.getByRole('link', { name: '[1]', exact: true })
				.last()
				.click();
			await expect(preview.locator('.whale-reference-content')).toContainText(
				'[1] 첫 번째 각주 내용입니다.',
			);
		});
	}

	for (const width of [390, 1440]) {
		test(`native tables keep their title and centered controls while folding at ${width}px`, async ({
			page,
		}) => {
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));
			await page.setViewportSize({ width, height: 844 });
			await page.goto(`${base}/index.php?title=표_예시&useskin=whale`);
			await page.waitForFunction(
				() => window.mw?.loader.getState('skins.whale.tables') === 'ready',
			);
			const empty = page.locator('#license-example tr').first().locator('td');
			await expect(empty).toHaveClass(/whale-empty-cell/);
			expect((await empty.boundingBox()).height).toBeLessThanOrEqual(6);
			await expect(empty).toHaveCSS('background-color', 'rgb(0, 128, 0)');
			for (const id of ['navigation-example', 'caption-example']) {
				const table = page.locator(`#${id}`);
				const toggle = table.locator(
					'.whale-table-toggle-bar .mw-collapsible-toggle',
				);
				await expect(toggle).toBeVisible();
				await expect(toggle).toHaveAttribute('aria-expanded', 'false');
				const title = table.locator('.whale-table-folding-title');
				const titleBox = await title.boundingBox();
				const bar = await toggle.boundingBox();
				expect(bar.y).toBeGreaterThanOrEqual(titleBox.y + titleBox.height);
				expect(bar.width).toBeGreaterThanOrEqual(
					(await table.boundingBox()).width - 2,
				);
				await toggle.press('Enter');
				await expect(toggle).toHaveAttribute('aria-expanded', 'true');
				await expect(table.locator('tbody tr').last()).toBeVisible();
				await toggle.click();
				await expect(table.locator('tbody tr').last()).toBeHidden();
				await expect(title).toBeVisible();
			}
			await expect(page.locator('#navigation-example th')).toHaveAttribute(
				'colspan',
				'3',
			);
			await expect(page.locator('#navigation-example tr')).toHaveCount(2);
			await page.waitForFunction(
				() =>
					document.getElementById('sorting-example')?.dataset.whaleSortable ===
					'ready',
			);
			await page.locator('#sorting-example th').first().click();
			await expect(
				page.locator('#sorting-example tbody tr').last(),
			).toContainText('Z');
			const foldToggle = page.locator('.whale-folding-toggle');
			await foldToggle.click();
			await expect(page.locator('.whale-folding-body')).toBeVisible();
			await foldToggle.click();
			await expect(page.locator('.whale-folding-body')).toBeHidden();
			expect(errors).toEqual([]);
		});
	}

	for (const width of [390, 768]) {
		test(`native wide tables can scroll to their final column at ${width}px`, async ({
			page,
		}) => {
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));
			await page.setViewportSize({ width, height: 844 });
			await page.goto(`${base}/index.php?title=표_예시&useskin=whale`);
			await page.waitForFunction(
				() => window.mw?.loader.getState('skins.whale.tables') === 'ready',
			);
			const folded = page.locator('#fold-overflow-example');
			const toggle = folded.locator('.mw-collapsible-toggle');
			await expect(toggle).toBeVisible();
			await expect(toggle).toHaveAttribute('aria-expanded', 'false');
			await toggle.click();
			await expect(toggle).toHaveAttribute('aria-expanded', 'true');
			for (const id of ['wide-overflow-example', 'fold-overflow-example']) {
				const table = page.locator(`#${id}`);
				const frame = table.locator('..');
				await expect(frame).toHaveClass('whale-table-wrapper');
				await expect(table).toHaveCSS('display', 'table');
				await expect(table).toHaveCSS('clip-path', 'none');
				expect(
					await frame.evaluate((el) => el.scrollWidth - el.clientWidth),
				).toBeGreaterThan(100);
				await frame.hover();
				await page.mouse.wheel(10000, 0);
				await expect
					.poll(() => frame.evaluate((el) => el.scrollLeft))
					.toBeGreaterThan(100);
				await expect
					.poll(() =>
						table
							.locator('tr')
							.last()
							.locator('td')
							.last()
							.evaluate((el) => el.getBoundingClientRect().right),
					)
					.toBeLessThanOrEqual(
						(await frame.boundingBox()).x +
							(await frame.boundingBox()).width +
							1,
					);
			}
			expect(
				await page.evaluate(
					() =>
						document.body.scrollWidth > document.documentElement.clientWidth,
				),
			).toBe(false);
			expect(errors).toEqual([]);
		});
	}

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

	for (const remember of [false, true]) {
		test(`the native login modal authenticates with remember-me ${remember ? 'checked' : 'unchecked'}`, async ({
			page,
		}) => {
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));
			await page.setViewportSize({ width: remember ? 390 : 1440, height: 844 });
			await page.goto(`${base}/index.php?title=대문&useskin=whale`);
			await page.waitForFunction(
				() =>
					window.mw?.loader.getState('skins.whale.loginjs') === 'ready' &&
					window.mw?.loader.getState('skins.whale.layoutjs') === 'ready',
			);
			await page.locator('[data-whale-target="#whale-login-modal"]').click();
			await expect(page.locator('#whale-login-modal')).toBeVisible();
			await page
				.locator('#whale-login-form [name="wpName"]')
				.fill('FixtureAdmin');
			await page
				.locator('#whale-login-form [name="wpPassword"]')
				.fill('Whale-local-fixture-2026');
			await page.locator('#wpRemember').setChecked(remember);
			const resultPromise = page.waitForResponse(
				(response) =>
					response.url().includes('/api.php') &&
					new URLSearchParams(response.request().postData() || '').get(
						'action',
					) === 'clientlogin',
				{ timeout: 10000 },
			);
			await page.locator('.whale-login-submit').click();
			const result = await (await resultPromise).json();
			expect(result.error || result.clientlogin?.status).toBe('PASS');
			await expect(page.locator('#login-menu')).toHaveAttribute(
				'title',
				'FixtureAdmin',
			);
			await expect(page.locator('#whale-login-modal')).toHaveCount(0);
			expect(errors).toEqual([]);
		});
	}

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
