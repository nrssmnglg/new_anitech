import { expect, test } from '@playwright/test';

test('Location Management searches barangays', async ({ page }) => {
  test.setTimeout(60_000);
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

  await page.goto('/admin/barangays');
  const main = page.getByRole('main');
  const search = main.getByLabel('Search');
  await search.fill('Abanon');
  await main.getByRole('button', { name: 'Apply' }).click();
  await expect(main.getByRole('table')).toContainText('Abanon', { timeout: 30_000 });
  await main.getByRole('button', { name: 'Reset' }).click();
  await expect(search).toHaveValue('');
});
