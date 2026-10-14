import { expect, test } from '@playwright/test';
import { renderSkin, skinStyles } from './helpers.mjs';

const fixture = async (page, width, registered = false, enhanced = true) => {
	await page.setViewportSize({ width, height: 844 });
	const markup = await renderSkin({
		'whale-close-label': '닫기',
		'html-title': '문서',
		'html-body-content': '<div id="mw-content-text"><p>본문</p></div>',
		'data-whale-nav': {
			'brand-logo':
				'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="140" height="32"%3E%3Crect width="140" height="32" fill="white"/%3E%3C/svg%3E',
			'brand-alt': '가온 위키',
			'navigation-label': '탐색 메뉴',
			'html-navigation-icon': '<span aria-hidden="true">☰</span>',
			items: [
				...['최근 바뀜', '랜덤 문서', '도구·도움말'].map((label) => ({
					classes: 'whale-navbar-item',
					'html-link': `<a class="whale-navbar-link" href="#content"><span class="hide-title">${label}</span></a>`,
				})),
				{
					classes: 'whale-navbar-item whale-dropdown',
					'html-link':
						'<button class="whale-navbar-link" data-whale-toggle="dropdown" aria-expanded="false"><span class="hide-title">커뮤니티</span></button>',
					'has-children': true,
					children: [
						{
							classes: '',
							'html-link':
								'<a class="whale-dropdown-item" href="#content">위키방</a>',
						},
					],
				},
			],
			'has-notifications': registered,
			notifications: ['알림', '메시지'].map((label) => ({
				html: `<li><a href="#content" aria-label="${label}">●</a></li>`,
			})),
			'data-login': {
				'is-registered': registered,
				'user-name': '사용자',
				'html-avatar': '<span class="profile-img">U</span>',
				'login-title': '로그인',
				'html-sign-in-icon': '<span aria-hidden="true">U</span>',
				'logout-label': '로그아웃',
				'logout-href': '#logout',
				'html-sign-out-icon': '나가기',
			},
			'data-search': {
				action: 'https://whale.test/w/index.php',
				'title-value': '특수:검색',
				placeholder: '가온 위키 검색',
				'search-label': '검색',
				'go-label': '보기',
				'html-search-icon': '<span aria-hidden="true">⌕</span>',
				'html-eye-icon': '<span aria-hidden="true">◉</span>',
			},
		},
	});
	await page.route('https://whale.test/**', (route) =>
		route.fulfill({
			contentType: 'text/html; charset=utf-8',
			body: `<body class="Whale whale-mobile-user-tools-right">${markup}</body>`,
		}),
	);
	await page.goto('https://whale.test/');
	await page.addStyleTag({ content: await skinStyles() });
	if (enhanced) {
		await page.evaluate(() => {
			window.mw = {
				message: (key) => ({ text: () => key }),
				cookie: { set: () => {} },
			};
		});
		await page.addScriptTag({ path: 'js/common.js' });
		await page.addScriptTag({ path: 'js/layout.js' });
	}
};

for (const width of [320, 390, 768, 1023]) {
	for (const registered of [false, true]) {
		test(`mobile navigation stays in one row at ${width}px for ${registered ? 'registered' : 'anonymous'} readers`, async ({
			page,
		}) => {
			const errors = [];
			page.on('pageerror', (error) => errors.push(error.message));
			await fixture(page, width, registered);
			const nav = page.locator('.whale-nav-wrapper');
			const height = (await nav.boundingBox()).height;
			expect(height).toBeLessThanOrEqual(56);
			await expect(page.locator('#searchInput')).toBeHidden();
			await page.getByRole('button', { name: '탐색 메뉴' }).click();
			await expect(
				page.getByRole('link', { name: '최근 바뀜', exact: true }),
			).toBeVisible();
			await page.getByRole('button', { name: '커뮤니티', exact: true }).click();
			await expect(
				page.getByRole('link', { name: '위키방', exact: true }),
			).toBeVisible();
			expect((await nav.boundingBox()).height).toBe(height);
			await page.keyboard.press('Escape');
			await expect(
				page.getByRole('button', { name: '탐색 메뉴' }),
			).toBeFocused();
			await expect(page.locator('.whale-navbar-menu')).toBeHidden();
			await page.locator('.whale-mobile-search-toggle').click();
			await expect(page.locator('#searchInput')).toBeFocused();
			await page.locator('#searchInput').fill('메카시티:ZERO');
			expect((await nav.boundingBox()).height).toBe(height);
			await page.keyboard.press('Escape');
			await expect(page.locator('.whale-mobile-search-toggle')).toBeFocused();
			await page.locator('#firstHeading').click();
			await page.keyboard.press('/');
			await expect(page.locator('#searchInput')).toBeFocused();
			await expect(page.locator('#searchInput')).toHaveValue('메카시티:ZERO');
			await page.locator('.whale-mobile-search-close').click();
			await expect(page.locator('.whale-mobile-search-toggle')).toBeFocused();
			expect(
				await page.evaluate(
					() =>
						document.body.scrollWidth > document.documentElement.clientWidth,
				),
			).toBe(false);
			await page.locator('.whale-mobile-search-toggle').click();
			await page.setViewportSize({ width: 1440, height: 1000 });
			await expect(page.locator('.whale-mobile-search-toggle')).toBeHidden();
			await expect(page.locator('#searchInput')).toBeVisible();
			await expect(page.locator('.whale-navbar-menu')).toBeVisible();
			await page.setViewportSize({ width, height: 844 });
			await expect(page.locator('#searchInput')).toBeHidden();
			expect(errors).toEqual([]);
		});
	}
}

for (const dark of [false, true]) {
	test(`mobile menu hover and keyboard focus stay readable in ${dark ? 'dark' : 'light'} mode`, async ({
		page,
	}) => {
		await fixture(page, 390);
		if (dark) {
			await page.evaluate(() => document.body.classList.add('whale-dark'));
		}
		await page.getByRole('button', { name: '탐색 메뉴' }).click();
		const link = page.getByRole('link', { name: '최근 바뀜', exact: true });
		const foreground = await link.evaluate(
			(element) => getComputedStyle(element).color,
		);
		await page.getByRole('button', { name: '커뮤니티', exact: true }).click();
		for (const control of [
			link,
			page.getByRole('button', { name: '커뮤니티', exact: true }),
			page.getByRole('link', { name: '위키방', exact: true }),
		]) {
			for (const state of ['hover', 'focus']) {
				await page.mouse.move(0, 0);
				await page.getByRole('button', { name: '탐색 메뉴' }).focus();
				if (state === 'hover') {
					await control.hover();
				} else {
					await control.focus();
				}
				await expect(control).toHaveCSS('color', foreground);
				const colors = await control.evaluate((element) => ({
					foreground: getComputedStyle(element).color,
					background: getComputedStyle(element).backgroundColor,
					surface: getComputedStyle(
						element.closest('.whale-dropdown-menu, .whale-navbar-menu'),
					).backgroundColor,
				}));
				const rgb = (color) => color.match(/[\d.]+/g).map(Number);
				const luminance = (color) => {
					const channels = color.slice(0, 3).map((value) => {
						const normalized = value / 255;
						return normalized <= 0.04045
							? normalized / 12.92
							: ((normalized + 0.055) / 1.055) ** 2.4;
					});
					return (
						channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722
					);
				};
				const background = rgb(colors.background);
				const surface = rgb(colors.surface);
				const alpha = background[3] ?? 1;
				const paintedBackground = background
					.slice(0, 3)
					.map((value, index) => value * alpha + surface[index] * (1 - alpha));
				const values = [
					luminance(rgb(colors.foreground)),
					luminance(paintedBackground),
				];
				expect(
					(Math.max(...values) + 0.05) / (Math.min(...values) + 0.05),
				).toBeGreaterThanOrEqual(4.5);
			}
		}
	});
}

for (const button of ['#searchGoButton', '#mw-searchButton']) {
	test(`mobile search keeps the native form submission through ${button}`, async ({
		page,
	}) => {
		await fixture(page, 390);
		await page.locator('.whale-mobile-search-toggle').click();
		await page.locator('#searchInput').fill('메카시티:ZERO');
		await Promise.all([
			page.waitForURL('https://whale.test/w/index.php?*'),
			page.locator(button).click(),
		]);
		const query = new URL(page.url()).searchParams;
		expect(query.get('title')).toBe('특수:검색');
		expect(query.get('search')).toBe('메카시티:ZERO');
		expect(query.has(button === '#searchGoButton' ? 'go' : 'fulltext')).toBe(
			true,
		);
	});
}

test('search and navigation remain available before JavaScript initializes', async ({
	page,
}) => {
	await fixture(page, 390, false, false);
	await expect(page.locator('#searchInput')).toBeVisible();
	await expect(page.locator('.whale-navbar-menu')).toBeVisible();
});
