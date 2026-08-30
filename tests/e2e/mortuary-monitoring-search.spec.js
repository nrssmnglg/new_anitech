import { expect, test } from '@playwright/test';

const farmerCode = 'PW-2026-00004';
const farmerName = 'Playwright Mortuary Test Farmer';

test.describe('Mortuary Monitoring — search', () => {
    test.setTimeout(60_000);

    test('finds the dedicated mortuary claim by farmer code', async ({ page }) => {
        const email = process.env.E2E_STAFF_EMAIL;
        const password = process.env.E2E_STAFF_PASSWORD;
        if (!email || !password) throw new Error('Set E2E_STAFF_EMAIL and E2E_STAFF_PASSWORD before running this test.');

        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(email);
        await page.getByPlaceholder('Password').fill(password);
        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST' && new URL(response.url()).pathname === '/login'),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        await page.goto('/admin/mortuary-claims?section=records', { waitUntil: 'domcontentloaded' });
        const main = page.getByRole('main');
        await expect(main.getByRole('heading', { name: 'Mortuary Records' })).toBeVisible();

        await main.getByLabel('Search').fill(farmerCode);
        await main.getByRole('button', { name: 'Apply Filters' }).click();

        const table = main.getByRole('table');
        await expect(table).toContainText(farmerCode, { timeout: 30_000 });
        await expect(table).toContainText(farmerName);
    });
});
