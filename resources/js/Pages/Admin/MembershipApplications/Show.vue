<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import InternalNotesPanel from '../../../Components/Admin/InternalNotesPanel.vue';
import RecordWarningsPanel from '../../../Components/Admin/RecordWarningsPanel.vue';
import ReviewDocumentsPanel from '../../../Components/Admin/MembershipApplications/ReviewDocumentsPanel.vue';
import ReviewHero from '../../../Components/Admin/MembershipApplications/ReviewHero.vue';
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
        <div class="space-y-7 bg-[radial-gradient(circle_at_top_left,_rgba(186,238,217,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(165,213,119,0.12),_transparent_22%)]">
            <ReviewHero :application="application" :flow="flow" />

            <ReviewSteps :flow="flow" />

            <section v-if="flashSuccess" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ flashSuccess }}
            </section>

            <section v-if="pageErrors.application || pageErrors.payment || pageErrors.document" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
                {{ pageErrors.application || pageErrors.payment || pageErrors.document }}
            </section>

            <ReviewOverviewGrid
                :application="application"
                :farmer="farmer"
                :flow="flow"
                :assessment="assessment"
                :payment-status-label="paymentStatusLabel"
            />

            <RecordWarningsPanel :warnings="recordWarnings" :section-targets="warningSectionTargets" />

            <section class="grid gap-4 md:grid-cols-2">
                <article class="rounded-[22px] border border-[#dbe2de] bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Last Updated By</p>
                    <p class="mt-2 text-lg font-bold text-[#14202c]">{{ application.accountability?.lastUpdatedBy || 'No staff update recorded' }}</p>
                    <p class="mt-1 text-sm text-[#6c7772]">{{ application.accountability?.lastUpdatedAt || 'Not recorded' }}</p>
                </article>
                <article class="rounded-[22px] border border-[#dbe2de] bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Assigned Staff</p>
                    <p class="mt-2 text-lg font-bold text-[#14202c]">{{ application.accountability?.assignedStaff || 'Unassigned' }}</p>
                </article>
            </section>

            <section class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
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

                <InternalNotesPanel :notes="internalNotes" :submit-url="urls.storeInternalNote" title="Application Internal Notes" />
            </section>
        </div>
    </AdminLayout>
</template>
