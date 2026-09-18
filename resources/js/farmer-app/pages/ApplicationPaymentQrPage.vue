<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { apiGet } from '../services/api';
import { resolveFarmerAppBasePath } from '../utils/paths';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const error = ref('');
const qrPayment = ref(null);
let pollTimer = null;
const query = computed(() => ({ transaction: String(route.query.transaction ?? 'application'), application_no: String(route.query.application_no ?? ''), birth_date: String(route.query.birth_date ?? ''), renewal_id: String(route.query.renewal_id ?? '') }));
const isRenewal = computed(() => query.value.transaction === 'renewal');
const hasLookup = computed(() => isRenewal.value ? Boolean(query.value.renewal_id) : Boolean(query.value.application_no && query.value.birth_date));
const paymentBreakdown = computed(() => {
    const breakdown = qrPayment.value?.breakdown ?? {};
    return [{ label: 'Membership Fee', amount: breakdown.membership_fee }, { label: 'Annual Due', amount: breakdown.annual_due }, { label: 'Mortuary Fee', amount: breakdown.mortuary_fee }].filter((item) => Number(item.amount || 0) > 0);
});
const trackStatusTarget = computed(() => isRenewal.value ? { name: 'payments' } : { name: 'track-status', query: { application_no: query.value.application_no, birth_date: query.value.birth_date } });

function clearPoll() { if (pollTimer) { window.clearTimeout(pollTimer); pollTimer = null; } }
async function handleRedirect(target) {
    if (!target) return;
    const url = new URL(target, window.location.origin);
    const base = resolveFarmerAppBasePath();
    if (url.pathname.startsWith(`${base}/`) || url.pathname === base) {
        await router.push(`${url.pathname.slice(base.length) || '/'}${url.search}`);
        return;
    }
    window.location.href = target;
}
async function loadQrPayment() {
    if (!hasLookup.value) {
        await router.replace(isRenewal.value ? { name: 'payments' } : { name: 'track' });
        return;
    }
    loading.value = true;
    error.value = '';
    try {
        const response = isRenewal.value
            ? await apiGet(`/renewals/${encodeURIComponent(query.value.renewal_id)}/payment/qr`)
            : await apiGet(`/application/${encodeURIComponent(query.value.application_no)}/payment/qr`, { params: { birth_date: query.value.birth_date } });
        if (response?.data?.redirect_url) { await handleRedirect(response.data.redirect_url); return; }
        qrPayment.value = response?.data ?? null;
    } catch (err) {
        const redirectUrl = err?.response?.data?.data?.redirect_url;
        if (redirectUrl) { await handleRedirect(redirectUrl); return; }
        error.value = err?.apiMessage || 'Unable to load the QR payment screen.';
        qrPayment.value = null;
    } finally { loading.value = false; }
}
function schedulePoll() {
    clearPoll();
    if (!qrPayment.value || qrPayment.value.is_expired) return;
    pollTimer = window.setTimeout(async () => { await loadQrPayment(); schedulePoll(); }, 5000);
}
onMounted(async () => { await loadQrPayment(); schedulePoll(); });
onBeforeUnmount(clearPoll);
</script>

<template>
    <div class="farmer-payment-qr">
        <main class="farmer-payment-qr__shell">
            <header class="farmer-payment-qr__header">
                <div><h1>{{ qrPayment?.is_test ? 'Test Payment' : 'Scan to Pay' }}</h1><p>{{ qrPayment?.is_test ? 'Simulate payment through PayMongo. No wallet payment is needed.' : 'Use a QR Ph-supported bank or e-wallet.' }}</p></div>
                <RouterLink :to="trackStatusTarget">Back</RouterLink>
            </header>
            <AppState v-if="error" type="error" :message="error" />
            <AppLoader v-else-if="loading && !qrPayment" />
            <template v-if="qrPayment && !error">
                <section class="farmer-payment-qr__summary">
                    <div><span>Amount due</span><strong>PHP {{ Number(qrPayment.amount_due || 0).toFixed(2) }}</strong></div>
                    <span class="farmer-app__badge" :class="`farmer-app__badge--${qrPayment.assessment_status}`">{{ qrPayment.assessment_status_label }}</span>
                </section>
                <section class="farmer-payment-qr__breakdown">
                    <div class="farmer-payment-qr__row"><span>Reference</span><strong>{{ qrPayment.reference_no }}</strong></div>
                    <div v-for="item in paymentBreakdown" :key="item.label" class="farmer-payment-qr__row"><span>{{ item.label }}</span><strong>PHP {{ Number(item.amount).toFixed(2) }}</strong></div>
                </section>
                <section class="farmer-payment-qr__code" :class="{ 'is-expired': qrPayment.is_expired }">
                    <template v-if="qrPayment.is_test">
                        <p>Test mode — do not scan or pay using a bank or e-wallet.</p>
                        <a v-if="qrPayment.test_url && !qrPayment.is_expired" :href="qrPayment.test_url" target="_blank" rel="noopener noreferrer" class="farmer-payment-qr__primary">Open PayMongo Test Simulator</a>
                        <p v-else-if="!qrPayment.is_expired">PayMongo did not provide a test simulation link. Return to the payment page and generate a new session.</p>
                    </template>
                    <img v-else :src="qrPayment.qr_image_url" :alt="`QR Ph payment code for ${qrPayment.reference_no}`" class="farmer-payment-qr__image">
                    <div class="farmer-payment-qr__expiry"><span>Expires</span><strong>{{ qrPayment.expires_at ? new Date(qrPayment.expires_at).toLocaleString() : '30 minutes after generation' }}</strong></div>
                    <p>{{ qrPayment.is_expired ? 'This payment session has expired. Return to the payment page to generate a new one.' : qrPayment.is_test ? 'Complete the simulation, then check the payment status.' : 'Scan the code and confirm the exact amount, then check the payment status.' }}</p>
                </section>
                <RouterLink :to="trackStatusTarget" class="farmer-payment-qr__primary">Check Payment Status</RouterLink>
            </template>
        </main>
    </div>
</template>

<style scoped>
.farmer-payment-qr { min-height: 100dvh; background: #f3f7f4; }
.farmer-payment-qr__shell { width: min(100%, 430px); min-height: 100dvh; margin: 0 auto; padding: 18px 14px 28px; display: grid; align-content: start; gap: 11px; }
.farmer-payment-qr__header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 2px; }
.farmer-payment-qr__header h1 { margin: 0; color: var(--pwa-ink); font-size: 1.2rem; }.farmer-payment-qr__header p { margin: 3px 0 0; color: var(--pwa-muted); font-size: .76rem; }
.farmer-payment-qr__header a { min-width: 44px; min-height: 44px; display: grid; place-items: center; color: var(--pwa-green-800); text-decoration: none; font-size: .76rem; font-weight: 700; }
.farmer-payment-qr__summary, .farmer-payment-qr__breakdown, .farmer-payment-qr__code { padding: 12px; border: 1px solid var(--pwa-border); border-radius: 10px; background: #fff; box-shadow: var(--pwa-shadow-soft); }
.farmer-payment-qr__summary { display: flex; align-items: center; justify-content: space-between; gap: 12px; }.farmer-payment-qr__summary span:first-child { display: block; color: var(--pwa-muted); font-size: .7rem; }.farmer-payment-qr__summary strong { display: block; margin-top: 2px; color: var(--pwa-green-900); font-size: 1.3rem; }
.farmer-payment-qr__breakdown { display: grid; padding-top: 5px; padding-bottom: 5px; }.farmer-payment-qr__row { min-height: 38px; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-top: 1px solid var(--pwa-border); font-size: .74rem; }.farmer-payment-qr__row:first-child { border-top: 0; }.farmer-payment-qr__row span { color: var(--pwa-muted); }.farmer-payment-qr__row strong { color: var(--pwa-ink); text-align: right; }
.farmer-payment-qr__code { display: grid; justify-items: center; gap: 10px; text-align: center; }.farmer-payment-qr__code.is-expired .farmer-payment-qr__image { opacity: .35; filter: grayscale(1); }.farmer-payment-qr__image { display: block; width: min(100%, 240px); aspect-ratio: 1; border: 1px solid var(--pwa-border); border-radius: 8px; object-fit: contain; }
.farmer-payment-qr__expiry { width: 100%; display: flex; justify-content: space-between; gap: 10px; padding-top: 9px; border-top: 1px solid var(--pwa-border); font-size: .7rem; text-align: left; }.farmer-payment-qr__expiry span { color: var(--pwa-muted); }.farmer-payment-qr__expiry strong { text-align: right; }.farmer-payment-qr__code p { margin: 0; color: var(--pwa-muted); font-size: .72rem; line-height: 1.4; }
.farmer-payment-qr__primary { min-height: 46px; display: grid; place-items: center; padding: 10px 14px; border-radius: 10px; background: var(--pwa-green-800); color: #fff; text-decoration: none; font-size: .8rem; font-weight: 700; }
</style>
