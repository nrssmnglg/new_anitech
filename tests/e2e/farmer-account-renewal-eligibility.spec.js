import { expect, test } from '@playwright/test';

const farmerEmail = process.env.E2E_FARMER_EMAIL;
const farmerPassword = process.env.E2E_FARMER_PASSWORD;

test.describe('Farmer Account — renewal eligibility', () => {
    test.setTimeout(90_000);

    test('shows the eligible or blocked renewal outcome without creating duplicates', async ({ page }) => {
        test.skip(!farmerEmail || !farmerPassword, 'Set E2E_FARMER_EMAIL and E2E_FARMER_PASSWORD first.');

        await page.goto('/farmer/app/login', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Email Address').fill(farmerEmail);
        await page.locator('#password').fill(farmerPassword);
        await page.getByRole('button', { name: 'SIGN IN' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/?$/, { timeout: 30_000 });

        const eligibilityResponse = await page.request.get('/api/farmer/renewals/eligibility', {
            headers: { Accept: 'application/json' },
        });
        expect(eligibilityResponse.ok()).toBeTruthy();
        const eligibility = (await eligibilityResponse.json()).data;
        expect(Number(eligibility.year)).toBeGreaterThan(2020);
        expect(eligibility).toHaveProperty('can_start');
        expect(eligibility).toHaveProperty('can_resume');

        const xsrfToken = (await page.context().cookies())
            .find((cookie) => cookie.name === 'XSRF-TOKEN')?.value;
        expect(xsrfToken).toBeTruthy();
        const requestHeaders = {
            Accept: 'application/json',
            'X-XSRF-TOKEN': decodeURIComponent(xsrfToken),
        };

        // A blocked farmer must receive a clear rejection and no renewal is
        // created. An eligible farmer starts or resumes the same record when
        // the endpoint is called twice.
        const firstResponse = await page.request.post('/api/farmer/renewals', {
            data: { year: eligibility.year },
            headers: requestHeaders,
        });

        if (!firstResponse.ok()) {
            expect(firstResponse.status()).toBe(422);
            const failedStart = await firstResponse.json();
            expect(failedStart.errors?.renewal?.[0] ?? failedStart.message).toBeTruthy();

            await page.goto('/farmer/app/renewals', { waitUntil: 'domcontentloaded' });
            await expect(page.getByRole('button', { name: 'Renewal Not Available' })).toBeVisible({ timeout: 60_000 });
            return;
        }

        const firstRenewal = (await firstResponse.json()).data;

        const secondResponse = await page.request.post('/api/farmer/renewals', {
            data: { year: eligibility.year },
            headers: requestHeaders,
        });
        expect(secondResponse.ok()).toBeTruthy();
        const secondRenewal = (await secondResponse.json()).data;

        // RenewalResource exposes an encrypted public route key as `id`.
        // That encryption is intentionally non-deterministic, so compare the
        // stable renewal reference instead of the opaque key.
        expect(secondRenewal.application_no).toBe(firstRenewal.application_no);
        expect(secondRenewal.year).toBe(firstRenewal.year);

        await page.goto('/farmer/app/renewals', { waitUntil: 'domcontentloaded' });
        await expect(page.getByText(`${eligibility.year} Renewal Record`, { exact: true })).toBeVisible({ timeout: 60_000 });
        await expect(page.getByText(firstRenewal.application_no, { exact: true })).toBeVisible();
    });
});
