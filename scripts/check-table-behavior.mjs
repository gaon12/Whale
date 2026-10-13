import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const classes = () => {
	const values = new Set();
	return {
		add: (name) => values.add(name),
		contains: (name) => values.has(name),
		values,
	};
};
const header = {
	classList: classes(),
	attributes: {},
	setAttribute(name, value) {
		this.attributes[name] = value;
	},
	addEventListener() {
		throw new Error('The skin must not add a second sort handler.');
	},
};
const table = {
	dataset: {},
	classList: classes(),
	querySelectorAll: () => [header],
};
const content = { querySelectorAll: () => [table] };
const loaderCalls = [];
let coreInitializations = 0;
let sortEnd;
let onContent;
const coreTable = {
	data: () => (coreInitializations > 0 ? {} : null),
	tablesorter: () => {
		coreInitializations++;
	},
	on: (_event, callback) => {
		sortEnd = callback;
	},
};
runInNewContext(
	readFileSync(new URL('../js/table.js', import.meta.url), 'utf8'),
	{
		document: {
			body: {
				classList: {
					contains: (name) => name === 'whale-sortable-tables-enabled',
				},
			},
			getElementById: () => content,
		},
		mw: {
			loader: {
				using: async (name) => {
					loaderCalls.push(name);
				},
			},
			hook: () => ({
				add: (callback) => {
					onContent = callback;
				},
			}),
		},
		window: { jQuery: () => coreTable, addEventListener() {} },
		whale: {
			ready: (callback) => callback(),
			rafThrottle: (callback) => callback,
		},
		console,
	},
);
await new Promise(setImmediate);
assert.equal(
	coreInitializations,
	1,
	'Late-loaded modules must initialize at DOM readiness, without waiting for load.',
);
assert.equal(loaderCalls[0], 'jquery.tablesorter');
assert.equal(table.dataset.whaleSortable, 'ready');
onContent([content]);
await new Promise(setImmediate);
assert.equal(
	coreInitializations,
	1,
	'Content hooks must not initialize an existing core sorter twice.',
);
header.classList.add('headerSortUp');
sortEnd();
assert.equal(header.attributes['aria-sort'], 'ascending');
header.classList.values.delete('headerSortUp');
header.classList.add('headerSortDown');
sortEnd();
assert.equal(header.attributes['aria-sort'], 'descending');
