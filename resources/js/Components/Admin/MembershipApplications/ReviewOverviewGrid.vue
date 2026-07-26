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
    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.64rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Farmer</p>
            <p class="mt-2.5 text-[1.05rem] font-black leading-tight text-[#191c1c]">{{ farmer.fullName }}</p>
            <p class="mt-1.5 text-[0.9rem] text-[#6b7873]">{{ farmer.farmerCode }}</p>
        </article>
        <article class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.64rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Checklist</p>
            <p class="mt-2.5 text-[2rem] font-black leading-none text-[#191c1c]">{{ flow.verifiedCount }}/{{ flow.requiredCount }}</p>
            <p class="mt-1.5 text-[0.9rem] text-[#6b7873]">Required documents cleared</p>
        </article>
        <article class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.64rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Payment</p>
            <p class="mt-2.5 text-[1.05rem] font-black leading-tight text-[#191c1c]">{{ paymentStatusLabel }}</p>
            <p class="mt-1.5 text-[0.9rem] text-[#6b7873]">PHP {{ Number(assessment.totalAmountDue || 0).toFixed(2) }}</p>
        </article>
        <article class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.64rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Member Type</p>
            <p class="mt-2.5 text-[1.05rem] font-black text-[#191c1c]">{{ farmer.memberType?.code || 'N/A' }}</p>
            <p class="mt-1.5 text-[0.9rem] text-[#6b7873]">{{ farmer.memberType?.name || 'Not set' }}</p>
        </article>
    </section>
</template>
