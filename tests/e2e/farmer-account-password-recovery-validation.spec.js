import { expect, test } from '@playwright/test';

test.describe('Farmer Account — password recovery validation', () => {
    test('rejects an invalid email without opening OTP verification', async ({ page }) => {
        await page.goto('/farmer/app/forgot-password', { waitUntil: 'domcontentloaded' });
        await expect(page.getByRole('heading', { name: 'Reset Password' })).toBeVisible();

        const email = page.getByLabel('Farmer Email Address');
        await email.fill('not-a-valid-email');
        await page.getByRole('button', { name: 'Send OTP' }).click();

        // The browser blocks an invalid type="email" value before the recovery
        // request can be sent, which avoids unnecessary OTP requests.
        expect(await email.evaluate((input) => input.validity.valid)).toBe(false);
        await expect(page).toHaveURL(/\/farmer\/app\/forgot-password$/);
    });
});
