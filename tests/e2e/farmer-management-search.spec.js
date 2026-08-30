import { expect, test } from '@playwright/test';

const farmerSearch = process.env.E2E_FARMER_SEARCH || 'FRM-2026-00050';
const farmerName = process.env.E2E_FARMER_NAME || 'MELCHOR LAVARIAS';

test.describe.configure({ mode: 'serial' });

test.describe('Farmer Management — search', () => {
    test.setTimeout(60_000);

    async function applySearch(page, search) {
        await page.getByLabel('Search').fill(search);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && url.searchParams.get('search') === search,
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
    }

    test.beforeEach(async ({ page }) => {
        await page.goto('/login');

        await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL);
        await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD);
        // Signing in redirects the page. Wait for that redirect before starting
        // another navigation, otherwise the two navigations can race.
        await Promise.all([
            page.waitForURL(/\/admin\//),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        // Navigate directly so this test does not rely on sidebar link text.
        await page.goto('/admin/farmers', { waitUntil: 'domcontentloaded' });
        await expect(page.getByText('Registry Table')).toBeVisible();
    });

    test('finds a farmer by code or name', async ({ page }) => {
        await applySearch(page, farmerSearch);

        await expect(page.getByRole('cell', {
            name: new RegExp(farmerName, 'i'),
        })).toBeVisible();
    });

    test('shows an empty-state message when no farmer matches', async ({ page }) => {
        await applySearch(page, 'farmer-that-does-not-exist-12345');

        await expect(page.getByText('No farmer records found.')).toBeVisible();
    });

    test('clears the search filter with Reset', async ({ page }) => {
        await applySearch(page, farmerSearch);

        await Promise.all([
            page.waitForURL(
                (url) => url.pathname === '/admin/farmers' && !url.searchParams.get('search'),
                { timeout: 20_000 },
            ),
            page.getByRole('button', { name: 'Reset' }).click(),
        ]);

        await expect(page.getByLabel('Search')).toHaveValue('');
    });
});
