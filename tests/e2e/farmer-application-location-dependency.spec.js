import { expect, test } from '@playwright/test';

test.describe('Farmer Application — location dependency', () => {
    test('selects an association valid for the newly selected barangay', async ({ page }) => {
        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill('Playwright');
        await page.getByLabel('Last Name').fill('Location');
        await page.getByLabel('Birth Date').fill('1990-01-15');
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByRole('button', { name: 'Continue' }).click();

        const barangay = page.getByLabel('Barangay');
        const association = page.getByLabel('Association');
        const barangayValues = await barangay.locator('option').evaluateAll((options) => options
            .map((option) => option.value)
            .filter(Boolean));

        expect(barangayValues.length).toBeGreaterThan(1);
        await barangay.selectOption(barangayValues[0]);
        await expect(association.locator('option').nth(1)).toBeAttached({ timeout: 30_000 });
        await association.selectOption({ index: 1 });

        await barangay.selectOption(barangayValues[1]);
        await expect(association.locator('option').nth(1)).toBeAttached({ timeout: 30_000 });
        const firstAvailableAssociation = await association.locator('option').nth(1).getAttribute('value');
        await expect(association).toHaveValue(firstAvailableAssociation);
    });
});
