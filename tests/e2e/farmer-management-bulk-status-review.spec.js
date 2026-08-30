import { expect, test } from '@playwright/test';

const farmerCode = 'PW-2026-00001';
const inactiveReason = 'Playwright automated status review test';

test.describe('Farmer Management — bulk status review', () => {
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

    async function selectFarmerAndOpenReview(page) {
        await page.locator('table tbody input[type="checkbox"]').first().check();
        await page.getByRole('button', { name: 'Review' }).click();
    }

    test('updates the status and restores the dedicated test farmer', async ({ page }) => {
        await selectFarmerAndOpenReview(page);

        const reviewDialog = page.locator('section').filter({
            has: page.getByRole('heading', { name: 'Status review' }),
        });
        const reviewStatus = reviewDialog.locator('label')
            .filter({ hasText: /^Review Status/ })
            .locator('select');

        await reviewStatus.selectOption({ label: 'Inactive' });
        await reviewDialog.getByLabel('Inactive Reason').fill(inactiveReason);
        await page.getByRole('button', { name: 'Apply Action' }).click();

        await expect(page.getByText('1 farmer record(s) updated for status review.')).toBeVisible({ timeout: 20_000 });
        await expect(page.locator('table tbody tr')).toContainText('Inactive');

        await selectFarmerAndOpenReview(page);
        await reviewStatus.selectOption({ label: 'Active' });
        await page.getByRole('button', { name: 'Apply Action' }).click();

        await expect(page.getByText('1 farmer record(s) updated for status review.')).toBeVisible({ timeout: 20_000 });
        await expect(page.locator('table tbody tr')).toContainText('Active');
    });
});
