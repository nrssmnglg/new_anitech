import { expect, test } from '@playwright/test';

const farmerCode = 'PW-2026-00001';

test.describe('Farmer Management — bulk assign dialog', () => {
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
        await page.getByLabel('Search').fill(farmerCode);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('search') === farmerCode,
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await page.locator('table tbody input[type="checkbox"]').first().check();
    });

    test('changes the member type and restores the dedicated test farmer', async ({ page }) => {
        await page.getByRole('button', { name: 'Assign' }).click();

        const assignDialog = page.locator('section').filter({
            has: page.getByRole('heading', { name: 'Assign records' }),
        });
        const memberTypeFilter = assignDialog.locator('label')
            .filter({ hasText: /^Member Type/ })
            .locator('select');

        await memberTypeFilter.selectOption({ label: 'NM - New Member' });
        await page.getByRole('button', { name: 'Apply Action' }).click();

        await expect(page.getByText('1 farmer record(s) reassigned.')).toBeVisible({ timeout: 20_000 });
        await expect(page.locator('table tbody tr')).toContainText('NM');

        await page.locator('table tbody input[type="checkbox"]').first().check();
        await page.getByRole('button', { name: 'Assign' }).click();

        await memberTypeFilter.selectOption({ label: 'OM - Old Member' });
        await page.getByRole('button', { name: 'Apply Action' }).click();

        await expect(page.getByText('1 farmer record(s) reassigned.')).toBeVisible({ timeout: 20_000 });
        await expect(page.locator('table tbody tr')).toContainText('OM');
    });
});
