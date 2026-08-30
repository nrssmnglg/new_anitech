import { expect, test } from '@playwright/test';

test.describe('Membership Applications — complete walk-in flow', () => {
    test.setTimeout(120_000);

    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL ?? '');
        await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD ?? '');

        await Promise.all([
            page.waitForURL(/\/admin\//),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);
    });

    test('creates, checks in, pays, and activates a sample walk-in applicant', async ({ page }) => {
        const runId = Date.now();
        const firstName = 'Playwright';
        const lastName = `Flow${runId}`;
        const fullName = `${firstName} ${lastName}`;
        const reference = `PW-APP-${runId}`;

        await page.goto('/admin/membership-applications/create', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill(firstName);
        await page.getByLabel('Last Name').fill(lastName);
        await page.getByLabel('Birth Date').fill('1992-05-20');
        await page.getByLabel('Sex').selectOption('female');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
        await page.getByLabel('Association').selectOption({ index: 1 });
        await page.getByLabel('Address').fill('Playwright automated membership-flow record — Abanon.');
        await page.getByLabel('Application Remarks').fill(
            `Automated end-to-end test ${runId}; this is not a real applicant.`,
        );

        await Promise.all([
            page.waitForURL(/\/admin\/membership-applications\/(?!create(?:\?|$))[^/?]+$/, { timeout: 30_000 }),
            page.getByRole('button', { name: 'Save Membership Application' }).click(),
        ]);
        await expect(page.getByText(fullName, { exact: true })).toBeVisible();

        // Starting intake creates the currently configured required-document rows.
        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && /\/admin\/membership-applications\/[^/]+\/initialize-checklist$/.test(response.url())),
            page.getByRole('button', { name: 'Start Intake Checklist' }).click(),
        ]);
        await expect(page.getByText('Document checklist started. Continue with intake confirmation and review.')).toBeVisible();

        // Walk-in confirmation verifies a received office document. Process every
        // configured requirement before the payment controls are available.
        const confirmButtons = page.getByRole('button', { name: 'Confirm Document' });
        const requiredDocumentCount = await confirmButtons.count();
        expect(requiredDocumentCount).toBeGreaterThan(0);

        for (let index = 0; index < requiredDocumentCount; index += 1) {
            await Promise.all([
                page.waitForResponse((response) => response.request().method() === 'POST'
                    && /\/admin\/membership-applications\/[^/]+\/documents\/[^/]+\/review$/.test(response.url())),
                confirmButtons.first().click(),
            ]);
            await expect(confirmButtons).toHaveCount(requiredDocumentCount - index - 1, { timeout: 20_000 });
        }

        await expect(page.getByText(new RegExp(`${requiredDocumentCount}/${requiredDocumentCount} verified`))).toBeVisible();

        const amountField = page.getByLabel('Amount Paid');
        expect(Number(await amountField.inputValue())).toBeGreaterThan(0);
        await page.getByLabel('Payment Method').fill('cash');
        await page.getByLabel('Reference No.').fill(reference);

        const paymentResponse = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && /\/admin\/membership-applications\/[^/]+\/payment$/.test(response.url())),
            page.getByRole('button', { name: 'Record Payment And Finish' }).click(),
        ]).then(([response]) => response);

        expect([302, 303]).toContain(paymentResponse.status());
        await page.waitForURL('/admin/farmers', { timeout: 30_000 });
        await expect(page.getByText('Payment recorded and membership completed.')).toBeVisible();

        // Farmer List searches individual name fields, so use the unique last
        // name and then verify the complete displayed name in the result row.
        await page.getByLabel('Search').fill(lastName);
        await Promise.all([
            page.waitForURL((url) => url.pathname === '/admin/farmers' && url.searchParams.get('search') === lastName, { timeout: 20_000 }),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await expect(page.locator('table tbody tr')).toContainText(fullName);
        await expect(page.locator('table tbody tr')).toContainText('Active');
    });
});
