import { expect, test } from '@playwright/test';

const barangay = 'Abanon';

test.describe('Farmer Management — barangay filter', () => {
    test.setTimeout(60_000);

    test.beforeEach(async ({ page }) => {
        await page.goto('/login');

        await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL);
        await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD);

        await Promise.all([
            page.waitForURL(/\/admin\//),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        await page.goto('/admin/farmers', { waitUntil: 'domcontentloaded' });
        await expect(page.getByText('Registry Table')).toBeVisible();
    });

    test(`shows only farmers from ${barangay}`, async ({ page }) => {
        const barangayFilter = page.locator('label')
            .filter({ hasText: /^Barangay/ })
            .locator('select');

        await barangayFilter.selectOption({ label: barangay });

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.has('barangay_id'),
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        const locationCells = page.locator('table tbody tr td:nth-child(3)');
        const rowCount = await locationCells.count();

        expect(rowCount).toBeGreaterThan(0);
        await expect(locationCells).toContainText(Array(rowCount).fill(barangay));
    });
});
