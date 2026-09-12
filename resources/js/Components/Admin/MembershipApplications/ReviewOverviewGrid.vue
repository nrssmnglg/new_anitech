<script setup>
defineProps({
    application: { type: Object, required: true },
    farmer: { type: Object, required: true },
    flow: { type: Object, required: true },
    assessment: { type: Object, required: true },
    paymentStatusLabel: { type: String, required: true },
});
</script>

<template>
    <section class="grid grid-cols-2 gap-2 xl:grid-cols-4">
        <article class="rounded-lg border border-[#dbe2de] bg-white p-3">
            <p class="text-[0.64rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Farmer</p>
            <p class="mt-1.5 text-sm font-semibold leading-tight text-[#191c1c]">{{ farmer.fullName }}</p>
            <p class="mt-1 text-xs text-[#6b7873]">{{ farmer.farmerCode }}</p>
        </article>
        <article class="rounded-lg border border-[#dbe2de] bg-white p-3">
            <p class="text-[0.64rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Checklist</p>
            <p class="mt-1.5 text-lg font-semibold leading-none text-[#191c1c]">{{ flow.isHistorical ? 'Not required' : `${flow.verifiedCount}/${flow.requiredCount}` }}</p>
            <p class="mt-1 text-xs text-[#6b7873]">{{ flow.isHistorical ? 'Historical record' : 'Required documents cleared' }}</p>
        </article>
        <article class="rounded-lg border border-[#dbe2de] bg-white p-3">
            <p class="text-[0.64rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Payment</p>
            <p class="mt-1.5 text-sm font-semibold leading-tight text-[#191c1c]">{{ paymentStatusLabel }}</p>
            <p class="mt-1 text-xs text-[#6b7873]">PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}</p>
        </article>
        <article class="rounded-lg border border-[#dbe2de] bg-white p-3">
            <p class="text-[0.64rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Member Type</p>
            <p class="mt-1.5 text-sm font-semibold text-[#191c1c]">{{ farmer.memberType?.code || 'N/A' }}</p>
            <p class="mt-1 text-xs text-[#6b7873]">{{ farmer.memberType?.name || 'Not set' }}</p>
        </article>
    </section>
</template>
