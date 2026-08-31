import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — profile validation', () => {
    test.setTimeout(90_000);

    test('rejects an invalid mobile number without saving the profile', async ({ page }) => {
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

        await page.getByLabel('Mobile Number').fill('invalid-number');
        const saveResponse = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'PUT'
                && new URL(response.url()).pathname.endsWith('/api/farmer/profile')),
            page.getByRole('button', { name: 'Save Changes' }).click(),
        ]).then(([response]) => response);

        expect(saveResponse.status()).toBe(422);
        await expect(page.locator('small.farmer-app__field-error')).toHaveText(
            'Use a valid mobile number such as 09171234567 or +639171234567.',
        );
        await expect(page.getByRole('button', { name: 'Save Changes' })).toBeVisible();
    });
});
