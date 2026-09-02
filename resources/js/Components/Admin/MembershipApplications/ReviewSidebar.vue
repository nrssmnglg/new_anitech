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
        <section class="rounded-lg border border-[#dbe2de] bg-white">
            <div class="border-b border-[#e4ebe7] bg-[#f6f8f7] px-4 py-2.5">
                <p class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Step 3</p>
                <h2 class="mt-0.5 text-xs font-semibold text-[#1a2420]">
                    {{ flow.isMobile ? 'Mobile payment' : 'Payment recording' }}
                </h2>
            </div>

            <div class="space-y-3 p-3">
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-2.5">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Membership Fee</p>
                        <p class="mt-1 text-sm font-semibold text-[#191c1c]">PHP {{ Number(assessment.membershipFee || 0).toFixed(2) }}</p>
                    </div>
                    <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-2.5">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Annual Due</p>
                        <p class="mt-1 text-sm font-semibold text-[#191c1c]">PHP {{ Number(assessment.annualDue || 0).toFixed(2) }}</p>
                    </div>
                    <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-2.5">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Mortuary Fee</p>
                        <p class="mt-1 text-sm font-semibold text-[#191c1c]">PHP {{ Number(assessment.mortuaryFee || 0).toFixed(2) }}</p>
                    </div>
                    <div class="rounded-md border border-[#d4e3da] bg-[#eef6f0] p-2.5">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#6a7b73]">Total Due</p>
                        <p class="mt-1 text-sm font-semibold text-[#163b31]">PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}</p>
                    </div>
                </div>

                <form v-if="flow.canRecordPaymentInAdmin" class="space-y-2.5" @submit.prevent="$emit('submit-payment')">
                    <label class="space-y-2">
                        <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Payment Method</span>
                        <input v-model="paymentForm.payment_method" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Reference No.</span>
                        <input v-model="paymentForm.reference_no" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Amount Paid</span>
                        <input v-model="paymentForm.amount_paid" type="number" min="0.01" step="0.01" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Paid At</span>
                        <input v-model="paymentForm.paid_at" type="datetime-local" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                    </label>
                    <button type="submit" class="inline-flex h-9 w-full items-center justify-center rounded-md bg-[#003629] px-4 text-xs font-semibold text-white transition hover:bg-[#0d4637]" :disabled="paymentForm.processing">
                        {{ paymentForm.processing ? 'Recording...' : 'Record Payment And Finish' }}
                    </button>
                </form>

                <div v-else class="rounded-md border border-dashed border-[#dbe2de] bg-[#f8faf9] px-3 py-2.5 text-xs text-[#5f6c67]">
                    <span v-if="flow.paymentSettled">Payment is already recorded for this application.</span>
                    <span v-else>Finish the document checklist first before recording the payment.</span>
                </div>
            </div>
        </section>

        <section v-if="payments.length" class="rounded-lg border border-[#dbe2de] bg-white">
            <div class="border-b border-[#e4ebe7] bg-[#f6f8f7] px-4 py-2.5">
                <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Payments</p>
                <h2 class="mt-1 text-lg font-bold text-[#1a2420]">Recorded entries</h2>
            </div>

            <div class="space-y-2 p-3">
                <article v-for="payment in payments" :key="payment.id" class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-[#191c1c]">PHP {{ Number(payment.amountPaid || 0).toFixed(2) }}</p>
                            <p class="mt-1 text-xs text-[#78857f]">{{ payment.paidAt || 'No date' }}</p>
                        </div>
                        <span class="inline-flex rounded-full bg-[#eef7e3] px-2.5 py-1 text-xs font-black text-[#416918]">{{ payment.statusLabel }}</span>
                    </div>
                    <p class="mt-3 text-xs font-black uppercase tracking-[0.14em] text-[#7a8781]">{{ payment.method || 'METHOD' }}</p>
                    <p class="mt-1 text-sm text-[#5f6c67]">{{ payment.referenceNo || 'No reference number' }}</p>
                </article>
            </div>
        </section>

        <section
            v-if="permissions.canRejectDecision && !flow.paymentSettled && application.status.value !== 'rejected'"
            class="rounded-lg border border-[#f1c9c9] bg-[#fff8f8]"
        >
            <form class="space-y-4 p-4" @submit.prevent="$emit('submit-rejection')">
                <label class="space-y-2">
                    <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7f5555]">Rejection Reason</span>
                    <select v-model="rejectionForm.rejection_reason" required class="w-full rounded-2xl border border-[#e7bebe] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#b42318]">
                        <option value="">Select a reason</option>
                        <option v-for="reason in rejectionReasonOptions" :key="reason.value" :value="reason.value">{{ reason.label }}</option>
                    </select>
                </label>

                <label v-if="rejectionForm.rejection_reason === 'other'" class="space-y-2">
                    <span class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7f5555]">Rejection Details</span>
                    <textarea v-model="rejectionForm.rejection_details" required rows="3" class="w-full resize-none rounded-2xl border border-[#e7bebe] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#b42318]"></textarea>
                </label>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-[#a62828] px-5 py-3 text-sm font-extrabold text-white transition hover:bg-[#8f1e1e] disabled:cursor-not-allowed disabled:opacity-60" :disabled="rejectionForm.processing || !rejectionForm.rejection_reason">
                    {{ rejectionForm.processing ? 'Rejecting...' : 'Reject Application' }}
                </button>
            </form>
        </section>

        <section v-if="application.status.value === 'rejected' && urls.reapply" class="rounded-[24px] border border-[#f0d8a8] bg-[#fffaf0] p-5 shadow-[0_14px_32px_rgba(120,82,20,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#9a6700]">Next step</p>
            <h2 class="mt-1 text-base font-bold text-[#6e4a00]">Create a replacement application</h2>
            <p class="mt-2 text-sm leading-6 text-[#745b2f]">The rejected record remains in history. Start a new application using the corrected details.</p>
            <Link :href="urls.reapply" class="mt-4 inline-flex w-full items-center justify-center rounded-2xl border border-[#d6ad59] bg-white px-5 py-3 text-sm font-extrabold text-[#765000] transition hover:bg-[#fff4d9]">
                Reapply for Membership
            </Link>
        </section>
    </div>
</template>
