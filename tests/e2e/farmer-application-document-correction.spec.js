import { expect, test } from '@playwright/test';

const staffEmail = process.env.E2E_STAFF_EMAIL;
const staffPassword = process.env.E2E_STAFF_PASSWORD;

test.describe('Farmer Application — document correction', () => {
    test.setTimeout(180_000);

    test('replaces a document rejected by staff and makes it ready for verification', async ({ page }) => {
        test.skip(!staffEmail || !staffPassword, 'Set E2E_STAFF_EMAIL and E2E_STAFF_PASSWORD first.');

        const id = Date.now();
        const birthDate = '1990-01-15';
        const lastName = `Correction${id}`;
        const reviewRemark = `Playwright correction required ${id}: please upload a clearer copy.`;
        const originalFile = {
            name: `playwright-original-${id}.pdf`,
            mimeType: 'application/pdf',
            buffer: Buffer.from('%PDF-1.4\n% Playwright original document\n'),
        };
        const replacementFile = {
            name: `playwright-replacement-${id}.pdf`,
            mimeType: 'application/pdf',
            buffer: Buffer.from('%PDF-1.4\n% Playwright replacement document\n'),
        };

        // Farmer submits a new public application and one document.
        await page.goto('/farmer/app/apply', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('First Name').fill('Playwright');
        await page.getByLabel('Last Name').fill(lastName);
        await page.getByLabel('Birth Date').fill(birthDate);
        await page.getByLabel('Gender').selectOption('male');
        await page.getByLabel('Civil Status').selectOption('single');
        await page.getByLabel('Mobile Number').fill(`09${String(id).slice(-9)}`);
        await page.getByLabel('Email Address').fill(`playwright.correction.${id}@anitech.local`);
        await page.getByRole('button', { name: 'Continue' }).click();

        await page.getByLabel('Barangay').selectOption({ label: 'Abanon' });
        const association = page.getByLabel('Association');
        await expect(association.locator('option').nth(1)).toBeAttached();
        await association.selectOption({ index: 1 });
        await page.getByLabel('Home Address').fill('Playwright document-correction test address, Abanon');
        await page.getByRole('button', { name: 'Continue' }).click();
        await page.getByRole('button', { name: 'Continue' }).click();
        await page.getByRole('button', { name: /Submit Application/i }).click();

        await expect(page).toHaveURL(/\/farmer\/app\/upload\?/, { timeout: 30_000 });
        const applicationNo = new URL(page.url()).searchParams.get('application_no');
        expect(applicationNo).toBeTruthy();

        await page.locator('input[type="file"][accept="image/*,.pdf"]').first().setInputFiles(originalFile);
        await page.getByRole('button', { name: 'Submit Selected Documents' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/upload-complete\?/, { timeout: 30_000 });

        // Staff rejects that uploaded document with a clear correction remark.
        await page.goto('/login');
        await page.getByPlaceholder('Email address').fill(staffEmail);
        await page.getByPlaceholder('Password').fill(staffPassword);
        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && new URL(response.url()).pathname === '/login'),
            page.getByRole('button', { name: 'SIGN IN' }).click(),
        ]);

        await page.goto(`/admin/membership-applications/${encodeURIComponent(applicationNo)}`, { waitUntil: 'domcontentloaded' });
        const rejectedDocument = page.locator('article').filter({
            has: page.getByRole('button', { name: 'Reject Document' }),
        }).first();
        await expect(rejectedDocument).toBeVisible({ timeout: 30_000 });
        await rejectedDocument.getByPlaceholder('Optional remarks').fill(reviewRemark);
        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && /\/admin\/membership-applications\/[^/]+\/documents\/[^/]+\/review$/.test(new URL(response.url()).pathname)),
            rejectedDocument.getByRole('button', { name: 'Reject Document' }).click(),
        ]);
        await expect(page.getByText(reviewRemark, { exact: true })).toBeVisible({ timeout: 30_000 });

        // The farmer sees the correction state and replaces the flagged file.
        await page.goto(
            `/farmer/app/track-status?application_no=${encodeURIComponent(applicationNo)}&birth_date=${birthDate}`,
            { waitUntil: 'domcontentloaded' },
        );
        await expect(page.getByRole('heading', { name: 'Application Status' })).toBeVisible({ timeout: 30_000 });
        await expect(page.locator('.farmer-app__track-status-pill')).toHaveText('Correction Needed');
        await expect(page.getByText(reviewRemark, { exact: true })).toBeVisible();
        await page.getByRole('link', { name: 'Replace Required Files' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/upload\?/);

        const replacementInput = page.locator('.farmer-app__upload-item.is-flagged input[type="file"][accept="image/*,.pdf"]');
        await expect(replacementInput).toHaveCount(1);
        await replacementInput.setInputFiles(replacementFile);
        await page.getByRole('button', { name: 'Submit Selected Documents' }).click();
        await expect(page).toHaveURL(/\/farmer\/app\/upload-complete\?/, { timeout: 30_000 });

        // Staff can now verify the replaced document and continue the review.
        await page.goto(`/admin/membership-applications/${encodeURIComponent(applicationNo)}`, { waitUntil: 'domcontentloaded' });
        const confirmReplacement = page.locator('button:not([disabled])').filter({ hasText: 'Confirm Document' });
        await expect(confirmReplacement).toHaveCount(1, { timeout: 30_000 });
        await Promise.all([
            page.waitForResponse((response) => response.request().method() === 'POST'
                && /\/admin\/membership-applications\/[^/]+\/documents\/[^/]+\/review$/.test(new URL(response.url()).pathname)),
            confirmReplacement.click(),
        ]);
        await expect(page.getByRole('button', { name: 'Undo Confirmation' })).toBeVisible({ timeout: 30_000 });
    });
});
