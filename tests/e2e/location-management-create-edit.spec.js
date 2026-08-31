import { expect, test } from '@playwright/test';

test('Location Management creates and edits linked sample records', async ({ page }) => {
  test.setTimeout(120_000);
  const email = process.env.E2E_STAFF_EMAIL;
  const password = process.env.E2E_STAFF_PASSWORD;
  if (!email || !password) throw new Error('Set E2E_STAFF_EMAIL and E2E_STAFF_PASSWORD before running this test.');
  const id = Date.now();
  const barangayName = `Playwright Barangay ${id}`;
  const barangayEdited = `${barangayName} Edited`;
  const barangayCode = `PW${String(id).slice(-7)}`;
  const associationName = `Playwright Association ${id}`;
  const associationEdited = `${associationName} Edited`;

  await page.goto('/login');
  await page.getByPlaceholder('Email address').fill(email);
  await page.getByPlaceholder('Password').fill(password);
  await Promise.all([page.waitForResponse(r => r.request().method() === 'POST' && new URL(r.url()).pathname === '/login'), page.getByRole('button', { name: 'SIGN IN' }).click()]);

  await page.goto('/admin/barangays/create');
  await page.getByLabel('Barangay Name').fill(barangayName);
  await page.getByLabel('Barangay Code').fill(barangayCode);
  await Promise.all([page.waitForResponse(r => r.request().method() === 'POST' && new URL(r.url()).pathname === '/admin/barangays'), page.getByRole('button', { name: 'Save Barangay' }).click()]);
  await page.waitForURL(url => url.pathname.startsWith('/admin/barangays/') && !url.pathname.endsWith('/create'), { timeout: 30_000 });
  const barangayUrl = page.url();
  await page.goto(`${barangayUrl}/edit`);
  await page.getByLabel('Barangay Name').fill(barangayEdited);
  await Promise.all([page.waitForResponse(r => r.request().method() === 'PUT'), page.getByRole('button', { name: 'Update Barangay' }).click()]);
  await page.waitForURL(url => !url.pathname.endsWith('/edit'), { timeout: 30_000 });

  await page.goto('/admin/associations/create');
  const barangayOption = `${barangayEdited} (${barangayCode})`;
  await page.getByLabel('Barangay').selectOption({ label: barangayOption });
  await page.getByLabel('Association Name').fill(associationName);
  await page.getByLabel('Association Code').fill(`A${barangayCode}`);
  await page.getByLabel('Association President').fill('Playwright Test President');
  await Promise.all([page.waitForResponse(r => r.request().method() === 'POST' && new URL(r.url()).pathname === '/admin/associations'), page.getByRole('button', { name: 'Save Association' }).click()]);
  await page.waitForURL(url => url.pathname.startsWith('/admin/associations/') && !url.pathname.endsWith('/create'), { timeout: 30_000 });
  const associationUrl = page.url();
  await page.goto(`${associationUrl}/edit`);
  await page.getByLabel('Association Name').fill(associationEdited);
  await Promise.all([page.waitForResponse(r => r.request().method() === 'PUT'), page.getByRole('button', { name: 'Save Changes' }).click()]);
  await expect(page.getByText(associationEdited, { exact: true })).toBeVisible();
});
