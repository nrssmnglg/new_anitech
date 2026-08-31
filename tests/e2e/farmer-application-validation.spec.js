import { expect, test } from '@playwright/test';

test.describe('Farmer Application — validation', () => {
    test('keeps an incomplete application on the personal-information step', async ({ page }) => {
        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });

        await page.getByRole('button', { name: 'Continue' }).click();

        await expect(page.getByText('The first name field is required.', { exact: true })).toBeVisible();
        await expect(page.getByText('The last name field is required.', { exact: true })).toBeVisible();
        await expect(page.getByText('The birth date field is required.', { exact: true })).toBeVisible();
        await expect(page.getByText('The sex field is required.', { exact: true })).toBeVisible();
        await expect(page.getByText('The civil status field is required.', { exact: true })).toBeVisible();
        await expect(page.getByLabel('First Name')).toBeVisible();
        await expect(page.getByText('Location & Association', { exact: true })).not.toBeVisible();
    });

    test('rejects an incorrectly formatted mobile number', async ({ page }) => {
        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill('Playwright');
        await page.getByLabel('Last Name').fill('Validation');
        await page.getByLabel('Birth Date').fill('1990-01-15');
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Mobile Number').fill('invalid-number');

        await page.getByRole('button', { name: 'Continue' }).click();

        await expect(page.getByText('Use a valid mobile number such as 09171234567 or +639171234567.', { exact: true })).toBeVisible();
        await expect(page.getByText('Location & Association', { exact: true })).not.toBeVisible();
    });
});
