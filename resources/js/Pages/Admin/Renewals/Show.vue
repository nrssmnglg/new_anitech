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
        <div class="mx-auto max-w-[1536px] space-y-4">
            <!-- Sleek Top Header Row -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-[#64748b]">
                        <Link :href="urls.index" class="hover:text-[#014d3c] transition">Renewal Processing</Link>
                        <span>/</span>
                        <span>Renewal #{{ renewal.id }}</span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold tracking-tight text-[#0f172a] sm:text-2xl">{{ farmer.fullName }}</h1>
                        <span class="inline-flex items-center rounded-md bg-[#f1f5f3] px-2 py-0.5 font-mono text-xs font-semibold text-[#014d3c]">
                            {{ farmer.farmerCode || 'Unassigned' }}
                        </span>
                        <span class="inline-flex items-center rounded-md bg-[#e6f5ec] px-2 py-0.5 text-xs font-bold text-[#0f6b45]">
                            Step {{ flow.step }} of 4
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-[#64748b]">
                        Submitted: {{ renewal.submittedAt || 'No submission date recorded' }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="urls.index"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-3.5 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                    >
                        Back to Records
                    </Link>
                    <Link
                        :href="urls.farmerShow"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97]"
                    >
                        Open Farmer Profile
                    </Link>
                </div>
            </div>

            <!-- Flash & Error alerts -->
            <div v-if="flashSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
                {{ flashSuccess }}
            </div>

            <div v-if="pageErrors.payment || pageErrors.renewal" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
                {{ pageErrors.payment || pageErrors.renewal }}
            </div>

            <!-- Summary Cards (Simple & Compact) -->
            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <article class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-sm">
                    <p class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Renewal Year</p>
                    <p class="mt-1 text-base font-extrabold text-[#0f172a]">{{ renewal.year }}</p>
                </article>

                <article class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-sm">
                    <p class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Status</p>
                    <div class="mt-1">
                        <span
                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold"
                            :class="{
                                'bg-[#f0fdf4] text-[#15803d] border border-[#bbf7d0]': ['approved', 'completed'].includes(String(renewal.status.value || '').toLowerCase()),
                                'bg-[#fefce8] text-[#854d0e] border border-[#fef08a]': ['pending', 'submitted', 'review'].includes(String(renewal.status.value || '').toLowerCase()),
                                'bg-[#f8fafc] text-[#475569] border border-[#e2e8f0]': !['approved', 'completed', 'pending', 'submitted', 'review'].includes(String(renewal.status.value || '').toLowerCase()),
                            }"
                        >
                            {{ renewal.status.label }}
                        </span>
                    </div>
                </article>

                <article class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-sm">
                    <p class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Total Due</p>
                    <p class="mt-1 text-base font-extrabold text-[#014d3c]">PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}</p>
                </article>

                <article class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-sm">
                    <p class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Member Type</p>
                    <p class="mt-1 text-xs font-bold text-[#0f172a] truncate">{{ farmer.memberType?.name || farmer.memberType?.code || 'N/A' }}</p>
                </article>
            </section>

            <RecordWarningsPanel v-if="showApprovalBlockers" :warnings="recordWarnings" :section-targets="warningSectionTargets" />

            <section class="grid gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <div v-if="hasActiveDocumentRequirements" id="renewal-documents-section">
                        <ReviewDocumentsPanel v-if="!flow.isHistorical"
                            :application="{ source: renewal.source }"
                            :flow="flow"
                            :documents="documents"
                            :document-remarks="documentRemarks"
                            :features="{ walkInAttachScanEnabled: false }"
                            @document-action="submitDocumentAction"
                        />
                    </div>

                    <!-- Farmer Demographics Card -->
                    <section id="renewal-profile-section" class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#f1f5f9] px-4 py-3">
                            <h2 class="text-xs font-bold text-[#0f172a]">Farmer Demographics</h2>
                            <span class="text-[0.68rem] font-mono text-[#64748b]">Ref: #{{ renewal.id }}</span>
                        </div>

                        <div class="grid gap-3 p-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Barangay</span>
                                <p class="mt-0.5 text-xs font-semibold text-[#0f172a]">{{ farmer.barangay || 'Not set' }}</p>
                            </div>
                            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Association</span>
                                <p class="mt-0.5 text-xs font-semibold text-[#0f172a]">{{ farmer.association || 'No association' }}</p>
                            </div>
                            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Mobile Number</span>
                                <p class="mt-0.5 text-xs font-semibold text-[#0f172a]">{{ farmer.mobileNumber || 'Not set' }}</p>
                            </div>
                            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Submission Date</span>
                                <p class="mt-0.5 text-xs font-semibold text-[#0f172a]">{{ renewal.submittedAt || 'Not submitted' }}</p>
                            </div>
                            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3 sm:col-span-2">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Complete Address</span>
                                <p class="mt-0.5 text-xs font-semibold text-[#0f172a]">{{ farmer.address || 'No address recorded.' }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Farmer Renewal History Table -->
                    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#f1f5f9] px-4 py-3">
                            <h2 class="text-xs font-bold text-[#0f172a]">Renewal History</h2>
                            <span class="rounded-md bg-[#f1f5f3] px-2 py-0.5 text-[0.68rem] font-semibold text-[#014d3c]">
                                {{ renewalHistory.length }} record{{ renewalHistory.length === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-[#dde4de] bg-[#f8faf9] text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        <th class="px-4 py-2.5">Year</th>
                                        <th class="px-4 py-2.5">Settled Date</th>
                                        <th class="px-4 py-2.5">Amount Paid</th>
                                        <th class="px-4 py-2.5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f1f5f9]">
                                    <tr v-for="history in renewalHistory" :key="history.id" class="transition hover:bg-[#fbfcfb]">
                                        <td class="px-4 py-3 font-semibold text-[#0f172a]">
                                            <span v-if="history.isCurrent" class="mr-1.5 inline-flex items-center rounded-md bg-[#014d3c] px-1.5 py-0.5 text-[0.62rem] font-bold text-white">Current</span>
                                            {{ history.year }}
                                        </td>
                                        <td class="px-4 py-3 text-[#475569]">{{ history.settledAt || 'Not settled' }}</td>
                                        <td class="px-4 py-3 font-semibold text-[#0f172a]">PHP {{ Number(history.amountPaid || 0).toFixed(2) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <Link :href="history.showUrl" class="font-semibold text-[#014d3c] hover:underline">
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="!renewalHistory.length">
                                        <td colspan="4" class="px-4 py-6 text-center text-xs text-[#94a3b8]">No settled renewal history found for this farmer.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Payment Information Section -->
                    <section id="renewal-payment-section" class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="border-b border-[#f1f5f9] px-4 py-3">
                            <h2 class="text-xs font-bold text-[#0f172a]">Payment Information</h2>
                        </div>

                        <div class="p-4">
                            <div class="overflow-hidden rounded-xl border border-[#dde4de]">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-[#dde4de] bg-[#f8faf9] text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                            <th class="px-4 py-2">Fee Description</th>
                                            <th class="px-4 py-2 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#f1f5f9]">
                                        <tr v-for="item in paymentBreakdown" :key="item.key">
                                            <td class="px-4 py-2.5 font-medium text-[#0f172a]">{{ item.label }}</td>
                                            <td class="px-4 py-2.5 text-right font-semibold text-[#0f172a]">PHP {{ item.amount.toFixed(2) }}</td>
                                        </tr>
                                        <tr class="bg-[#f0fdf4]/50">
                                            <td class="px-4 py-2.5 font-bold text-[#014d3c]">Total Amount Due</td>
                                            <td class="px-4 py-2.5 text-right font-extrabold text-[#014d3c]">
                                                PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <form v-if="!flow.paymentSettled" class="mt-4 space-y-3.5" @submit.prevent="submitPayment">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="block space-y-1">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Payment Method</span>
                                        <select v-model="paymentForm.payment_method" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                                            <option value="cash">Cash Payment</option>
                                            <option value="gcash">GCash</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </label>
                                    <label class="block space-y-1">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Reference Number</span>
                                        <input v-model="paymentForm.reference_no" type="text" placeholder="OR No. / Transaction ID" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                                    </label>
                                    <label class="block space-y-1">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Amount Paid</span>
                                        <input v-model="paymentForm.amount_paid" type="number" min="0.01" step="0.01" placeholder="0.00" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs font-bold text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                                    </label>
                                    <label class="block space-y-1">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Payment Date</span>
                                        <input v-model="paymentForm.paid_at" type="datetime-local" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                                    </label>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60" :disabled="paymentForm.processing">
                                        {{ paymentForm.processing ? 'Recording...' : 'Record & Complete Renewal' }}
                                    </button>
                                </div>
                            </form>

                            <div v-else class="mt-4 rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] p-4 text-center">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#15803d] text-xs font-bold text-white">✓</span>
                                <h3 class="mt-2 text-xs font-bold text-[#15803d]">Payment Successfully Recorded</h3>
                                <p class="mt-1 text-xs text-[#475569]">
                                    This renewal is finalized. The farmer's membership status has been updated for {{ renewal.year }}.
                                </p>
                            </div>
                        </div>
                    </section>

                    <InternalNotesPanel compact :notes="internalNotes" :submit-url="urls.storeInternalNote" title="Internal Notes" />
                </div>

                <!-- Right Column: Payment History Log -->
                <div class="space-y-4">
                    <section class="flex flex-col overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="border-b border-[#f1f5f9] px-4 py-3">
                            <h2 class="text-xs font-bold text-[#0f172a]">Payment History</h2>
                        </div>

                        <div class="p-4 space-y-3">
                            <div v-for="payment in payments.data" :key="payment.id" class="rounded-xl border border-[#dde4de] bg-[#f8fafc] p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-extrabold text-[#0f172a]">PHP {{ Number(payment.amountPaid || 0).toFixed(2) }}</p>
                                        <p class="text-[0.68rem] text-[#64748b]">{{ payment.paidAt || 'No date' }}</p>
                                        <p class="text-[0.68rem] font-semibold text-[#014d3c]">Year {{ payment.renewalYear || renewal.year }}</p>
                                    </div>
                                    <span class="rounded-md bg-[#e6f5ec] px-1.5 py-0.5 text-[0.62rem] font-bold text-[#0f6b45]">
                                        {{ payment.statusLabel }}
                                    </span>
                                </div>

                                <div class="mt-2 space-y-0.5 text-xs text-[#64748b]">
                                    <p class="capitalize">{{ payment.method || 'Cash' }}</p>
                                    <p class="font-mono text-[0.68rem]">{{ payment.referenceNo || 'No reference' }}</p>
                                </div>

                                <div v-if="Number(payment.breakdown?.annualDue || 0) > 0 || Number(payment.breakdown?.mortuaryFee || 0) > 0" class="mt-2 space-y-1 border-t border-[#e2e8f0] pt-2 text-[0.68rem]">
                                    <div v-if="Number(payment.breakdown?.annualDue || 0) > 0" class="flex justify-between text-[#64748b]">
                                        <span>Annual Due</span>
                                        <span class="font-semibold text-[#0f172a]">PHP {{ Number(payment.breakdown.annualDue).toFixed(2) }}</span>
                                    </div>
                                    <div v-if="Number(payment.breakdown?.mortuaryFee || 0) > 0" class="flex justify-between text-[#64748b]">
                                        <span>Mortuary Fee</span>
                                        <span class="font-semibold text-[#0f172a]">PHP {{ Number(payment.breakdown.mortuaryFee).toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!payments.data.length" class="rounded-xl border border-dashed border-[#dde4de] p-6 text-center text-xs text-[#94a3b8]">
                                No previous payments recorded for this cycle.
                            </div>
                        </div>

                        <div v-if="payments.data.length" class="border-t border-[#f1f5f9] bg-[#f8faf9] px-4 py-2.5">
                            <div class="flex items-center justify-between text-xs text-[#64748b]">
                                <span>Total Records</span>
                                <span class="font-bold text-[#014d3c]">{{ payments.data.length }} of {{ payments.total }}</span>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
