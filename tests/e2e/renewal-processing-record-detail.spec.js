import { expect, test } from '@playwright/test';

test.describe('Renewal Processing — record details', () => {
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

    test('shows profile, payment, and history information for a renewal', async ({ page }) => {
        const recordsTable = page.getByRole('main').getByRole('table');
        const viewRecord = recordsTable.locator('a[title="View latest renewal record"]').first();

        await expect(viewRecord).toBeVisible();
        await Promise.all([
            page.waitForURL((url) => url.pathname.startsWith('/admin/renewals/') && url.pathname !== '/admin/renewals/create'),
            viewRecord.click(),
        ]);

        const main = page.getByRole('main');
        await expect(main.getByRole('heading', { level: 1 })).toBeVisible();
        await expect(main.getByText('Renewal Year', { exact: true })).toBeVisible();
        await expect(main.getByText('Status', { exact: true }).first()).toBeVisible();
        await expect(main.getByText('Total Due', { exact: true })).toBeVisible();
        await expect(main.getByRole('heading', { name: 'Farmer Demographics' })).toBeVisible();
        await expect(main.getByRole('heading', { name: 'Farmer Renewal History' })).toBeVisible();
        await expect(main.getByRole('heading', { name: 'Payment Information' })).toBeVisible();

        await expect(main.getByRole('columnheader', { name: 'Fee Description' })).toBeVisible();
        await expect(main.getByRole('columnheader', { name: 'Amount', exact: true })).toBeVisible();
    });
});
