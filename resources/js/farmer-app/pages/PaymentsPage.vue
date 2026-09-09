<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppPagination from '../components/ui/AppPagination.vue';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { apiGet } from '../services/api';
import { usePaginatedFetch } from '../composables/usePaginatedFetch';
import { useAppStore } from '../stores/app';
import { formatDateTime, formatMoney } from '../utils/navigation';

const app = useAppStore();
let paymentsPollTimer = null;

const { items: payments, meta, error, loading, fetchPage } = usePaginatedFetch(async (params) => {
    return apiGet('/payments', { params });
}, {}, { cacheKey: 'payments' });

const paymentRecords = computed(() => payments.value ?? []);
const verifiedPayments = computed(() => paymentRecords.value.filter((payment) => payment.status_key === 'verified'));
const verifiedTotal = computed(() => verifiedPayments.value.reduce((total, payment) => total + Number(payment.amount_paid || 0), 0));

const paymentStatusClass = (statusKey) => ({
    verified: 'farmer-app__payments-history-badge--success',
    rejected: 'farmer-app__payments-history-badge--rejected',
    under_verification: 'farmer-app__payments-history-badge--processing',
    submitted: 'farmer-app__payments-history-badge--submitted',
    not_yet_paid: 'farmer-app__payments-history-badge--pending',
}[statusKey] ?? 'farmer-app__payments-history-badge--pending');

function refreshPayments() {
    if (document.visibilityState !== 'visible') {
        return;
    }

    fetchPage().catch(() => {});
}

function startPaymentsPolling() {
    stopPaymentsPolling();

    paymentsPollTimer = window.setInterval(() => {
        refreshPayments();
    }, app.lowDataMode ? 90000 : 60000);
}

function stopPaymentsPolling() {
    if (paymentsPollTimer) {
        window.clearInterval(paymentsPollTimer);
        paymentsPollTimer = null;
    }
}

onMounted(() => {
    fetchPage();
    startPaymentsPolling();
    document.addEventListener('visibilitychange', refreshPayments);
    window.addEventListener('focus', refreshPayments);
});

onBeforeUnmount(() => {
    stopPaymentsPolling();
    document.removeEventListener('visibilitychange', refreshPayments);
    window.removeEventListener('focus', refreshPayments);
});
</script>

<template>
    <div class="farmer-app__payments-screen">
        <main class="farmer-app__payments-shell">
            <header class="farmer-app__payments-header">
                <div>
                    <span>Payments</span>
                    <h1>Payment History</h1>
                </div>
                <div v-if="paymentRecords.length" class="farmer-app__payments-summary">
                    <div>
                        <span>Verified</span>
                        <strong>{{ verifiedPayments.length }}</strong>
                    </div>
                    <div>
                        <span>Total paid</span>
                        <strong>{{ formatMoney(verifiedTotal) }}</strong>
                    </div>
                </div>
            </header>

            <AppLoader v-if="loading && !paymentRecords.length" />
            <AppState v-else-if="error && !paymentRecords.length" type="error" :message="error" />

            <section v-if="paymentRecords.length" class="farmer-app__payments-history-list">
                <article
                    v-for="payment in paymentRecords"
                    :key="payment.id"
                    class="farmer-app__payments-history-card"
                >
                    <div class="farmer-app__payments-history-head">
                        <div>
                            <strong>{{ payment.reference_no || 'No reference number' }}</strong>
                            <p>{{ payment.cycle?.label || 'Payment Record' }}</p>
                        </div>
                        <span class="farmer-app__payments-history-badge" :class="paymentStatusClass(payment.status_key)">
                            {{ payment.status_label }}
                        </span>
                    </div>

                    <div class="farmer-app__payments-history-grid">
                        <div>
                            <span>Date paid</span>
                            <strong>{{ formatDateTime(payment.paid_at) }}</strong>
                        </div>
                        <div>
                            <span>Amount paid</span>
                            <strong>{{ formatMoney(payment.amount_paid) }}</strong>
                        </div>
                        <div>
                            <span>Method</span>
                            <strong>{{ payment.payment_method?.name ?? 'No method recorded' }}</strong>
                        </div>
                        <div>
                            <span>Purpose</span>
                            <strong>{{ payment.cycle?.reference || 'Not recorded' }}</strong>
                        </div>
                    </div>
                </article>
            </section>

            <AppState
                v-else-if="!loading"
                message="No payment history available."
            />

            <div v-if="paymentRecords.length" class="farmer-app__payments-pagination-wrap">
                <AppPagination :meta="meta" @change="(page) => fetchPage({ page })" />
            </div>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__payments-screen {
    min-height: 100%;
    background: transparent;
}

.farmer-app__payments-shell {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    padding: 12px 12px 28px;
    gap: 10px;
}

.farmer-app__payments-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    padding: 0 2px 2px;
}

.farmer-app__payments-header > div:first-child > span {
    display: block;
    margin-bottom: 2px;
    color: var(--pwa-muted);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.farmer-app__payments-header h1 {
    margin: 0;
    color: var(--pwa-ink);
    font-size: 1.35rem;
    line-height: 1.2;
    letter-spacing: -0.025em;
}

.farmer-app__payments-summary {
    display: flex;
    align-items: center;
    gap: 16px;
}

.farmer-app__payments-summary div {
    display: grid;
    gap: 1px;
    text-align: right;
}

.farmer-app__payments-summary span {
    color: var(--pwa-muted);
    font-size: 0.67rem;
}

.farmer-app__payments-summary strong {
    color: var(--pwa-green-900);
    font-size: 0.82rem;
}

.farmer-app__payments-history-list {
    display: grid;
    gap: 8px;
}

.farmer-app__payments-history-card {
    background: #fff;
    border: 1px solid var(--pwa-border);
    box-shadow: none;
    border-radius: 12px;
    padding: 12px;
}

.farmer-app__payments-history-head {
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.farmer-app__payments-history-head strong,
.farmer-app__payments-history-grid strong {
    display: block;
    color: #10231b;
}

.farmer-app__payments-history-head p {
    margin: 2px 0 0;
    color: var(--pwa-muted);
    font-size: 0.75rem;
}

.farmer-app__payments-history-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 12px;
    padding-top: 9px;
    border-top: 1px solid var(--pwa-border);
}

.farmer-app__payments-history-grid span {
    display: block;
    color: var(--pwa-muted);
    font-size: 0.68rem;
    line-height: 1.3;
}

.farmer-app__payments-history-grid strong {
    margin-top: 2px;
    font-size: 0.78rem;
    line-height: 1.35;
}

.farmer-app__payments-history-badge {
    display: inline-flex;
    align-items: center;
    min-height: 25px;
    padding: 0 9px;
    border-radius: 999px;
    font-size: 0.66rem;
    font-weight: 800;
}

.farmer-app__payments-history-badge--success {
    background: #edf7f1;
    color: #1a6b4a;
}

.farmer-app__payments-history-badge--rejected {
    background: #fff3f2;
    color: #b42318;
}

.farmer-app__payments-history-badge--processing {
    background: #eef4ff;
    color: #2956a3;
}

.farmer-app__payments-history-badge--submitted,
.farmer-app__payments-history-badge--pending {
    background: #f4f4f5;
    color: #52525b;
}

.farmer-app__payments-pagination-wrap {
    padding-top: 2px;
}

@media (max-width: 520px) {
    .farmer-app__payments-header {
        align-items: flex-start;
    }

    .farmer-app__payments-summary {
        gap: 10px;
    }

    .farmer-app__payments-history-head {
        align-items: flex-start;
    }
}
</style>
