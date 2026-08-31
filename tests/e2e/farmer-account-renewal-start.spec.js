import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — renewal start', () => {
    test.setTimeout(90_000);

    test('starts or resumes the dedicated farmer renewal and opens payment', async ({ page }) => {
        test.skip(!farmerEmail || !farmerPassword, 'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD before running this test.');
        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });
        await page.goto('/farmer/app/renewals');
        const renewalAction = page.getByRole('button', { name: /^(Start Renewal|Continue Renewal)$/ });
        await expect(renewalAction).toBeVisible({ timeout: 60_000 });
        await renewalAction.click();
        await expect(page).toHaveURL(/\/farmer\/app\/payment\/qr\?transaction=renewal&renewal_id=/, { timeout: 30_000 });
        await expect(page.getByRole('heading', { name: 'Scan To Pay' })).toBeVisible({ timeout: 30_000 });
    });
});
