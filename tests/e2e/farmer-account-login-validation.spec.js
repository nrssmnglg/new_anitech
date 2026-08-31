import { expect, test } from '@playwright/test';

test.describe('Farmer Account — login validation', () => {
    test('rejects an unknown farmer account without creating a session', async ({ page }) => {
        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill('playwright-no-account@anitech.local');
        await page.locator('#password').fill('NotTheCorrectPassword!2026');

        const loginResponse = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && new URL(response.url()).pathname.endsWith('/api/farmer/login')),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]).then(([response]) => response);

        expect(loginResponse.status()).toBe(422);
        await expect(page.getByText('No farmer account was found for this email address.', { exact: true })).toBeVisible();
        await expect(page).toHaveURL(/\/farmer\/app\/login$/);
    });
});
