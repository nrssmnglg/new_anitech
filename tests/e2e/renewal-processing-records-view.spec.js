import { expect, test } from '@playwright/test';

test.describe('Renewal Processing — renewal records', () => {
    test.setTimeout(60_000);

    test.beforeEach(async ({ page }) => {
        const email = process.env.E2E_STAFF_EMAIL;
        const password = process.env.E2E_STAFF_PASSWORD;

        if (!email || !password) {
            throw new Error('Set E2E_STAFF_EMAIL and E2E_STAFF_PASSWORD in this CMD window before running this test.');
        }

        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(email);
        await page.getByPlaceholder('Password').fill(password);

        const [loginResponse] = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST' && new URL(response.url()).pathname === '/login'),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        expect([302, 303]).toContain(loginResponse.status());
        await page.goto('/admin/renewals?section=records', { waitUntil: 'domcontentloaded' });
    });

    test('opens an existing renewal record from history', async ({ page }) => {
        const main = page.getByRole('main');
        const recordsTable = main.getByRole('table');

        await expect(main.getByRole('heading', { name: 'Renewal Records' })).toBeVisible();
        await expect(recordsTable.getByRole('columnheader', { name: 'Farmer Name' })).toBeVisible();
        await expect(recordsTable.getByRole('columnheader', { name: 'Renewal Years' })).toBeVisible();
        await expect(recordsTable.getByRole('columnheader', { name: 'Total Paid' })).toBeVisible();

        const viewRecord = recordsTable.locator('a[title="View latest renewal record"]').first();
        await expect(viewRecord).toBeVisible();

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname.startsWith('/admin/renewals/') && url.pathname !== '/admin/renewals/create',
                { timeout: 30_000 },
            ),
            viewRecord.click(),
        ]);

        await expect(page.getByRole('main').getByText('Renewal Processing', { exact: true })).toBeVisible();
        await expect(page.getByRole('main').getByText('Renewal Year', { exact: true })).toBeVisible();
    });
});
