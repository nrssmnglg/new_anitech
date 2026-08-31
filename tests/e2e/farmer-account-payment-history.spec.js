import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — payment history', () => {
    test.setTimeout(90_000);

    test('shows the paid renewal in the farmer payment history', async ({ page }) => {
        test.skip(
            !farmerEmail || !farmerPassword,
            'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD before running this test.',
        );

        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

        await page.goto('/farmer/app/payments');
        const paymentCard = page.locator('.farmer-app__payments-history-card').first();
        await expect(paymentCard).toBeVisible({ timeout: 60_000 });
        await expect(paymentCard.getByText('Date Paid', { exact: true })).toBeVisible();
        await expect(paymentCard.getByText('Amount Paid', { exact: true })).toBeVisible();
        await expect(paymentCard.getByText('Payment Method', { exact: true })).toBeVisible();
        await expect(paymentCard.getByText('Purpose', { exact: true })).toBeVisible();
    });
});
