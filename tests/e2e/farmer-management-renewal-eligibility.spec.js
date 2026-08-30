import { expect, test } from '@playwright/test';

// This test changes and restores only a dedicated Playwright fixture.
const farmerCode = 'PW-2026-00002';
const inactiveReason = 'Playwright renewal eligibility test';
const renewalDisabledReason = 'This farmer is inactive and cannot be renewed from this screen.';

test.describe('Farmer Management — renewal eligibility', () => {
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
        await page.getByLabel('Search').fill(farmerCode);

        await Promise.all([
            page.waitForURL((url) => url.searchParams.get('search') === farmerCode, { timeout: 20_000 }),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
    });

    async function updateStatus(page, status, reason = '') {
        await page.locator('table tbody input[type="checkbox"]').first().check();
        await page.getByRole('button', { name: 'Review' }).click();

        const dialog = page.locator('section').filter({
            has: page.getByRole('heading', { name: 'Status review' }),
        });
        const statusSelect = dialog.locator('label')
            .filter({ hasText: /^Review Status/ })
            .locator('select');

        await statusSelect.selectOption({ label: status });
        if (reason) {
            await dialog.getByLabel('Inactive Reason').fill(reason);
        }

        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && response.url().endsWith('/admin/farmers/bulk-status-review')),
            dialog.getByRole('button', { name: 'Apply Action' }).click(),
        ]);
        await expect(page.getByText('1 farmer record(s) updated for status review.')).toBeVisible({ timeout: 20_000 });
    }

    test('disables renewal for an inactive farmer and restores the fixture', async ({ page }) => {
        await updateStatus(page, 'Inactive', inactiveReason);

        try {
            await expect(page.locator('table tbody tr')).toContainText('Inactive');
            await expect(page.getByLabel(`Renewal unavailable: ${renewalDisabledReason}`)).toBeVisible();
        } finally {
            // Keep the test fixture usable by subsequent Farmer List tests.
            await updateStatus(page, 'Active');
            await expect(page.locator('table tbody tr')).toContainText('Active');
        }
    });
});
