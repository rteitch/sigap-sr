import { expect, test, type Page } from '@playwright/test';

type PageIssue = {
    path: string;
    kind: string;
    detail: string;
};

const issues: PageIssue[] = [];

async function auditPage(page: Page, path: string): Promise<void> {
    const consoleErrors: string[] = [];
    const pageErrors: string[] = [];

    page.on('console', (message) => {
        if (message.type() === 'error') consoleErrors.push(message.text());
    });
    page.on('pageerror', (error) => pageErrors.push(error.message));

    const response = await page.goto(path, { waitUntil: 'networkidle' });

    if (!response || response.status() >= 400) {
        issues.push({ path, kind: 'http', detail: `Status ${response?.status() ?? 'no response'}` });
    }

    await expect(page.locator('main')).toBeVisible();

    const overflow = await page.evaluate(() => ({
        scrollWidth: document.documentElement.scrollWidth,
        innerWidth: window.innerWidth,
    }));

    if (overflow.scrollWidth > overflow.innerWidth + 1) {
        issues.push({
            path,
            kind: 'overflow',
            detail: `scrollWidth ${overflow.scrollWidth} > viewport ${overflow.innerWidth}`,
        });
    }

    const unnamedButtons = await page.locator('button:visible').evaluateAll((buttons) =>
        buttons
            .filter((button) => {
                const label = (button.getAttribute('aria-label') ?? '').trim();
                const text = (button.textContent ?? '').trim();
                const title = (button.getAttribute('title') ?? '').trim();

                return label === '' && text === '' && title === '';
            })
            .map((button) => button.outerHTML.slice(0, 180)),
    );

    if (unnamedButtons.length > 0) {
        issues.push({
            path,
            kind: 'a11y-button',
            detail: unnamedButtons.join(' | '),
        });
    }

    if (consoleErrors.length > 0) {
        issues.push({ path, kind: 'console', detail: consoleErrors.join(' | ') });
    }

    if (pageErrors.length > 0) {
        issues.push({ path, kind: 'pageerror', detail: pageErrors.join(' | ') });
    }

    for (const text of ['undefined', 'NaN', '[object Object]']) {
        const leaks = await page.getByText(text, { exact: true }).count();
        if (leaks > 0) {
            issues.push({ path, kind: 'render-leak', detail: text });
        }
    }
}

test.afterAll(() => {
    if (issues.length > 0) {
        const report = issues
            .map((issue) => `[${issue.kind}] ${issue.path}: ${issue.detail}`)
            .join('\n');
        throw new Error(`UI audit found ${issues.length} issue(s):\n${report}`);
    }
});

test('all primary and administrative pages render cleanly', async ({ page }) => {
    test.setTimeout(120_000);
    issues.length = 0;

    const paths = [
        '/dashboard',
        '/siswa',
        '/siswa/create',
        '/siswa/import',
        '/presensi',
        '/insiden',
        '/insiden/buat',
        '/asrama',
        '/laporan',
        '/notifikasi',
        '/admin/users',
        '/admin/users/create',
        '/admin/master',
        '/admin/settings',
        '/admin/categories',
        '/admin/audit',
        '/api/docs',
    ];

    for (const path of paths) {
        await auditPage(page, path);
    }
});

test('student and incident detail pages render cleanly', async ({ page }) => {
    test.setTimeout(60_000);
    issues.length = 0;

    await page.goto('/siswa', { waitUntil: 'networkidle' });
    await expect(page.locator('a[href^="/siswa/"]').first()).toBeVisible();
    const studentHref = await page
        .locator('a[href^="/siswa/"]')
        .evaluateAll((links) => links.map((link) => link.getAttribute('href') ?? ''))
        .then((hrefs) => hrefs.find((href) => /^\/siswa\/\d+$/.test(href)));

    expect(studentHref, 'expected a seeded student detail link').toBeTruthy();
    await auditPage(page, studentHref as string);

    await page.goto('/insiden', { waitUntil: 'networkidle' });
    await expect(page.locator('a[href^="/insiden/"]').first()).toBeVisible();
    const incidentHref = await page
        .locator('a[href^="/insiden/"]')
        .evaluateAll((links) => links.map((link) => link.getAttribute('href') ?? ''))
        .then((hrefs) => hrefs.find((href) => /^\/insiden\/\d+$/.test(href)));

    if (incidentHref) {
        await auditPage(page, incidentHref);
    }
});

test('master data modal creates a location and closes cleanly', async ({ page }) => {
    const name = `Lokasi E2E ${Date.now()}`;

    await page.goto('/admin/master');
    await page.getByRole('button', { name: /Lokasi Insiden/ }).click();
    await page.getByRole('button', { name: 'Tambah Lokasi Insiden' }).click();
    await page.locator('#location-name').fill(name);
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();

    await expect(page.locator('#location-name')).toBeHidden();
    await expect(page.getByText(name)).toBeVisible();
});

test('settings preview and save do not break branding', async ({ page }) => {
    await page.goto('/admin/settings');

    await expect(page.locator('#app-name')).toHaveValue(/SIGAP/);
    await page.locator('#primary-color').evaluate((input: HTMLInputElement) => {
        input.value = '#0f766e';
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    await page.getByRole('button', { name: 'Simpan pengaturan' }).first().click();
    await expect(page.getByText('Pengaturan tersimpan.')).toBeVisible();
});

test('incident form exposes master locations', async ({ page }) => {
    await page.goto('/insiden/buat');

    const options = page.locator('#location option');
    await expect.poll(async () => options.count()).toBeGreaterThan(1);

    const values = await options.evaluateAll((items) =>
        items.map((item) => (item as HTMLOptionElement).value).filter(Boolean),
    );
    expect(values.length).toBeGreaterThan(0);
});
