import { expect, test } from '@playwright/test';

// This is a dedicated fixture. This test creates real renewal/payment/audit rows
// only for this Playwright farmer; it never operates on a live farmer record.
const farmerCode = 'PW-2026-00003';
const farmerName = 'PLAYWRIGHT TEST FARMER 3';

test.describe('Farmer Management — renewal flow', () => {
    test.setTimeout(90_000);

    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL ?? '');
        await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD ?? '');

        await Promise.all([
            page.waitForURL(/\/admin\//),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        await page.goto('/admin/farmers', { waitUntil: 'domcontentloaded' });
    });

    test('creates a walk-in renewal and records its full payment', async ({ page }) => {
        await page.getByLabel('Search').fill(farmerCode);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('search') === farmerCode,
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        // The application selects the next renewal year that has no recorded annual due.
        await page.getByRole('link', { name: `Process renewal for ${farmerName}` }).click();
        await expect(page.getByRole('heading', { name: 'Renewal Setup' })).toBeVisible();

        const renewalYear = await page.getByLabel('Renewal Year').inputValue();
        const reference = `PW-RENEWAL-${Date.now()}`;

        await page.getByLabel('Remarks').fill(
            `Automated Playwright renewal test for ${farmerCode}, year ${renewalYear}.`,
        );

        await page.getByLabel('Payment Method').selectOption('cash');
        await page.getByLabel('Reference Number').fill(reference);
        await Promise.all([
            page.waitForURL(/\/admin\/farmers\/[^/?]+$/, { timeout: 30_000 }),
            page.getByRole('button', { name: 'Create & Complete Renewal' }).click(),
        ]);
        await expect(page.getByRole('heading', { name: farmerName })).toBeVisible();
        await expect(page.getByText('Membership', { exact: true })).toBeVisible();
    });
});
