import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — renewal QR payment safety', () => {
    test.setTimeout(90_000);

    test('does not mark a renewal paid when the farmer leaves the QR payment page', async ({ page }) => {
        test.skip(!farmerEmail || !farmerPassword, 'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD first.');

        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

        await page.goto('/farmer/app/renewals', { waitUntil: 'domcontentloaded' });
        const startOrContinue = page.getByRole('button', { name: /^(Start Renewal|Continue Renewal|Continue to Payment)$/ });
        await expect(startOrContinue).toBeVisible({ timeout: 60_000 });

        await startOrContinue.click();
        await expect(page).toHaveURL(/\/farmer\/app\/payment\/qr\?transaction=renewal&renewal_id=/, { timeout: 30_000 });
        await expect(page.getByRole('heading', { name: 'Scan To Pay' })).toBeVisible({ timeout: 30_000 });
        const reference = await page.locator('.farmer-app__card').filter({ hasText: 'Renewal Reference' }).getByRole('strong').textContent();
        expect(reference?.trim()).toBeTruthy();

        await page.getByRole('link', { name: 'Back to Payments' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/payments$/, { timeout: 30_000 });

        const renewalsResponse = await page.request.get('/api/farmer/renewals', {
            headers: { Accept: 'application/json' },
        });
        expect(renewalsResponse.ok()).toBeTruthy();
        const renewals = (await renewalsResponse.json()).data;
        const renewal = renewals.find((item) => item.application_no === reference?.trim());
        expect(renewal).toBeTruthy();
        expect(['paid', 'overpaid', 'waived']).not.toContain(String(renewal.assessment?.status ?? '').toLowerCase());
        expect(renewal.latest_payment).toBeNull();
    });
});
