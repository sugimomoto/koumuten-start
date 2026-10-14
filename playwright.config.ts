// E2E テスト（Playwright）の設定。
// 既定ではローカル（wp-env）に対して動かす。E2E_BASE_URL を指定すると、そのサイトに対して動かす。
//   npm run test:e2e                                          … ローカルで全シナリオ
//   E2E_BASE_URL=https://… npx playwright test --grep @readonly … 本番で「読むだけ」のシナリオだけ
import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL || 'http://localhost:8890';

export default defineConfig( {
	testDir: './tests/e2e',
	timeout: 30_000,
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI ? [ [ 'list' ], [ 'html', { open: 'never' } ] ] : 'list',
	globalSetup: './tests/e2e/global-setup.ts',
	globalTeardown: './tests/e2e/global-teardown.ts',
	use: {
		baseURL,
		locale: 'ja-JP',
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [ { name: 'chromium', use: { ...devices[ 'Desktop Chrome' ] } } ],
} );
