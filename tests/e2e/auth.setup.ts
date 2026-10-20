import { expect, test as setup } from '@playwright/test';
import fs from 'node:fs';

setup('authenticate as administrator', async ({ page }) => {
    fs.mkdirSync('./.auth', { recursive: true });

    await page.goto('/login');
    await page.locator('#email').fill('admin@sigap.test');
    await page.locator('#password').fill('password');
    await page.getByRole('button', { name: 'Masuk dengan aman' }).click();
    await page.waitForURL('**/dashboard');

    await expect(page.locator('main')).toBeVisible();
    await page.context().storageState({ path: './.auth/admin.json' });
});
