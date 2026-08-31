import { expect, test } from '@playwright/test';

test.describe('Farmer Application — document upload', () => {
    test.setTimeout(90_000);

    test('submits one document for a newly created sample application', async ({ page }) => {
        const id = Date.now();
        const birthDate = '1990-01-15';

        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill('Playwright');
        await page.getByLabel('Last Name').fill(`Upload${id}`);
        await page.getByLabel('Birth Date').fill(birthDate);
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Mobile Number').fill(`09${String(id).slice(-9)}`);
        await page.getByLabel('Email Address').fill(`playwright.upload.${id}@anitech.local`);
        await page.getByRole('button', { name: 'Continue' }).click();

        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
        const association = page.getByLabel('Association');
        await expect(association.locator('option').nth(1)).toBeAttached();
        await association.selectOption({ index: 1 });
        await page.getByLabel('Home Address').fill('Playwright document-upload test address, Abanon');
        await page.getByRole('button', { name: 'Continue' }).click();
        await page.getByRole('button', { name: 'Continue' }).click();

        await page.getByRole('button', { name: /Submit Application/i }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/upload\?/, { timeout: 30_000 });
        await expect(page.getByText('Application Number', { exact: true })).toBeVisible();

        const documentInput = page.locator('input[type="file"][accept="image/*,.pdf"]').first();
        await documentInput.setInputFiles({
            name: 'playwright-proof.pdf',
            mimeType: 'application/pdf',
            buffer: Buffer.from('%PDF-1.4\n% Playwright test document\n'),
        });
        await expect(page.getByText('playwright-proof.pdf', { exact: true })).toBeVisible();

        await page.getByRole('button', { name: 'Submit Selected Documents' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/upload-complete\?/, { timeout: 30_000 });
        await expect(page.getByRole('heading', { name: 'Documents Submitted' })).toBeVisible();
    });
});
