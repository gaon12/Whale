import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { runInNewContext } from 'node:vm';

class TestElement {
	constructor(tagName, className = '') {
		this.tagName = tagName.toUpperCase();
		this.attributes = {};
		this.children = [];
		this.parentNode = null;
		this.className = className;
		this.dataset = {};
		this.hidden = false;
		this.textContent = '';
		this.value = '';
		this.focused = false;
		this.selected = false;
	}

	setAttribute(name, value) {
		this.attributes[name] = String(value);
	}

	matches(selector) {
		if (selector.startsWith('.')) {
			return this.className.split(/\s+/).includes(selector.slice(1));
		}

		const attrMatch = selector.match(/^\[([^=\]]+)(?:="([^"]*)")?\]$/);
		if (attrMatch) {
			const [, name, value] = attrMatch;
			return value === undefined
				? Object.hasOwn(this.attributes, name)
				: this.attributes[name] === value;
		}

		return this.tagName.toLowerCase() === selector.toLowerCase();
	}

	append(...nodes) {
		for (const node of nodes) {
			node.parentNode = this;
			this.children.push(node);
		}
	}

	closest(selector) {
		let node = this;
		while (node) {
			if (node.matches(selector)) {
				return node;
			}
			node = node.parentNode;
		}

		return null;
	}

	querySelector(selector) {
		const visit = (node) => {
			for (const child of node.children) {
				if (child.matches(selector)) {
					return child;
				}

				const match = visit(child);
				if (match) {
					return match;
				}
			}

			return null;
		};

		return visit(this);
	}

	focus() {
		this.focused = true;
	}

	select() {
		this.selected = true;
	}
}

class TestDocument {
	constructor() {
		this.body = new TestElement('body');
		this.listeners = new Map();
	}

	addEventListener(type, listener) {
		const listeners = this.listeners.get(type) || [];
		listeners.push(listener);
		this.listeners.set(type, listeners);
	}

	querySelector(selector) {
		return this.body.querySelector(selector);
	}

	async dispatchClick(target) {
		let prevented = false;
		const event = {
			target,
			preventDefault: () => {
				prevented = true;
			},
		};

		await Promise.all(
			(this.listeners.get('click') || []).map((listener) => listener(event)),
		);

		return prevented;
	}
}

const document = new TestDocument();
const readyCallbacks = [];
const modal = new TestElement('div', 'whale-short-url-modal');
const input = new TestElement(
	'input',
	'whale-form-control whale-short-url-value',
);
const button = new TestElement('button', 'whale-btn whale-short-url-copy');
const status = new TestElement('p', 'whale-short-url-status');

status.setAttribute('data-whale-short-url-status', '');
status.hidden = true;
input.value = 'https://wiki.example/s/Ab1';
button.textContent = 'Copy';
modal.append(input, button, status);
document.body.append(modal);

let copyResult = false;
const context = {
	document,
	console,
	mw: {
		msg: (key) =>
			({
				'whale-short-url-copied': 'Copied',
				'whale-short-url-copy-failed': 'Copy failed',
			})[key] || key,
	},
	whale: {
		closest: (target, selector) => target.closest(selector),
		copyText: async () => copyResult,
		ready: (callback) => readyCallbacks.push(callback),
	},
	window: {
		setTimeout: () => {},
	},
};

runInNewContext(readFileSync(resolve('js/short-url.js'), 'utf8'), context);
for (const callback of readyCallbacks) {
	callback();
}

if (!(await document.dispatchClick(button))) {
	throw new Error('Short URL copy button should prevent the default click.');
}

if (status.hidden || status.textContent !== 'Copy failed') {
	throw new Error('Copy failure should show a visible status message.');
}

if (!input.focused || !input.selected) {
	throw new Error(
		'Copy failure should focus and select the URL for manual copy.',
	);
}

copyResult = true;
input.focused = false;
input.selected = false;

await document.dispatchClick(button);

if (!status.hidden || status.textContent !== '') {
	throw new Error('Successful copy should clear the failure status.');
}

if (button.textContent !== 'Copied') {
	throw new Error('Successful copy should still update the button label.');
}
