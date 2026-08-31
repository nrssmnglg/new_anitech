import { expect, test } from '@playwright/test';

test.describe('Farmer Application — saved draft', () => {
    test('restores entered personal information after a page reload', async ({ page }) => {
        const firstName = `PlaywrightDraft${Date.now()}`;

        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill(firstName);
        await page.getByLabel('Last Name').fill('Draft');
        await page.getByLabel('Birth Date').fill('1990-01-15');

        await page.reload({ waitUntil: 'domcontentloaded' });

        await expect(page.getByLabel('First Name')).toHaveValue(firstName);
        await expect(page.getByLabel('Last Name')).toHaveValue('Draft');
        await expect(page.getByLabel('Birth Date')).toHaveValue('1990-01-15');
    });
});
