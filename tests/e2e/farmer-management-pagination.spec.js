import { expect, test } from '@playwright/test';

test.describe('Farmer Management — pagination', () => {
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
        await expect(page.getByText(/Page 1 of \d+/)).toBeVisible();
    });

    test('opens the next page of farmers', async ({ page }) => {
        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('page') === '2',
                { timeout: 20_000 },
            ),
            page.getByRole('link', { name: /Next/ }).click(),
        ]);

        await expect(page.getByText(/Page 2 of \d+/)).toBeVisible();
        await expect(page.getByText(/Showing \d+ to \d+ of \d+ records/)).toBeVisible();
    });
});
