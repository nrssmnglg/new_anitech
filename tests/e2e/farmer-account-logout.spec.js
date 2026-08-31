import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — logout', () => {
    test('ends the farmer session after confirmation', async ({ page }) => {
        test.skip(
            !farmerEmail || !farmerPassword,
            'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD before running this test.',
        );

        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

        await page.goto('/farmer/app/profile');
        const logout = page.getByRole('button', { name: 'Log Out' });
        await expect(logout).toBeVisible({ timeout: 60_000 });
        page.once('dialog', (dialog) => dialog.accept());
        await logout.click();

        await expect(page).toHaveURL(/\/farmer\/app\/login$/, { timeout: 30_000 });
        await expect(page.getByRole('button', { name: 'SIGN IN' })).toBeVisible();
    });
});
