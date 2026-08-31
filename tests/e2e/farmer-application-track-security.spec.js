import { expect, test } from '@playwright/test';

test.describe('Farmer Application — tracking privacy', () => {
    test('does not reveal an application for an invalid lookup pair', async ({ page }) => {
        await page.goto('/farmer/app/track', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Application Number').fill('APP-TEST-NOT-FOUND');
        await page.getByLabel('Date of Birth').fill('1990-01-15');
        await page.getByRole('button', { name: 'Check Status' }).click();

        await expect(page).toHaveURL(/\/farmer\/app\/track-status\?application_no=/);
        await expect(page.locator('small.farmer-app__field-error')).toHaveText(
            'No membership application matched the application number and birth date you entered.',
            { timeout: 30_000 },
        );
        await expect(page.getByRole('heading', { name: 'Application Status' })).not.toBeVisible();
    });
});
