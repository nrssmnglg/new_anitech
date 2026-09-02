<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pageTitle: { type: String, required: true },
    pageSubtitle: { type: String, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
});

defineEmits(['export']);

const completionRate = computed(() => {
    const queue = Number(props.summary.queueCount || 0);
    const records = Number(props.summary.recordsCount || 0);
    const total = queue + records;

    if (!total) return 0;

    return Math.round((records / total) * 100);
});
</script>

<template>
    <section class="relative overflow-hidden rounded-xl bg-[#003629] px-4 py-3.5 text-white sm:px-5">
        <div class="absolute inset-0 bg-[radial-gradient(at_0%_0%,_#1b4d3e_0%,_transparent_50%),radial-gradient(at_100%_0%,_#376757_0%,_transparent_48%),radial-gradient(at_100%_100%,_#16332c_0%,_transparent_50%),radial-gradient(at_0%_100%,_#003629_0%,_transparent_48%)]"></div>
        <div class="absolute -right-14 top-[-52px] h-60 w-60 rounded-full bg-[#a5d577]/10 blur-[90px]"></div>
        <div class="absolute -bottom-24 left-[18%] h-72 w-72 rounded-full bg-white/10 blur-[110px]"></div>

        <div class="relative z-10 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="shrink-0">
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/65">Mortuary monitoring</p>
                    <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">{{ pageTitle }}</h1>
                </div>

                <div class="grid grid-cols-3 overflow-hidden rounded-lg border border-white/15 bg-white/[0.08]">
                    <div class="px-3 py-2 text-center">
                        <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-white/60">Pending</p>
                        <p class="mt-0.5 text-base font-semibold">{{ summary.queueCount }}</p>
                    </div>
                    <div class="border-x border-white/10 px-3 py-2 text-center">
                        <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-white/60">Records</p>
                        <p class="mt-0.5 text-base font-semibold">{{ summary.recordsCount }}</p>
                    </div>
                    <div class="px-3 py-2 text-center">
                        <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-white/60">Complete</p>
                        <p class="mt-0.5 text-base font-semibold text-[#c0f190]">{{ completionRate }}%</p>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 gap-2">
                <button type="button" class="inline-flex h-8 items-center justify-center rounded-md bg-white px-3 text-[0.68rem] font-semibold text-[#003629] transition hover:bg-[#f1f7f3]" @click="$emit('export')">Export</button>
                <Link :href="urls.queue" class="inline-flex h-8 items-center justify-center rounded-md border border-white/25 px-3 text-[0.68rem] font-semibold text-white transition hover:bg-white/10">Claim Queue</Link>
            </div>
        </div>
    </section>
</template>
