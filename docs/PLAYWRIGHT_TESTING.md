# Playwright testing guide

This project uses Playwright for end-to-end browser tests. The tests run against the local Laravel app and use the development database configured in `.env`.

## First-time setup

Run this once from the project root to download the Chromium browser used by Playwright:

```powershell
npx.cmd playwright install chromium
```

## Run the tests

The Playwright configuration starts Laravel automatically at `http://127.0.0.1:8000` when it is not already running.

```powershell
# Run all browser tests headlessly
npm.cmd run test:e2e

# Run with a visible browser window
npm.cmd run test:e2e:headed

# Open Playwright's interactive test runner
npm.cmd run test:e2e:ui
```

For a normal local development session, keep Vite running in a separate terminal so Vue and CSS changes update immediately:

```powershell
npm.cmd run dev
```

If Laravel is already running through XAMPP rather than `php artisan serve`, point Playwright to that URL instead:

```powershell
$env:PLAYWRIGHT_BASE_URL = 'http://localhost/new_anitech_v2/public'
npm.cmd run test:e2e:headed
```

## Create a test by recording your actions

1. Start the app and Vite if they are not already running.
2. Run `npm.cmd run test:e2e:codegen`.
3. In the browser that opens, perform one small user flow, such as signing in or creating a farmer record.
4. Copy the generated code into a new file named `tests/e2e/<feature>.spec.js`.
5. Replace fragile generated selectors with user-facing selectors such as `getByRole`, `getByLabel`, or `getByPlaceholder`.
6. Run the new test with `npm.cmd run test:e2e:headed`.

Example test structure:

```js
import { expect, test } from '@playwright/test';

test('staff can open the farmers list', async ({ page }) => {
    await page.goto('/login');
    await page.getByPlaceholder('Email address').fill(process.env.E2E_STAFF_EMAIL);
    await page.getByPlaceholder('Password').fill(process.env.E2E_STAFF_PASSWORD);
    await page.getByRole('button', { name: 'SIGN IN' }).click();

    await expect(page).toHaveURL(/admin/);
    await page.getByRole('link', { name: /farmers/i }).click();
    await expect(page.getByRole('heading', { name: /farmers/i })).toBeVisible();
});
```

Set the credentials only in the terminal session; do not place real passwords in a test file or commit them:

```powershell
$env:E2E_STAFF_EMAIL = 'test-staff@example.test'
$env:E2E_STAFF_PASSWORD = 'your-test-password'
npm.cmd run test:e2e:headed
```

Use dedicated test accounts and data. Avoid destructive flows (deleting users, farmers, or records) against production.

## Review failures

After a failed run, open the HTML report:

```powershell
npm.cmd run test:e2e:report
```

The report includes the failed step, screenshot, video, and trace when available. Test output is kept in `test-results/` and the report in `playwright-report/`; neither is committed to Git.
