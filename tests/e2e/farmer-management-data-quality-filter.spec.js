import { expect, test } from '@playwright/test';

const qualityValue = 'invalid_mobile';
const qualityLabel = 'Invalid mobile numbers';

test.describe('Farmer Management — data quality filter', () => {
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

    test(`applies the ${qualityLabel} filter`, async ({ page }) => {
        const qualityFilter = page.locator('label')
            .filter({ hasText: /^Data Quality/ })
            .locator('select');

        await qualityFilter.selectOption(qualityValue);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('quality') === qualityValue,
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await expect(qualityFilter).toHaveValue(qualityValue);
        await expect(page.getByText('1 active filter', { exact: true })).toBeVisible();
    });
});
