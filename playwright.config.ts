import { defineConfig } from '@playwright/test';

export default defineConfig( {
	testDir: './tests/e2e',
	timeout: 30000,
	retries: 1,
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://commerceflow.local/wp-admin',
		headless: true,
	},
	webServer: {
		command: 'npm run build',
		port: 3000,
		reuseExistingServer: true,
	},
} );
