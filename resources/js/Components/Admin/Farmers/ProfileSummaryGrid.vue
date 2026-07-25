<script setup>
defineProps({
    farmer: { type: Object, required: true },
    summary: { type: Object, required: true },
});
</script>

<template>
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Registered</p>
            <p class="mt-2 text-lg font-black text-[#003629]">{{ farmer.registeredAt || 'Not recorded' }}</p>
        </article>
        <article class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Collections</p>
            <p class="mt-2 text-xl font-black text-[#191c1c]">PHP {{ Number(summary.totalPaid || 0).toFixed(2) }}</p>
        </article>
        <article class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Current Ledger</p>
            <p class="mt-2 text-xl font-black text-[#416918]">{{ summary.currentYearLedger?.paymentStatus || 'No record' }}</p>
            <p class="mt-1 text-xs text-[#6a7872]">{{ summary.currentYearLedger?.year ? `Ledger year ${summary.currentYearLedger.year}` : 'No active yearly ledger' }}</p>
        </article>
        <article class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Record Source</p>
            <p class="mt-2 text-xl font-black text-[#191c1c]">{{ farmer.recordOrigin || 'Not set' }}</p>
            <p class="mt-1 text-xs text-[#6a7872]">{{ farmer.barangay?.name || 'No barangay assigned' }}</p>
        </article>
    </section>
</template>
