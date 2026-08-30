import { expect, test } from '@playwright/test';

test('Mortuary records filters completed claims', async ({ page }) => {
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
  await main.getByLabel('Status').selectOption({ label: 'Completed' });
  await main.getByRole('button', { name: 'Apply Filters' }).click();
  await expect(main.getByRole('table')).toContainText('Completed');
  await main.getByRole('button', { name: 'Reset' }).click();
  await expect(main.getByLabel('Status')).toHaveValue('');
});
