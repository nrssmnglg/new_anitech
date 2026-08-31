import { expect, test } from '@playwright/test';

const staffEmail = process.env.E2E_STAFF_EMAIL;
const staffPassword = process.env.E2E_STAFF_PASSWORD;

test.describe('Farmer Account — setup after approval', () => {
    test.setTimeout(180_000);

    test('creates an account for a newly approved dedicated sample applicant', async ({ page, browser }) => {
        test.skip(!staffEmail || !staffPassword, 'Set E2E_STAFF_EMAIL and E2E_STAFF_PASSWORD first.');

        const id = Date.now();
        const firstName = 'Playwright';
        const lastName = `Account${id}`;
        const fullName = `${firstName} ${lastName}`;
        const birthDate = '1992-05-20';
        const accountEmail = `playwright.account.${id}@anitech.local`;
        const accountPassword = `PlaywrightAccount!${id}`;

        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(staffEmail);
        await page.getByPlaceholder('Password').fill(staffPassword);
        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && new URL(response.url()).pathname === '/login'),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        await page.goto('/admin/membership-applications/create');
        await page.getByLabel('First Name').fill(firstName);
        await page.getByLabel('Last Name').fill(lastName);
        await page.getByLabel('Birth Date').fill(birthDate);
        await page.getByLabel('Sex').selectOption('female');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
        await page.getByLabel('Association').selectOption({ index: 1 });
        await page.getByLabel('Address').fill('Playwright account-setup test record, Abanon.');
        await page.getByLabel('Application Remarks').fill(`Automated account setup test ${id}; not a real applicant.`);

        await Promise.all([
            page.waitForURL(/\/admin\/membership-applications\/(?!create(?:\?|$))[^/?]+$/, { timeout: 30_000 }),
            page.getByRole('button', { name: 'Save Membership Application' }).click(),
        ]);
        const applicationNo = decodeURIComponent(new URL(page.url()).pathname.split('/').pop() || '');
        expect(applicationNo).toMatch(/^APP-/);
        await expect(page.getByText(fullName, { exact: true })).toBeVisible();

        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && /\/admin\/membership-applications\/[^/]+\/initialize-checklist$/.test(new URL(response.url()).pathname)),
            page.getByRole('button', { name: 'Start Intake Checklist' }).click(),
        ]);

        const confirmButtons = page.getByRole('button', { name: 'Confirm Document' });
        await expect(confirmButtons.first()).toBeVisible({ timeout: 30_000 });
        const documentCount = await confirmButtons.count();
        expect(documentCount).toBeGreaterThan(0);

        for (let index = 0; index < documentCount; index += 1) {
            await Promise.all([
                page.waitForResponse((response) => response.request().method() === 'POST'
                    && /\/admin\/membership-applications\/[^/]+\/documents\/[^/]+\/review$/.test(new URL(response.url()).pathname)),
                confirmButtons.first().click(),
            ]);
            await expect(confirmButtons).toHaveCount(documentCount - index - 1, { timeout: 30_000 });
        }

        const amountPaid = page.getByLabel('Amount Paid');
        expect(Number(await amountPaid.inputValue())).toBeGreaterThan(0);
        await page.getByLabel('Payment Method').fill('cash');
        await page.getByLabel('Reference No.').fill(`PW-ACCOUNT-${id}`);
        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && /\/admin\/membership-applications\/[^/]+\/payment$/.test(new URL(response.url()).pathname)),
            page.getByRole('button', { name: 'Record Payment And Finish' }).click(),
        ]);
        await page.waitForURL('/admin/farmers', { timeout: 30_000, waitUntil: 'domcontentloaded' });

        await page.goto(
            `/farmer/app/setup-account?application_no=${encodeURIComponent(applicationNo)}&birth_date=${birthDate}`,
            { waitUntil: 'domcontentloaded' },
        );
        await expect(page.getByRole('heading', { name: 'Account Setup' })).toBeVisible({ timeout: 30_000 });
        await expect(page.getByLabel('Application Number')).toHaveValue(applicationNo);
        await page.getByLabel('Email Address').fill(accountEmail);
        await page.getByLabel('Password', { exact: true }).fill(accountPassword);
        await page.getByLabel('Confirm Password').fill(accountPassword);

        const setupResponse = await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && new URL(response.url()).pathname === '/api/farmer/setup'),
            page.getByRole('button', { name: 'Create Account' }).click(),
        ]).then(([response]) => response);
        expect(setupResponse.status()).toBe(201);

        await page.waitForURL(/\/farmer\/app\/login$/, { timeout: 15_000, waitUntil: 'domcontentloaded' });

        // Keep the staff and new farmer authentication cookies isolated.
        const farmerContext = await browser.newContext();
        const farmerPage = await farmerContext.newPage();
        await farmerPage.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await farmerPage.getByLabel('Email Address').fill(accountEmail);
        await farmerPage.locator('#password').fill(accountPassword);
        await farmerPage.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(farmerPage).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

        // The dashboard loads its profile data asynchronously. The profile
        // page is the stable authenticated destination that displays the
        // newly created farmer account's identity.
        await farmerPage.goto('/farmer/app/profile', { waitUntil: 'domcontentloaded' });
        await expect(farmerPage.getByRole('heading', { name: fullName })).toBeVisible({ timeout: 60_000 });
        await farmerContext.close();
    });
});
