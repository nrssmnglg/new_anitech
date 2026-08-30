import { expect, test } from '@playwright/test';

test.describe('Farmer Management — status filter', () => {
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

    test('shows only active farmers when Active is selected', async ({ page }) => {
        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('status') === 'active',
                { timeout: 20_000 },
            ),
            page.getByLabel('Status').selectOption({ label: 'Active' }),
        ]);

        const tableRows = page.locator('table tbody tr');
        const statusCells = page.locator('table tbody tr td:nth-child(5)');
        const rowCount = await tableRows.count();

        expect(rowCount).toBeGreaterThan(0);
        await expect(statusCells).toHaveText(Array(rowCount).fill('Active'));
    });
});
