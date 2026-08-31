import { expect, test } from '@playwright/test';

async function reachMembershipStep(page, birthDate) {
    await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
    await page.getByLabel('First Name').fill('Playwright');
    await page.getByLabel('Last Name').fill('MemberType');
    await page.getByLabel('Birth Date').fill(birthDate);
    await page.getByLabel('Gender').selectOption('male');
    await page.getByLabel('Civil Status').selectOption('single');
    await page.getByRole('button', { name: 'Continue' }).click();

    await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
    const association = page.getByLabel('Association');
    await expect(association.locator('option').nth(1)).toBeAttached();
    await association.selectOption({ index: 1 });
    await page.getByLabel('Home Address').fill('Playwright member type test address, Abanon');
    await page.getByRole('button', { name: 'Continue' }).click();
}

test.describe('Farmer Application — member-type guidance', () => {
    test('identifies a standard adult applicant as a New Member', async ({ page }) => {
        await reachMembershipStep(page, '1990-01-15');

        await expect(page.getByText('Estimated Member Type', { exact: true })).toBeVisible();
        await expect(page.getByText('NM', { exact: true })).toBeVisible();
        await expect(page.getByText('New Member', { exact: true })).toBeVisible();
    });

    test('identifies a senior applicant as a New Senior Citizen', async ({ page }) => {
        await reachMembershipStep(page, '1950-01-15');

        await expect(page.getByText('Estimated Member Type', { exact: true })).toBeVisible();
        await expect(page.getByText('NSC', { exact: true })).toBeVisible();
        await expect(page.getByText('New Senior Citizen', { exact: true })).toBeVisible();
    });
});
