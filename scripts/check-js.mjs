import { readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const root = fileURLToPath(new URL('..', import.meta.url));
const jsDir = join(root, 'js');

for (const file of readdirSync(jsDir)) {
	if (!file.endsWith('.js')) {
		continue;
	}

	const path = join(jsDir, file);
	try {
		new vm.Script(readFileSync(path, 'utf8'), { filename: path });
	} catch (error) {
		console.error(error);
		process.exit(1);
	}
}
