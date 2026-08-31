import { expect, test } from '@playwright/test';

// This dedicated test farmer has no renewal created by this test.
const farmerCode = 'PW-2026-00001';
const farmerName = 'PLAYWRIGHT TEST FARMER 1';

test.describe('Farmer Management — renewal button', () => {
    test.setTimeout(60_000);

    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL ?? '');
        await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD ?? '');

        await Promise.all([
            page.waitForURL(/\/admin\//),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        await page.goto('/admin/farmers', { waitUntil: 'domcontentloaded' });
    });

    test('opens the renewal setup form for an eligible farmer', async ({ page }) => {
        await page.getByLabel('Search').fill(farmerCode);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('search') === farmerCode,
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await page.getByRole('link', { name: `Process renewal for ${farmerName}` }).click();

        await expect(page).toHaveURL(/\/admin\/renewals\/create\?farmer_id=\d+&year=\d{4}/);
        await expect(page.getByRole('heading', { name: 'Renewal Setup' })).toBeVisible();
        await expect(page.getByText(farmerName, { exact: true })).toBeVisible();
        await expect(page.getByText(farmerCode, { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Create & Complete Renewal' })).toBeVisible();
    });
});
