import { defineConfig } from '@playwright/test';

export default defineConfig( {
	testDir: './tests/e2e',
	timeout: 30000,
	retries: 1,
	use: {
		baseURL:
			process.env.WP_BASE_URL || 'http://commerceflow.local/wp-admin',
		headless: true,
	},
	// The E2E tests require a real WordPress + WooCommerce instance.
	// Skip them on CI unless WP_BASE_URL is explicitly set.
	projects: [
		{
			name: 'wp-e2e',
			testMatch: '**/*.spec.ts',
		},
	],
} );
