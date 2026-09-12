import { expect, test } from '@playwright/test';

test.describe('Farmer Management — encode old record', () => {
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

        // Navigate explicitly after authentication instead of depending on the login page's redirect timing.
        await page.goto('/admin/farmers/create', { waitUntil: 'domcontentloaded' });
        await expect(page).toHaveURL(/\/admin\/farmers\/create$/);
        await page.getByLabel('Farmer code or name').fill('Playwright New ' + Date.now());
        await page.getByRole('button', { name: 'Search', exact: true }).click();
        await page.getByRole('button', { name: 'This is a new farmer record' }).click();
    });

    test('opens the old-record encoding form', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Encode Old Record' })).toBeVisible();
        await expect(page.getByText('Next Farmer Code', { exact: true })).toBeVisible();

        await expect(page.getByLabel('Member Type')).toBeVisible();
        await expect(page.getByLabel('Registry Status')).toBeVisible();
        await expect(page.getByLabel('First Name')).toBeVisible();
        await expect(page.getByLabel('Last Name')).toBeVisible();
        await expect(page.getByLabel('Birth Date')).toBeVisible();
        await expect(page.getByLabel('Sex')).toBeVisible();
        await expect(page.getByLabel('Barangay')).toBeVisible();
        await expect(page.getByLabel('Association')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Save Old Record' })).toBeVisible();
    });

    test('creates a clearly marked sample old farmer record', async ({ page }) => {
        const identifier = Date.now();
        const firstName = 'Playwright Old';
        const lastName = `Record${identifier}`;
        const fullName = `${firstName} ${lastName}`;

        // A first real option keeps the test independent of the particular member type names in this database.
        await page.getByLabel('Member Type').selectOption({ index: 1 });
        await page.getByLabel('First Name').fill(firstName);
        await page.getByLabel('Last Name').fill(lastName);
        await page.getByLabel('Birth Date').fill('1990-01-15');
        await page.getByLabel('Sex').selectOption('male');
        await page.getByLabel('Mobile Number').fill(`09${String(identifier).slice(-9)}`);

        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
        const association = page.getByLabel('Association');
        await expect(association.locator('option').nth(1)).toBeAttached();
        await association.selectOption({ index: 1 });

        const year = process.env.E2E_HISTORICAL_YEAR || String(new Date().getFullYear());
        await page.getByLabel('Year to record').fill(year);
        await page.getByLabel('Remarks').fill(`Playwright test sample ${identifier}.`);

        const [storeResponse] = await Promise.all([
            page.waitForResponse((response) => (
                response.request().method() === 'POST'
                && new URL(response.url()).pathname === '/admin/farmers'
            ), { timeout: 60_000 }),
            page.getByRole('button', { name: 'Save Old Record' }).click(),
        ]);

        expect([302, 303]).toContain(storeResponse.status());
        await page.waitForURL(
            (url) => /^\/admin\/farmers\/[^/]+$/.test(url.pathname) && url.pathname !== '/admin/farmers/create',
            { timeout: 30_000 },
        );

        await expect(page.getByRole('heading', { name: fullName })).toBeVisible();
        await expect(page.getByText('Old Record', { exact: true }).first()).toBeVisible();
    });
});
