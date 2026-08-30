import { expect, test } from '@playwright/test';

test.describe('Farmer Management — export options', () => {
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
        await expect(page.getByText('Registry Table')).toBeVisible();
    });

    test('opens and closes the PDF export column selector', async ({ page }) => {
        await page.getByRole('button', { name: 'Export PDF' }).last().click();

        await expect(page.getByText('Export Columns', { exact: true })).toBeVisible();
        await expect(page.getByText('Farmer Code', { exact: true })).toBeVisible();
        await expect(page.getByText('Full Name', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Export PDF' }).last()).toBeVisible();

        await page.getByRole('button', { name: 'Cancel' }).click();
        await expect(page.getByText('Export Columns', { exact: true })).not.toBeVisible();
    });

    test('downloads a PDF farmer registry export', async ({ page }) => {
        await page.getByRole('button', { name: 'Export PDF' }).last().click();

        const exportModal = page.locator('section').filter({
            has: page.getByText('Export Columns', { exact: true }),
        });

        await expect(exportModal).toBeVisible();

        const downloadPromise = page.waitForEvent('download');
        await exportModal.getByRole('button', { name: 'Export PDF' }).click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.pdf$/i);
    });

    test('downloads an Excel farmer registry export', async ({ page }) => {
        await page.getByRole('button', { name: 'Export Excel' }).last().click();

        const exportModal = page.locator('section').filter({
            has: page.getByText('Export Columns', { exact: true }),
        });

        await expect(exportModal).toBeVisible();

        const downloadPromise = page.waitForEvent('download');
        await exportModal.getByRole('button', { name: 'Export Excel' }).click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.xlsx$/i);
    });
});
