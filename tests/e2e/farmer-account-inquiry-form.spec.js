import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — inquiry form', () => {
    test.setTimeout(90_000);

    test('opens and cancels a new inquiry without submitting it', async ({ page }) => {
        test.skip(!farmerEmail || !farmerPassword, 'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD first.');

        await page.goto('/farmer/app/login');
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/);

        await page.goto('/farmer/app/inquiries');
        await expect(page.getByRole('heading', { name: 'Inbox' })).toBeVisible({ timeout: 60_000 });
        await page.locator('.farmer-app__inquiries-plus').click();

        await expect(page.getByRole('heading', { name: 'New Inquiry' })).toBeVisible();
        await expect(page.getByLabel('Topic')).toBeVisible();
        await expect(page.getByLabel('Subject')).toBeVisible();
        await expect(page.getByLabel('Message')).toBeVisible();

        await page.getByRole('button', { name: 'Cancel' }).click();
        await expect(page.getByRole('heading', { name: 'New Inquiry' })).not.toBeVisible();
    });
});
