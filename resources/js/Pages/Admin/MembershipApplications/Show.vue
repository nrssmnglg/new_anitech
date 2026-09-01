<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
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
    if (props.flow.paymentSettled) return 'Recorded';
    if (props.flow.paymentReady) return 'Ready For Payment';
    return 'Checklist First';
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
    <Head :title="`Membership ${application.applicationNo}`" />

    <AdminLayout title="Membership Review">
        <div class="membership-review-compact mx-auto w-full max-w-[1536px] space-y-4">
            <ReviewSteps :flow="flow" />

            <section v-if="flashSuccess" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-medium text-emerald-800">
                {{ flashSuccess }}
            </section>

            <section v-if="pageErrors.application || pageErrors.payment || pageErrors.document" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-medium text-rose-700">
                {{ pageErrors.application || pageErrors.payment || pageErrors.document }}
            </section>

            <ReviewOverviewGrid
                :application="application"
                :farmer="farmer"
                :flow="flow"
                :assessment="assessment"
                :payment-status-label="paymentStatusLabel"
            />

            <RecordWarningsPanel title="" description="" :warnings="recordWarnings" :section-targets="warningSectionTargets" />

            <section class="grid gap-2 md:grid-cols-2">
                <article class="rounded-lg border border-[#dbe2de] bg-white p-3">
                    <p class="text-[0.62rem] font-semibold uppercase tracking-[0.08em] text-[#7b8782]">Last Updated By</p>
                    <p class="mt-1 text-xs font-semibold text-[#14202c]">{{ application.accountability?.lastUpdatedBy || 'No staff update recorded' }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-[#6c7772]">{{ application.accountability?.lastUpdatedAt || 'Not recorded' }}</p>
                </article>
                <article class="rounded-lg border border-[#dbe2de] bg-white p-3">
                    <p class="text-[0.62rem] font-semibold uppercase tracking-[0.08em] text-[#7b8782]">Assigned Staff</p>
                    <p class="mt-1 text-xs font-semibold text-[#14202c]">{{ application.accountability?.assignedStaff || 'Unassigned' }}</p>
                </article>
            </section>

            <section class="space-y-4">
                <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
                    <div id="application-profile-section">
                        <ReviewProfilePanel :application="application" :farmer="farmer" />
                    </div>

                    <div id="application-payment-section">
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
                    </div>
                </div>

                <div id="application-documents-section">
                    <ReviewDocumentsPanel
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

                <InternalNotesPanel :notes="internalNotes" :submit-url="urls.storeInternalNote" title="Application Internal Notes" compact />
            </section>
        </div>
    </AdminLayout>
</template>

<style scoped>
.membership-review-compact :deep(section),
.membership-review-compact :deep(article) {
    box-shadow: none !important;
}
.membership-review-compact :deep([class*='rounded-[24px]']),
.membership-review-compact :deep([class*='rounded-[22px]']),
.membership-review-compact :deep([class*='rounded-[20px]']),
.membership-review-compact :deep([class*='rounded-[18px]']) {
    border-radius: 0.5rem !important;
}
.membership-review-compact :deep(section > [class*='border-b']) {
    padding: 0.625rem 1rem !important;
}
.membership-review-compact :deep(section > [class*='px-5'][class*='py-5']) {
    padding: 0.75rem 1rem !important;
}
.membership-review-compact :deep([class~='p-4']),
.membership-review-compact :deep([class~='px-4'][class~='py-4']) {
    padding: 0.75rem !important;
}
.membership-review-compact :deep([class~='p-5']) {
    padding: 0.875rem !important;
}
.membership-review-compact :deep([class~='space-y-6']) {
    row-gap: 1rem !important;
}
.membership-review-compact :deep([class~='space-y-4']) {
    row-gap: 0.75rem !important;
}
.membership-review-compact :deep([class~='text-[2rem]']),
.membership-review-compact :deep([class~='text-[1.8rem]']) {
    font-size: 1.125rem !important;
    line-height: 1.35rem !important;
}
.membership-review-compact :deep([class~='text-lg']),
.membership-review-compact :deep([class~='text-base']) {
    font-size: 0.875rem !important;
    line-height: 1.25rem !important;
}
.membership-review-compact :deep([class~='tracking-[0.22em]']),
.membership-review-compact :deep([class~='tracking-[0.2em]']) {
    letter-spacing: 0.08em !important;
}
.membership-review-compact :deep(input:not([type='checkbox'])),
.membership-review-compact :deep(select) {
    min-height: 2.25rem !important;
    border-radius: 0.375rem !important;
    padding: 0.5rem 0.75rem !important;
    font-size: 0.75rem !important;
}
.membership-review-compact :deep(textarea) {
    border-radius: 0.375rem !important;
    padding: 0.625rem 0.75rem !important;
    font-size: 0.75rem !important;
}
.membership-review-compact :deep(button),
.membership-review-compact :deep(a) {
    border-radius: 0.375rem !important;
}
</style>
