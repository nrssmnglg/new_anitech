<script setup>
defineProps({
    flow: { type: Object, required: true },
});

const steps = [
    { number: '01', label: 'Form Intake' },
    { number: '02', label: 'Documents' },
    { number: '03', label: 'Payment' },
    { number: '04', label: 'Approved' },
];
</script>

<template>
    <section class="grid gap-4 xl:grid-cols-4">
        <article
            v-for="(step, index) in steps"
            :key="step.number"
            class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.05)]"
            :class="index + 1 < flow.step ? 'bg-[#eef7e3]' : (index + 1 === flow.step ? 'ring-2 ring-[#1b4d3e]/20' : 'opacity-85')"
        >
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">{{ step.number }}</p>
            <p class="mt-3 text-2xl font-black text-[#191c1c]">{{ step.label }}</p>
        </article>
    </section>
</template>
