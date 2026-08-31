import { expect, test } from '@playwright/test';

test('Location Management filters associations by search and barangay', async ({ page }) => {
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

  await page.goto('/admin/associations');
  const main = page.getByRole('main');
  const search = main.getByLabel('Search');
  const barangay = main.getByLabel('Barangay');

  await search.fill('ABANON AGRIFARMERS');
  await barangay.selectOption({ label: 'Abanon (BRGY01)' });
  await main.getByRole('button', { name: 'Apply' }).click();

  const table = main.getByRole('table');
  await expect(table).toContainText('ABANON AGRIFARMERS', { timeout: 30_000 });
  await expect(table).toContainText('Abanon');

  await main.getByRole('button', { name: 'Clear' }).click();
  await expect(search).toHaveValue('');
  await expect(barangay).toHaveValue('');
});
