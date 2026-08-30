import { expect, test } from '@playwright/test';

test.describe('Mortuary Monitoring — list views', () => {
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
        await page.goto('/admin/mortuary-claims', { waitUntil: 'domcontentloaded' });
        await expect(page).toHaveURL(/\/admin\/mortuary-claims$/);
    });

    test('shows the claim queue and mortuary records views', async ({ page }) => {
        const main = page.getByRole('main');
        const claimQueueTab = main.getByRole('link', { name: 'Claim Queue' }).last();

        await expect(main.getByRole('heading', { name: 'Mortuary Claim Queue' })).toBeVisible();
        await expect(claimQueueTab).toBeVisible();
        await expect(main.getByRole('link', { name: 'Mortuary Records' })).toBeVisible();
        await expect(main.getByRole('heading', { name: 'Eligible farmers' })).toBeVisible();

        const queueTable = main.getByRole('table');
        await expect(queueTable).toBeVisible();
        await expect(queueTable.getByRole('columnheader', { name: 'Farmer Name / Code' })).toBeVisible();
        await expect(queueTable.getByRole('columnheader', { name: 'Expected Claim' })).toBeVisible();

        await Promise.all([
            page.waitForURL(/\/admin\/mortuary-claims\?section=records/, { timeout: 20_000 }),
            main.getByRole('link', { name: 'Mortuary Records' }).click(),
        ]);

        await expect(main.getByRole('heading', { name: 'Mortuary Records' })).toBeVisible();
        const recordsTable = main.getByRole('table');
        await expect(recordsTable).toBeVisible();
        await expect(recordsTable.getByRole('columnheader', { name: 'Reference' })).toBeVisible();
        await expect(recordsTable.getByRole('columnheader', { name: 'Claim Date' })).toBeVisible();
    });
});
