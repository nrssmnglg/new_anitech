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
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Farmer</p>
            <p class="mt-3 text-2xl font-black text-[#191c1c]">{{ farmer.fullName }}</p>
            <p class="mt-2 text-sm text-[#6b7873]">{{ farmer.farmerCode }}</p>
        </article>
        <article class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Checklist</p>
            <p class="mt-3 text-4xl font-black text-[#191c1c]">{{ flow.verifiedCount }}/{{ flow.requiredCount }}</p>
            <p class="mt-2 text-sm text-[#6b7873]">Required documents cleared</p>
        </article>
        <article class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Payment</p>
            <p class="mt-3 text-2xl font-black text-[#191c1c]">{{ paymentStatusLabel }}</p>
            <p class="mt-2 text-sm text-[#6b7873]">PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}</p>
        </article>
        <article class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Member Type</p>
            <p class="mt-3 text-2xl font-black text-[#191c1c]">{{ farmer.memberType?.code || 'N/A' }}</p>
            <p class="mt-2 text-sm text-[#6b7873]">{{ farmer.memberType?.name || 'Not set' }}</p>
        </article>
    </section>
</template>
