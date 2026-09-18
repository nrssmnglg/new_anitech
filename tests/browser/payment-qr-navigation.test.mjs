// Run after npm run build: node --test tests/browser/payment-qr-navigation.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { chromium } from '@playwright/test';

for (const lateResponse of ['pending', 'paid', 'error']) {
    test(`leaving QR payment ignores a delayed ${lateResponse} response`, async () => {
        const manifest = JSON.parse(await readFile('public/build/manifest.json', 'utf8'));
        const entry = manifest['resources/js/farmer-app/app.js'].file;
        const browser = await chromium.launch();
        try {
            const page = await browser.newPage({ serviceWorkers: 'block' });
            let releaseResponse;
            let requestStarted;
            const pendingResponse = new Promise(resolve => { releaseResponse = resolve; });
            const started = new Promise(resolve => { requestStarted = resolve; });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', async route => {
                const url = new URL(route.request().url());
                if (url.pathname.startsWith('/build/assets/')) {
                    return route.fulfill({
                        contentType: url.pathname.endsWith('.js') ? 'text/javascript' : 'text/css',
                        body: await readFile(`public${url.pathname}`),
                    });
                }
                if (url.pathname.endsWith('/payment/qr') && url.pathname.startsWith('/api/')) {
                    requestStarted();
                    await pendingResponse;
                    return route.fulfill({
                        status: lateResponse === 'error' ? 409 : 200,
                        json: { data: lateResponse === 'pending'
                            ? { reference_no: 'APP-2026-DEMO', amount_due: 350, is_expired: false, is_test: true }
                            : { redirect_url: '/farmer/app/track-status?application_no=APP-2026-DEMO' } },
                    });
                }
                if (url.pathname === '/api/farmer/application/track') {
                    return route.fulfill({ json: { data: {
                        application_no: 'APP-2026-DEMO', status: 'approved',
                        farmer: { name: 'Test Applicant', membership_status: 'active' },
                        documents: [], payment: { is_verified: true },
                        account: { can_setup: true, has_account: false },
                    } } });
                }
                if (route.request().isNavigationRequest()) {
                    return route.fulfill({ contentType: 'text/html', body: `
                        <script>window.__FARMER_PWA__ = {authenticated:false, apiBase:'/api/farmer', appBase:'/farmer/app'};</script>
                        <div id="farmer-pwa-app"></div><script type="module" src="/build/${entry}"></script>
                    ` });
                }
                return route.fulfill({ status: 404, body: '' });
            });
            await page.goto('http://localhost/farmer/app/payment/qr?application_no=APP-2026-DEMO&birth_date=1990-01-01');
            await started;
            await page.getByRole('link', { name: 'Back', exact: true }).click();
            await page.getByRole('link', { name: 'Account Setup', exact: true }).click();
            await page.getByLabel('Email Address').fill('applicant@example.test');
            releaseResponse();
            // Wait beyond the old QR screen's five-second polling interval.
            await page.waitForTimeout(5500);
            assert.equal(new URL(page.url()).pathname, '/farmer/app/setup-account');
            assert.equal(await page.getByLabel('Email Address').inputValue(), 'applicant@example.test');
            assert.deepEqual(errors, []);
        } finally {
            await browser.close();
        }
    });
}
