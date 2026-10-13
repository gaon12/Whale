import { readFileSync } from 'node:fs';

const source = readFileSync('js/recovery.js', 'utf8');
for (const forbidden of [
	'delete window.mw',
	'delete window.mediaWiki',
	'window.RLQ = []',
	'force: true',
]) {
	if (source.includes(forbidden)) {
		throw new Error(
			`Recovery must preserve existing runtime ownership: ${forbidden}`,
		);
	}
}
// Startup races and callback preservation are exercised in real browser tests.
