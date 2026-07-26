<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    reactivation: { type: Object, required: true },
    farmer: { type: Object, required: true },
    assessment: { type: Object, required: true },
    documents: { type: Array, required: true },
    payments: { type: Array, required: true },
    flow: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const page = usePage();
const documentRemarks = reactive(
    Object.fromEntries(props.documents.map((document) => [document.id, document.remarks || ''])),
);
const uploadForms = Object.fromEntries(
    props.documents.map((document) => [document.id, useForm({ document: null })]),
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

const flashSuccess = computed(() => page.props.flash?.success || '');
const pageErrors = computed(() => page.props.errors || {});

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

function selectDocumentFile(documentId, event) {
    uploadForms[documentId].document = event.target.files?.[0] ?? null;
}

function submitDocumentUpload(document) {
    const form = uploadForms[document.id];

    if (!form.document) {
        window.alert('Choose a file first.');
        return;
    }

    form.post(document.actions.attachUrl, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
}

function submitPayment() {
    paymentForm.post(props.urls.recordPayment, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Reactivation ${reactivation.applicationNo}`" />

    <AdminLayout title="Farmer Reactivation">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[24px] bg-[linear-gradient(135deg,_#0f5137_0%,_#1d6a50_100%)] text-white shadow-[0_18px_44px_rgba(15,23,42,0.14)]">
                <div class="flex flex-col gap-5 px-6 py-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.24em] text-white/70">Reactivation Workflow</p>
                        <h1 class="mt-2 text-3xl font-black">{{ reactivation.applicationNo }}</h1>
                        <p class="mt-2 text-sm text-white/75">{{ farmer.fullName }} • {{ farmer.farmerCode }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-white/12 px-4 py-2 text-sm font-black uppercase tracking-[0.18em]">{{ reactivation.status.label }}</span>
                        <Link :href="urls.farmerShow" class="rounded-full bg-white px-4 py-2 text-sm font-black text-[#0f5137]">Back To Farmer</Link>
                    </div>
                </div>
            </section>

            <section v-if="flashSuccess" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ flashSuccess }}
            </section>

            <section v-if="pageErrors.reactivation || pageErrors.payment || pageErrors.document" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
                {{ pageErrors.reactivation || pageErrors.payment || pageErrors.document }}
            </section>

            <section class="grid gap-4 xl:grid-cols-[minmax(0,1.05fr)_minmax(320px,0.95fr)]">
                <article class="rounded-[22px] border border-[#dbe2de] bg-white p-5 shadow-[0_10px_26px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Farmer Record</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[18px] border border-[#e4ebe7] bg-[#f7faf8] p-4">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Member Type</p>
                            <p class="mt-1.5 text-base font-black text-[#17211d]">{{ farmer.memberTypeCode }}</p>
                            <p class="mt-1 text-sm text-[#5f6c67]">{{ farmer.memberTypeLabel }}</p>
                        </div>
                        <div class="rounded-[18px] border border-[#e4ebe7] bg-[#f7faf8] p-4">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Registered At</p>
                            <p class="mt-1.5 text-base font-black text-[#17211d]">{{ farmer.registeredAt || 'No record' }}</p>
                        </div>
                        <div class="rounded-[18px] border border-[#e4ebe7] bg-[#f7faf8] p-4">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Barangay</p>
                            <p class="mt-1.5 text-base font-black text-[#17211d]">{{ farmer.barangay || 'No barangay' }}</p>
                        </div>
                        <div class="rounded-[18px] border border-[#e4ebe7] bg-[#f7faf8] p-4">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Association</p>
                            <p class="mt-1.5 text-base font-black text-[#17211d]">{{ farmer.association || 'No association' }}</p>
                        </div>
                    </div>
                    <div v-if="farmer.inactiveReason" class="mt-4 rounded-[18px] border border-[#f1d7d7] bg-[#fff6f6] px-4 py-3 text-sm text-[#8f3f3f]">
                        {{ farmer.inactiveReason }}
                    </div>
                </article>

                <article class="rounded-[22px] border border-[#dbe2de] bg-white p-5 shadow-[0_10px_26px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Payment Assessment</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[18px] border border-[#e4ebe7] bg-[#f7faf8] p-4">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Arrears Years</p>
                            <p class="mt-1.5 text-base font-black text-[#17211d]">{{ assessment.arrearsYears.join(', ') }}</p>
                        </div>
                        <div class="rounded-[18px] border border-[#d4e3da] bg-[linear-gradient(135deg,_#eef6f0_0%,_#ffffff_100%)] p-4">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#6a7b73]">Total Due</p>
                            <p class="mt-1.5 text-[1.25rem] font-black text-[#163b31]">PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}</p>
                        </div>
                        <div class="rounded-[18px] border border-[#e4ebe7] bg-[#f7faf8] p-4">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Annual Due</p>
                            <p class="mt-1.5 text-base font-black text-[#17211d]">PHP {{ Number(assessment.annualDue || 0).toFixed(2) }}</p>
                        </div>
                        <div class="rounded-[18px] border border-[#e4ebe7] bg-[#f7faf8] p-4">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Mortuary Fee</p>
                            <p class="mt-1.5 text-base font-black text-[#17211d]">PHP {{ Number(assessment.mortuaryFee || 0).toFixed(2) }}</p>
                        </div>
                    </div>

                    <form class="mt-5 space-y-3" @submit.prevent="submitPayment">
                        <label class="space-y-2">
                            <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Payment Method</span>
                            <input v-model="paymentForm.payment_method" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                        </label>
                        <label class="space-y-2">
                            <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Reference No.</span>
                            <input v-model="paymentForm.reference_no" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="space-y-2">
                                <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Amount Paid</span>
                                <input v-model="paymentForm.amount_paid" type="number" min="0.01" step="0.01" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Paid At</span>
                                <input v-model="paymentForm.paid_at" type="datetime-local" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            </label>
                        </div>
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-[#003629] px-5 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637] disabled:opacity-60" :disabled="paymentForm.processing || !flow.canRecordPaymentInAdmin">
                            {{ paymentForm.processing ? 'Recording...' : 'Record Reactivation Payment' }}
                        </button>
                        <p v-if="!flow.canRecordPaymentInAdmin" class="text-sm text-[#8a5a00]">Verify all required documents first before recording payment.</p>
                    </form>
                </article>
            </section>

            <section class="rounded-[22px] border border-[#dbe2de] bg-white shadow-[0_10px_26px_rgba(15,23,42,0.05)]">
                <div class="border-b border-[#e4ebe7] px-5 py-4">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Document Checklist</p>
                    <h2 class="mt-1 text-lg font-black text-[#17211d]">{{ flow.verifiedCount }}/{{ flow.requiredCount }} verified</h2>
                </div>

                <div class="space-y-4 px-5 py-5">
                    <article v-for="document in documents" :key="document.id" class="rounded-[20px] border border-[#e4ebe7] bg-[#fbfcfb] p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-base font-black text-[#17211d]">{{ document.label }}</h3>
                                <p class="mt-1 text-sm text-[#5f6c67]">{{ document.originalName }}</p>
                                <p class="mt-2 text-xs font-bold uppercase tracking-[0.14em] text-[#7a8781]">{{ document.verificationStatus.label }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a v-if="document.actions.viewUrl" :href="document.actions.viewUrl" target="_blank" class="rounded-full border border-[#d7e0db] px-3 py-2 text-xs font-black text-[#17322b]">View</a>
                                <button type="button" class="rounded-full border border-[#d7e0db] px-3 py-2 text-xs font-black text-[#17322b]" @click="submitDocumentAction(document, document.isReceived ? 'unreceive' : 'receive')">
                                    {{ document.isReceived ? 'Unreceive' : 'Receive' }}
                                </button>
                                <button type="button" class="rounded-full bg-[#eef7e3] px-3 py-2 text-xs font-black text-[#416918]" @click="submitDocumentAction(document, 'verify')">Verify</button>
                                <button type="button" class="rounded-full bg-[#fff0ef] px-3 py-2 text-xs font-black text-[#ba1a1a]" @click="submitDocumentAction(document, 'reject')">Reject</button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                            <textarea v-model="documentRemarks[document.id]" rows="2" class="w-full rounded-2xl border border-[#d7e0db] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]" placeholder="Remarks for verification or rejection"></textarea>
                            <div class="flex min-w-[220px] flex-col gap-2">
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-2xl border border-[#d7e0db] bg-white px-3 py-2.5 text-sm text-[#191c1c]" @change="selectDocumentFile(document.id, $event)">
                                <button type="button" class="rounded-2xl border border-[#d7e0db] bg-[#f7faf8] px-4 py-2.5 text-sm font-black text-[#17322b]" :disabled="uploadForms[document.id].processing" @click="submitDocumentUpload(document)">
                                    {{ uploadForms[document.id].processing ? 'Uploading...' : 'Attach Scan' }}
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section v-if="payments.length" class="rounded-[22px] border border-[#dbe2de] bg-white shadow-[0_10px_26px_rgba(15,23,42,0.05)]">
                <div class="border-b border-[#e4ebe7] px-5 py-4">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Recorded Payments</p>
                </div>
                <div class="space-y-3 px-5 py-5">
                    <article v-for="payment in payments" :key="payment.id" class="rounded-[18px] border border-[#e4ebe7] bg-[#f7faf8] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-[#17211d]">PHP {{ Number(payment.amountPaid || 0).toFixed(2) }}</p>
                                <p class="mt-1 text-sm text-[#5f6c67]">{{ payment.paidAt }}</p>
                            </div>
                            <span class="rounded-full bg-[#eef7e3] px-3 py-1 text-xs font-black text-[#416918]">{{ payment.statusLabel }}</span>
                        </div>
                        <p class="mt-2 text-xs font-black uppercase tracking-[0.14em] text-[#7a8781]">{{ payment.method || 'Cash' }}</p>
                        <p class="mt-1 text-sm text-[#5f6c67]">{{ payment.referenceNo || 'No reference number' }}</p>
                    </article>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
