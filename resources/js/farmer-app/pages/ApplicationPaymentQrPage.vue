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

const query = computed(() => ({
    transaction: String(route.query.transaction ?? 'application'),
    application_no: String(route.query.application_no ?? ''),
    birth_date: String(route.query.birth_date ?? ''),
    renewal_id: String(route.query.renewal_id ?? ''),
}));

const isRenewal = computed(() => query.value.transaction === 'renewal');
const hasLookup = computed(() => isRenewal.value
    ? Boolean(query.value.renewal_id)
    : Boolean(query.value.application_no && query.value.birth_date));
const paymentBreakdown = computed(() => {
    const breakdown = qrPayment.value?.breakdown ?? {};

    return [
        { label: 'Membership Fee', amount: breakdown.membership_fee },
        { label: 'Annual Due', amount: breakdown.annual_due },
        { label: 'Mortuary Fee', amount: breakdown.mortuary_fee },
    ].filter((item) => Number(item.amount || 0) > 0);
});

const trackStatusTarget = computed(() => isRenewal.value
    ? { name: 'payments' }
    : {
        name: 'track-status',
        query: {
            application_no: query.value.application_no,
            birth_date: query.value.birth_date,
        },
    });

function clearPoll() {
    if (pollTimer) {
        window.clearTimeout(pollTimer);
        pollTimer = null;
    }
}

async function handleRedirect(target) {
    if (!target) {
        return;
    }

    const url = new URL(target, window.location.origin);
    const appBasePath = resolveFarmerAppBasePath();

    if (url.pathname.startsWith(`${appBasePath}/`) || url.pathname === appBasePath) {
        const relativePath = url.pathname.slice(appBasePath.length) || '/';
        await router.push(`${relativePath}${url.search}`);
        return;
    }

    window.location.href = target;
}

async function loadQrPayment() {
    if (!hasLookup.value) {
        error.value = isRenewal.value ? 'Missing renewal record.' : 'Missing application number or birth date.';
        qrPayment.value = null;
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const response = isRenewal.value
            ? await apiGet(`/renewals/${encodeURIComponent(query.value.renewal_id)}/payment/qr`)
            : await apiGet(`/application/${encodeURIComponent(query.value.application_no)}/payment/qr`, {
                params: {
                    birth_date: query.value.birth_date,
                },
            });

        if (response?.data?.redirect_url) {
            await handleRedirect(response.data.redirect_url);
            return;
        }

        qrPayment.value = response?.data ?? null;
    } catch (err) {
        const redirectUrl = err?.response?.data?.data?.redirect_url;

        if (redirectUrl) {
            await handleRedirect(redirectUrl);
            return;
        }

        error.value = err?.apiMessage || 'Unable to load the QR payment screen.';
        qrPayment.value = null;
    } finally {
        loading.value = false;
    }
}

function schedulePoll() {
    clearPoll();

    if (!qrPayment.value || qrPayment.value.is_expired) {
        return;
    }

    pollTimer = window.setTimeout(async () => {
        await loadQrPayment();
        schedulePoll();
    }, 5000);
}

onMounted(async () => {
    await loadQrPayment();
    schedulePoll();
});

onBeforeUnmount(() => {
    clearPoll();
});
</script>

<template>
    <div class="pwa-shell farmer-app__shell">
        <section class="farmer-app__auth-card farmer-app__stack">
            <div class="farmer-app__section-title">
                <div>
                    <h1>Scan To Pay</h1>
                    <p>Use any QR Ph-supported bank or e-wallet app to complete this payment.</p>
                </div>
            </div>

            <RouterLink :to="trackStatusTarget" class="farmer-app__btn farmer-app__btn--soft">
                {{ isRenewal ? 'Back to Payments' : 'Back to Status' }}
            </RouterLink>

            <AppState v-if="error" type="error" :message="error" />
            <AppLoader v-else-if="loading && !qrPayment" />

            <template v-if="qrPayment">
                <div class="farmer-app__split">
                    <strong>PHP {{ Number(qrPayment.amount_due || 0).toFixed(2) }}</strong>
                    <span class="farmer-app__badge" :class="`farmer-app__badge--${qrPayment.assessment_status}`">
                        {{ qrPayment.assessment_status_label }}
                    </span>
                </div>

                <article class="farmer-app__card farmer-app__stack">
                    <div class="farmer-app__split">
                        <span>Reference</span>
                        <strong>{{ qrPayment.reference_no }}</strong>
                    </div>
                    <article v-for="item in paymentBreakdown" :key="item.label" class="farmer-app__track-row">
                        <span>{{ item.label }}</span>
                        <strong>PHP {{ Number(item.amount).toFixed(2) }}</strong>
                    </article>
                </article>

                <article class="farmer-app__card farmer-app__stack">
                    <img
                        :src="qrPayment.qr_image_url"
                        :alt="isRenewal ? `QR Ph payment code for renewal ${qrPayment.reference_no}` : `QR Ph payment code for application ${qrPayment.application_no}`"
                        style="width: 100%; max-width: 320px; align-self: center; border-radius: 16px;"
                    >

                    <div class="farmer-app__split">
                        <span>{{ isRenewal ? 'Renewal Reference' : 'Application No.' }}</span>
                        <strong>{{ isRenewal ? qrPayment.reference_no : qrPayment.application_no }}</strong>
                    </div>
                    <div class="farmer-app__split">
                        <span>Expires</span>
                        <strong>{{ qrPayment.expires_at ? new Date(qrPayment.expires_at).toLocaleString() : '30 minutes after generation' }}</strong>
                    </div>
                    <p class="farmer-app__muted">
                        {{
                            qrPayment.is_expired
                                ? 'This QR code has expired. Go back to the payment panel and generate a new code.'
                                : 'Scan this QR code, confirm the exact amount in your bank or e-wallet app, then return here or the tracking page to check the payment status.'
                        }}
                    </p>
                </article>

                <div class="farmer-app__actions">
                    <RouterLink :to="trackStatusTarget" class="farmer-app__btn farmer-app__btn--primary">
                        Check Payment Status
                    </RouterLink>
                </div>
            </template>
        </section>
    </div>
</template>
