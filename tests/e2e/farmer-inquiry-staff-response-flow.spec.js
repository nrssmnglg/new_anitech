import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;
const staffEmail = process.env.E2E_STAFF_EMAIL;
const staffPassword = process.env.E2E_STAFF_PASSWORD;

test.describe('Farmer Help — staff response flow', () => {
    test.setTimeout(150_000);

    test('creates a farmer concern, sends a staff response, and shows it to the farmer', async ({ browser }) => {
        test.skip(
            !farmerEmail || !farmerPassword || !staffEmail || !staffPassword,
            'Set farmer and staff E2E email/password variables before running this test.',
        );

        const id = Date.now();
        const subject = `Playwright staff response ${id}`;
        const concern = `Automated Playwright concern requiring a staff response ${id}.`;
        const responseText = `Automated Playwright staff response ${id}. Your concern has been received.`;
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
            await farmerPage.getByLabel('Message').fill(concern);

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
            const staffResponse = await Promise.all([
                staffPage.waitForResponse((response) => response.request().method() === 'POST'
                    && /\/admin\/queries\/[^/]+\/respond$/.test(new URL(response.url()).pathname)),
                staffPage.getByRole('button', { name: 'Send Response' }).click(),
            ]).then(([response]) => response);
            expect([302, 303]).toContain(staffResponse.status());

            await farmerPage.goto(`/farmer/app/inquiries/${inquiryId}`);
            await expect(farmerPage.getByRole('heading', { name: subject })).toBeVisible({ timeout: 30_000 });
            await expect(farmerPage.getByText(responseText, { exact: true })).toBeVisible({ timeout: 30_000 });
        } finally {
            await farmerContext.close();
            await staffContext.close();
        }
    });
});
