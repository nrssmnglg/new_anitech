<script setup>
defineProps({
    farmer: { type: Object, required: true },
    summary: { type: Object, required: true },
});
</script>

<template>
    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Membership</p>
            <p class="mt-3 text-2xl font-black text-[#003629]">{{ farmer.membershipStatusLabel || 'Not set' }}</p>
        </article>
        <article class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Collections</p>
            <p class="mt-3 text-2xl font-black text-[#191c1c]">PHP {{ Number(summary.totalPaid || 0).toFixed(2) }}</p>
        </article>
        <article class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Current Ledger</p>
            <p class="mt-3 text-2xl font-black text-[#416918]">{{ summary.currentYearLedger?.paymentStatus || 'No record' }}</p>
        </article>
        <article class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Member Type</p>
            <p class="mt-3 text-2xl font-black text-[#191c1c]">{{ farmer.memberType?.code || 'N/A' }}</p>
            <p class="mt-2 text-sm text-[#6a7872]">{{ farmer.memberType?.name || 'Not set' }}</p>
        </article>
    </section>
</template>
