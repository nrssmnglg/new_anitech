import { expect, test } from '@playwright/test';

test.describe('Farmer Application — tracking', () => {
    test.setTimeout(75_000);

    test('tracks a newly submitted sample application', async ({ page }) => {
        const id = Date.now();
        const birthDate = '1990-01-15';
        const fullName = `Playwright Track${id}`;

        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill('Playwright');
        await page.getByLabel('Last Name').fill(`Track${id}`);
        await page.getByLabel('Birth Date').fill(birthDate);
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Mobile Number').fill(`09${String(id).slice(-9)}`);
        await page.getByLabel('Email Address').fill(`playwright.track.${id}@anitech.local`);
        await page.getByRole('button', { name: 'Continue' }).click();
        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
        const association = page.getByLabel('Association');
        await expect(association.locator('option').nth(1)).toBeAttached();
        await association.selectOption({ index: 1 });
        await page.getByLabel('Home Address').fill('Playwright tracking test address, Abanon');
        await page.getByRole('button', { name: 'Continue' }).click();
        await page.getByRole('button', { name: 'Continue' }).click();
        await page.getByRole('button', { name: /Submit Application/i }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/upload\?/, { timeout: 30_000 });
        const applicationNo = new URL(page.url()).searchParams.get('application_no');
        expect(applicationNo).toBeTruthy();
        await page.goto(`/farmer/app/track-status?application_no=${encodeURIComponent(applicationNo)}&birth_date=${birthDate}`);
        await expect(page.getByRole('heading', { name: 'Application Status' })).toBeVisible({ timeout: 30_000 });
        await expect(page.getByRole('heading', { name: fullName })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Required Documents' })).toBeVisible();
    });
});
