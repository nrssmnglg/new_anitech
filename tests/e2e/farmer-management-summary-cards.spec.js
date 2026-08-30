import { expect, test } from '@playwright/test';

test.describe('Farmer Management — summary cards', () => {
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

    test('filters to active farmers and clears the filter from summary cards', async ({ page }) => {
        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('status') === 'active',
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: /Active Members/i }).click(),
        ]);

        await expect(page.getByText('1 active filter', { exact: true })).toBeVisible();
        await expect(page.locator('table tbody tr td:nth-child(5)')).toHaveText(
            Array(await page.locator('table tbody tr td:nth-child(5)').count()).fill('Active'),
        );

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && !url.searchParams.get('status'),
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: /Registered Farmers/i }).click(),
        ]);

        await expect(page.getByText('0 active filters', { exact: true })).toBeVisible();
    });
});
