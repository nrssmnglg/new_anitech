import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;
const staffEmail = process.env.E2E_STAFF_EMAIL;
const staffPassword = process.env.E2E_STAFF_PASSWORD;

test.describe('Farmer Account — full renewal payment flow', () => {
    test.setTimeout(120_000);

    test('starts a renewal, records payment as staff, and shows it to the farmer', async ({ browser }) => {
        test.skip(
            !farmerEmail || !farmerPassword || !staffEmail || !staffPassword,
            'Set E2E_FARMER_EMAIL, E2E_FARMER_PASSWORD, E2E_STAFF_EMAIL, and E2E_STAFF_PASSWORD before running this test.',
        );

        const farmerContext = await browser.newContext();
        const farmerPage = await farmerContext.newPage();
        const staffContext = await browser.newContext();
        const staffPage = await staffContext.newPage();
        const reference = `PW-FARMER-RENEWAL-${Date.now()}`;

        try {
            await farmerPage.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
            await farmerPage.getByLabel('Email Address').fill(farmerEmail);
            await farmerPage.locator('#password').fill(farmerPassword);
            await farmerPage.getByRole('button', { name: 'SIGN IN' }).click();
            await expect(farmerPage).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

            await farmerPage.goto('/farmer/app/renewals');
            const renewalAction = farmerPage.getByRole('button', { name: /^(Start Renewal|Continue Renewal)$/ });
            await expect(renewalAction).toBeVisible({ timeout: 60_000 });
            await renewalAction.click();
            await expect(farmerPage).toHaveURL(/\/farmer\/app\/payment\/qr\?transaction=renewal&renewal_id=/, { timeout: 30_000 });

            const renewalKey = new URL(farmerPage.url()).searchParams.get('renewal_id');
            expect(renewalKey).toBeTruthy();
            await expect(farmerPage.getByRole('heading', { name: 'Scan To Pay' })).toBeVisible({ timeout: 30_000 });

            await staffPage.goto('/login', { waitUntil: 'domcontentloaded' });
            await staffPage.getByPlaceholder('Email address').fill(staffEmail);
            await staffPage.getByPlaceholder('Password').fill(staffPassword);
            await Promise.all([
                staffPage.waitForResponse((response) => response.request().method() === 'POST'
                    && new URL(response.url()).pathname === '/login'),
                staffPage.getByRole('button', { name: 'SIGN IN' }).click(),
            ]);

            await staffPage.goto(`/admin/renewals/${encodeURIComponent(renewalKey)}`, { waitUntil: 'domcontentloaded' });
            const amountField = staffPage.getByLabel('Amount Paid');
            await expect(amountField).toBeVisible({ timeout: 30_000 });
            expect(Number(await amountField.inputValue())).toBeGreaterThan(0);
            await staffPage.getByLabel('Payment Method').selectOption('cash');
            await staffPage.getByLabel('Reference Number').fill(reference);

            const paymentResponse = await Promise.all([
                staffPage.waitForResponse((response) => response.request().method() === 'POST'
                    && /\/admin\/renewals\/[^/]+\/payment$/.test(new URL(response.url()).pathname)),
                staffPage.getByRole('button', { name: 'Record & Complete Renewal' }).click(),
            ]).then(([response]) => response);

            expect([302, 303]).toContain(paymentResponse.status());
            // The redirect target does not render the flash message consistently;
            // verify the durable payment-history entry instead.
            await expect(staffPage.getByText(reference, { exact: true })).toBeVisible({ timeout: 30_000 });

            await farmerPage.goto('/farmer/app/renewals');
            await expect(farmerPage.locator('.farmer-app__renewals-list')).toBeVisible({ timeout: 60_000 });
            await expect(farmerPage.getByText(reference, { exact: true })).toBeVisible();
        } finally {
            await farmerContext.close();
            await staffContext.close();
        }
    });
});
