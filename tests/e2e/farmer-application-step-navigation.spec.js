import { expect, test } from '@playwright/test';

test.describe('Farmer Application — step navigation', () => {
    test('preserves personal details when returning from location to the previous step', async ({ page }) => {
        const firstName = `PlaywrightStep${Date.now()}`;

        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill(firstName);
        await page.getByLabel('Last Name').fill('Navigation');
        await page.getByLabel('Birth Date').fill('1990-01-15');
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByRole('button', { name: 'Continue' }).click();

        await expect(page.getByText('Location & Association', { exact: true })).toBeVisible();
        await page.getByRole('button', { name: 'Back' }).click();

        await expect(page.getByText('Personal Information', { exact: true })).toBeVisible();
        await expect(page.getByLabel('First Name')).toHaveValue(firstName);
        await expect(page.getByLabel('Last Name')).toHaveValue('Navigation');
        await expect(page.getByLabel('Birth Date')).toHaveValue('1990-01-15');
    });
});
