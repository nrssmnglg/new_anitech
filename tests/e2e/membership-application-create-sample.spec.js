import { expect, test } from '@playwright/test';

test.describe('Membership Applications — create sample', () => {
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

    test('creates a clearly marked sample walk-in membership application', async ({ page }) => {
        const runId = Date.now();
        const firstName = 'Playwright';
        const lastName = `Sample${runId}`;
        const fullName = `${firstName} ${lastName}`;

        await page.goto('/admin/membership-applications/create', { waitUntil: 'domcontentloaded' });

        await page.getByLabel('First Name').fill(firstName);
        await page.getByLabel('Last Name').fill(lastName);
        await page.getByLabel('Birth Date').fill('1992-05-20');
        await page.getByLabel('Sex').selectOption('female');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });

        // The form limits Association choices to the chosen barangay.
        await page.getByLabel('Association').selectOption({ index: 1 });
        await page.getByLabel('Address').fill('Playwright automated test record — Abanon.');
        await page.getByLabel('Application Remarks').fill(
            `Automated sample application. Test run ${runId}; do not treat as a real applicant.`,
        );

        const createResponse = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && response.url().endsWith('/admin/membership-applications')),
            page.getByRole('button', { name: 'Save Membership Application' }).click(),
        ]).then(([response]) => response);

        expect([302, 303]).toContain(createResponse.status());
        await page.waitForURL(/\/admin\/membership-applications\/(?!create(?:\?|$))[^/?]+$/, { timeout: 30_000 });
        await expect(page.getByText('Walk-in membership application saved. Continue with document review and payment.')).toBeVisible();
        await expect(page.getByText(fullName, { exact: true })).toBeVisible();
        // New walk-in applications start in the Submitted workflow state.
        await expect(page.locator('div').filter({ hasText: /^Submitted$/ }).first()).toBeVisible();
    });
});
