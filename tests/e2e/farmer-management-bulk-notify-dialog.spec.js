import { expect, test } from '@playwright/test';

const farmerCode = 'PW-2026-00001';

test.describe('Farmer Management — bulk notify dialog', () => {
    test.setTimeout(60_000);

    test.beforeEach(async ({ page }) => {
        await page.goto('/login');

        await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL);
        await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD);

        await Promise.all([
            page.waitForURL(/\/admin\//),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        await page.goto('/admin/farmers', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Search').fill(farmerCode);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('search') === farmerCode,
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await page.locator('table tbody input[type="checkbox"]').first().check();
    });

    test('queues a notification for the connected farmer account', async ({ page }) => {
        await page.getByRole('button', { name: 'Notify' }).click();

        await expect(page.getByRole('heading', { name: 'Notify farmers' })).toBeVisible();
        await page.getByLabel('Subject').fill('Playwright notification test');
        await page.getByLabel('Message').fill('This is an automated local test notification.');

        await page.getByRole('button', { name: 'Apply Action' }).click();

        await expect(page.getByText('1 farmer notification(s) queued.')).toBeVisible({ timeout: 20_000 });
    });
});
