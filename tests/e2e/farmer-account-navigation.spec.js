import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — navigation', () => {
    test('opens each core authenticated section', async ({ page }) => {
        test.skip(
            !farmerEmail || !farmerPassword,
            'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD before running this test.',
        );

        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

        await page.getByRole('link', { name: 'Renew' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/renewals$/);

        await page.getByRole('link', { name: 'Help' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/inquiries$/);

        await page.getByRole('link', { name: 'Profile' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/profile$/);

        await page.getByRole('link', { name: 'Home' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/);
    });
});
