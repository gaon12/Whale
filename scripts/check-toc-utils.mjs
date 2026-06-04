import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { runInNewContext } from 'node:vm';

class TestElement {
	constructor(tagName, options = {}) {
		this.tagName = tagName;
		this.classes = options.classes || [];
		this.attributes = options.attributes || {};
		this.ownText = options.text || '';
		this.children = options.children || [];
		this.parentElement = null;
		for (const child of this.children) {
			child.parentElement = this;
		}
	}

	get classList() {
		return this.classes;
	}

	get textContent() {
		return `${this.ownText}${this.children
			.map((child) => child.textContent)
			.join('')}`;
	}

	set textContent(value) {
		this.ownText = value;
		this.children = [];
	}

	closest(selector) {
		if (selector === 'li') {
			let node = this;
			while (node) {
				if (node.tagName === 'li') {
					return node;
				}
				node = node.parentElement;
			}
		}

		return null;
	}

	querySelector(selector) {
		return this.querySelectorAll(selector)[0] || null;
	}

	querySelectorAll(selector) {
		const selectors = selector.split(',').map((item) => item.trim());
		const matches = [];

		const visit = (node) => {
			for (const child of node.children) {
				if (
					selectors.some(
						(item) =>
							item.startsWith('.') && child.classes.includes(item.slice(1)),
					)
				) {
					matches.push(child);
				}
				visit(child);
			}
		};

		visit(this);
		return matches;
	}

	cloneNode(deep) {
		const clone = new TestElement(this.tagName, {
			attributes: { ...this.attributes },
			classes: [...this.classes],
			text: this.ownText,
			children: deep ? this.children.map((child) => child.cloneNode(true)) : [],
		});

		for (const child of clone.children) {
			child.parentElement = clone;
		}

		return clone;
	}

	remove() {
		if (!this.parentElement) {
			return;
		}

		this.parentElement.children = this.parentElement.children.filter(
			(child) => child !== this,
		);
		this.parentElement = null;
	}
}

const source = readFileSync(resolve('js/toc-utils.js'), 'utf8');
const context = { window: {} };
runInNewContext(source, context);

const {
	formatTocNumber,
	getLinkText,
	getTargetText,
	getTocLevel,
	getTocNumber,
} = context.window.whale.tocUtils;

const tocLink = new TestElement('a', {
	children: [
		new TestElement('span', { classes: ['tocnumber'], text: '2.1' }),
		new TestElement('span', { classes: ['toctext'], text: 'Wrong TOC label' }),
	],
});
const tocItem = new TestElement('li', {
	classes: ['toclevel-3'],
	children: [tocLink],
});
tocLink.parentElement = tocItem;

const heading = new TestElement('h2', {
	children: [
		new TestElement('span', {
			classes: ['mw-headline'],
			text: '  Correct   section  ',
			children: [
				new TestElement('span', {
					classes: ['whale-heading-number'],
					text: '2.1 ',
				}),
				new TestElement('span', {
					classes: ['whale-heading-anchor'],
					text: '#',
				}),
				new TestElement('span', {
					classes: ['mw-editsection'],
					text: 'edit',
				}),
			],
		}),
	],
});

if (getTocLevel(tocLink) !== 3) {
	throw new Error('TOC level should come from the closest toclevel class.');
}

if (getTocNumber(tocLink) !== '2.1') {
	throw new Error('Nested TOC numbers should keep dotted numbering.');
}

if (getLinkText(tocLink, heading) !== '2.1 Correct section') {
	throw new Error(
		'Floating TOC label should include the cleaned section number.',
	);
}

if (getTargetText(heading) !== 'Correct section') {
	throw new Error(
		'Heading helper should remove numbers, anchors, and edit links.',
	);
}

const deepLink = new TestElement('a');
const deepItem = new TestElement('li', {
	classes: ['toclevel-9'],
	children: [deepLink],
});
deepLink.parentElement = deepItem;

if (getTocLevel(deepLink) !== 6) {
	throw new Error('TOC levels deeper than 6 should be clamped.');
}

const fallbackLink = new TestElement('a', {
	children: [
		new TestElement('span', { classes: ['tocnumber'], text: '1' }),
		new TestElement('span', { classes: ['toctext'], text: 'Fallback title' }),
	],
});

if (formatTocNumber('1') !== '1.') {
	throw new Error('Top-level TOC numbers should render with a trailing dot.');
}

if (getLinkText(fallbackLink, null) !== '1. Fallback title') {
	throw new Error('TOC label fallback should include .tocnumber and .toctext.');
}
