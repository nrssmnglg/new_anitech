import { expect, test } from '@playwright/test';

test.describe('Renewal Processing — queue table controls', () => {
    test.setTimeout(60_000);

    test.beforeEach(async ({ page }) => {
        const email = process.env.E2E_STAFF_EMAIL;
        const password = process.env.E2E_STAFF_PASSWORD;

        if (!email || !password) {
            throw new Error('Set E2E_STAFF_EMAIL and E2E_STAFF_PASSWORD in this CMD window before running this test.');
        }

        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(email);
        await page.getByPlaceholder('Password').fill(password);

        const [loginResponse] = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST' && new URL(response.url()).pathname === '/login'),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        expect([302, 303]).toContain(loginResponse.status());
        await page.goto('/admin/renewals', { waitUntil: 'domcontentloaded' });
        await expect(page.getByRole('heading', { name: 'Farmers Due This Year' })).toBeVisible();
    });

    test('sorts farmer rows and toggles compact display', async ({ page }) => {
        const main = page.getByRole('main');
        const table = main.getByRole('table');
        const farmerNames = table.locator('tbody tr td:first-child p:first-of-type');
        const densityToggle = main.getByRole('button', { name: /Compact Rows|Comfortable Rows/ });

        await expect(farmerNames.first()).toBeVisible();
        const initialDensityLabel = await densityToggle.textContent();
        const expectedDensityLabel = initialDensityLabel?.trim() === 'Compact Rows' ? 'Comfortable Rows' : 'Compact Rows';

        await densityToggle.click();
        await expect(densityToggle).toHaveText(expectedDensityLabel);
        await densityToggle.click();
        await expect(densityToggle).toHaveText(initialDensityLabel?.trim() || 'Compact Rows');

        await table.getByRole('button', { name: 'Farmer' }).click();
        const firstOrder = await farmerNames.allTextContents();

        await table.getByRole('button', { name: 'Farmer' }).click();
        await expect.poll(async () => farmerNames.allTextContents()).toEqual([...firstOrder].reverse());
    });
});
