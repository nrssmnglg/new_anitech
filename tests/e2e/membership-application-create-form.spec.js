import { expect, test } from '@playwright/test';

test.describe('Membership Applications — create form', () => {
    test.setTimeout(60_000);

    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL ?? '');
        await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD ?? '');

        await Promise.all([
            page.waitForURL(/\/admin\//),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);
    });

    test('opens a ready-to-fill new membership application form', async ({ page }) => {
        await page.goto('/admin/membership-applications/create', { waitUntil: 'domcontentloaded' });

        await expect(page.getByRole('heading', { name: 'New Membership Application' })).toBeVisible();
        await expect(page.getByLabel('Farmer Code')).toHaveValue(/FRM-/);
        await expect(page.getByLabel('Registration Date')).toBeEditable();
        await expect(page.getByLabel('First Name')).toBeEditable();
        await expect(page.getByLabel('Last Name')).toBeEditable();
        await expect(page.getByLabel('Birth Date')).toBeEditable();
        await expect(page.getByLabel('Barangay')).toBeEditable();
        await expect(page.getByLabel('Association')).toBeEditable();
        await expect(page.getByRole('button', { name: 'Save Membership Application' })).toBeVisible();
    });
});
