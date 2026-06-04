import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { runInNewContext } from 'node:vm';

class TestElement {
	constructor(className = '') {
		this.className = className;
		this.dataset = {};
		this.parentElement = null;
		this.textContent = 'Share';
	}

	closest(selector) {
		if (selector === '.tools-share' && this.className.includes('tools-share')) {
			return this;
		}

		return this.parentElement?.closest(selector) || null;
	}
}

const createContext = ({ nativeShare } = {}) => {
	const readyCallbacks = [];
	const listeners = new Map();
	const copied = [];
	const shared = [];
	const config = new Map([
		['wgServer', '//wiki.example'],
		['wgNamespaceNumber', 4],
		['wgTitle', 'Project page'],
		['wgSiteName', 'Whale Wiki'],
		['wgFormattedNamespaces', { 4: 'Project' }],
		['wgScriptPath', '/w'],
		['wgArticleId', 42],
	]);
	const context = {
		document: {
			addEventListener: (type, listener) => {
				const typeListeners = listeners.get(type) || [];
				typeListeners.push(listener);
				listeners.set(type, typeListeners);
			},
		},
		location: { protocol: 'https:' },
		mw: {
			config: { get: (key) => config.get(key) },
			msg: (key) => key,
		},
		navigator: {},
		window: {
			setTimeout: (callback) => {
				context.pendingTimeout = callback;
			},
		},
		whale: {
			closest: (target, selector) => target.closest(selector),
			copyText: async (text) => {
				copied.push(text);
				return true;
			},
			ready: (callback) => readyCallbacks.push(callback),
		},
	};

	if (nativeShare) {
		context.navigator.share = async (data) => {
			shared.push(data);
		};
	}

	runInNewContext(readFileSync(resolve('js/share-button.js'), 'utf8'), context);
	for (const callback of readyCallbacks) {
		callback();
	}

	return { context, copied, listeners, shared };
};

const dispatchClick = async (listeners, target) => {
	let prevented = false;
	const event = {
		target,
		preventDefault: () => {
			prevented = true;
		},
	};
	await Promise.all(
		(listeners.get('click') || []).map((listener) => listener(event)),
	);
	return prevented;
};

const copyRun = createContext();
const copyButton = new TestElement('tools-share');
if (!(await dispatchClick(copyRun.listeners, copyButton))) {
	throw new Error('Share button should prevent default click behavior.');
}

if (
	copyRun.copied.join('|') !== 'https://wiki.example/w/index.php?curid=42' ||
	copyButton.textContent !== 'whale-share-copied'
) {
	throw new Error('Share fallback should copy the page URL and show feedback.');
}

copyRun.context.pendingTimeout();
if (copyButton.textContent !== 'Share') {
	throw new Error('Share feedback should restore the original button label.');
}

const nativeRun = createContext({ nativeShare: true });
const nativeButton = new TestElement('tools-share');
await dispatchClick(nativeRun.listeners, nativeButton);

if (
	nativeRun.shared.length !== 1 ||
	nativeRun.shared[0].title !== 'Project:Project page' ||
	nativeRun.shared[0].text !== 'Project:Project page - Whale Wiki' ||
	nativeRun.copied.length !== 0
) {
	throw new Error(
		'Native Web Share should receive share data without copying.',
	);
}
