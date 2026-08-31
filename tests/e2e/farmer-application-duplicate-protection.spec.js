import { expect, test } from '@playwright/test';

async function completeApplicationForm(page, applicant) {
    await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
    await page.getByLabel('First Name').fill(applicant.firstName);
    await page.getByLabel('Last Name').fill(applicant.lastName);
    await page.getByLabel('Birth Date').fill('1990-01-15');
    await page.getByLabel('Gender').selectOption('male');
    await page.getByLabel('Civil Status').selectOption('single');
    await page.getByLabel('Mobile Number').fill(applicant.mobile);
    await page.getByLabel('Email Address').fill(applicant.email);
    await page.getByRole('button', { name: 'Continue' }).click();

    await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
    const association = page.getByLabel('Association');
    await expect(association.locator('option').nth(1)).toBeAttached();
    await association.selectOption({ index: 1 });
    await page.getByLabel('Home Address').fill('Playwright duplicate-protection test address, Abanon');
    await page.getByRole('button', { name: 'Continue' }).click();
    await page.getByRole('button', { name: 'Continue' }).click();
}

test.describe('Farmer Application — duplicate protection', () => {
    test.setTimeout(120_000);

    test('blocks a second submission with matching farmer details', async ({ page }) => {
        const id = Date.now();
        const applicant = {
            firstName: 'Playwright',
            lastName: `Duplicate${id}`,
            mobile: `09${String(id).slice(-9)}`,
            email: `playwright.duplicate.${id}@anitech.local`,
        };

        await completeApplicationForm(page, applicant);
        await page.getByRole('button', { name: /Submit Application/i }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/upload\?/, { timeout: 30_000 });

        await completeApplicationForm(page, applicant);
        const duplicateResponse = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && new URL(response.url()).pathname.endsWith('/api/farmer/application')),
            page.getByRole('button', { name: /Submit Application/i }).click(),
        ]).then(([response]) => response);

        expect(duplicateResponse.status()).toBe(422);
        await expect(page.getByText('A possible duplicate farmer record may already exist in the system. If this is your record, do not create another application. Contact the office and provide your full name, birth date, and mobile number for verification.')).toBeVisible();
        await expect(page).toHaveURL(/\/farmer\/app\/apply$/);
    });
});
