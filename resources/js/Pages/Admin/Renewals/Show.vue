<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import InternalNotesPanel from '../../../Components/Admin/InternalNotesPanel.vue';
import RecordWarningsPanel from '../../../Components/Admin/RecordWarningsPanel.vue';
import ReviewDocumentsPanel from '../../../Components/Admin/MembershipApplications/ReviewDocumentsPanel.vue';

const props = defineProps({
    renewal: { type: Object, required: true },
    renewalHistory: { type: Array, required: true },
    farmer: { type: Object, required: true },
    assessment: { type: Object, required: true },
    payments: { type: Object, required: true },
    recordWarnings: { type: Array, required: true },
    internalNotes: { type: Array, required: true },
    flow: { type: Object, required: true },
    documents: { type: Array, required: true },
    urls: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const pageErrors = computed(() => page.props.errors || {});
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

const paymentBreakdown = computed(() => ([
    {
        key: 'annual',
        label: 'Annual Due',
        amount: Number(props.assessment.annualDue || 0),
    },
    {
        key: 'mortuary',
        label: 'Mortuary Fee',
        amount: Number(props.assessment.mortuaryFee || 0),
    },
]).filter((item) => item.amount > 0));

const pageTitle = computed(() => `Renewal ${props.renewal.year}`);
const hasActiveDocumentRequirements = computed(() => Number(props.flow?.requiredCount || 0) > 0);
const showApprovalBlockers = computed(() => {
    const status = String(props.renewal?.status?.value || '').toLowerCase();

    return !['approved', 'completed'].includes(status) && !props.flow?.paymentSettled;
});
const warningSectionTargets = computed(() => ({
    missing_documents: hasActiveDocumentRequirements.value ? 'renewal-documents-section' : '',
    duplicate_risk: 'renewal-profile-section',
    invalid_mobile: 'renewal-profile-section',
    unpaid_assessment: 'renewal-payment-section',
}));

function submitPayment() {
    paymentForm.post(props.urls.recordPayment, {
        preserveScroll: true,
    });
}

function submitDocumentAction(document, action) {
    router.post(document.actions.reviewUrl, {
        action,
        remarks: documentRemarks[document.id] || '',
    }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="pageTitle" />

    <AdminLayout title="Renewal Processing">
        <div class="renewal-record-view space-y-3">
            <section class="renewal-record-hero relative overflow-hidden rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="absolute inset-0 bg-[radial-gradient(at_0%_0%,_#003629_0%,_transparent_55%),radial-gradient(at_50%_0%,_#0b503d_0%,_transparent_50%),radial-gradient(at_100%_0%,_#376757_0%,_transparent_48%)]"></div>
                <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-[100px]"></div>
                <div class="absolute -left-12 -bottom-20 h-72 w-72 rounded-full bg-[#c0f190]/10 blur-[120px]"></div>

                <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="rounded-full bg-white/10 px-3.5 py-1.5 text-[0.64rem] font-black uppercase tracking-[0.2em] text-[#baeed9] backdrop-blur">
                                Renewal Processing
                            </span>
                            <span class="text-[0.82rem] font-semibold text-white/70">Step {{ flow.step }} of 4</span>
                        </div>

                        <h1 class="mt-3 text-[2.1rem] font-black tracking-[-0.04em] sm:text-[2.6rem]">{{ farmer.fullName }}</h1>

                        <div class="mt-3 flex flex-wrap items-center gap-3 text-[0.82rem] text-white/80">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold">{{ farmer.farmerCode || 'No farmer code' }}</span>
                            </div>
                            <div class="h-4 w-px bg-white/20"></div>
                            <div class="flex items-center gap-2">
                                <span>{{ renewal.submittedAt || 'No submission date' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2.5 sm:flex-row">
                        <Link :href="urls.index" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-5 py-2.5 text-[0.85rem] font-bold text-white backdrop-blur transition hover:bg-white/15">
                            Back To Registry
                        </Link>
                        <Link :href="urls.farmerShow" class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-[0.85rem] font-extrabold text-[#003629] shadow-lg transition hover:bg-[#f0f6f3]">
                            Open Farmer Record
                        </Link>
                    </div>
                </div>
            </section>

            <section v-if="flashSuccess" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ flashSuccess }}
            </section>

            <section v-if="pageErrors.payment || pageErrors.renewal" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
                {{ pageErrors.payment || pageErrors.renewal }}
            </section>

            <section class="renewal-summary grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                <article class="flex items-center gap-3 rounded-[18px] border border-[#dddeda] bg-white p-4 shadow-sm">
                    <div class="rounded-xl bg-[#eef5f1] px-3 py-2.5 text-[0.85rem] font-black text-[#003629]">Y</div>
                    <div>
                        <p class="text-[0.66rem] font-bold uppercase tracking-[0.18em] text-slate-500">Renewal Year</p>
                        <p class="mt-1 text-[1.35rem] font-black text-slate-950">{{ renewal.year }}</p>
                    </div>
                </article>
                <article class="flex items-center gap-3 rounded-[18px] border border-[#dddeda] bg-white p-4 shadow-sm">
                    <div class="rounded-xl bg-[#eef7e3] px-3 py-2.5 text-[0.85rem] font-black text-[#416918]">S</div>
                    <div>
                        <p class="text-[0.66rem] font-bold uppercase tracking-[0.18em] text-slate-500">Status</p>
                        <p class="mt-1 text-[1.35rem] font-black text-slate-950">{{ renewal.status.label }}</p>
                    </div>
                </article>
                <article class="flex items-center gap-3 rounded-[18px] border border-[#dddeda] bg-white p-4 shadow-sm">
                    <div class="rounded-xl bg-[#e7f0ed] px-3 py-2.5 text-[0.85rem] font-black text-[#16332c]">P</div>
                    <div>
                        <p class="text-[0.66rem] font-bold uppercase tracking-[0.18em] text-slate-500">Total Due</p>
                        <p class="mt-1 text-[1.35rem] font-black text-slate-950">PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}</p>
                    </div>
                </article>
                <article class="flex items-center gap-3 rounded-[18px] border border-[#dddeda] bg-white p-4 shadow-sm">
                    <div class="rounded-xl bg-[#eef5f1] px-3 py-2.5 text-[0.85rem] font-black text-[#003629]">M</div>
                    <div>
                        <p class="text-[0.66rem] font-bold uppercase tracking-[0.18em] text-slate-500">Member Type</p>
                        <p class="mt-1 text-[1.2rem] font-black text-slate-950">{{ farmer.memberType?.name || farmer.memberType?.code || 'N/A' }}</p>
                    </div>
                </article>
            </section>

            <RecordWarningsPanel v-if="showApprovalBlockers" :warnings="recordWarnings" :section-targets="warningSectionTargets" />

            <section class="grid gap-5 lg:grid-cols-3">
                <div class="space-y-5 lg:col-span-2">
                    <div v-if="hasActiveDocumentRequirements" id="renewal-documents-section">
                        <ReviewDocumentsPanel
                            :application="{ source: renewal.source }"
                            :flow="flow"
                            :documents="documents"
                            :document-remarks="documentRemarks"
                            :features="{ walkInAttachScanEnabled: false }"
                            @document-action="submitDocumentAction"
                        />
                    </div>

                    <section id="renewal-profile-section" class="overflow-hidden rounded-[20px] border border-[#dddeda] bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#e1e3e2] bg-[#f2f4f3] px-5 py-3.5">
                            <h2 class="text-[1.02rem] font-bold text-[#003629]">Farmer Demographics</h2>
                            <span class="text-[0.82rem] font-semibold text-slate-500">Reference: #{{ renewal.id }}</span>
                        </div>

                        <div class="grid gap-3 px-5 py-5 md:grid-cols-2">
                            <div class="rounded-xl border border-[#e2e6e3] bg-[#fbfcfb] p-3.5">
                                <p class="text-[0.66rem] font-bold uppercase tracking-[0.16em] text-slate-500">Barangay</p>
                                <p class="mt-1.5 text-[0.9rem] font-semibold text-slate-900">{{ farmer.barangay || 'Not set' }}</p>
                            </div>
                            <div class="rounded-xl border border-[#e2e6e3] bg-[#fbfcfb] p-3.5">
                                <p class="text-[0.66rem] font-bold uppercase tracking-[0.16em] text-slate-500">Association</p>
                                <p class="mt-1.5 text-[0.9rem] font-semibold text-slate-900">{{ farmer.association || 'No association' }}</p>
                            </div>
                            <div class="rounded-xl border border-[#e2e6e3] bg-[#fbfcfb] p-3.5">
                                <p class="text-[0.66rem] font-bold uppercase tracking-[0.16em] text-slate-500">Mobile Number</p>
                                <p class="mt-1.5 text-[0.9rem] font-semibold text-slate-900">{{ farmer.mobileNumber || 'Not set' }}</p>
                            </div>
                            <div class="rounded-xl border border-[#e2e6e3] bg-[#fbfcfb] p-3.5">
                                <p class="text-[0.66rem] font-bold uppercase tracking-[0.16em] text-slate-500">Submission Date</p>
                                <p class="mt-1.5 text-[0.9rem] font-semibold text-slate-900">{{ renewal.submittedAt || 'Not submitted' }}</p>
                            </div>
                            <div class="rounded-xl border border-[#e2e6e3] bg-[#fbfcfb] p-3.5 md:col-span-2">
                                <p class="text-[0.66rem] font-bold uppercase tracking-[0.16em] text-slate-500">Complete Address</p>
                                <p class="mt-1.5 text-[0.9rem] leading-6 text-slate-700">{{ farmer.address || 'No address recorded.' }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-[20px] border border-[#dddeda] bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#e1e3e2] bg-[#f2f4f3] px-5 py-3.5">
                            <h2 class="text-[1.02rem] font-bold text-[#003629]">Farmer Renewal History</h2>
                            <span class="text-[0.82rem] font-semibold text-slate-500">{{ renewalHistory.length }} record{{ renewalHistory.length === 1 ? '' : 's' }}</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[#e5ece8] text-[0.88rem]">
                                <thead class="bg-[#f8faf9]">
                                    <tr class="text-left text-[0.66rem] font-black uppercase tracking-[0.14em] text-[#6d7873]">
                                        <th class="px-5 py-3">Year</th>
                                        <th class="px-5 py-3">Settled Date</th>
                                        <th class="px-5 py-3">Amount Paid</th>
                                        <th class="px-5 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#edf2ef]">
                                    <tr v-for="history in renewalHistory" :key="history.id" class="transition hover:bg-[#fbfdfc]">
                                        <td class="px-5 py-3.5 font-bold text-[#191c1c]">
                                            <span v-if="history.isCurrent" class="mr-2 rounded-full bg-[#003629] px-2 py-1 text-[0.65rem] font-black uppercase tracking-[0.12em] text-white">Current</span>
                                            {{ history.year }}
                                        </td>
                                        <td class="px-5 py-3.5 text-[#475651]">{{ history.settledAt || 'Not settled' }}</td>
                                        <td class="px-5 py-3.5 font-bold text-[#191c1c]">PHP {{ Number(history.amountPaid || 0).toFixed(2) }}</td>
                                        <td class="px-5 py-3.5 text-right">
                                            <Link :href="history.showUrl" class="inline-flex items-center rounded-xl text-[0.84rem] font-bold text-[#003629] transition hover:underline">
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="!renewalHistory.length">
                                        <td colspan="4" class="px-5 py-8 text-center text-[0.88rem] text-[#6a7872]">No settled renewal history found for this farmer.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section id="renewal-payment-section" class="overflow-hidden rounded-[20px] border border-[#dddeda] bg-white shadow-sm">
                        <div class="border-b border-[#e1e3e2] bg-[#f2f4f3] px-5 py-3.5">
                            <h2 class="text-[1.02rem] font-bold text-[#003629]">Payment Information</h2>
                        </div>

                        <div class="px-5 py-5">
                            <div class="overflow-hidden rounded-[16px] border border-[#dfe5e1]">
                                <table class="min-w-full">
                                    <thead class="bg-[#eceeed]">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6f7b76]">Fee Description</th>
                                            <th class="px-4 py-2.5 text-right text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6f7b76]">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#e4ebe7] bg-white">
                                        <tr v-for="item in paymentBreakdown" :key="item.key">
                                            <td class="px-4 py-3 text-[0.88rem] font-medium text-[#191c1c]">{{ item.label }}</td>
                                            <td class="px-4 py-3 text-right text-[0.88rem] font-semibold text-[#191c1c]">PHP {{ item.amount.toFixed(2) }}</td>
                                        </tr>
                                        <tr class="bg-[#f5faf7]">
                                            <td class="px-4 py-3 text-[0.88rem] font-black text-[#003629]">Total Amount Due</td>
                                            <td class="px-4 py-3 text-right text-[1.4rem] font-black tracking-[-0.03em] text-[#003629]">
                                                PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <form v-if="!flow.paymentSettled" class="mt-5 space-y-4" @submit.prevent="submitPayment">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="space-y-2">
                                        <span class="text-[0.66rem] font-black uppercase tracking-[0.16em] text-slate-500">Payment Method</span>
                                        <select v-model="paymentForm.payment_method" class="w-full rounded-xl border border-[#c0c9c3] bg-white px-3.5 py-2.5 text-[0.88rem] text-slate-900 outline-none transition focus:border-[#003629]">
                                            <option value="cash">Cash Payment</option>
                                            <option value="gcash">GCash</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </label>
                                    <label class="space-y-2">
                                        <span class="text-[0.66rem] font-black uppercase tracking-[0.16em] text-slate-500">Reference Number</span>
                                        <input v-model="paymentForm.reference_no" type="text" placeholder="OR No. / Transaction ID" class="w-full rounded-xl border border-[#c0c9c3] bg-white px-3.5 py-2.5 text-[0.88rem] text-slate-900 outline-none transition focus:border-[#003629]">
                                    </label>
                                    <label class="space-y-2">
                                        <span class="text-[0.66rem] font-black uppercase tracking-[0.16em] text-slate-500">Amount Paid</span>
                                        <input v-model="paymentForm.amount_paid" type="number" min="0.01" step="0.01" placeholder="0.00" class="w-full rounded-xl border border-[#c0c9c3] bg-white px-3.5 py-2.5 text-[0.88rem] text-slate-900 outline-none transition focus:border-[#003629]">
                                    </label>
                                    <label class="space-y-2">
                                        <span class="text-[0.66rem] font-black uppercase tracking-[0.16em] text-slate-500">Payment Date</span>
                                        <input v-model="paymentForm.paid_at" type="datetime-local" class="w-full rounded-xl border border-[#c0c9c3] bg-white px-3.5 py-2.5 text-[0.88rem] text-slate-900 outline-none transition focus:border-[#003629]">
                                    </label>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#003629] px-5 py-2.5 text-[0.88rem] font-extrabold text-white shadow-md transition hover:bg-[#0e4638] disabled:cursor-not-allowed disabled:opacity-60" :disabled="paymentForm.processing">
                                        {{ paymentForm.processing ? 'Recording...' : 'Record & Complete Renewal' }}
                                    </button>
                                </div>
                            </form>

                            <div v-else class="mt-5 rounded-[18px] border-2 border-dashed border-[#c8dfb7] bg-[#f5fbef] px-5 py-6 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#416918] text-xl font-black text-white">OK</div>
                                <h3 class="mt-3 text-[1.25rem] font-black text-[#416918]">Payment Successfully Recorded</h3>
                                <p class="mx-auto mt-2 max-w-xl text-[0.88rem] leading-6 text-[#5d6a65]">
                                    This renewal process is now finalized. The farmer's membership status has been updated for {{ renewal.year }}.
                                </p>
                            </div>
                        </div>
                    </section>

                    <InternalNotesPanel compact :notes="internalNotes" :submit-url="urls.storeInternalNote" title="Internal Notes" />
                </div>

                <div class="space-y-5">
                    <section class="flex h-full flex-col overflow-hidden rounded-[20px] border border-[#dddeda] bg-white shadow-sm">
                        <div class="border-b border-[#e1e3e2] bg-[#f2f4f3] px-5 py-3.5">
                            <h2 class="text-[1.02rem] font-bold text-[#003629]">Payment History</h2>
                        </div>

                        <div class="flex-1 overflow-y-auto px-5 py-5">
                            <div class="space-y-4">
                                <div v-for="payment in payments.data" :key="payment.id" class="relative border-l-2 border-[#dce4df] pl-7">
                                    <div class="absolute -left-[9px] top-1 h-4 w-4 rounded-full bg-[#003629] ring-4 ring-white"></div>
                                    <article class="rounded-[16px] border border-[#dde3df] bg-[#fbfcfb] p-3.5 shadow-sm">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-[1rem] font-black text-[#191c1c]">PHP {{ Number(payment.amountPaid || 0).toFixed(2) }}</p>
                                                <p class="mt-1 text-xs font-medium text-[#75827c]">{{ payment.paidAt || 'No date' }}</p>
                                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-[#4f6b61]">Renewal Year {{ payment.renewalYear || renewal.year }}</p>
                                            </div>
                                            <span class="rounded-full bg-[#dff1cf] px-3 py-1 text-[0.72rem] font-black uppercase tracking-[0.12em] text-[#466f1e]">
                                                {{ payment.statusLabel }}
                                            </span>
                                        </div>

                                        <div class="mt-3 space-y-1 text-[0.84rem] text-[#5f6c67]">
                                            <p>{{ payment.method || 'METHOD' }}</p>
                                            <p>{{ payment.referenceNo || 'No reference number' }}</p>
                                        </div>

                                        <div class="mt-3 grid gap-2">
                                            <div v-if="Number(payment.breakdown?.annualDue || 0) > 0" class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-[0.84rem]">
                                                <span class="text-[#5f6c67]">Annual Due</span>
                                                <span class="font-semibold text-[#191c1c]">PHP {{ Number(payment.breakdown.annualDue).toFixed(2) }}</span>
                                            </div>
                                            <div v-if="Number(payment.breakdown?.mortuaryFee || 0) > 0" class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-[0.84rem]">
                                                <span class="text-[#5f6c67]">Mortuary Fee</span>
                                                <span class="font-semibold text-[#191c1c]">PHP {{ Number(payment.breakdown.mortuaryFee).toFixed(2) }}</span>
                                            </div>
                                        </div>
                                    </article>
                                </div>

                                <div v-if="!payments.data.length" class="rounded-[16px] border border-dashed border-[#d7e2db] bg-[#fafcfb] px-5 py-8 text-center text-[0.88rem] text-[#72817a]">
                                    No previous payments found for this cycle.
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-[#e1e3e2] bg-[#f2f4f3] px-5 py-3.5">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between rounded-xl border border-[#dde3df] bg-white px-4 py-2.5">
                                    <span class="text-[0.84rem] font-medium text-[#697772]">Showing</span>
                                    <span class="text-[0.84rem] font-bold text-[#003629]">{{ payments.data.length }} of {{ payments.total }}</span>
                                </div>
                                <div v-if="payments.last_page > 1" class="flex flex-wrap items-center justify-center gap-2">
                                    <template v-for="link in payments.links" :key="link.label">
                                        <span v-if="!link.url" class="inline-flex items-center rounded-xl border border-[#dbe2de] px-3 py-2 text-[0.84rem] text-[#9aa6a1]" v-html="link.label" />
                                        <Link
                                            v-else
                                            :href="link.url"
                                            class="inline-flex items-center rounded-xl border px-3 py-2 text-[0.84rem] font-bold transition"
                                            :class="link.active ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#dbe2de] bg-white text-[#5f6b66] hover:bg-[#f7faf8]'"
                                            preserve-scroll
                                            preserve-state
                                            v-html="link.label"
                                        />
                                    </template>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<style scoped>
.renewal-record-hero > div:not(.relative) {
    display: none;
}
.renewal-record-hero > .relative {
    align-items: center;
    gap: 0.75rem;
}
.renewal-record-hero .rounded-full {
    border-radius: 0.375rem;
    padding: 0.2rem 0.5rem;
    font-size: 0.55rem;
    font-weight: 600;
    letter-spacing: 0.08em;
}
.renewal-record-hero h1 {
    margin-top: 0.3rem;
    font-size: 1.25rem;
    line-height: 1.5rem;
    font-weight: 600;
}
.renewal-record-hero h1 + div {
    margin-top: 0.3rem;
    font-size: 0.65rem;
}
.renewal-record-hero a {
    min-height: 2rem;
    border-radius: 0.375rem;
    padding: 0 0.75rem;
    font-size: 0.68rem;
    font-weight: 600;
    box-shadow: none;
}
.renewal-summary {
    gap: 0.5rem;
}
.renewal-summary article {
    gap: 0.6rem;
    border-radius: 0.5rem;
    padding: 0.65rem 0.75rem;
    box-shadow: none;
}
.renewal-summary article > div:first-child {
    border-radius: 0.375rem;
    padding: 0.35rem 0.55rem;
    font-size: 0.65rem;
}
.renewal-summary article p:first-child {
    font-size: 0.55rem;
    letter-spacing: 0.08em;
}
.renewal-summary article p:last-child {
    margin-top: 0.15rem;
    font-size: 0.9rem;
    line-height: 1.15rem;
    font-weight: 600;
}
.renewal-record-view > .grid:last-of-type {
    gap: 0.75rem;
}
.renewal-record-view section.rounded-\[20px\] {
    border-radius: 0.5rem;
    box-shadow: none;
}
.renewal-record-view section.rounded-\[20px\] > div:first-child {
    padding: 0.7rem 1rem;
}
.renewal-record-view section.rounded-\[20px\] h2 {
    font-size: 0.875rem;
    font-weight: 600;
}
.renewal-record-view section.rounded-\[20px\] .grid > div {
    border-radius: 0.375rem;
    padding: 0.65rem 0.75rem;
}
.renewal-record-view section.rounded-\[20px\] .grid > div p:first-child {
    font-size: 0.55rem;
    letter-spacing: 0.08em;
}
.renewal-record-view section.rounded-\[20px\] .grid > div p:last-child {
    margin-top: 0.2rem;
    font-size: 0.7rem;
}
.renewal-record-view input,
.renewal-record-view select {
    min-height: 2.25rem;
    border-radius: 0.375rem;
    padding: 0 0.75rem;
    font-size: 0.75rem;
}
.renewal-record-view button[type='submit'] {
    min-height: 2.25rem;
    border-radius: 0.375rem;
    padding: 0 1rem;
    font-size: 0.75rem;
    box-shadow: none;
}
.renewal-record-view table th {
    padding-top: 0.6rem;
    padding-bottom: 0.6rem;
    font-size: 0.58rem;
}
.renewal-record-view table td {
    padding-top: 0.7rem;
    padding-bottom: 0.7rem;
    font-size: 0.7rem;
}
</style>
