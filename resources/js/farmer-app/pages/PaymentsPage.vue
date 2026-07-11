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
        <div class="farmer-app__payments-backdrop"></div>

        <main class="farmer-app__payments-shell">
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
                            <span>Date Paid</span>
                            <strong>{{ formatDateTime(payment.paid_at) }}</strong>
                        </div>
                        <div>
                            <span>Amount Paid</span>
                            <strong>{{ formatMoney(payment.amount_paid) }}</strong>
                        </div>
                        <div>
                            <span>Payment Method</span>
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
.farmer-app__payments-history-list {
    display: grid;
    gap: 12px;
}

.farmer-app__payments-history-card {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(22, 63, 49, 0.1);
    box-shadow: 0 10px 24px rgba(20, 48, 37, 0.06);
    backdrop-filter: blur(10px);
    border-radius: 22px;
    padding: 18px;
}

.farmer-app__payments-history-head {
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.farmer-app__payments-history-head strong,
.farmer-app__payments-history-grid strong {
    display: block;
    color: #10231b;
}

.farmer-app__payments-history-head p {
    margin: 6px 0 0;
    color: #737874;
}

.farmer-app__payments-history-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.farmer-app__payments-history-grid span {
    display: block;
    color: #737874;
    font-size: 0.8rem;
}

.farmer-app__payments-history-badge {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 0.74rem;
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
</style>
