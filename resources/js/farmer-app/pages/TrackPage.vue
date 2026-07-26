<script setup>
import { computed, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppState from '../components/ui/AppState.vue';
import { useLocale } from '../composables/useLocale';
import { extractApiMessage, extractValidationErrors } from '../utils/api';
import { farmerApi } from '../services/api';
import { brandLogoUrl } from '../utils/asset';
import { readStorage, writeStorage } from '../utils/storage';

const route = useRoute();
const router = useRouter();
const { t } = useLocale();
const loading = ref(false);
const paying = ref(false);
const error = ref('');
const success = ref('');
const validationErrors = ref({});
const application = ref(null);
const brandLogo = brandLogoUrl();
const lookup = ref({
    application_no: String(route.query.application_no ?? ''),
    birth_date: String(route.query.birth_date ?? ''),
});

const pageMode = computed(() => route.name === 'track-status' ? 'status' : 'start');
const hasLookup = computed(() => Boolean(lookup.value.application_no && lookup.value.birth_date));
const cachedStatusTimestamp = ref('');
const blockerMessage = computed(() => {
    if (!application.value) {
        return '';
    }

    if (application.value.reapply?.can_reapply || documentSummary.value.corrections > 0) {
        return t('track.blocked_correction');
    }

    if (documentSummary.value.uploaded < documentSummary.value.total) {
        return t('track.blocked_missing_docs');
    }

    if (application.value.payment?.can_pay) {
        return t('track.blocked_payment');
    }

    if (!application.value.account?.has_account) {
        return t('track.blocked_review');
    }

    return '';
});
const waitingIndicator = computed(() => {
    if (!application.value) {
        return null;
    }

    if (application.value.reapply?.can_reapply || documentSummary.value.corrections > 0 || documentSummary.value.uploaded < documentSummary.value.total || application.value.payment?.can_pay) {
        return {
            title: t('track.waiting_farmer'),
            copy: t('track.waiting_farmer_copy'),
        };
    }

    return {
        title: t('track.waiting_staff'),
        copy: t('track.waiting_staff_copy'),
    };
});
const historyItems = computed(() => {
    if (!application.value) {
        return [];
    }

    const items = [
        application.value.submitted_at ? {
            key: 'submitted',
            label: t('track.submitted_event'),
            at: application.value.submitted_at,
        } : null,
        application.value.reviewed_at ? {
            key: 'reviewed',
            label: t('track.reviewed_event'),
            at: application.value.reviewed_at,
        } : null,
        application.value.rejection?.created_at || application.value.rejected_at ? {
            key: 'correction',
            label: t('track.correction_event'),
            at: application.value.rejection?.created_at || application.value.rejected_at,
        } : null,
        application.value.payment?.paid_at ? {
            key: 'payment-recorded',
            label: t('track.payment_recorded_event'),
            at: application.value.payment.paid_at,
        } : null,
        application.value.payment?.verified_at ? {
            key: 'payment-verified',
            label: t('track.payment_verified_event'),
            at: application.value.payment.verified_at,
        } : null,
    ].filter(Boolean);

    return items.sort((left, right) => new Date(right.at).getTime() - new Date(left.at).getTime());
});

const documentSummary = computed(() => {
    const documents = application.value?.documents ?? [];

    return {
        total: documents.length,
        uploaded: documents.filter((document) => document.uploaded).length,
        corrections: documents.filter((document) => document.needs_correction).length,
    };
});

const stepDefinitions = [
    { key: 'submitted', label: 'Submitted' },
    { key: 'under_review', label: 'Under Review' },
    { key: 'needs_correction', label: 'Needs Correction' },
    { key: 'approved', label: 'Approved' },
    { key: 'payment_pending', label: 'Payment Pending' },
    { key: 'completed', label: 'Completed' },
];

const timelineState = computed(() => {
    const data = application.value;
    if (!data) {
        return {};
    }

    const isRejected = data.status === 'rejected';
    const allUploaded = documentSummary.value.total > 0 && documentSummary.value.uploaded === documentSummary.value.total;
    const paymentVerified = Boolean(data.payment?.is_verified);
    const paymentPending = Boolean(data.payment?.can_pay);
    const completed = Boolean(data.account?.has_account || data.farmer?.membership_status === 'active' || paymentVerified);

    return {
        submitted: true,
        under_review: allUploaded || ['approved', 'rejected'].includes(data.status),
        needs_correction: isRejected || documentSummary.value.corrections > 0,
        approved: data.status === 'approved' || paymentPending || paymentVerified || completed,
        payment_pending: paymentPending || paymentVerified || completed,
        completed,
    };
});

const stageItems = computed(() => {
    const state = timelineState.value;
    const currentIndex = stepDefinitions.findIndex((step) => !state[step.key]);
    const activeIndex = currentIndex === -1 ? stepDefinitions.length - 1 : currentIndex;

    return stepDefinitions.map((step, index) => ({
        ...step,
        stepNumber: index + 1,
        complete: Boolean(state[step.key]) && index < activeIndex,
        current: index === activeIndex,
        visible: step.key !== 'needs_correction' || Boolean(state.needs_correction),
    })).filter((step) => step.visible);
});

const stageNote = computed(() => {
    if (!application.value) {
        return '';
    }

    if (application.value.reapply?.can_reapply) {
        return 'Your application was returned for correction. Review the office notes below, update only the affected details, and resubmit.';
    }

    if (documentSummary.value.corrections > 0) {
        return 'One or more uploaded documents need replacement before approval can continue.';
    }

    if (application.value.payment?.is_verified) {
        return 'The office has verified your payment. Final account activation is underway.';
    }

    if (application.value.payment?.can_pay) {
        return 'Your documents are approved. Review the payment breakdown and generate your QR payment code.';
    }

    if (documentSummary.value.uploaded < documentSummary.value.total) {
        return 'Upload the remaining required documents so the office can continue reviewing your application.';
    }

    return 'Your application is in review. Check this page for document remarks, payment readiness, and final approval.';
});

const rejectionText = computed(() => {
    const rejection = application.value?.rejection;
    if (!rejection) {
        return '';
    }

    return [rejection.reason_label, rejection.details].filter(Boolean).join(': ');
});

const uploadTarget = computed(() => ({
    name: 'upload',
    query: {
        application_no: lookup.value.application_no,
        birth_date: lookup.value.birth_date,
    },
}));

const accountSetupTarget = computed(() => ({
    name: 'account-setup',
    query: {
        application_no: lookup.value.application_no,
        birth_date: lookup.value.birth_date,
    },
}));

const correctionTarget = computed(() => ({
    name: 'apply',
    query: {
        application_no: lookup.value.application_no,
        birth_date: lookup.value.birth_date,
        reapply_from_application_id: application.value?.reapply?.application_id,
    },
}));

const canSetupAccount = computed(() => {
    return Boolean(application.value?.account?.can_setup) && hasLookup.value;
});

const paymentBreakdown = computed(() => {
    const breakdown = application.value?.payment?.breakdown ?? {};

    return [
        { label: 'Membership Fee', amount: breakdown.membership_fee },
        { label: 'Annual Due', amount: breakdown.annual_due },
        { label: 'Mortuary Fee', amount: breakdown.mortuary_fee },
    ].filter((item) => Number(item.amount || 0) > 0);
});

const paymentInstructions = computed(() => application.value?.payment?.instructions ?? []);

const topStatusLabel = computed(() => {
    if (!application.value) {
        return '';
    }

    if (application.value.reapply?.can_reapply || documentSummary.value.corrections > 0) {
        return 'Correction Needed';
    }

    if (application.value.payment?.is_verified) {
        return 'Payment Verified';
    }

    if (application.value.payment?.can_pay) {
        return 'Payment Pending';
    }

    if (application.value.account?.has_account) {
        return 'Completed';
    }

    return application.value.status_label || 'Under Review';
});

function documentReviewBadge(document) {
    if (document?.needs_correction) {
        return {
            label: 'Correction Needed',
            className: 'is-rejected',
        };
    }

    const status = String(document?.verification_status || '').toLowerCase();

    if (status === 'verified') {
        return {
            label: 'Verified',
            className: 'is-verified',
        };
    }

    if (document?.uploaded) {
        return {
            label: 'Pending Review',
            className: 'is-pending',
        };
    }

    return {
        label: 'Missing',
        className: 'is-pending',
    };
}

function persistLookup() {
    window.localStorage.setItem('anitech_mobile_application', JSON.stringify({
        application_no: lookup.value.application_no,
        birth_date: lookup.value.birth_date,
    }));
}

function restoreLookup() {
    if (hasLookup.value) {
        return;
    }

    try {
        const stored = JSON.parse(window.localStorage.getItem('anitech_mobile_application') || 'null');
        if (stored?.application_no && stored?.birth_date) {
            lookup.value = {
                application_no: String(stored.application_no),
                birth_date: String(stored.birth_date),
            };
        }
    } catch {
    }
}

function cacheKey() {
    return `application-track:${lookup.value.application_no}:${lookup.value.birth_date}`;
}

function hydrateCachedApplication() {
    const cached = readStorage(cacheKey());

    if (!cached?.application) {
        return false;
    }

    application.value = cached.application;
    cachedStatusTimestamp.value = cached.cached_at ?? '';

    return true;
}

async function loadApplication() {
    if (!hasLookup.value) {
        error.value = 'Enter your application number and birth date.';
        application.value = null;
        return;
    }

    if (pageMode.value === 'start') {
        persistLookup();
        await router.push({
            name: 'track-status',
            query: {
                application_no: lookup.value.application_no,
                birth_date: lookup.value.birth_date,
            },
        });
        return;
    }

    loading.value = true;
    error.value = '';
    success.value = '';
    validationErrors.value = {};

    try {
        const response = await farmerApi.get('/application/track', {
            params: lookup.value,
        });

        application.value = response?.data?.data ?? null;
        cachedStatusTimestamp.value = new Date().toISOString();
        writeStorage(cacheKey(), {
            application: application.value,
            cached_at: cachedStatusTimestamp.value,
        });
        persistLookup();
        success.value = '';
    } catch (err) {
        if (!err?.response && hydrateCachedApplication()) {
            error.value = '';
            success.value = cachedStatusTimestamp.value
                ? `Showing saved status from ${new Date(cachedStatusTimestamp.value).toLocaleString()}.`
                : 'Showing saved status from this device.';
        } else {
            application.value = null;
            error.value = extractApiMessage(err, t('track.lookup_failed'));
            validationErrors.value = extractValidationErrors(err);
        }
    } finally {
        loading.value = false;
    }
}

async function submitPayment() {
    if (!application.value?.payment?.can_pay) {
        return;
    }

    paying.value = true;
    error.value = '';
    success.value = '';

    try {
        const response = await farmerApi.post(`/application/${encodeURIComponent(lookup.value.application_no)}/payment`, {
            birth_date: lookup.value.birth_date,
            payment_method: 'qrph',
            reference_no: application.value.payment?.reference_no || lookup.value.application_no,
        });

        if (response?.data?.data?.qr_page_url) {
            window.location.href = response.data.data.qr_page_url;
            return;
        }

        application.value = response?.data?.data?.application ?? application.value;
        success.value = response?.data?.message ?? 'Payment recorded successfully.';
    } catch (err) {
        error.value = extractApiMessage(err, t('track.payment_failed'));
    } finally {
        paying.value = false;
    }
}

restoreLookup();

if (pageMode.value === 'status' && hasLookup.value) {
    loadApplication();
}
</script>

<template>
    <div class="farmer-app__track-screen">
        <div class="farmer-app__track-bg"></div>
        <div class="farmer-app__track-overlay"></div>
        <div class="farmer-app__track-grain"></div>

        <main class="farmer-app__track-shell">
            <section v-if="pageMode === 'start' || !application" class="farmer-app__track-lookup">
                <div class="farmer-app__track-lookup-icon">
                    <img :src="brandLogo" alt="AniTech mark">
                </div>
                <h1>{{ t('track.title') }}</h1>
                <p>{{ t('track.intro') }}</p>

                <div class="farmer-app__track-form">
                    <label class="farmer-app__track-field">
                        <span>Application Number</span>
                        <input v-model="lookup.application_no" type="text" placeholder="e.g. AT-2024-8832">
                        <small v-if="validationErrors.application_no" class="farmer-app__field-error">{{ validationErrors.application_no }}</small>
                    </label>

                    <label class="farmer-app__track-field">
                        <span>Date of Birth</span>
                        <input v-model="lookup.birth_date" type="date" class="farmer-app__date-input">
                        <small v-if="validationErrors.birth_date" class="farmer-app__field-error">{{ validationErrors.birth_date }}</small>
                    </label>

                    <button type="button" class="farmer-app__btn farmer-app__track-primary" :disabled="loading" @click="loadApplication">
                        {{ loading ? t('track.checking_status') : t('track.check_status') }}
                    </button>

                    <AppState v-if="error" type="error" :message="error" />
                    <AppState v-if="success" :message="success" />

                    <p class="farmer-app__track-support">
                        Having trouble?
                        <RouterLink :to="{ name: 'login' }">Farmer Login</RouterLink>
                    </p>
                </div>
            </section>

            <section v-else class="farmer-app__track-status">
                <div class="farmer-app__track-status-head">
                    <div>
                        <h1>Application Status</h1>
                    </div>
                    <div class="farmer-app__track-status-pill">
                        <span></span>
                        {{ topStatusLabel }}
                    </div>
                </div>

                <AppState v-if="error" type="error" :message="error" />
                <AppState v-if="success" :message="success" />

                <article class="farmer-app__track-card">
                    <div class="farmer-app__track-timeline">
                        <div
                            v-for="stage in stageItems"
                            :key="stage.key"
                            class="farmer-app__track-step"
                        >
                            <div
                                class="farmer-app__track-step-dot"
                                :class="{ 'is-complete': stage.complete, 'is-current': stage.current }"
                            >
                                {{ stage.complete ? '✓' : stage.current ? '•' : stage.stepNumber }}
                            </div>
                            <span>{{ stage.label }}</span>
                        </div>
                    </div>
                </article>

                <article class="farmer-app__track-card">
                    <div class="farmer-app__track-overview">
                        <div>
                            <h2>{{ application.farmer?.name || 'Application Overview' }}</h2>
                            <p>Submitted {{ new Date(application.submitted_at).toLocaleString() }}</p>
                        </div>
                        <div class="farmer-app__track-overview-mark">
                            <img :src="brandLogo" alt="AniTech mark">
                        </div>
                    </div>
                    <div class="farmer-app__track-note">
                        {{ stageNote }}
                    </div>
                </article>

                <article class="farmer-app__track-card">
                    <div class="farmer-app__track-card-head">
                        <h3>Required Documents</h3>
                        <span>{{ documentSummary.uploaded }}/{{ documentSummary.total }} uploaded</span>
                    </div>

                    <div class="farmer-app__track-documents">
                        <article
                            v-for="document in application.documents"
                            :key="document.type"
                            class="farmer-app__track-doc"
                            :class="{ 'is-flagged': document.needs_correction }"
                        >
                            <div>
                                <strong>{{ document.label }}</strong>
                                <p>{{ document.verification_status_label }}</p>
                                <small v-if="document.remarks" class="farmer-app__track-doc-remark">{{ document.remarks }}</small>
                            </div>
                            <span class="farmer-app__track-doc-badge" :class="documentReviewBadge(document).className">
                                {{ documentReviewBadge(document).label }}
                            </span>
                        </article>
                    </div>

                    <div v-if="documentSummary.uploaded < documentSummary.total || documentSummary.corrections" class="farmer-app__track-actions">
                        <RouterLink :to="uploadTarget" class="farmer-app__btn farmer-app__track-primary">
                            {{ documentSummary.corrections ? 'Replace Required Files' : 'Continue Upload' }}
                        </RouterLink>
                    </div>
                </article>

                <article v-if="application.reapply?.can_reapply" class="farmer-app__track-card">
                    <h3>Correction and Resubmission</h3>
                    <p class="farmer-app__muted">{{ rejectionText || 'The office returned this application. Correct the requested details and resubmit from this same mobile workflow.' }}</p>
                    <div class="farmer-app__track-actions">
                        <RouterLink :to="correctionTarget" class="farmer-app__btn farmer-app__track-primary">
                            Open Correction Form
                        </RouterLink>
                    </div>
                </article>

                <article class="farmer-app__track-card farmer-app__track-card--payment">
                    <div class="farmer-app__track-card-head">
                        <h3>Payment Guidance</h3>
                        <strong>
                            {{ application.payment?.total_amount_due ? `PHP ${Number(application.payment.total_amount_due).toFixed(2)}` : 'Payment not ready yet' }}
                        </strong>
                    </div>

                    <div class="farmer-app__track-payment-rows">
                        <div v-for="item in paymentBreakdown" :key="item.label" class="farmer-app__track-pay-row">
                            <span>{{ item.label }}</span>
                            <strong>PHP {{ Number(item.amount).toFixed(2) }}</strong>
                        </div>
                    </div>

                    <div class="farmer-app__track-payment-reference">
                        <span>Payment Reference</span>
                        <strong>{{ application.payment?.reference_no || application.application_no }}</strong>
                    </div>

                    <div class="farmer-app__track-payment-status">
                        <span>{{ application.payment?.is_verified ? 'Office verification complete.' : 'Office verification pending.' }}</span>
                    </div>

                    <ul class="farmer-app__track-payment-guide">
                        <li v-for="item in paymentInstructions" :key="item">{{ item }}</li>
                    </ul>

                    <button
                        type="button"
                        class="farmer-app__btn farmer-app__track-primary"
                        :disabled="paying || !application.payment?.can_pay"
                        @click="submitPayment"
                    >
                        {{ paying ? 'Opening QR...' : application.payment?.can_pay ? 'Generate QR Payment' : 'Waiting For Approval' }}
                    </button>
                </article>

                <article class="farmer-app__track-card" :class="{ 'is-disabled': !canSetupAccount && !application.account?.has_account }">
                    <h3>Finalize Account</h3>
                    <p class="farmer-app__muted">
                        {{
                            application.account?.has_account
                                ? `Your farmer account is already set up${application.account?.email ? ` with ${application.account.email}` : ''}.`
                                : canSetupAccount
                                    ? application.account?.requires_resetup
                                        ? 'Your farmer account is inactive. Set your email and password again to make it active.'
                                        : 'Your membership is active. Set your email and password now.'
                                    : 'Available after payment and final approval.'
                        }}
                    </p>
                    <div class="farmer-app__track-actions">
                        <RouterLink v-if="canSetupAccount" :to="accountSetupTarget" class="farmer-app__btn farmer-app__track-primary">
                            Account Setup
                        </RouterLink>
                        <RouterLink v-else-if="application.account?.has_account" :to="{ name: 'login' }" class="farmer-app__btn farmer-app__track-secondary">
                            Sign In
                        </RouterLink>
                    </div>
                </article>

                <button type="button" class="farmer-app__track-back" @click="router.push({ name: 'track' })">
                    Back to Lookup
                </button>
            </section>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__track-timeline {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
    gap: 0.9rem;
}

.farmer-app__track-step {
    display: grid;
    gap: 0.45rem;
    justify-items: center;
    text-align: center;
}

.farmer-app__track-step-dot {
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: #edf3ef;
    color: #45695e;
    font-weight: 700;
}

.farmer-app__track-step-dot.is-complete {
    background: #e1f4e8;
    color: #0c6a52;
}

.farmer-app__track-step-dot.is-current {
    background: #0c6a52;
    color: #fff;
}

.farmer-app__track-doc.is-flagged {
    border-color: rgba(179, 84, 58, 0.24);
    background: #fff7f3;
}

.farmer-app__track-doc-remark {
    display: block;
    margin-top: 0.3rem;
    color: #9b553f;
}

.farmer-app__track-payment-reference,
.farmer-app__track-payment-status {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.8rem 0;
    border-top: 1px solid rgba(0, 54, 41, 0.08);
}

.farmer-app__track-payment-guide {
    margin: 0;
    padding-left: 1.1rem;
    color: #4b625a;
    display: grid;
    gap: 0.45rem;
}
</style>
