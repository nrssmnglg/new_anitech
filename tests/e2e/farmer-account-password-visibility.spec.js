import { expect, test } from '@playwright/test';

test.describe('Farmer Account — password visibility', () => {
    test('shows and hides the login password field', async ({ page }) => {
        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        const password = page.locator('#password');

        await expect(password).toHaveAttribute('type', 'password');
        await page.getByRole('button', { name: 'Show password' }).click();
        await expect(password).toHaveAttribute('type', 'text');
        await expect(page.getByRole('button', { name: 'Hide password' })).toBeVisible();

        await page.getByRole('button', { name: 'Hide password' }).click();
        await expect(password).toHaveAttribute('type', 'password');
    });
});
