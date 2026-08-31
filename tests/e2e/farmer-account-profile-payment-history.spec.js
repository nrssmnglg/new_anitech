import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — profile payment history', () => {
    test.setTimeout(90_000);
    test('opens payment history from the farmer profile', async ({ page }) => {
        test.skip(!farmerEmail || !farmerPassword, 'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD before running this test.');
        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });
        await page.goto('/farmer/app/profile');
        const paymentHistory = page.getByRole('button', { name: 'Payment History' });
        await expect(paymentHistory).toBeVisible({ timeout: 60_000 });
        await paymentHistory.click();
        await expect(page).toHaveURL(/\/farmer\/app\/payments$/);
        await expect(page.locator('.farmer-app__payments-history-card').first()).toBeVisible({ timeout: 60_000 });
    });
});
