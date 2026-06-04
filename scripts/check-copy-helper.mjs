import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { runInNewContext } from 'node:vm';

class TestTextarea {
	constructor(document) {
		this.document = document;
		this.attributes = {};
		this.style = {};
		this.value = '';
		this.removed = false;
		this.selected = false;
	}

	setAttribute(name, value) {
		this.attributes[name] = String(value);
	}

	select() {
		this.selected = true;
	}

	setSelectionRange(start, end) {
		this.selectionRange = [start, end];
	}

	remove() {
		this.removed = true;
		this.document.body.children = this.document.body.children.filter(
			(child) => child !== this,
		);
	}
}

const createContext = ({ clipboard, execCommand }) => {
	const document = {
		readyState: 'complete',
		body: {
			children: [],
			append(node) {
				this.children.push(node);
			},
		},
		addEventListener: () => {},
		createElement: () => {
			const textarea = new TestTextarea(document);
			document.lastTextarea = textarea;
			return textarea;
		},
		execCommand,
		getElementById: () => null,
		querySelector: () => null,
	};
	const context = {
		document,
		navigator: { clipboard },
		window: { requestAnimationFrame: (callback) => callback() },
		Element: class Element {},
	};

	runInNewContext(readFileSync(resolve('js/common.js'), 'utf8'), context);
	return context;
};

const clipboardWrites = [];
const clipboardContext = createContext({
	clipboard: {
		writeText: async (text) => {
			clipboardWrites.push(text);
		},
	},
	execCommand: () => {
		throw new Error('Fallback should not run when Clipboard API succeeds.');
	},
});

if (!(await clipboardContext.window.whale.copyText('direct copy'))) {
	throw new Error('Clipboard API copy should report success.');
}

if (clipboardWrites.join('|') !== 'direct copy') {
	throw new Error('Clipboard API should receive the requested text.');
}

let fallbackCommand = '';
const fallbackContext = createContext({
	clipboard: {
		writeText: async () => {
			throw new Error('Clipboard permission denied.');
		},
	},
	execCommand: (command) => {
		fallbackCommand = command;
		return true;
	},
});

if (!(await fallbackContext.window.whale.copyText('fallback copy'))) {
	throw new Error('Textarea fallback should report success.');
}

if (
	fallbackCommand !== 'copy' ||
	fallbackContext.document.lastTextarea?.value !== 'fallback copy' ||
	!fallbackContext.document.lastTextarea?.selected ||
	!fallbackContext.document.lastTextarea?.removed
) {
	throw new Error(
		'Textarea fallback should select, copy, and clean itself up.',
	);
}

const unsupportedContext = createContext({
	clipboard: null,
	execCommand: undefined,
});

if (await unsupportedContext.window.whale.copyText('no copy support')) {
	throw new Error('Missing copy support should report failure.');
}
