import { expect, test } from '@playwright/test';

const farmerCode = 'PW-2026-00001';
const farmerName = 'Playwright Test Farmer 1';

test.describe('Renewal Processing — active queue filters', () => {
    test.describe.configure({ mode: 'serial' });
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
        await page.goto('/admin/renewals', { waitUntil: 'domcontentloaded' });
        await expect(page.getByRole('main').getByRole('heading', { name: 'Farmers Due This Year' })).toBeVisible();
    });

    test('searches for and clears a due farmer in the active queue', async ({ page }) => {
        const main = page.getByRole('main');
        const search = main.getByLabel('Farmer');

        await search.fill(farmerCode);
        await Promise.all([
            page.waitForResponse((response) => (
                response.request().method() === 'GET'
                && new URL(response.url()).pathname === '/admin/renewals'
                && new URL(response.url()).searchParams.get('queue_search') === farmerCode
            ), { timeout: 30_000 }),
            main.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        const queueTable = main.getByRole('table');
        await expect(queueTable).toContainText(farmerName);
        await expect(queueTable).toContainText(farmerCode);

        await Promise.all([
            page.waitForResponse((response) => (
                response.request().method() === 'GET'
                && new URL(response.url()).pathname === '/admin/renewals'
                && !new URL(response.url()).searchParams.get('queue_search')
            ), { timeout: 30_000 }),
            main.getByRole('button', { name: 'Reset' }).click(),
        ]);

        await expect(search).toHaveValue('');
    });

    test('filters the active queue by barangay and member type', async ({ page }) => {
        const main = page.getByRole('main');
        const barangay = main.getByLabel('Barangay');
        const memberType = main.getByLabel('Member Type');

        await barangay.selectOption({ label: 'Abanon' });
        await memberType.selectOption({ label: 'OM - Old Member' });

        await Promise.all([
            page.waitForResponse((response) => {
                if (response.request().method() !== 'GET') return false;

                const url = new URL(response.url());

                return url.pathname === '/admin/renewals'
                    && url.searchParams.has('queue_barangay_id')
                    && url.searchParams.has('queue_member_type_id');
            }, { timeout: 30_000 }),
            main.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await expect(main.getByRole('table')).toContainText(farmerCode);

        await Promise.all([
            page.waitForResponse((response) => {
                if (response.request().method() !== 'GET') return false;

                const url = new URL(response.url());

                return url.pathname === '/admin/renewals'
                    && !url.searchParams.get('queue_barangay_id')
                    && !url.searchParams.get('queue_member_type_id');
            }, { timeout: 30_000 }),
            main.getByRole('button', { name: 'Reset' }).click(),
        ]);

        await expect(barangay).toHaveValue('');
        await expect(memberType).toHaveValue('');
    });
});
