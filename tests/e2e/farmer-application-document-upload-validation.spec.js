import { expect, test } from '@playwright/test';

test.describe('Farmer Application — document upload validation', () => {
    test.setTimeout(90_000);

    test('requires at least one file before submitting documents', async ({ page }) => {
        const id = Date.now();

        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill('Playwright');
        await page.getByLabel('Last Name').fill(`UploadValidation${id}`);
        await page.getByLabel('Birth Date').fill('1990-01-15');
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Mobile Number').fill(`09${String(id).slice(-9)}`);
        await page.getByLabel('Email Address').fill(`playwright.upload-validation.${id}@anitech.local`);
        await page.getByRole('button', { name: 'Continue' }).click();

        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
        const association = page.getByLabel('Association');
        await expect(association.locator('option').nth(1)).toBeAttached();
        await association.selectOption({ index: 1 });
        await page.getByLabel('Home Address').fill('Playwright document-upload validation address, Abanon');
        await page.getByRole('button', { name: 'Continue' }).click();
        await page.getByRole('button', { name: 'Continue' }).click();
        await page.getByRole('button', { name: /Submit Application/i }).click();

        await expect(page).toHaveURL(/\/farmer\/app\/upload\?/, { timeout: 30_000 });
        await page.getByRole('button', { name: 'Submit Selected Documents' }).click();
        await expect(page.getByText('Choose at least one file before submitting.', { exact: true })).toBeVisible();
    });
});
