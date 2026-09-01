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
    <section class="rounded-lg border border-[#dbe2de] bg-white px-4 py-3">
        <div class="relative mx-auto flex max-w-3xl justify-between">
            <div class="absolute left-0 right-0 top-3.5 h-0.5 rounded-full bg-[#dfe6e1]"></div>
            <div class="absolute left-0 top-3.5 h-0.5 rounded-full bg-[#003629]" :style="{ width: `${Math.max(0, (flow.step - 1) / 3 * 100)}%` }"></div>
            <div v-for="(step, index) in steps" :key="step.number" class="relative z-10 flex flex-col items-center gap-1.5">
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-full text-[0.65rem] font-semibold ring-2 ring-white"
                    :class="index + 1 <= flow.step ? 'bg-[#003629] text-white' : 'bg-[#eef1ef] text-[#6b7873]'"
                >{{ index + 1 }}</div>
                <span class="text-[0.68rem]" :class="index + 1 === flow.step ? 'font-semibold text-[#003629]' : 'font-medium text-[#6b7873]'">{{ step.label }}</span>
            </div>
        </div>
    </section>
</template>
