import { expect, test } from '@playwright/test';

test.describe('Farmer Management — list', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');

        await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL);
        await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD);

        // Wait for the login redirect before starting another navigation.
        await Promise.all([
            page.waitForURL(/\/admin\//),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        await page.goto('/admin/farmers', { waitUntil: 'domcontentloaded' });
        await expect(page.getByText('Registry Table')).toBeVisible();
    });

    test('shows the farmer registry list', async ({ page }) => {
        const registry = page.getByRole('table');

        await expect(registry).toBeVisible();
        await expect(registry.getByRole('columnheader', { name: 'Farmer' })).toBeVisible();
        await expect(registry.getByRole('columnheader', { name: 'Contact & Location' })).toBeVisible();
        await expect(registry.getByRole('columnheader', { name: 'Type' })).toBeVisible();
        await expect(registry.getByRole('columnheader', { name: 'Status' })).toBeVisible();
        await expect(registry.getByRole('columnheader', { name: 'Actions' })).toBeVisible();

        // This works whether the database has farmer records or is currently empty.
        await expect(page.getByText(/Showing \d+ to \d+ of \d+ records/)).toBeVisible();
        await expect(page.getByText(/Page \d+ of \d+/)).toBeVisible();
    });
});
