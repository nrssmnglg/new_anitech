import { expect, test } from '@playwright/test';

const farmerCode = 'PW-2026-00004';
const farmerName = 'Playwright Mortuary Test Farmer';

test.describe('Mortuary Monitoring — complete claim flow', () => {
    test.setTimeout(120_000);

    test.beforeEach(async ({ page }) => {
        const email = process.env.E2E_STAFF_EMAIL;
        const password = process.env.E2E_STAFF_PASSWORD;

        if (!email || !password) {
            throw new Error('Set E2E_STAFF_EMAIL and E2E_STAFF_PASSWORD in this CMD window before running this test.');
        }

        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(email);
        await page.getByPlaceholder('Password').fill(password);

        const [loginResponse] = await Promise.all([
            page.waitForResponse((response) => (
                response.request().method() === 'POST'
                && new URL(response.url()).pathname === '/login'
            )),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        expect([302, 303]).toContain(loginResponse.status());
    });

    test('files and releases a claim for the dedicated test farmer', async ({ page }) => {
        await page.goto('/admin/mortuary-claims/create', { waitUntil: 'domcontentloaded' });

        await expect(page.getByRole('heading', { name: 'File Mortuary Claim' })).toBeVisible();
        const farmerSelector = page.getByRole('button', { name: new RegExp(`${farmerName}.*${farmerCode}`, 'i') });
        await expect(farmerSelector).toBeVisible();
        await farmerSelector.click();

        await expect(page.getByText(farmerName, { exact: true }).first()).toBeVisible();
        const amount = page.getByLabel('Claim Amount (PHP)');
        expect(Number(await amount.inputValue())).toBeGreaterThan(0);

        for (const checklistItem of await page.getByRole('checkbox').all()) {
            await checklistItem.check();
        }

        await page.getByLabel('Full Name of Claimer').fill('Playwright Claimant');
        await page.getByLabel('Relationship').fill('Test beneficiary');
        await page.getByLabel('Contact Number').fill('09171234567');
        await page.getByLabel('Current Address').fill('Playwright test address, Abanon');
        await page.getByLabel('Remarks & Processing Notes').fill('Automated Playwright mortuary-claim test.');

        const [storeResponse] = await Promise.all([
            page.waitForResponse((response) => (
                response.request().method() === 'POST'
                && new URL(response.url()).pathname === '/admin/mortuary-claims'
            ), { timeout: 60_000 }),
            page.getByRole('button', { name: 'File Claim' }).click(),
        ]);

        expect([302, 303]).toContain(storeResponse.status());
        await page.waitForURL(
            (url) => url.pathname.startsWith('/admin/mortuary-claims/')
                && url.pathname !== '/admin/mortuary-claims/create',
            { timeout: 30_000 },
        );

        await expect(page.getByRole('heading', { name: 'Claim Released' })).toBeVisible();
        await expect(page.getByText('Released', { exact: true }).first()).toBeVisible();
        await expect(page.getByText('Deceased', { exact: true })).toBeVisible();
        await expect(page.getByText(farmerCode, { exact: true })).toBeVisible();
    });
});
