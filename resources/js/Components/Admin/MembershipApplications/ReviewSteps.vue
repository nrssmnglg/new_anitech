<script setup>
defineProps({
    flow: { type: Object, required: true },
});

const steps = [
    { number: 1, label: 'Form Intake', subtitle: 'Application captured' },
    { number: 2, label: 'Document Verification', subtitle: 'Checklist validation' },
    { number: 3, label: 'Payment Assessment', subtitle: 'Fee settlement' },
    { number: 4, label: 'Membership Enrolled', subtitle: 'Final approval' },
];
</script>

<template>
    <section class="rounded-2xl border border-[#dde4de] bg-white px-4 py-3.5 shadow-xs sm:px-6">
        <div class="relative mx-auto max-w-4xl">
            <!-- Background track -->
            <div class="absolute left-6 right-6 top-4 hidden h-0.5 rounded-full bg-slate-200 sm:block"></div>
            <!-- Progress fill line -->
            <div
                class="absolute left-6 top-4 hidden h-0.5 rounded-full bg-[#003629] transition-all duration-500 sm:block"
                :style="{ width: `${Math.min(100, Math.max(0, (flow.step - 1) / 3 * 100))}%` }"
            ></div>

            <div class="relative z-10 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-2">
                <div
                    v-for="step in steps"
                    :key="step.number"
                    class="flex items-center gap-2.5 sm:flex-col sm:text-center"
                >
                    <!-- Step circle -->
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-all duration-200"
                        :class="[
                            step.number < flow.step
                                ? 'bg-[#003629] text-white shadow-xs'
                                : step.number === flow.step
                                    ? 'bg-[#003629] text-white ring-4 ring-[#003629]/15 shadow-sm'
                                    : 'border border-slate-200 bg-slate-100 text-slate-400'
                        ]"
                    >
                        <!-- Checkmark for completed steps -->
                        <svg v-if="step.number < flow.step" viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                        </svg>
                        <span v-else>{{ step.number }}</span>
                    </div>

                    <!-- Step text -->
                    <div class="min-w-0">
                        <p
                            class="truncate text-xs"
                            :class="step.number === flow.step ? 'font-bold text-[#003629]' : step.number < flow.step ? 'font-semibold text-slate-700' : 'font-medium text-slate-400'"
                        >
                            {{ step.label }}
                        </p>
                        <p class="hidden text-[0.65rem] text-slate-400 sm:block">
                            {{ step.subtitle }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
