import { expect, test } from '@playwright/test';

const farmerCode = 'FRM-2026-00050';
const farmerName = 'MELCHOR LAVARIAS';

test.describe('Farmer Management — edit form', () => {
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
    });

    test('opens a farmer edit form with the saved record details', async ({ page }) => {
        await page.getByLabel('Search').fill(farmerCode);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('search') === farmerCode,
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await page.locator(`a[href$="/admin/farmers/${farmerCode}/edit"]`).click();

        await expect(page.getByRole('heading', { name: farmerName })).toBeVisible({ timeout: 30_000 });
        await expect(page).toHaveURL(new RegExp(`/admin/farmers/${farmerCode}/edit$`));
        await expect(page.getByText('Assignment and status', { exact: true })).toBeVisible();
        await expect(page.getByText('Personal details', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Save Changes' })).toBeVisible();
    });
});
