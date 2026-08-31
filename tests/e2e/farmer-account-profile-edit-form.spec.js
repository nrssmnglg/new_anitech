import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — profile edit form', () => {
    test.setTimeout(90_000);

    test('saves a complete profile and restores the original address', async ({ page }) => {
        test.skip(
            !farmerEmail || !farmerPassword,
            'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD before running this test.',
        );

        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

        await page.goto('/farmer/app/profile');
        const editProfile = page.getByRole('button', { name: 'Edit Profile' });
        await expect(editProfile).toBeVisible({ timeout: 60_000 });
        await editProfile.click();
        await expect(page.getByLabel('Civil Status')).toBeVisible();
        await expect(page.getByLabel('Mobile Number')).toBeVisible();
        const address = page.getByLabel('Address');
        const originalAddress = await address.inputValue();
        const temporaryAddress = `Playwright temporary address ${Date.now()}, Abanon`;

        // The dedicated test account starts with these fields empty, but the API
        // requires a complete profile for every save.
        await page.getByLabel('Civil Status').selectOption({ label: 'Single' });
        await page.getByLabel('Mobile Number').fill('09171234567');
        await address.fill(temporaryAddress);
        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'PUT'
                && new URL(response.url()).pathname.endsWith('/profile')),
            page.getByRole('button', { name: 'Save Changes' }).click(),
        ]);
        await expect(page.getByText('Profile updated successfully.', { exact: true })).toBeVisible();
        await expect(page.locator('.farmer-app__profile-edit-preview')).toContainText(temporaryAddress);

        await page.getByRole('button', { name: 'Edit Profile' }).click();
        await page.getByLabel('Address').fill(originalAddress);
        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'PUT'
                && new URL(response.url()).pathname.endsWith('/profile')),
            page.getByRole('button', { name: 'Save Changes' }).click(),
        ]);
        await expect(page.getByText('Profile updated successfully.', { exact: true })).toBeVisible();
        await expect(page.locator('.farmer-app__profile-edit-preview')).toContainText(originalAddress || 'Not provided');
    });
});
