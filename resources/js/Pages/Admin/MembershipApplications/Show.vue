<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import InternalNotesPanel from '../../../Components/Admin/InternalNotesPanel.vue';
import RecordWarningsPanel from '../../../Components/Admin/RecordWarningsPanel.vue';
import ReviewDocumentsPanel from '../../../Components/Admin/MembershipApplications/ReviewDocumentsPanel.vue';
import ReviewOverviewGrid from '../../../Components/Admin/MembershipApplications/ReviewOverviewGrid.vue';
import ReviewProfilePanel from '../../../Components/Admin/MembershipApplications/ReviewProfilePanel.vue';
import ReviewSidebar from '../../../Components/Admin/MembershipApplications/ReviewSidebar.vue';
import ReviewSteps from '../../../Components/Admin/MembershipApplications/ReviewSteps.vue';

const props = defineProps({
    application: { type: Object, required: true },
    farmer: { type: Object, required: true },
    flow: { type: Object, required: true },
    documents: { type: Array, required: true },
    assessment: { type: Object, required: true },
    payments: { type: Array, required: true },
    recordWarnings: { type: Array, required: true },
    internalNotes: { type: Array, required: true },
    permissions: { type: Object, required: true },
    features: { type: Object, default: () => ({ walkInAttachScanEnabled: false }) },
    rejectionReasonOptions: { type: Array, required: true },
    urls: { type: Object, required: true },
});

const page = usePage();
const documentRemarks = reactive(
    Object.fromEntries(props.documents.map((document) => [document.id, document.remarks || ''])),
);

function localDateTimeValue(date = new Date()) {
    const offset = date.getTimezoneOffset();
    const local = new Date(date.getTime() - (offset * 60 * 1000));
    return local.toISOString().slice(0, 16);
}

const paymentForm = useForm({
    payment_method: 'cash',
    reference_no: '',
    amount_paid: props.assessment.totalAmountDue || '',
    paid_at: localDateTimeValue(),
});

const rejectionForm = useForm({
    action: 'reject',
    rejection_reason: '',
    rejection_details: '',
});

const flashSuccess = computed(() => page.props.flash?.success || '');
const pageErrors = computed(() => page.props.errors || {});
const paymentStatusLabel = computed(() => {
    if (props.flow.paymentSettled) return 'Payment Settled';
    if (props.flow.paymentReady) return 'Ready for Payment';
    return 'Checklist Incomplete';
});

const warningSectionTargets = {
    missing_documents: 'application-documents-section',
    duplicate_risk: 'application-profile-section',
    invalid_mobile: 'application-profile-section',
    unpaid_assessment: 'application-payment-section',
};

function submitDocumentAction(document, action) {
    if (action === 'reject' && !String(documentRemarks[document.id] || '').trim()) {
        window.alert('Enter remarks before rejecting this document.');
        return;
    }

    router.post(document.actions.reviewUrl, {
        action,
        remarks: documentRemarks[document.id] || '',
    }, {
        preserveScroll: true,
    });
}

function initializeChecklist() {
    router.post(props.urls.initializeChecklist, {}, {
        preserveScroll: true,
    });
}

function submitPayment() {
    paymentForm.post(props.urls.recordPayment, {
        preserveScroll: true,
    });
}

function submitRejection() {
    rejectionForm.post(props.urls.review, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Review: ${application.applicationNo}`" />

    <AdminLayout title="Application Review">
        <div class="mx-auto w-full max-w-[1536px] space-y-4">
            <!-- Back to Queue Header -->
            <div class="flex items-center justify-between px-1">
                <Link
                    :href="urls.index"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-[#003629]"
                >
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                    </svg>
                    <span>Back to Application Queue</span>
                </Link>

                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-slate-700">{{ application.applicationNo }}</span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[0.62rem] font-bold uppercase tracking-wider"
                        :class="application.status.value === 'approved' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : application.status.value === 'rejected' ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-amber-200 bg-amber-50 text-amber-800'"
                    >
                        <span class="h-1.5 w-1.5 rounded-full" :class="application.status.value === 'approved' ? 'bg-emerald-500' : application.status.value === 'rejected' ? 'bg-rose-500' : 'bg-amber-500 animate-pulse'"></span>
                        {{ application.status.label }}
                    </span>
                </div>
            </div>

            <!-- Stepper Progress Tracker -->
            <ReviewSteps :flow="flow" />

            <!-- Flash & Error Messages -->
            <div v-if="flashSuccess" class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
                <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-emerald-600" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                </svg>
                <span>{{ flashSuccess }}</span>
            </div>

            <div v-if="pageErrors.application || pageErrors.payment || pageErrors.document" class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
                <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-rose-600" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <span>{{ pageErrors.application || pageErrors.payment || pageErrors.document }}</span>
            </div>

            <!-- Overview KPI Cards -->
            <ReviewOverviewGrid
                :application="application"
                :farmer="farmer"
                :flow="flow"
                :assessment="assessment"
                :payment-status-label="paymentStatusLabel"
            />

            <!-- Warnings Banner (if any) -->
            <RecordWarningsPanel
                title=""
                description=""
                :warnings="recordWarnings"
                :section-targets="warningSectionTargets"
            />

            <!-- Main Content Grid -->
            <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(340px,0.8fr)]">
                <!-- Left Column: Profile & Documents -->
                <div class="space-y-4">
                    <div id="application-profile-section">
                        <ReviewProfilePanel :application="application" :farmer="farmer" />
                    </div>

                    <div id="application-documents-section">
                        <ReviewDocumentsPanel
                            v-if="!flow.isHistorical"
                            :application="application"
                            :flow="flow"
                            :documents="documents"
                            :document-remarks="documentRemarks"
                            :features="features"
                            :urls="urls"
                            @document-action="submitDocumentAction"
                            @initialize-checklist="initializeChecklist"
                        />
                    </div>
                </div>

                <!-- Right Column: Payment Assessment, History & Rejection -->
                <div id="application-payment-section" class="space-y-4">
                    <ReviewSidebar
                        :application="application"
                        :flow="flow"
                        :assessment="assessment"
                        :payments="payments"
                        :permissions="permissions"
                        :rejection-reason-options="rejectionReasonOptions"
                        :urls="urls"
                        :payment-form="paymentForm"
                        :rejection-form="rejectionForm"
                        @submit-payment="submitPayment"
                        @submit-rejection="submitRejection"
                    />

                    <!-- Internal Notes -->
                    <InternalNotesPanel
                        :notes="internalNotes"
                        :submit-url="urls.storeInternalNote"
                        title="Application Internal Notes"
                        compact
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
