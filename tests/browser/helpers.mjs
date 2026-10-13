import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import less from 'less';

export const skinStyles = async () => {
	const filename = resolve('less/default.less');
	return (await less.render(await readFile(filename, 'utf8'), { filename }))
		.css;
};

export const runtime = async (page) => {
	await page.evaluate(() => {
		const hooks = new Map();
		window.whale = {
			ready: (callback) => callback(),
			rafThrottle: (callback) => callback,
			getNavHeight: () => 48,
			getAnchorTarget: (href) =>
				document.getElementById(decodeURIComponent(href.slice(1))),
			scrollToTarget: (target) => target.scrollIntoView(),
		};
		window.mw = {
			config: { get: () => 'ko' },
			message: (key) => ({
				text: () =>
					({
						'whale-live-recent-error': '최근 변경을 불러오지 못했습니다.',
						'whale-live-recent-retry': '다시 시도',
						'whale-live-recent-no-data': '표시할 내용이 없습니다',
					})[key] || key,
			}),
			util: { getUrl: (title) => `/wiki/${encodeURIComponent(title)}` },
			hook: (name) => {
				if (!hooks.has(name)) {
					const callbacks = [];
					hooks.set(name, {
						add: (callback) => callbacks.push(callback),
						fire: (value) =>
							callbacks.forEach((callback) => {
								callback(value);
							}),
					});
				}
				return hooks.get(name);
			},
		};
	});
};
