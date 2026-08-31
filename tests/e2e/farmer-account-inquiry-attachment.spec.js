import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — concern attachment', () => {
    test.setTimeout(120_000);

    test('submits a concern with a PDF attachment', async ({ page }) => {
        test.skip(
            !farmerEmail || !farmerPassword,
            'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD before running this test.',
        );

        const id = Date.now();
        const subject = `Playwright attachment concern ${id}`;
        const message = `Automated Playwright attachment test ${id}. Please disregard this dedicated test record.`;
        const fileName = `playwright-concern-${id}.pdf`;

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
        await page.locator('input[type="file"][multiple]').setInputFiles({
            name: fileName,
            mimeType: 'application/pdf',
            buffer: Buffer.from('%PDF-1.4\n% Playwright inquiry attachment\n'),
        });
        await expect(page.getByText(fileName, { exact: true })).toBeVisible();

        const createResponse = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && new URL(response.url()).pathname.endsWith('/api/farmer/inquiries')),
            page.getByRole('button', { name: 'Submit Inquiry' }).click(),
        ]).then(([response]) => response);

        expect(createResponse.status()).toBe(201);
        const inquiryId = (await createResponse.json()).data.id;
        await page.goto(`/farmer/app/inquiries/${inquiryId}`);
        await expect(page.getByRole('heading', { name: subject })).toBeVisible({ timeout: 30_000 });
        await expect(page.getByText(fileName, { exact: true })).toBeVisible();
    });
});
