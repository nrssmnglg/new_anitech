import { expect, test } from '@playwright/test';

test.describe('Farmer Application — age validation', () => {
    test('blocks an applicant younger than 18 from continuing', async ({ page }) => {
        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill('Playwright');
        await page.getByLabel('Last Name').fill('Underage');
        await page.getByLabel('Birth Date').fill('2012-01-15');
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');

        await page.getByRole('button', { name: 'Continue' }).click();

        await expect(page.getByText('Applicant must be at least 18 years old.', { exact: true })).toBeVisible();
        await expect(page.getByText('Location & Association', { exact: true })).not.toBeVisible();
    });
});
