import { expect, test } from '@playwright/test';

const memberType = 'OM - Old Member';

test.describe('Farmer Management — member type filter', () => {
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

    test('shows only Old Member farmers when OM is selected', async ({ page }) => {
        const memberTypeFilter = page.locator('label')
            .filter({ hasText: /^Member Type/ })
            .locator('select');

        await memberTypeFilter.selectOption({ label: memberType });

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.has('member_type_id'),
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        const memberTypeCells = page.locator('table tbody tr td:nth-child(4)');
        const rowCount = await memberTypeCells.count();

        expect(rowCount).toBeGreaterThan(0);
        await expect(memberTypeCells).toContainText(Array(rowCount).fill('OM'));
    });
});
