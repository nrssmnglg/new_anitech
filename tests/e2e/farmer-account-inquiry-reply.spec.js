import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — concern reply', () => {
    test.setTimeout(120_000);

    test('creates a concern and sends a follow-up reply', async ({ page }) => {
        test.skip(
            !farmerEmail || !farmerPassword,
            'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD before running this test.',
        );

        const id = Date.now();
        const subject = `Playwright reply concern ${id}`;
        const message = `Initial automated Playwright concern ${id}.`;
        const reply = `Automated Playwright follow-up reply ${id}.`;

        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

        await page.goto('/farmer/app/inquiries');
        await expect(page.getByRole('heading', { name: 'Inbox' })).toBeVisible({ timeout: 60_000 });
        await page.locator('.farmer-app__inquiries-plus').click();
        const topic = page.getByLabel('Topic');
        await expect(topic.locator('option').nth(1)).toBeAttached({ timeout: 60_000 });
        await topic.selectOption({ index: 1 });
        await page.getByLabel('Subject').fill(subject);
        await page.getByLabel('Message').fill(message);

        const createResponse = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && new URL(response.url()).pathname.endsWith('/api/farmer/inquiries')),
            page.getByRole('button', { name: 'Submit Inquiry' }).click(),
        ]).then(([response]) => response);

        expect(createResponse.status()).toBe(201);
        const inquiryId = (await createResponse.json()).data.id;
        expect(inquiryId).toBeTruthy();

        await page.goto(`/farmer/app/inquiries/${inquiryId}`);
        await expect(page.getByRole('heading', { name: subject })).toBeVisible({ timeout: 30_000 });
        await expect(page.getByText(message, { exact: true })).toBeVisible();

        await page.getByPlaceholder('Type here...').fill(reply);
        const replyResponse = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && new URL(response.url()).pathname.endsWith(`/api/farmer/inquiries/${inquiryId}/reply`)),
            page.getByRole('button', { name: 'Send reply' }).click(),
        ]).then(([response]) => response);

        expect(replyResponse.status()).toBe(200);
        await expect(page.getByText(reply, { exact: true })).toBeVisible({ timeout: 30_000 });
    });
});
