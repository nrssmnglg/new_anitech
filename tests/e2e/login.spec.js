import { expect, test } from '@playwright/test';

test.describe('Office portal login', () => {
    test('shows the login form', async ({ page }) => {
        await page.goto('/login');

        await expect(page).toHaveTitle(/Portal Login/i);
        await expect(page.getByRole('img', { name: 'AniTech logo' })).toBeVisible();
        await expect(page.getByPlaceholder('Email address')).toBeVisible();
        await expect(page.getByPlaceholder('Password')).toBeVisible();
        await expect(page.getByRole('button', { name: 'SIGN IN' })).toBeVisible();
    });

    test('validates an empty sign-in attempt', async ({ page }) => {
        await page.goto('/login');

        await page.getByRole('button', { name: 'SIGN IN' }).click();

        // The browser keeps the user on the form because the required inputs are empty.
        await expect(page).toHaveURL(/\/login$/);
    });
});
