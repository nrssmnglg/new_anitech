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
    <div class="space-y-6">
        <section class="rounded-[24px] border border-[#dbe2de] bg-white shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
            <div class="border-b border-[#e4ebe7] px-5 py-4">
                <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Step 3</p>
                <h2 class="mt-1 text-base font-bold text-[#1a2420]">
                    {{ flow.isMobile ? 'Mobile payment' : 'Payment recording' }}
                </h2>
            </div>

            <div class="space-y-4 px-5 py-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Membership Fee</p>
                        <p class="mt-1.5 text-[1.05rem] font-black text-[#191c1c]">PHP {{ Number(assessment.membershipFee || 0).toFixed(2) }}</p>
                    </div>
                    <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Annual Due</p>
                        <p class="mt-1.5 text-[1.05rem] font-black text-[#191c1c]">PHP {{ Number(assessment.annualDue || 0).toFixed(2) }}</p>
                    </div>
                    <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Mortuary Fee</p>
                        <p class="mt-1.5 text-[1.05rem] font-black text-[#191c1c]">PHP {{ Number(assessment.mortuaryFee || 0).toFixed(2) }}</p>
                    </div>
                    <div class="rounded-[20px] border border-[#d4e3da] bg-[linear-gradient(135deg,_#eef6f0_0%,_#ffffff_100%)] p-4">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#6a7b73]">Total Due</p>
                        <p class="mt-1.5 text-[1.05rem] font-black text-[#163b31]">PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}</p>
                    </div>
                </div>

                <form v-if="flow.canRecordPaymentInAdmin" class="space-y-4" @submit.prevent="$emit('submit-payment')">
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
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-[#003629] px-5 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]" :disabled="paymentForm.processing">
                        {{ paymentForm.processing ? 'Recording...' : 'Record Payment And Finish' }}
                    </button>
                </form>

                <div v-else class="rounded-[20px] border border-dashed border-[#dbe2de] bg-[#f8faf9] px-4 py-5 text-sm text-[#5f6c67]">
                    <span v-if="flow.paymentSettled">Payment is already recorded for this application.</span>
                    <span v-else>Finish the document checklist first before recording the payment.</span>
                </div>
            </div>
        </section>

        <section v-if="payments.length" class="rounded-[24px] border border-[#dbe2de] bg-white shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
            <div class="border-b border-[#e4ebe7] px-5 py-4">
                <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Payments</p>
                <h2 class="mt-1 text-lg font-bold text-[#1a2420]">Recorded entries</h2>
            </div>

            <div class="space-y-3 px-5 py-5">
                <article v-for="payment in payments" :key="payment.id" class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
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
    </div>
</template>
