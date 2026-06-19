import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { runInNewContext } from 'node:vm';

class TestClassList {
	constructor(element) {
		this.element = element;
		this.items = new Set();
	}

	add(...names) {
		for (const name of names) {
			this.items.add(name);
		}
		this.sync();
	}

	remove(...names) {
		for (const name of names) {
			this.items.delete(name);
		}
		this.sync();
	}

	contains(name) {
		return this.items.has(name);
	}

	toggle(name, force) {
		const shouldAdd = force ?? !this.items.has(name);
		if (shouldAdd) {
			this.items.add(name);
		} else {
			this.items.delete(name);
		}
		this.sync();
		return shouldAdd;
	}

	sync() {
		this.element.attributes.class = [...this.items].join(' ');
	}
}

class TestElement {
	constructor(tagName, { className = '', id = '' } = {}) {
		this.tagName = tagName.toUpperCase();
		this.attributes = {};
		this.children = [];
		this.parentNode = null;
		this.classList = new TestClassList(this);
		this.hidden = false;

		if (className) {
			this.className = className;
		}
		if (id) {
			this.id = id;
		}
	}

	get className() {
		return this.attributes.class || '';
	}

	set className(value) {
		this.classList.items = new Set(String(value).split(/\s+/).filter(Boolean));
		this.classList.sync();
	}

	get id() {
		return this.attributes.id || '';
	}

	set id(value) {
		this.setAttribute('id', value);
	}

	append(...nodes) {
		for (const node of nodes) {
			node.parentNode = this;
			this.children.push(node);
		}
	}

	setAttribute(name, value) {
		this.attributes[name] = String(value);
		if (name === 'class') {
			this.className = value;
		}
	}

	getAttribute(name) {
		return this.attributes[name] ?? null;
	}

	hasAttribute(name) {
		return Object.hasOwn(this.attributes, name);
	}

	querySelector(selector) {
		return this.querySelectorAll(selector)[0] || null;
	}

	querySelectorAll(selector) {
		const selectors = selector.split(',').map((item) => item.trim());
		const matches = [];
		const visit = (node) => {
			for (const child of node.children) {
				if (selectors.some((item) => child.matches(item))) {
					matches.push(child);
				}
				visit(child);
			}
		};
		visit(this);
		return matches;
	}

	matches(selector) {
		if (selector.startsWith('.')) {
			return this.classList.contains(selector.slice(1));
		}
		if (selector.startsWith('#')) {
			return this.id === selector.slice(1);
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
}

class TestDocument extends TestElement {
	constructor() {
		super('document');
		this.body = new TestElement('body');
		this.documentElement = new TestElement('html');
		this.readyState = 'complete';
		this.listeners = new Map();
		this.append(this.body);
	}

	createElement(tagName) {
		return new TestElement(tagName);
	}

	addEventListener(type, listener) {
		const listeners = this.listeners.get(type) || [];
		listeners.push(listener);
		this.listeners.set(type, listeners);
	}

	dispatch(type, event) {
		for (const listener of this.listeners.get(type) || []) {
			listener(event);
		}
	}

	getElementById(id) {
		return this.querySelector(`#${id}`);
	}
}

const document = new TestDocument();
const readyCallbacks = [];
const context = {
	document,
	console,
	mw: {
		cookie: { set: () => {} },
		message: (key) => ({ text: () => key }),
	},
	window: {
		clearTimeout,
		matchMedia: () => ({ matches: true }),
		PointerEvent: function PointerEvent() {},
		requestAnimationFrame: (callback) => callback(),
		scrollTo: () => {},
		setTimeout: (callback) => callback(),
	},
};

context.whale = {
	closest: (target, selector) => target.closest(selector),
	getNavHeight: () => 0,
	rafThrottle: (callback) => callback,
	ready: (callback) => readyCallbacks.push(callback),
	scrollToTarget: () => {},
};
context.window.whale = context.whale;
document.body.classList.add(
	'whale-content-skeleton-enabled',
	'whale-content-skeleton-loading',
);

const container = new TestElement('div', {
	className: 'whale-section-container is-collapsed',
});
const heading = new TestElement('h2', {
	className: 'whale-section-heading is-collapsed',
});
const toggle = new TestElement('button', {
	className: 'whale-section-toggle',
});
toggle.setAttribute('aria-controls', 'section-body');
toggle.setAttribute('aria-expanded', 'false');
toggle.setAttribute('data-expand-label', 'Expand');
toggle.setAttribute('data-collapse-label', 'Collapse');
const body = new TestElement('div', { id: 'section-body' });
body.hidden = true;
const headline = new TestElement('span', { className: 'mw-headline' });
const editSection = new TestElement('span', { className: 'mw-editsection' });
const editLink = new TestElement('a');
headline.textContent = 'Heading';
editSection.append(editLink);
heading.append(toggle, headline, editSection);
container.append(heading, body);
document.body.append(container);

runInNewContext(readFileSync(resolve('js/layout.js'), 'utf8'), context);
for (const callback of readyCallbacks) {
	callback();
}

if (document.body.classList.contains('whale-content-skeleton-loading')) {
	throw new Error('Content skeleton loading state should clear after ready.');
}

document.dispatch('click', {
	target: toggle,
	preventDefault: () => {},
});

if (body.hidden) {
	throw new Error('Section body should become visible after clicking toggle.');
}

if (
	toggle.getAttribute('aria-expanded') !== 'true' ||
	toggle.getAttribute('aria-label') !== 'Collapse'
) {
	throw new Error('Section toggle ARIA state should switch to expanded.');
}

if (
	heading.classList.contains('is-collapsed') ||
	container.classList.contains('is-collapsed')
) {
	throw new Error(
		'Section heading and container should clear collapsed state.',
	);
}

document.dispatch('click', {
	target: toggle,
	preventDefault: () => {},
});

if (
	!body.hidden ||
	toggle.getAttribute('aria-expanded') !== 'false' ||
	!heading.classList.contains('is-collapsed') ||
	!container.classList.contains('is-collapsed')
) {
	throw new Error(
		'Section toggle should collapse body, heading, and container.',
	);
}

document.dispatch('pointerup', {
	target: toggle,
	pointerType: 'touch',
	preventDefault: () => {},
	stopPropagation: () => {},
});

if (
	body.hidden ||
	toggle.getAttribute('aria-expanded') !== 'true' ||
	heading.classList.contains('is-collapsed') ||
	container.classList.contains('is-collapsed')
) {
	throw new Error('Touch pointerup should expand the section immediately.');
}

document.dispatch('click', {
	target: toggle,
	preventDefault: () => {},
});

if (body.hidden || toggle.getAttribute('aria-expanded') !== 'true') {
	throw new Error(
		'Synthetic click after touch pointerup should be suppressed.',
	);
}

document.dispatch('click', {
	target: headline,
	preventDefault: () => {},
});

if (
	!body.hidden ||
	toggle.getAttribute('aria-expanded') !== 'false' ||
	!heading.classList.contains('is-collapsed') ||
	!container.classList.contains('is-collapsed')
) {
	throw new Error('Clicking the section heading should collapse the section.');
}

document.dispatch('click', {
	target: editLink,
	preventDefault: () => {
		throw new Error('Section edit links should keep their default behavior.');
	},
});

if (
	!body.hidden ||
	toggle.getAttribute('aria-expanded') !== 'false' ||
	!heading.classList.contains('is-collapsed') ||
	!container.classList.contains('is-collapsed')
) {
	throw new Error('Section edit links should not toggle the section.');
}
