import { expect, test } from '@playwright/test';
import { runtime } from './helpers.mjs';

test('direct images, nested images and content-hook roots receive identical preparation', async ({
	page,
}) => {
	await page.setContent(
		'<main id="mw-content-text"></main><aside><img id="outside"></aside>',
	);
	await runtime(page);
	await page.addScriptTag({ path: 'js/lazy-images.js' });
	await page.evaluate(() => {
		const root = document.getElementById('mw-content-text');
		const image = document.createElement('img');
		image.id = 'direct';
		root.append(image);
		const wrapper = document.createElement('div');
		wrapper.innerHTML = '<img id="nested">';
		root.append(wrapper);
		mw.hook('wikipage.content').fire([image]);
	});
	for (const id of ['direct', 'nested']) {
		await expect(page.locator(`#${id}`)).toHaveAttribute('loading', 'lazy');
		await expect(page.locator(`#${id}`)).toHaveAttribute('decoding', 'async');
		await expect(page.locator(`.whale-lazy-image-shell #${id}`)).toHaveCount(1);
	}
	await expect(page.locator('.whale-lazy-image-shell')).toHaveCount(2);
	await expect(page.locator('#outside')).not.toHaveAttribute('loading');
	await page.locator('#direct').dispatchEvent('load');
	await expect(page.locator('.whale-lazy-image-shell.is-loading')).toHaveCount(
		1,
	);
});

test('explicit eager loading survives repeated content preparation', async ({
	page,
}) => {
	await page.setContent(
		'<main id="mw-content-text"><img id="lead" loading="eager"></main>',
	);
	await runtime(page);
	await page.addScriptTag({ path: 'js/lazy-images.js' });
	await page.evaluate(() =>
		mw.hook('wikipage.content').fire([document.getElementById('lead')]),
	);
	await expect(page.locator('#lead')).toHaveAttribute('loading', 'eager');
	await expect(page.locator('.whale-lazy-image-shell')).toHaveCount(1);
});
