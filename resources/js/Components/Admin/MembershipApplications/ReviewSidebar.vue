<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    application: { type: Object, required: true },
    flow: { type: Object, required: true },
    assessment: { type: Object, required: true },
    payments: { type: Array, required: true },
    permissions: { type: Object, required: true },
    rejectionReasonOptions: { type: Array, required: true },
    urls: { type: Object, required: true },
    paymentForm: { type: Object, required: true },
    rejectionForm: { type: Object, required: true },
});

defineEmits(['submit-payment', 'submit-rejection']);
</script>

<template>
    <div class="space-y-4">
        <!-- Assessment & Payment Card -->
        <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
            <div class="border-b border-[#dde4de] bg-[#f9fbfa] px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-[#003629]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M1 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4Zm12 4a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm12 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h2 class="text-xs font-bold text-[#0f172a]">
                            {{ flow.isMobile ? 'Mobile Payment' : 'Payment Assessment' }}
                        </h2>
                    </div>
                    <span class="rounded bg-[#003629]/10 px-2 py-0.5 text-[0.6rem] font-bold text-[#003629]">
                        Step 3
                    </span>
                </div>
            </div>

            <div class="p-4">
                <!-- Fee Summary Grid -->
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-2">
                    <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-2.5">
                        <span class="text-[0.58rem] font-bold uppercase tracking-wider text-[#64748b]">Membership Fee</span>
                        <p class="mt-0.5 text-xs font-bold text-[#0f172a]">₱{{ Number(assessment.membershipFee || 0).toFixed(2) }}</p>
                    </div>
                    <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-2.5">
                        <span class="text-[0.58rem] font-bold uppercase tracking-wider text-[#64748b]">Annual Due</span>
                        <p class="mt-0.5 text-xs font-bold text-[#0f172a]">₱{{ Number(assessment.annualDue || 0).toFixed(2) }}</p>
                    </div>
                    <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-2.5">
                        <span class="text-[0.58rem] font-bold uppercase tracking-wider text-[#64748b]">Mortuary Fee</span>
                        <p class="mt-0.5 text-xs font-bold text-[#0f172a]">₱{{ Number(assessment.mortuaryFee || 0).toFixed(2) }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-2.5">
                        <span class="text-[0.58rem] font-bold uppercase tracking-wider text-emerald-800">Total Amount</span>
                        <p class="mt-0.5 text-xs font-bold text-[#003629]">₱{{ Number(assessment.totalAmountDue || 0).toFixed(2) }}</p>
                    </div>
                </div>

                <!-- Payment Recording Form -->
                <form v-if="flow.canRecordPaymentInAdmin" class="mt-4 space-y-3 border-t border-[#edf2ee] pt-3.5" @submit.prevent="$emit('submit-payment')">
                    <div>
                        <label class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Payment Method</label>
                        <input
                            v-model="paymentForm.payment_method"
                            type="text"
                            placeholder="e.g. Cash, GCash"
                            class="mt-1 h-9 w-full rounded-lg border border-[#dde4de] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                        >
                    </div>

                    <div>
                        <label class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Reference Number (Optional)</label>
                        <input
                            v-model="paymentForm.reference_no"
                            type="text"
                            placeholder="Official Receipt # / Transaction ID"
                            class="mt-1 h-9 w-full rounded-lg border border-[#dde4de] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                        >
                    </div>

                    <div>
                        <label class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Amount Paid (PHP)</label>
                        <input
                            v-model="paymentForm.amount_paid"
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="mt-1 h-9 w-full rounded-lg border border-[#dde4de] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                        >
                    </div>

                    <div>
                        <label class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Date & Time Paid</label>
                        <input
                            v-model="paymentForm.paid_at"
                            type="datetime-local"
                            class="mt-1 h-9 w-full rounded-lg border border-[#dde4de] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                        >
                    </div>

                    <button
                        type="submit"
                        class="mt-2 inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-xl bg-[#003629] px-4 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a] hover:shadow-md disabled:opacity-60"
                        :disabled="paymentForm.processing"
                    >
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ paymentForm.processing ? 'Recording...' : 'Record Payment & Settle' }}</span>
                    </button>
                </form>

                <!-- Status Note when form not active -->
                <div v-else class="mt-3 rounded-xl border border-dashed border-[#dde4de] bg-[#f9fbfa] p-3 text-center text-xs text-slate-500">
                    <span v-if="flow.paymentSettled" class="font-semibold text-emerald-700">
                        Payment has been recorded and settled.
                    </span>
                    <span v-else>
                        Verify and clear required documents first before recording payment.
                    </span>
                </div>
            </div>
        </section>

        <!-- Recorded Payments History -->
        <section v-if="payments.length" class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
            <div class="border-b border-[#dde4de] bg-[#f9fbfa] px-4 py-3">
                <span class="text-xs font-bold text-[#0f172a]">Payment History</span>
            </div>

            <div class="space-y-2 p-3">
                <article
                    v-for="payment in payments"
                    :key="payment.id"
                    class="rounded-xl border border-emerald-100 bg-emerald-50/20 p-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold text-[#0f172a]">₱{{ Number(payment.amountPaid || 0).toFixed(2) }}</p>
                            <p class="mt-0.5 text-[0.65rem] text-slate-500">{{ payment.paidAt || 'No date' }}</p>
                        </div>
                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[0.6rem] font-bold text-emerald-700 border border-emerald-200">
                            {{ payment.statusLabel }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-[0.68rem] text-slate-500">
                        <span>Method: <strong class="text-slate-700">{{ payment.method || 'CASH' }}</strong></span>
                        <span v-if="payment.referenceNo">Ref: <strong class="text-slate-700">{{ payment.referenceNo }}</strong></span>
                    </div>
                </article>
            </div>
        </section>

        <!-- Rejection Control -->
        <section
            v-if="permissions.canRejectDecision && !flow.paymentSettled && application.status.value !== 'rejected'"
            class="overflow-hidden rounded-2xl border border-rose-200 bg-rose-50/30 p-4 shadow-xs"
        >
            <div class="flex items-center gap-2 text-rose-800">
                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <h3 class="text-xs font-bold">Reject Application</h3>
            </div>

            <form class="mt-3 space-y-3" @submit.prevent="$emit('submit-rejection')">
                <div>
                    <label class="block text-[0.62rem] font-bold uppercase tracking-wider text-rose-800">Rejection Reason</label>
                    <select
                        v-model="rejectionForm.rejection_reason"
                        required
                        class="mt-1 h-9 w-full rounded-lg border border-rose-200 bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-200/50"
                    >
                        <option value="">Select reason</option>
                        <option v-for="reason in rejectionReasonOptions" :key="reason.value" :value="reason.value">{{ reason.label }}</option>
                    </select>
                </div>

                <div v-if="rejectionForm.rejection_reason === 'other'">
                    <label class="block text-[0.62rem] font-bold uppercase tracking-wider text-rose-800">Reason Details</label>
                    <textarea
                        v-model="rejectionForm.rejection_details"
                        required
                        rows="3"
                        placeholder="Provide detailed explanation for rejection..."
                        class="mt-1 w-full rounded-lg border border-rose-200 bg-white p-2.5 text-xs text-[#0f172a] outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-200/50"
                    />
                </div>

                <button
                    type="submit"
                    class="inline-flex h-9 w-full items-center justify-center rounded-xl bg-rose-600 px-4 text-xs font-bold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-60"
                    :disabled="rejectionForm.processing || !rejectionForm.rejection_reason"
                >
                    {{ rejectionForm.processing ? 'Rejecting...' : 'Confirm Rejection' }}
                </button>
            </form>
        </section>

        <!-- Reapply Prompt if Rejected -->
        <section
            v-if="application.status.value === 'rejected' && urls.reapply"
            class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4"
        >
            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-amber-800">Replacement Record</span>
            <p class="mt-1 text-xs text-amber-900">
                This application was rejected. You can create a new replacement record with corrected information.
            </p>
            <Link
                :href="urls.reapply"
                class="mt-3 inline-flex h-9 w-full items-center justify-center rounded-xl border border-amber-300 bg-white px-4 text-xs font-bold text-amber-900 shadow-xs transition hover:bg-amber-50"
            >
                Reapply for Membership
            </Link>
        </section>
    </div>
</template>
