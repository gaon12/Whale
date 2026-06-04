import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const tests = [
	'check-js.mjs',
	'check-copy-helper.mjs',
	'check-external-link-warning.mjs',
	'check-share-button.mjs',
	'check-toc-utils.mjs',
	'check-theme-toggle.mjs',
	'check-ui-features.mjs',
	'check-floating-toc-behavior.mjs',
	'check-layout-interactions.mjs',
];

for (const test of tests) {
	const result = spawnSync(process.execPath, [`scripts/${test}`], {
		cwd: root,
		stdio: 'inherit',
	});

	if (result.status !== 0) {
		process.exit(result.status ?? 1);
	}
}
