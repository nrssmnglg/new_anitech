import { expect, test } from '@playwright/test';

test.describe('Membership Applications — rejection and reapplication', () => {
    test.setTimeout(90_000);

    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL ?? '');
        await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD ?? '');

        await Promise.all([
            page.waitForURL(/\/admin\//),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);
    });

    test('rejects a sample application and opens its replacement form', async ({ page }) => {
        const runId = Date.now();
        const firstName = 'Playwright';
        const lastName = `Reject${runId}`;
        const fullName = `${firstName} ${lastName}`;

        await page.goto('/admin/membership-applications/create', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill(firstName);
        await page.getByLabel('Last Name').fill(lastName);
        await page.getByLabel('Birth Date').fill('1992-05-20');
        await page.getByLabel('Sex').selectOption('female');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
        await page.getByLabel('Association').selectOption({ index: 1 });
        await page.getByLabel('Address').fill('Playwright rejection/reapplication test record — Abanon.');
        await page.getByLabel('Application Remarks').fill(`Automated rejection test ${runId}; not a real applicant.`);

        await Promise.all([
            page.waitForURL(/\/admin\/membership-applications\/(?!create(?:\?|$))[^/?]+$/, { timeout: 30_000 }),
            page.getByRole('button', { name: 'Save Membership Application' }).click(),
        ]);
        await expect(page.getByText(fullName, { exact: true })).toBeVisible();

        await page.getByLabel('Rejection Reason').selectOption('missing_documents');
        const applicationUrl = page.url();
        const [rejectionResponse] = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && /\/admin\/membership-applications\/[^/]+\/review$/.test(response.url())),
            page.getByRole('button', { name: 'Reject Application' }).click(),
        ]);

        expect([302, 303]).toContain(rejectionResponse.status());
        // Rejection returns to the same review URL, so wait for Inertia's
        // follow-up page request rather than waiting for a URL change.
        await page.waitForResponse(
            (response) => response.request().method() === 'GET'
                && response.url() === applicationUrl
                && response.status() === 200,
            { timeout: 20_000 },
        );
        await expect(page.getByText('Rejected', { exact: true }).first()).toBeVisible();
        await expect(page.getByRole('link', { name: 'Reapply for Membership' })).toBeVisible();

        await Promise.all([
            page.waitForURL(/\/admin\/membership-applications\/create\?reapply_from_application=/, { timeout: 30_000 }),
            page.getByRole('link', { name: 'Reapply for Membership' }).click(),
        ]);
        await expect(page.getByRole('heading', { name: 'Replacement membership record' })).toBeVisible();
        await expect(page.getByText(fullName, { exact: true })).toBeVisible();
        await expect(page.getByText('Missing Documents', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Save Replacement Application' })).toBeVisible();
    });
});
