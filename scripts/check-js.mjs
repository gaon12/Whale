import { readdirSync } from 'node:fs';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const jsDir = join(root, 'js');

for (const file of readdirSync(jsDir)) {
	if (!file.endsWith('.js')) {
		continue;
	}

	const result = spawnSync(process.execPath, ['--check', join(jsDir, file)], {
		stdio: 'inherit',
	});

	if (result.status !== 0) {
		process.exit(result.status);
	}
}
