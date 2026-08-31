import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — create concern', () => {
    test.setTimeout(90_000);

    test('submits a clearly marked support concern and shows it in inbox', async ({ page }) => {
        test.skip(!farmerEmail || !farmerPassword, 'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD first.');
        const id = Date.now();
        const subject = `Playwright concern ${id}`;
        const message = `Automated Playwright test concern ${id}. Please disregard this dedicated test record.`;

        await page.goto('/farmer/app/login');
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/);

        await page.goto('/farmer/app/inquiries');
        await expect(page.getByRole('heading', { name: 'Inbox' })).toBeVisible({ timeout: 60_000 });
        await page.locator('.farmer-app__inquiries-plus').click();

        const topic = page.getByLabel('Topic');
        await expect(topic.locator('option').nth(1)).toBeAttached({ timeout: 60_000 });
        await topic.selectOption({ index: 1 });
        await page.getByLabel('Subject').fill(subject);
        await page.getByLabel('Message').fill(message);

        const response = await Promise.all([
            page.waitForResponse((item) => item.request().method() === 'POST'
                && new URL(item.url()).pathname.endsWith('/api/farmer/inquiries')),
            page.getByRole('button', { name: 'Submit Inquiry' }).click(),
        ]).then(([item]) => item);

        expect(response.status()).toBe(201);
        await expect(page.getByText(subject, { exact: true })).toBeVisible({ timeout: 30_000 });
    });
});
