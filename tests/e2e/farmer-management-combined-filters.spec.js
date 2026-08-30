import { expect, test } from '@playwright/test';

test.describe('Farmer Management — combined filters', () => {
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

    test('combines Active, Abanon, and OM filters', async ({ page }) => {
        const statusFilter = page.locator('label').filter({ hasText: /^Status/ }).locator('select');
        const barangayFilter = page.locator('label').filter({ hasText: /^Barangay/ }).locator('select');
        const memberTypeFilter = page.locator('label').filter({ hasText: /^Member Type/ }).locator('select');

        await barangayFilter.selectOption({ label: 'Abanon' });
        await memberTypeFilter.selectOption({ label: 'OM - Old Member' });

        await Promise.all([
            page.waitForURL((url) =>
                url.pathname === '/admin/farmers'
                && url.searchParams.get('status') === 'active'
                && url.searchParams.has('barangay_id')
                && url.searchParams.has('member_type_id'), { timeout: 20_000 }),
            statusFilter.selectOption({ label: 'Active' }),
        ]);

        const rows = page.locator('table tbody tr');
        const rowCount = await rows.count();

        expect(rowCount).toBeGreaterThan(0);
        await expect(page.locator('table tbody tr td:nth-child(3)')).toContainText(Array(rowCount).fill('Abanon'));
        await expect(page.locator('table tbody tr td:nth-child(4)')).toContainText(Array(rowCount).fill('OM'));
        await expect(page.locator('table tbody tr td:nth-child(5)')).toHaveText(Array(rowCount).fill('Active'));
    });
});
