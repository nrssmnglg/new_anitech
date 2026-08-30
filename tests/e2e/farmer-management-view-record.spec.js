import { expect, test } from '@playwright/test';

const farmerCode = 'FRM-2026-00050';
const farmerName = 'MELCHOR LAVARIAS';

test.describe('Farmer Management — view record', () => {
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

    test('opens the selected farmer record', async ({ page }) => {
        await page.getByLabel('Search').fill(farmerCode);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('search') === farmerCode,
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await page.locator(`a[href$="/admin/farmers/${farmerCode}"]`).click();

        // An Inertia link updates the page without a full browser load.
        await expect(page.getByRole('heading', { name: farmerName })).toBeVisible({ timeout: 30_000 });
        await expect(page).toHaveURL(new RegExp(`/admin/farmers/${farmerCode}$`));
        await expect(page.getByText(farmerCode, { exact: true }).first()).toBeVisible();
        await expect(page.getByText('Membership', { exact: true })).toBeVisible();
    });
});
