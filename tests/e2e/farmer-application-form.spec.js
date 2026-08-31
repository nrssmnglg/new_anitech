import { expect, test } from '@playwright/test';

test.describe('Farmer Application — public form', () => {
    test.setTimeout(60_000);

    test('opens the new farmer application form and its first steps', async ({ page }) => {
        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });

        await expect(page.getByRole('link', { name: 'Apply as New Farmer' })).toBeVisible();
        await page.getByRole('link', { name: 'Apply as New Farmer' }).click();

        await expect(page).toHaveURL(/\/farmer\/app\/apply/);
        await expect(page.getByRole('heading', { name: 'Apply as New Farmer' })).toBeVisible();
        await expect(page.getByLabel('First Name')).toBeVisible();
        await expect(page.getByLabel('Last Name')).toBeVisible();
        await expect(page.getByLabel('Birth Date')).toBeVisible();
        await expect(page.getByLabel('Gender')).toBeVisible();
        await expect(page.getByLabel('Mobile Number')).toBeVisible();
        await expect(page.getByLabel('Email Address')).toBeVisible();

        await page.getByLabel('First Name').fill('Playwright');
        await page.getByLabel('Last Name').fill('Applicant');
        await page.getByLabel('Birth Date').fill('1990-01-15');
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Mobile Number').fill('09171234567');

        await page.getByRole('button', { name: 'Continue' }).click();
        await expect(page.getByText('Location & Association', { exact: true })).toBeVisible();
        await expect(page.getByLabel('Barangay')).toBeVisible();
    });

    test('submits a clearly marked new farmer application', async ({ page }) => {
        const id = Date.now();
        const firstName = 'Playwright';
        const lastName = `Applicant${id}`;

        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill(firstName);
        await page.getByLabel('Last Name').fill(lastName);
        await page.getByLabel('Birth Date').fill('1990-01-15');
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Mobile Number').fill(`09${String(id).slice(-9)}`);
        await page.getByLabel('Email Address').fill(`playwright.app.${id}@anitech.local`);
        await page.getByRole('button', { name: 'Continue' }).click();

        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
        const association = page.getByLabel('Association');
        await expect(association.locator('option').nth(1)).toBeAttached();
        await association.selectOption({ index: 1 });
        await page.getByLabel('Home Address').fill('Playwright application test address, Abanon');
        await page.getByRole('button', { name: 'Continue' }).click();

        await expect(page.getByText('Membership & Document Checklist', { exact: true })).toBeVisible();
        await page.getByRole('button', { name: 'Continue' }).click();
        await expect(page.getByText('Review Before Submit', { exact: true })).toBeVisible();

        await page.getByRole('button', { name: /Submit Application/i }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/upload\?/ , { timeout: 30_000 });
        await expect(page.getByText('Application Number', { exact: true })).toBeVisible();
    });
});
