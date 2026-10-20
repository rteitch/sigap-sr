import { expect, test, type Page } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

type PageAuditTarget = {
    name: string;
    path: string;
};

const targets: PageAuditTarget[] = [
    { name: '01-dashboard', path: '/dashboard' },
    { name: '02-profile-edit', path: '/profil' },
    { name: '03-students-index', path: '/siswa' },
    { name: '04-students-create', path: '/siswa/create' },
    { name: '05-attendance-index', path: '/presensi' },
    { name: '06-incidents-index', path: '/insiden' },
    { name: '07-incidents-create', path: '/insiden/buat' },
    { name: '08-dorm-index', path: '/asrama' },
    { name: '09-reports-index', path: '/laporan' },
    { name: '10-notifications-index', path: '/notifikasi' },
    { name: '11-admin-master', path: '/admin/master' },
    { name: '12-admin-users', path: '/admin/users' },
    { name: '13-admin-users-create', path: '/admin/users/create' },
    { name: '14-admin-categories', path: '/admin/categories' },
    { name: '15-admin-settings', path: '/admin/settings' },
    { name: '16-admin-audit', path: '/admin/audit' },
    { name: '17-privacy-policy', path: '/kebijakan-privasi' },
    { name: '18-terms-of-service', path: '/persyaratan-layanan' },
];

test.describe('Full Application Visual & Layout Screenshot Audit', () => {
    test.setTimeout(180_000);

    for (const target of targets) {
        test(`capture and evaluate ${target.name} (${target.path})`, async ({ page }, testInfo) => {
            const isMobile = testInfo.project.name === 'mobile';
            const screenshotDir = path.resolve('storage/app/screenshots', isMobile ? 'mobile' : 'desktop');
            fs.mkdirSync(screenshotDir, { recursive: true });

            const consoleErrors: string[] = [];
            const pageErrors: string[] = [];

            page.on('console', (msg) => {
                if (msg.type() === 'error') consoleErrors.push(msg.text());
            });
            page.on('pageerror', (err) => pageErrors.push(err.message));

            const response = await page.goto(target.path, { waitUntil: 'networkidle' });
            expect(response?.status()).toBeLessThan(400);

            await expect(page.locator('main')).toBeVisible();

            // Check horizontal scroll overflow
            const overflow = await page.evaluate(() => ({
                scrollWidth: document.documentElement.scrollWidth,
                innerWidth: window.innerWidth,
            }));

            expect(
                overflow.scrollWidth,
                `Horizontal overflow detected on ${target.path}: scrollWidth (${overflow.scrollWidth}) > viewport (${overflow.innerWidth})`
            ).toBeLessThanOrEqual(overflow.innerWidth + 1);

            // Check for render leaks
            for (const leak of ['undefined', 'NaN', '[object Object]']) {
                const count = await page.getByText(leak, { exact: true }).count();
                expect(count, `Render leak detected for text "${leak}" on ${target.path}`).toBe(0);
            }

            // Capture screenshot
            const screenshotPath = path.join(screenshotDir, `${target.name}.png`);
            await page.screenshot({ path: screenshotPath, fullPage: true });

            expect(fs.existsSync(screenshotPath)).toBe(true);
            expect(consoleErrors.length, `Console errors on ${target.path}: ${consoleErrors.join(', ')}`).toBe(0);
            expect(pageErrors.length, `Page errors on ${target.path}: ${pageErrors.join(', ')}`).toBe(0);
        });
    }

    test('capture login page on desktop and mobile and verify zero icon-text overlap', async ({ browser }, testInfo) => {
        const isMobile = testInfo.project.name === 'mobile';
        const context = await browser.newContext({
            storageState: { cookies: [], origins: [] },
            viewport: isMobile ? { width: 390, height: 844 } : { width: 1280, height: 800 },
            isMobile,
        });
        const page = await context.newPage();

        const response = await page.goto('/login', { waitUntil: 'networkidle' });
        expect(response?.status()).toBeLessThan(400);

        const screenshotDir = path.resolve('storage/app/screenshots', isMobile ? 'mobile' : 'desktop');
        fs.mkdirSync(screenshotDir, { recursive: true });

        // Verify Privacy Policy & Terms of Service links exist
        await expect(page.getByRole('link', { name: 'Kebijakan Privasi' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Persyaratan Layanan' })).toBeVisible();

        // Check computed padding of email and password inputs to verify NO icon overlap
        const emailPaddingLeft = await page.locator('#email').evaluate((el) => {
            return parseFloat(window.getComputedStyle(el).paddingLeft);
        });
        const passwordPaddingLeft = await page.locator('#password').evaluate((el) => {
            return parseFloat(window.getComputedStyle(el).paddingLeft);
        });
        const passwordPaddingRight = await page.locator('#password').evaluate((el) => {
            return parseFloat(window.getComputedStyle(el).paddingRight);
        });

        // Left icon ends at 30px (14px left + 16px width). Padding-left must be >= 36px (target 40px)
        expect(emailPaddingLeft, 'Email input padding-left must be at least 36px to prevent icon overlap').toBeGreaterThanOrEqual(36);
        expect(passwordPaddingLeft, 'Password input padding-left must be at least 36px to prevent icon overlap').toBeGreaterThanOrEqual(36);
        expect(passwordPaddingRight, 'Password input padding-right must be at least 36px for toggle button').toBeGreaterThanOrEqual(36);

        // Capture empty form
        const screenshotPath = path.join(screenshotDir, '00-login.png');
        await page.screenshot({ path: screenshotPath, fullPage: true });
        expect(fs.existsSync(screenshotPath)).toBe(true);

        // Fill credentials to visually prove text does not overlap icons
        await page.fill('#email', 'kepsek@sigap.test');
        await page.fill('#password', 'rahasia12345');

        const screenshotTypedPath = path.join(screenshotDir, '00-login-typed.png');
        await page.screenshot({ path: screenshotTypedPath, fullPage: true });
        expect(fs.existsSync(screenshotTypedPath)).toBe(true);

        await context.close();
    });
});
