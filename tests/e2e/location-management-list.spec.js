import { expect, test } from '@playwright/test';

test.describe('Location Management — lists', () => {
  test.setTimeout(60_000);

  test.beforeEach(async ({ page }) => {
    const email = process.env.E2E_STAFF_EMAIL;
    const password = process.env.E2E_STAFF_PASSWORD;
    if (!email || !password) throw new Error('Set E2E_STAFF_EMAIL and E2E_STAFF_PASSWORD before running this test.');
    await page.goto('/login');
    await page.getByPlaceholder('Email address').fill(email);
    await page.getByPlaceholder('Password').fill(password);
    await Promise.all([
      page.waitForResponse((r) => r.request().method() === 'POST' && new URL(r.url()).pathname === '/login'),
      page.getByRole('button', { name: 'SIGN IN' }).click(),
    ]);
  });

  test('opens barangay and association management lists', async ({ page }) => {
    await page.goto('/admin/barangays');
    const main = page.getByRole('main');
    await expect(main.getByRole('link', { name: 'Add Barangay' })).toBeVisible();
    await expect(main.getByRole('heading', { name: 'Registered Locations' })).toBeVisible();
    await expect(main.getByRole('table').getByRole('columnheader', { name: 'Barangay' })).toBeVisible();

    await page.goto('/admin/associations');
    await expect(main.getByRole('heading', { name: 'Association Management' })).toBeVisible();
    await expect(main.getByRole('link', { name: 'Add Association' })).toBeVisible();
    await expect(main.getByRole('table').getByRole('columnheader', { name: 'Association' })).toBeVisible();
  });
});
