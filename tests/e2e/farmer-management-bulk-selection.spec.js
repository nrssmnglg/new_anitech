import { expect, test } from '@playwright/test';

test.describe('Farmer Management — bulk selection', () => {
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

    test('shows bulk controls for a selected farmer and clears the selection', async ({ page }) => {
        const firstFarmerCheckbox = page.locator('table tbody input[type="checkbox"]').first();

        await expect(firstFarmerCheckbox).toBeVisible();
        await firstFarmerCheckbox.check();

        await expect(page.getByText('1 selected', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Notify' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Assign' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Follow-up' })).toBeVisible();

        await page.getByRole('button', { name: 'Clear' }).click();
        await expect(firstFarmerCheckbox).not.toBeChecked();
    });
});
