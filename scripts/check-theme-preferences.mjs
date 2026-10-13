import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(
	new URL('../js/theme-toggle.js', import.meta.url),
	'utf8',
);
const run = ({
	mode = 'auto',
	user = false,
	systemDark = false,
	cookie = null,
	failSave = false,
} = {}) => {
	const classes = new Set(mode === 'dark' ? ['whale-dark'] : []);
	const events = new Map();
	const saved = [];
	const notices = [];
	const button = {
		setAttribute() {},
		querySelector() {},
		querySelectorAll: () => [],
	};
	const media = { matches: systemDark };
	let onMediaChange;
	const context = {
		document: {
			body: {
				dataset: {
					whaleThemeMode: mode,
					whaleThemeStorage: user ? 'user' : 'browser',
				},
				classList: {
					contains: (name) => classes.has(name),
					toggle: (name, enabled) =>
						enabled ? classes.add(name) : classes.delete(name),
				},
			},
			documentElement: { style: {} },
			querySelector: () => null,
			querySelectorAll: (selector) =>
				selector === '[data-whale-theme-toggle]' ? [button] : [],
			addEventListener: (type, callback) => events.set(type, callback),
		},
		location: { protocol: 'https:' },
		window: {
			matchMedia: () => media,
			localStorage: { getItem: () => cookie, setItem() {} },
		},
		mw: {
			cookie: { get: () => cookie, set: (_name, value) => saved.push(value) },
			message: (key) => ({ text: () => key }),
			notify: (message) => notices.push(message),
		},
		whale: {
			ready: (callback) => callback(),
			closest: (target) => target,
			onMediaChange: (_media, callback) => {
				onMediaChange = callback;
			},
			getApi: async () => ({
				postWithToken: async (token, params) => {
					if (failSave) throw new Error('Save failed');
					saved.push({ token, ...params });
				},
			}),
		},
	};
	runInNewContext(source, context);
	return {
		classes,
		saved,
		notices,
		button,
		click: () => events.get('click')({ target: button, preventDefault() {} }),
		changeSystem: (dark) => {
			media.matches = dark;
			onMediaChange();
		},
	};
};

const dark = run({ user: true, mode: 'dark', cookie: 'light' });
assert(
	dark.classes.has('whale-dark'),
	'An account dark preference must beat a light system and stale cookie.',
);
dark.changeSystem(false);
assert(
	dark.classes.has('whale-dark'),
	'Explicit account preferences must ignore system changes.',
);
await dark.click();
assert.equal(dark.saved[0].optionname, 'whale-dark');
assert.equal(dark.saved[0].optionvalue, 'light');
assert.equal(dark.saved[0].token, 'csrf');
assert(!dark.classes.has('whale-dark'));

const light = run({
	user: true,
	mode: 'light',
	systemDark: true,
	cookie: 'dark',
});
assert(
	!light.classes.has('whale-dark'),
	'An account light preference must beat a dark system and cookie.',
);
const automatic = run({ user: true, cookie: 'dark' });
assert(
	!automatic.classes.has('whale-dark'),
	'Account auto mode must ignore browser overrides.',
);
automatic.changeSystem(true);
assert(automatic.classes.has('whale-dark'));
assert(automatic.classes.has('whale-auto-dark'));

const anon = run({ cookie: 'dark' });
assert(anon.classes.has('whale-dark'));
await anon.click();
assert.equal(anon.saved[0], 'light');

const failed = run({ user: true, mode: 'dark', failSave: true });
await failed.click();
assert(
	failed.classes.has('whale-dark'),
	'Failed preference saves must restore the previous mode.',
);
assert.equal(failed.notices.length, 1);
assert.equal(failed.button.disabled, false);
