import { expect, test } from '@playwright/test';

test('Mortuary records export downloads an Excel report', async ({ page }) => {
  test.setTimeout(60_000);
  const email = process.env.E2E_STAFF_EMAIL;
  const password = process.env.E2E_STAFF_PASSWORD;
  if (!email || !password) throw new Error('Set E2E_STAFF_EMAIL and E2E_STAFF_PASSWORD before running this test.');

  await page.goto('/login');
  await page.getByPlaceholder('Email address').fill(email);
  await page.getByPlaceholder('Password').fill(password);
  await Promise.all([
    page.waitForResponse((response) => response.request().method() === 'POST' && new URL(response.url()).pathname === '/login'),
    page.getByRole('button', { name: 'SIGN IN' }).click(),
  ]);

  await page.goto('/admin/mortuary-claims?section=records');
  const main = page.getByRole('main');
  await expect(main.getByRole('heading', { name: 'Mortuary Records' })).toBeVisible();
  await main.getByRole('button', { name: 'Export', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Generate Mortuary Records' })).toBeVisible();
  await page.getByRole('button', { name: 'Excel' }).click();

  const [download] = await Promise.all([
    page.waitForEvent('download'),
    page.getByRole('button', { name: 'Export Excel' }).click(),
  ]);

  expect(download.suggestedFilename()).toMatch(/\.xlsx$/i);
});
