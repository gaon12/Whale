import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { runInNewContext } from 'node:vm';

const source = readFileSync(resolve('js/recovery.js'), 'utf8');

const runRecovery = ({ loaderStarted, scripts }) => {
	const scheduled = [];
	const appended = [];
	const documentElement = { dataset: {} };
	const document = {
		documentElement,
		head: {
			append: (script) => {
				appended.push(script);
			},
		},
		readyState: 'loading',
		addEventListener: () => {},
		createElement: () => ({
			remove: () => {},
		}),
		querySelectorAll: (selector) => (selector === 'script' ? scripts : []),
	};
	const localStorage = {
		removeItem: () => {},
		setItem: () => {},
	};
	const window = {
		localStorage,
		mw: loaderStarted ? { loader: {} } : undefined,
		setTimeout: (callback) => {
			scheduled.push(callback);
		},
	};

	runInNewContext(source, {
		AbortController,
		URLSearchParams,
		document,
		fetch: () => Promise.reject(new Error('Unexpected fetch.')),
		window,
	});
	scheduled[0]();

	return { appended, documentElement };
};

const activeScript = {
	dataset: {},
	getAttribute: () => 'text/javascript',
	hasAttribute: () => false,
	src: '/w/load.php?modules=startup&only=scripts',
};
const started = runRecovery({
	loaderStarted: true,
	scripts: [activeScript],
});
if (started.appended.length !== 0) {
	throw new Error('Recovery must not replay scripts after mw.loader starts.');
}

let inertRemoved = false;
const inertScript = {
	dataset: {},
	getAttribute: (name) => (name === 'type' ? 'abc-text/javascript' : ''),
	hasAttribute: (name) => name === 'data-cf-settings',
	remove: () => {
		inertRemoved = true;
	},
	src: '',
	text: 'RLCONF={};',
};
const stalled = runRecovery({
	loaderStarted: false,
	scripts: [inertScript],
});
await Promise.resolve();
await Promise.resolve();

if (
	stalled.appended.length !== 1 ||
	!inertRemoved ||
	stalled.documentElement.dataset.whaleResourceLoaderRecovery !== 'attempted'
) {
	throw new Error('Recovery should replay only an inert Rocket Loader script.');
}
