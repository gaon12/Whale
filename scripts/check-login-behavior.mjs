import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(
	new URL('../js/login-request.js', import.meta.url),
	'utf8',
);
const run = async ({
	status = 'PASS',
	missingToken = false,
	networkFailure = false,
} = {}) => {
	const requests = [];
	const redirects = [];
	let reloads = 0;
	const submit = { querySelector: () => null };
	const alert = { classList: { add() {}, remove() {} }, focus() {} };
	class Form {
		constructor() {
			this.action = '/w/Special:UserLogin';
			this.dataset = {
				fullLoginUrl: '/w/Special:UserLogin?returnto=Article',
				errorMessage: 'Network error',
			};
			this.elements = {
				wpName: { value: 'Example' },
				wpPassword: { value: 'secret' },
				wpRemember: { checked: true },
			};
			this.handlers = new Map();
		}
		querySelector(selector) {
			return selector === '.whale-login-submit' ? submit : null;
		}
		closest() {
			return { querySelector: () => alert };
		}
		setAttribute() {}
		addEventListener(type, callback) {
			this.handlers.set(type, callback);
		}
		reportValidity() {
			return true;
		}
	}
	const form = new Form();
	runInNewContext(source, {
		document: { getElementById: () => form },
		HTMLFormElement: Form,
		HTMLInputElement: class {},
		location: {
			href: 'https://example.org/wiki/Article',
			assign: (url) => redirects.push(url),
			reload: () => {
				reloads++;
			},
		},
		mw: { config: { get: () => 0 } },
		whale: {
			ready: (callback) => callback(),
			getApi: async () => ({
				post: async (params) => {
					requests.push(params);
					if (networkFailure) throw new Error('Offline');
					return params.action === 'query'
						? {
								query: {
									tokens: { logintoken: missingToken ? null : 'TOKEN' },
								},
							}
						: { clientlogin: { status, message: 'Login rejected' } };
				},
			}),
		},
	});
	const submitEvent = { preventDefault() {} };
	form.handlers.get('submit')(submitEvent);
	form.handlers.get('submit')(submitEvent);
	await new Promise(setImmediate);
	return { form, submit, alert, requests, redirects, reloads };
};

const passed = await run();
assert.equal(passed.reloads, 1);
assert.equal(
	passed.requests.length,
	2,
	'Duplicate submissions must share one token and login request.',
);
assert.equal(passed.requests[1].logintoken, 'TOKEN');
assert(Object.isFrozen(passed.requests[1]));
assert.equal(passed.submit.disabled, false);

for (const status of ['UI', 'CONTINUE', 'REDIRECT', 'RESTART']) {
	const result = await run({ status });
	assert.deepEqual(result.redirects, ['/w/Special:UserLogin?returnto=Article']);
	assert.equal(
		result.form.elements.wpPassword.value,
		'',
		'Passwords must not be carried into native-login redirects.',
	);
	assert.equal(result.reloads, 0);
}
const failed = await run({ status: 'FAIL' });
assert.equal(failed.alert.textContent, 'Login rejected');
assert.equal(failed.redirects.length, 0);
const offline = await run({ networkFailure: true });
assert.equal(offline.alert.textContent, 'Network error');
assert.equal(offline.submit.disabled, false);
const missing = await run({ missingToken: true });
assert.equal(
	missing.requests.length,
	1,
	'A missing login token must prevent sending credentials.',
);
