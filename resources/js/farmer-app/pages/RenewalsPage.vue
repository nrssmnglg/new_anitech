<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppPagination from '../components/ui/AppPagination.vue';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { apiGet, apiPost } from '../services/api';
import { usePaginatedFetch } from '../composables/usePaginatedFetch';
import { useLocale } from '../composables/useLocale';
import { useAppStore } from '../stores/app';
import { useSyncQueueStore } from '../stores/syncQueue';
import { formatDateTime, formatMoney } from '../utils/navigation';
import { useRouter } from 'vue-router';

const router = useRouter();
const app = useAppStore();
const syncQueue = useSyncQueueStore();
const { t } = useLocale();
const eligibility = ref(null);
const actionError = ref('');
const actionSuccess = ref('');
const starting = ref(false);
const initialLoadComplete = ref(false);
const { items: renewals, meta, error, loading, fetchPage, fromCache, lastSyncedAt } = usePaginatedFetch(async (params) => {
    const response = await apiGet('/renewals', { params });
    eligibility.value = response?.meta?.eligibility ?? null;
    return response;
}, {}, { cacheKey: 'renewals' });
let renewalsPollTimer = null;

const totalRenewals = computed(() => renewals.value.length);
const currentYearRenewal = computed(() => {
    if (!eligibility.value?.year) {
        return null;
    }

    return renewals.value.find((renewal) => Number(renewal.year) === Number(eligibility.value.year)) ?? null;
});

const eligibilityDeadlineLabel = computed(() => eligibility.value?.deadline ? formatDateTime(eligibility.value.deadline) : 'Not set');
const renewalChecklist = computed(() => eligibility.value?.checklist ?? []);
const hasRenewalChecklist = computed(() => renewalChecklist.value.length > 0);
const renewalFees = computed(() => eligibility.value?.fees ?? {});
const renewalFeeItems = computed(() => ([
    { key: 'mortuary_fee', label: 'Mortuary Fee' },
    { key: 'membership_fee', label: 'Membership Fee' },
]).filter((item) => Number(renewalFees.value?.[item.key] || 0) > 0));
const currentYearPayment = computed(() => currentYearRenewal.value?.latest_payment ?? null);
const hasCurrentYearRenewal = computed(() => Boolean(currentYearRenewal.value));
const currentYearRenewalSettled = computed(() => ['paid', 'overpaid', 'waived'].includes(String(currentYearRenewal.value?.assessment?.status || '').toLowerCase()));
const renewalBlocker = computed(() => {
    if (!eligibility.value) {
        return '';
    }

    if (currentYearRenewalSettled.value) {
        return '';
    }

    if (renewalChecklist.value.some((item) => !item.uploaded || item.needs_resubmission)) {
        return t('renewals.blocked_documents');
    }

    if ((renewalFees.value.status || '').toLowerCase() === 'pending' || (renewalFees.value.total_due || 0) > 0) {
        const activeRenewal = renewals.value.find((item) => ['approved', 'pending', 'submitted', 'under_review'].includes(String(item.status || '').toLowerCase()));

        if (activeRenewal?.assessment && !['paid', 'overpaid', 'waived'].includes(String(activeRenewal.assessment.status || '').toLowerCase())) {
            return t('renewals.blocked_payment');
        }
    }

    if (eligibility.value.state === 'under_review' || /review/i.test(String(eligibility.value.phase || ''))) {
        return t('renewals.blocked_review');
    }

    return '';
});
const renewalWaiting = computed(() => {
    if (!eligibility.value) {
        return '';
    }

    if (currentYearRenewalSettled.value) {
        return '';
    }

    if (renewalBlocker.value === t('renewals.blocked_review')) {
        return t('renewals.waiting_staff');
    }

    if (renewalBlocker.value) {
        return t('renewals.waiting_farmer');
    }

    return '';
});
const renewalHistory = computed(() => {
    return renewals.value
        .flatMap((renewal) => ([
            renewal.submitted_at ? { key: `${renewal.id}-submitted`, label: `${t('renewals.submitted_event')} (${renewal.year})`, at: renewal.submitted_at } : null,
            renewal.reviewed_at ? { key: `${renewal.id}-reviewed`, label: `${t('renewals.reviewed_event')} (${renewal.year})`, at: renewal.reviewed_at } : null,
            renewal.payment_recorded_at || renewal.assessment?.paid_at ? { key: `${renewal.id}-payment`, label: `${t('renewals.payment_recorded_event')} (${renewal.year})`, at: renewal.payment_recorded_at || renewal.assessment?.paid_at } : null,
            renewal.completed_at ? { key: `${renewal.id}-completed`, label: `${t('renewals.completed_event')} (${renewal.year})`, at: renewal.completed_at } : null,
        ]))
        .filter(Boolean)
        .sort((left, right) => new Date(right.at).getTime() - new Date(left.at).getTime());
});
const syncLabel = computed(() => {
    if (fromCache.value) {
        return lastSyncedAt.value ? t('renewals.saved_data_from', { time: formatDateTime(lastSyncedAt.value) }) : t('renewals.saved_data');
    }

    return lastSyncedAt.value ? t('dashboard.last_synced', { time: formatDateTime(lastSyncedAt.value) }) : t('renewals.up_to_date');
});

const badgeClass = (status) => {
    if (status === 'approved' || status === 'completed') {
        return 'farmer-app__renewals-badge--approved';
    }

    if (status === 'rejected') {
        return 'farmer-app__renewals-badge--rejected';
    }

    return 'farmer-app__renewals-badge--pending';
};

const paymentSummary = (renewal) => {
    if (!renewal.assessment) {
        return t('dashboard.no_payment_yet');
    }

    return renewal.assessment.status_label || renewal.assessment.status || `${renewal.assessment.payments_count} payments`;
};

const renewalSteps = (renewal) => {
    const status = String(renewal.status || '').toLowerCase();
    const assessmentStatus = String(renewal.assessment?.status || '').toLowerCase();

    return [
        { key: 'submitted', label: 'Submitted', active: true },
        { key: 'review', label: 'Waiting for Review', active: ['submitted', 'under_review'].includes(status) },
        { key: 'payment', label: 'Waiting for Payment', active: status === 'approved' && !['paid', 'overpaid', 'waived'].includes(assessmentStatus) },
        { key: 'completed', label: 'Completed', active: status === 'completed' || ['paid', 'overpaid', 'waived'].includes(assessmentStatus) },
    ];
};

const continueToPayment = async (renewal) => {
    if (!renewal?.id) {
        return;
    }

    const assessmentStatus = String(renewal?.assessment?.status || '').toLowerCase();
    if (!renewal?.assessment || ['paid', 'overpaid', 'waived'].includes(assessmentStatus)) {
        return;
    }

    const paymentResponse = await apiPost(`/renewals/${encodeURIComponent(renewal.id)}/payment`, {
        payment_method: 'qrph',
    });
    const qrPageUrl = paymentResponse?.data?.qr_page_url;

    if (qrPageUrl) {
        const url = new URL(qrPageUrl, window.location.origin);
        await router.push(`${url.pathname.replace(/.*\/farmer\/app/, '')}${url.search}`);

        return;
    }

    await router.push({ name: 'payments' });
};

const openRenewalRecord = async (renewal) => {
    if (starting.value || currentYearRenewalSettled.value) {
        return;
    }

    starting.value = true;
    actionSuccess.value = '';
    actionError.value = '';

    try {
        await continueToPayment(renewal);
    } catch (err) {
        actionError.value = err?.apiMessage || t('renewals.open_failed');
    } finally {
        starting.value = false;
    }
};

const startOrResumeRenewal = async () => {
    starting.value = true;
    actionError.value = '';
    actionSuccess.value = '';
    const payload = {
        year: eligibility.value?.year,
    };

    if (typeof navigator !== 'undefined' && !navigator.onLine) {
        syncQueue.enqueue({
            type: 'renewal.start',
            url: '/renewals',
            payload,
            meta: {
                year: eligibility.value?.year,
            },
        });
        actionSuccess.value = t('renewals.saved_request');
        starting.value = false;
        return;
    }

    try {
        const response = await apiPost('/renewals', payload);

        const renewal = response?.data ?? null;
        actionSuccess.value = response?.message ?? 'Renewal is ready.';

        if (renewal?.application_no) {
            await fetchPage();
        }

        await continueToPayment(renewal);
    } catch (err) {
        if (!err?.response) {
            syncQueue.enqueue({
                type: 'renewal.start',
                url: '/renewals',
                payload,
                meta: {
                    year: eligibility.value?.year,
                },
            });
            actionSuccess.value = t('renewals.saved_request');
        } else {
            actionError.value = err?.apiMessage || t('renewals.open_failed');
        }
    } finally {
        starting.value = false;
    }
};

function refreshRenewals() {
    if (document.visibilityState !== 'visible') {
        return;
    }

    fetchPage().catch(() => {});
}

async function loadRenewalsPage(params = {}) {
    try {
        await fetchPage(params);
    } finally {
        initialLoadComplete.value = true;
    }
}

function startRenewalsPolling() {
    stopRenewalsPolling();

    renewalsPollTimer = window.setInterval(() => {
        refreshRenewals();
    }, app.lowDataMode ? 45000 : 15000);
}

function stopRenewalsPolling() {
    if (renewalsPollTimer) {
        window.clearInterval(renewalsPollTimer);
        renewalsPollTimer = null;
    }
}

onMounted(() => {
    loadRenewalsPage();
    startRenewalsPolling();
    document.addEventListener('visibilitychange', refreshRenewals);
    window.addEventListener('focus', refreshRenewals);
});

onBeforeUnmount(() => {
    stopRenewalsPolling();
    document.removeEventListener('visibilitychange', refreshRenewals);
    window.removeEventListener('focus', refreshRenewals);
});
</script>

<template>
    <div class="farmer-app__renewals-screen">
        <div class="farmer-app__renewals-backdrop"></div>

        <main class="farmer-app__renewals-shell">
            <AppLoader v-if="!initialLoadComplete && loading && !renewals.length && !eligibility" />
            <AppState v-else-if="!initialLoadComplete && error && !renewals.length && !eligibility" type="error" :message="error" />

            <div v-else class="farmer-app__renewals-stack">
                <AppState v-if="actionError" type="error" :message="actionError" />
                <AppState v-if="actionSuccess" :message="actionSuccess" />
                <AppState v-if="renewalBlocker" type="error" :message="`${t('renewals.blocked_title')}: ${renewalBlocker}`" />
                <AppState v-if="renewalWaiting" :message="`${t('renewals.waiting_title')}: ${renewalWaiting}`" />

                <section v-if="eligibility && !currentYearRenewal" class="farmer-app__renewals-eligibility">
                    <div class="farmer-app__renewals-card-stats">
                        <div>
                            <span>{{ t('renewals.total_due') }}</span>
                            <strong>{{ formatMoney(renewalFees.total_due || 0) }}</strong>
                        </div>
                    </div>

                    <div class="farmer-app__renewals-checklist-grid">
                        <article v-if="hasRenewalChecklist" class="farmer-app__renewals-checklist-card">
                            <strong>Required documents</strong>
                            <ul>
                                <li v-for="item in renewalChecklist" :key="item.type">
                                    {{ item.label }} · {{ item.needs_resubmission ? 'Needs resubmission' : item.uploaded ? 'On file' : 'Missing' }}
                                </li>
                            </ul>
                        </article>

                        <article class="farmer-app__renewals-checklist-card">
                            <strong>Fee checklist</strong>
                            <ul>
                                <li v-for="item in renewalFeeItems" :key="item.key">{{ item.label }} · {{ formatMoney(renewalFees[item.key] || 0) }}</li>
                            </ul>
                        </article>
                    </div>

                    <div class="farmer-app__renewals-actions">
                        <button type="button" class="farmer-app__btn farmer-app__upload-primary" :disabled="starting" @click="startOrResumeRenewal">
                            {{
                                starting
                                    ? 'Preparing renewal...'
                                    : eligibility.can_resume
                                        ? 'Continue Renewal'
                                        : eligibility.can_start
                                            ? 'Start Renewal'
                                            : 'Renewal Not Available'
                            }}
                        </button>
                    </div>
                </section>

                <section v-if="hasCurrentYearRenewal" class="farmer-app__renewals-list">
                    <article
                        :key="`${currentYearRenewal.id}-payment`"
                        class="farmer-app__renewals-card farmer-app__renewals-card--actionable"
                        :aria-disabled="starting || currentYearRenewalSettled"
                        :tabindex="starting || currentYearRenewalSettled ? -1 : 0"
                        role="button"
                        @click="openRenewalRecord(currentYearRenewal)"
                        @keydown.enter.prevent="openRenewalRecord(currentYearRenewal)"
                        @keydown.space.prevent="openRenewalRecord(currentYearRenewal)"
                    >
                        <div class="farmer-app__renewals-card-main">
                            <div class="farmer-app__renewals-card-head">
                                <span class="farmer-app__renewals-app-no">{{ currentYearPayment?.reference_no || currentYearRenewal.application_no }}</span>
                                <span class="farmer-app__renewals-badge farmer-app__renewals-badge--approved">
                                    {{ currentYearPayment?.status_label || currentYearRenewal.status_label || 'Recorded' }}
                                </span>
                            </div>

                            <div class="farmer-app__renewals-title-block">
                                <h2>{{ currentYearRenewal.year }} Renewal Record</h2>
                                <p>{{ currentYearPayment?.paid_at ? `Paid on ${formatDateTime(currentYearPayment.paid_at)}` : `Submitted on ${formatDateTime(currentYearRenewal.submitted_at)}` }}</p>
                            </div>
                        </div>

                        <div class="farmer-app__renewals-card-stats">
                            <div>
                                <span>Renewal year</span>
                                <strong>{{ currentYearRenewal.year }}</strong>
                            </div>
                            <div>
                                <span>{{ currentYearPayment ? 'Amount paid' : 'Total due' }}</span>
                                <strong>{{ formatMoney(currentYearPayment?.amount_paid || currentYearRenewal.assessment?.total_amount_due || 0) }}</strong>
                            </div>
                            <div>
                                <span>{{ currentYearPayment ? 'Payment reference' : 'Payment status' }}</span>
                                <strong>{{ currentYearPayment?.reference_no || paymentSummary(currentYearRenewal) }}</strong>
                            </div>
                        </div>
                        <div v-if="!currentYearRenewalSettled" class="farmer-app__renewals-actions">
                            <button type="button" class="farmer-app__btn farmer-app__upload-primary" :disabled="starting" @click.stop="openRenewalRecord(currentYearRenewal)">
                                {{ starting ? 'Opening payment...' : 'Continue to Payment' }}
                            </button>
                        </div>
                    </article>
                </section>

                <AppState v-else message="No renewal records available." />

                <div v-if="totalRenewals" class="farmer-app__renewals-pagination-wrap">
                    <AppPagination :meta="meta" @change="(page) => fetchPage({ page })" />
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__renewals-eligibility,
.farmer-app__renewals-checklist-grid,
.farmer-app__renewals-actions,
.farmer-app__renewals-eligibility-head {
    display: grid;
    gap: 0.9rem;
}

.farmer-app__renewals-eligibility {
    border: 1px solid rgba(0, 54, 41, 0.12);
    border-radius: 22px;
    background: #fbfdfb;
    padding: 1.1rem;
}

.farmer-app__renewals-eligibility-head {
    grid-template-columns: 1fr auto;
    align-items: start;
}

.farmer-app__renewals-kicker {
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 0.72rem;
    color: #5e736a;
    font-weight: 700;
}

.farmer-app__renewals-checklist-grid {
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.farmer-app__renewals-checklist-card {
    border: 1px solid rgba(0, 54, 41, 0.1);
    border-radius: 18px;
    padding: 1rem;
    background: #fff;
}

.farmer-app__renewals-checklist-card ul {
    margin: 0.7rem 0 0;
    padding-left: 1rem;
    display: grid;
    gap: 0.45rem;
}

.farmer-app__renewals-card--actionable {
    cursor: pointer;
}

.farmer-app__renewals-card--actionable:focus-visible {
    outline: 3px solid rgba(12, 106, 82, 0.45);
    outline-offset: 3px;
}

.farmer-app__renewals-card--actionable[aria-disabled='true'] {
    cursor: default;
}

.farmer-app__renewals-warning {
    color: #ab5a3d;
}

.farmer-app__renewals-timeline {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.7rem;
    margin: 0.9rem 0 1rem;
}

.farmer-app__renewals-step {
    display: grid;
    gap: 0.3rem;
    justify-items: center;
    text-align: center;
}

.farmer-app__renewals-step span {
    width: 1.8rem;
    height: 1.8rem;
    border-radius: 999px;
    background: #edf3ef;
    display: grid;
    place-items: center;
    color: #4d6b61;
}

.farmer-app__renewals-step span.is-current {
    background: #0c6a52;
    color: #fff;
}
</style>
