import { expect, test } from '@playwright/test';

const farmerCode = 'PW-2026-00001';
const followUpNote = 'Playwright automated follow-up test';

test.describe('Farmer Management — bulk follow-up', () => {
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

    test('adds a follow-up note to the dedicated test farmer', async ({ page }) => {
        await page.getByRole('button', { name: 'Follow-up' }).click();

        await expect(page.getByRole('heading', { name: 'Mark for follow-up' })).toBeVisible();
        await page.getByLabel('Follow-up Note').fill(followUpNote);
        await page.getByRole('button', { name: 'Apply Action' }).click();

        await expect(page.getByText('1 farmer record(s) marked for follow-up.')).toBeVisible({ timeout: 20_000 });
    });
});
