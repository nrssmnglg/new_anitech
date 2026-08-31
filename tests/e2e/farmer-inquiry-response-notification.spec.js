import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;
const staffEmail = process.env.E2E_STAFF_EMAIL;
const staffPassword = process.env.E2E_STAFF_PASSWORD;

test.describe('Farmer Help — response notification', () => {
    test.setTimeout(240_000);

    test('notifies the farmer when staff responds to a concern', async ({ browser }) => {
        test.skip(
            !farmerEmail || !farmerPassword || !staffEmail || !staffPassword,
            'Set farmer and staff E2E email/password variables before running this test.',
        );

        const id = Date.now();
        const subject = `Playwright notification concern ${id}`;
        const responseText = `Automated staff response for notification test ${id}.`;
        const farmerContext = await browser.newContext();
        const farmerPage = await farmerContext.newPage();
        const staffContext = await browser.newContext();
        const staffPage = await staffContext.newPage();

        try {
            await farmerPage.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
            await farmerPage.getByLabel('Email Address').fill(farmerEmail);
            await farmerPage.locator('#password').fill(farmerPassword);
            await farmerPage.getByRole('button', { name: 'SIGN IN' }).click();
            await expect(farmerPage).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

            await farmerPage.goto('/farmer/app/inquiries');
            await expect(farmerPage.getByRole('heading', { name: 'Inbox' })).toBeVisible({ timeout: 60_000 });
            await farmerPage.locator('.farmer-app__inquiries-plus').click();
            const topic = farmerPage.getByLabel('Topic');
            await expect(topic.locator('option').nth(1)).toBeAttached({ timeout: 60_000 });
            await topic.selectOption({ index: 1 });
            await farmerPage.getByLabel('Subject').fill(subject);
            await farmerPage.getByLabel('Message').fill(`Automated farmer concern for notification test ${id}.`);
            const createResponse = await Promise.all([
                farmerPage.waitForResponse((response) => response.request().method() === 'POST'
                    && new URL(response.url()).pathname.endsWith('/api/farmer/inquiries')),
                farmerPage.getByRole('button', { name: 'Submit Inquiry' }).click(),
            ]).then(([response]) => response);
            expect(createResponse.status()).toBe(201);
            const inquiryId = (await createResponse.json()).data.id;

            await staffPage.goto('/login', { waitUntil: 'domcontentloaded' });
            await staffPage.getByPlaceholder('Email address').fill(staffEmail);
            await staffPage.getByPlaceholder('Password').fill(staffPassword);
            await Promise.all([
                staffPage.waitForResponse((response) => response.request().method() === 'POST'
                    && new URL(response.url()).pathname === '/login'),
                staffPage.getByRole('button', { name: 'SIGN IN' }).click(),
            ]);
            await staffPage.goto(`/admin/queries/${inquiryId}`, { waitUntil: 'domcontentloaded' });
            await expect(staffPage.getByText(subject, { exact: true })).toBeVisible({ timeout: 30_000 });
            await staffPage.getByPlaceholder(/Type your response to/i).fill(responseText);
            await staffPage.getByRole('button', { name: 'Send Response' }).click();
            await expect(staffPage.getByText(responseText, { exact: true })).toBeVisible({ timeout: 30_000 });

            await farmerPage.goto('/farmer/app/notifications');
            const notification = farmerPage.locator('.farmer-app__notifications-card').filter({ hasText: subject });
            await expect(notification).toBeVisible({ timeout: 60_000 });
            await expect(notification.getByText('Inquiry Response Available', { exact: true })).toBeVisible();
        } finally {
            await farmerContext.close();
            await staffContext.close();
        }
    });
});
