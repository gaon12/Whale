import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { runInNewContext } from 'node:vm';

class TestElement {
	constructor(tagName, { id = '' } = {}) {
		this.tagName = tagName.toUpperCase();
		this.attributes = {};
		this.children = [];
		this.parentNode = null;
		this.hidden = false;
		this.textContent = '';

		if (id) {
			this.setAttribute('id', id);
		}
	}

	get href() {
		return this.attributes.href || '';
	}

	set href(value) {
		this.attributes.href = String(value);
	}

	get target() {
		return this.attributes.target || '';
	}

	set target(value) {
		this.attributes.target = String(value);
	}

	append(...nodes) {
		for (const node of nodes) {
			node.parentNode = this;
			this.children.push(node);
		}
	}

	setAttribute(name, value) {
		this.attributes[name] = String(value);
	}

	getAttribute(name) {
		return this.attributes[name] ?? null;
	}

	matches(selector) {
		if (selector.startsWith('#')) {
			return this.attributes.id === selector.slice(1);
		}
		if (selector === 'a[href]') {
			return this.tagName === 'A' && Boolean(this.attributes.href);
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

	closest(selector) {
		const selectors = selector.split(',').map((item) => item.trim());
		let node = this;
		while (node) {
			if (selectors.some((item) => node.matches(item))) {
				return node;
			}
			node = node.parentNode;
		}
		return null;
	}

	querySelector(selector) {
		const selectors = selector.split(',').map((item) => item.trim());
		const visit = (node) => {
			for (const child of node.children) {
				if (selectors.some((item) => child.matches(item))) {
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
}

class TestDocument {
	constructor() {
		this.body = new TestElement('body');
		this.listeners = new Map();
		this.openEvents = [];
	}

	addEventListener(type, listener) {
		const listeners = this.listeners.get(type) || [];
		listeners.push(listener);
		this.listeners.set(type, listeners);
	}

	dispatchEvent(event) {
		if (event.type === 'whale:openModal') {
			this.openEvents.push(event);
		}
		for (const listener of this.listeners.get(event.type) || []) {
			listener(event);
		}
	}

	dispatchClick(target, overrides = {}) {
		let prevented = false;
		const event = {
			type: 'click',
			target,
			button: 0,
			defaultPrevented: false,
			metaKey: false,
			ctrlKey: false,
			shiftKey: false,
			altKey: false,
			preventDefault: () => {
				prevented = true;
				event.defaultPrevented = true;
			},
			...overrides,
		};
		this.dispatchEvent(event);
		return prevented;
	}

	getElementById(id) {
		return this.body.querySelector(`#${id}`);
	}
}

const document = new TestDocument();
const readyCallbacks = [];
const modal = new TestElement('div', { id: 'whale-external-link-modal' });
const urlBox = new TestElement('div');
urlBox.setAttribute('data-whale-external-url', '');
const continueLink = new TestElement('a');
continueLink.setAttribute('data-whale-external-continue', '');
const toggle = new TestElement('button');
toggle.setAttribute('data-whale-external-toggle', '');
modal.append(urlBox, continueLink, toggle);
document.body.append(modal);

const createLink = (href, target = '') => {
	const link = new TestElement('a');
	link.setAttribute('href', href);
	if (target) {
		link.target = target;
	}
	document.body.append(link);
	return link;
};

const context = {
	document,
	console,
	CustomEvent: class CustomEvent {
		constructor(type, init = {}) {
			this.type = type;
			this.detail = init.detail;
		}
	},
	location: {
		href: 'https://wiki.example/wiki/Page',
		origin: 'https://wiki.example',
	},
	mw: {
		message: (key) => ({ text: () => key }),
	},
	URL,
	whale: {
		closest: (target, selector) => target.closest(selector),
		ready: (callback) => readyCallbacks.push(callback),
	},
};

runInNewContext(
	readFileSync(resolve('js/external-link-warning.js'), 'utf8'),
	context,
);
for (const callback of readyCallbacks) {
	callback();
}

const internalLink = createLink('/wiki/Internal');
if (document.dispatchClick(internalLink)) {
	throw new Error('Internal links should not be intercepted.');
}

const javascriptLink = createLink('javascript:alert(1)');
if (document.dispatchClick(javascriptLink)) {
	throw new Error('javascript: links should not open the external warning.');
}

const externalLink = createLink(
	'https://external.example/encoded%20path',
	'_self',
);
if (!document.dispatchClick(externalLink)) {
	throw new Error('External http(s) links should be intercepted.');
}

if (
	document.openEvents.length !== 1 ||
	document.openEvents[0].detail?.modal !== modal ||
	document.openEvents[0].detail?.trigger !== externalLink
) {
	throw new Error('External links should request the warning modal.');
}

if (
	continueLink.href !== 'https://external.example/encoded%20path' ||
	continueLink.target !== '_self' ||
	urlBox.textContent !== 'https://external.example/encoded%20path'
) {
	throw new Error('External modal should preserve the original target URL.');
}

if (toggle.hidden !== false) {
	throw new Error(
		'Encoded external URLs should offer a decoded preview toggle.',
	);
}

if (!document.dispatchClick(toggle)) {
	throw new Error('Decoded URL toggle should prevent default button behavior.');
}

if (
	urlBox.textContent !== 'https://external.example/encoded path' ||
	toggle.textContent !== 'whale-external-link-show-original'
) {
	throw new Error('Decoded URL toggle should switch the displayed URL only.');
}
