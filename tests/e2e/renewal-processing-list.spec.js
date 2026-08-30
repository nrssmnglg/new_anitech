import { expect, test } from '@playwright/test';

test.describe('Renewal Processing — list views', () => {
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
            page.waitForResponse((response) => (
                response.request().method() === 'POST'
                && new URL(response.url()).pathname === '/login'
            )),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        expect([302, 303]).toContain(loginResponse.status());
        await page.goto('/admin/renewals', { waitUntil: 'domcontentloaded' });
        await expect(page).toHaveURL(/\/admin\/renewals$/);
    });

    test('shows the active renewal queue and renewal records views', async ({ page }) => {
        const main = page.getByRole('main');

        await expect(page.getByRole('heading', { name: 'Renewal Processing' })).toBeVisible();
        await expect(main.getByRole('link', { name: 'Active Queue' })).toBeVisible();
        await expect(main.getByRole('link', { name: 'Renewal Records' })).toBeVisible();
        await expect(main.getByRole('heading', { name: 'Farmers Due This Year' })).toBeVisible();

        const queueTable = main.getByRole('table');
        await expect(queueTable).toBeVisible();
        await expect(queueTable.getByRole('columnheader', { name: 'Farmer' })).toBeVisible();
        await expect(queueTable.getByRole('columnheader', { name: 'Renewal Status' })).toBeVisible();

        await Promise.all([
            page.waitForURL(/\/admin\/renewals\?section=records/, { timeout: 20_000 }),
            main.getByRole('link', { name: 'Renewal Records' }).click(),
        ]);

        await expect(main.getByRole('heading', { name: 'Renewal Records' })).toBeVisible();
        const recordsTable = main.getByRole('table');
        await expect(recordsTable).toBeVisible();
        await expect(recordsTable.getByRole('columnheader', { name: 'Farmer Name' })).toBeVisible();
        await expect(recordsTable.getByRole('columnheader', { name: 'Renewal Years' })).toBeVisible();
    });
});
