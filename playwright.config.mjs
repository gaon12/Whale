import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { defineConfig } from '@playwright/test';

export default defineConfig({
	testDir: './tests/browser',
	outputDir: join(tmpdir(), 'whale-browser-tests'),
	workers: 1,
	reporter: 'list',
	use: {
		browserName: 'chromium',
		launchOptions: { channel: process.env.WHALE_BROWSER_CHANNEL || undefined },
		viewport: { width: 1440, height: 1000 },
	},
});
