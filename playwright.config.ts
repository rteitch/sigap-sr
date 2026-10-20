import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 30_000,
    expect: { timeout: 7_000 },
    reporter: [['list']],
    use: {
        baseURL: 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        actionTimeout: 10_000,
    },
    projects: [
        {
            name: 'setup',
            testMatch: /auth\.setup\.ts/,
            use: { ...devices['Desktop Chrome'], channel: 'chrome' },
        },
        {
            name: 'desktop',
            dependencies: ['setup'],
            use: {
                ...devices['Desktop Chrome'],
                channel: 'chrome',
                storageState: './.auth/admin.json',
            },
        },
        {
            name: 'mobile',
            dependencies: ['setup'],
            use: {
                ...devices['Pixel 7'],
                channel: 'chrome',
                storageState: './.auth/admin.json',
            },
        },
    ],
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: 'http://127.0.0.1:8000',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
    },
});
