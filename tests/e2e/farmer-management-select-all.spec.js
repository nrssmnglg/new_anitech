import { expect, test } from '@playwright/test';

const testFarmerSearch = 'PW-2026-';

test.describe('Farmer Management — select all', () => {
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
        await page.getByLabel('Search').fill(testFarmerSearch);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('search') === testFarmerSearch,
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await expect(page.getByText('Registry Table')).toBeVisible();
    });

    async function assignMemberType(page, label) {
        await page.getByRole('button', { name: 'Assign' }).click();

        const assignDialog = page.locator('section').filter({
            has: page.getByRole('heading', { name: 'Assign records' }),
        });

        await assignDialog.locator('label')
            .filter({ hasText: /^Member Type/ })
            .locator('select')
            .selectOption({ label });

        await Promise.all([
            page.waitForResponse((response) =>
                response.request().method() === 'POST'
                && response.url().includes('/admin/farmers/bulk-assign'),
            ),
            page.getByRole('button', { name: 'Apply Action' }).click(),
        ]);
    }

    test('changes every dedicated test farmer and restores them', async ({ page }) => {
        const rowCheckboxes = page.locator('table tbody input[type="checkbox"]');
        const rowCount = await rowCheckboxes.count();

        expect(rowCount).toBe(3);

        await page.locator('table thead input[type="checkbox"]').check();

        await expect(page.getByText(`${rowCount} selected`, { exact: true })).toBeVisible();
        for (let index = 0; index < rowCount; index += 1) {
            await expect(rowCheckboxes.nth(index)).toBeChecked();
        }

        await assignMemberType(page, 'NM - New Member');
        const memberTypeCells = page.locator('table tbody tr td:nth-child(4)');
        await expect(memberTypeCells).toContainText(Array(rowCount).fill('NM'), { timeout: 20_000 });

        await page.locator('table thead input[type="checkbox"]').check();
        await assignMemberType(page, 'OM - Old Member');
        await expect(memberTypeCells).toContainText(Array(rowCount).fill('OM'), { timeout: 20_000 });
    });
});
