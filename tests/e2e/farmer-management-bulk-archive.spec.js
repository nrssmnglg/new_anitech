import { expect, test } from '@playwright/test';

const farmerCode = 'PW-2026-00001';
const archiveReason = 'Playwright automated archive test';

test.describe('Farmer Management — bulk archive', () => {
    test.setTimeout(90_000);

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
    });

    async function selectFarmer(page) {
        await page.locator('table tbody input[type="checkbox"]').first().check();
    }

    async function restoreActive(page) {
        await selectFarmer(page);
        await page.getByRole('button', { name: 'Review' }).click();

        const reviewDialog = page.locator('section').filter({
            has: page.getByRole('heading', { name: 'Status review' }),
        });

        await reviewDialog.locator('label')
            .filter({ hasText: /^Review Status/ })
            .locator('select')
            .selectOption({ label: 'Active' });
        await page.getByRole('button', { name: 'Apply Action' }).click();

        await expect(page.getByText('1 farmer record(s) updated for status review.')).toBeVisible({ timeout: 20_000 });
        await expect(page.locator('table tbody tr')).toContainText('Active');
    }

    test('archives and restores the dedicated test farmer', async ({ page }) => {
        await selectFarmer(page);
        await page.getByRole('button', { name: 'Archive' }).click();

        const archiveDialog = page.locator('section').filter({
            has: page.getByRole('heading', { name: 'Archive old records' }),
        });

        await expect(archiveDialog).toBeVisible();
        await archiveDialog.getByLabel('Archive Reason').fill(archiveReason);
        await page.getByRole('button', { name: 'Apply Action' }).click();

        await expect(page.getByText('1 farmer record(s) archived.')).toBeVisible({ timeout: 20_000 });
        await expect(page.locator('table tbody tr')).toContainText('Inactive');

        await restoreActive(page);
    });
});
