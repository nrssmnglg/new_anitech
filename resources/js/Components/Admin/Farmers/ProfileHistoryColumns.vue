<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    recentApplications: { type: Array, required: true },
    recentAssessments: { type: Array, required: true },
    recentLedgers: { type: Array, required: true },
});
</script>

<template>
    <section class="grid gap-4 xl:grid-cols-3">
        <section class="overflow-hidden rounded-[20px] border border-[#dbe2de] bg-white shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <div class="flex items-center justify-between border-b border-[#e4ebe7] bg-[#f4f7f5] px-4 py-3">
                <h2 class="text-base font-bold text-[#1a2420]">Applications</h2>
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#003629]/10 text-xs font-black text-[#003629]">{{ recentApplications.length }}</span>
            </div>
            <div class="space-y-2.5 px-4 py-4">
                <article v-for="item in recentApplications" :key="item.id" class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-[#003629]">{{ item.applicationNo }}</p>
                            <p class="mt-1 text-[0.68rem] font-black uppercase tracking-[0.14em] text-[#7a8781]">{{ item.sourceLabel }}</p>
                            <p class="mt-1.5 text-xs text-[#5f6c67]">{{ item.submittedAt || 'Not submitted' }}</p>
                        </div>
                        <Link :href="item.showUrl" class="rounded-lg border border-[#d7e0db] px-2.5 py-1.5 text-[11px] font-bold text-[#5f6c67] transition hover:bg-white">
                            Open
                        </Link>
                    </div>
                </article>
                <p v-if="!recentApplications.length" class="text-xs text-[#6a7872]">No membership applications recorded yet.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-[20px] border border-[#dbe2de] bg-white shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <div class="flex items-center justify-between border-b border-[#e4ebe7] bg-[#f4f7f5] px-4 py-3">
                <h2 class="text-base font-bold text-[#1a2420]">Assessments</h2>
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#416918]/10 text-xs font-black text-[#416918]">{{ recentAssessments.length }}</span>
            </div>
            <div class="space-y-2.5 px-4 py-4">
                <article v-for="item in recentAssessments" :key="item.id" class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                    <p class="text-sm font-bold text-[#191c1c]">PHP {{ Number(item.totalAmountDue || 0).toFixed(2) }}</p>
                    <p class="mt-1 text-[0.68rem] font-black uppercase tracking-[0.14em] text-[#7a8781]">{{ item.statusLabel }}</p>
                    <p class="mt-1.5 text-xs text-[#5f6c67]">
                        <span v-if="item.latestPayment">Latest payment: PHP {{ Number(item.latestPayment.amountPaid || 0).toFixed(2) }}</span>
                        <span v-else>No payment yet</span>
                    </p>
                </article>
                <p v-if="!recentAssessments.length" class="text-xs text-[#6a7872]">No assessments recorded yet.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-[20px] border border-[#dbe2de] bg-white shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <div class="flex items-center justify-between border-b border-[#e4ebe7] bg-[#f4f7f5] px-4 py-3">
                <h2 class="text-base font-bold text-[#1a2420]">Ledger History</h2>
                <span class="text-[#7a8781]">&bull;</span>
            </div>
            <div class="space-y-2.5 px-4 py-4">
                <article v-for="item in recentLedgers" :key="item.id" class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                    <p class="text-sm font-bold text-[#191c1c]">{{ item.year }}</p>
                    <p class="mt-1 text-[0.68rem] font-black uppercase tracking-[0.14em] text-[#7a8781]">{{ item.paymentStatus }}</p>
                    <p class="mt-1.5 text-xs text-[#5f6c67]">Paid: PHP {{ Number(item.amountPaid || 0).toFixed(2) }}</p>
                </article>
                <p v-if="!recentLedgers.length" class="text-xs text-[#6a7872]">No ledger entries yet.</p>
            </div>
        </section>
    </section>
</template>
