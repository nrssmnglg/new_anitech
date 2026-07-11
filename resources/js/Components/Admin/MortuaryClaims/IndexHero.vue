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
    <section class="relative overflow-hidden rounded-[28px] bg-[#002117] px-6 py-6 text-white shadow-[0_22px_50px_rgba(15,23,42,0.14)] sm:px-7 lg:px-8">
        <div class="absolute inset-0 bg-[radial-gradient(at_0%_0%,_#1b4d3e_0%,_transparent_50%),radial-gradient(at_100%_0%,_#376757_0%,_transparent_48%),radial-gradient(at_100%_100%,_#16332c_0%,_transparent_50%),radial-gradient(at_0%_100%,_#003629_0%,_transparent_48%)]"></div>
        <div class="absolute -right-14 top-[-52px] h-60 w-60 rounded-full bg-[#a5d577]/10 blur-[90px]"></div>
        <div class="absolute -bottom-24 left-[18%] h-72 w-72 rounded-full bg-white/10 blur-[110px]"></div>

        <div class="relative z-10 space-y-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-md">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#c0f190] shadow-[0_0_16px_rgba(192,241,144,0.8)]"></span>
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.28em] text-white/85">Claim Monitoring</span>
                    </div>

                    <h1 class="mt-4 text-4xl font-black tracking-[-0.045em] sm:text-[2.85rem]">{{ pageTitle }}</h1>
                    <p class="mt-2 max-w-2xl text-[1rem] leading-8 text-[#c7ddd4]">{{ pageSubtitle }}</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="button" class="inline-flex items-center justify-center rounded-full bg-[#c0f190] px-5 py-3 text-sm font-extrabold text-[#2a5000] transition hover:scale-[1.02]" @click="$emit('export')">
                        Export Summary
                    </button>
                    <Link :href="urls.queue" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm font-extrabold text-white backdrop-blur transition hover:bg-white/15">
                        Claim Queue
                    </Link>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-[22px] border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Claim Queue</p>
                    <div class="mt-2.5 flex items-end gap-3">
                        <span class="text-3xl font-black tracking-[-0.04em]">{{ summary.queueCount }}</span>
                        <span class="pb-1 text-xs font-black uppercase tracking-[0.18em] text-[#c0f190]">Pending</span>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                        <div class="h-full w-[42%] rounded-full bg-[#c0f190]"></div>
                    </div>
                </div>

                <div class="rounded-[22px] border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Mortuary Records</p>
                    <div class="mt-2.5 flex items-end gap-3">
                        <span class="text-3xl font-black tracking-[-0.04em]">{{ summary.recordsCount }}</span>
                        <span class="pb-1 text-xs font-black uppercase tracking-[0.18em] text-white/45">Archived</span>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                        <div class="h-full w-[78%] rounded-full bg-[#baeed9]"></div>
                    </div>
                </div>

                <div class="rounded-[22px] border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Completion Rate</p>
                    <div class="mt-2.5 flex items-end gap-3">
                        <span class="text-3xl font-black tracking-[-0.04em]">{{ completionRate }}%</span>
                        <span class="pb-1 text-xs font-black uppercase tracking-[0.18em] text-[#c0f190]">Processed</span>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                        <div class="h-full rounded-full bg-[#c0f190]" :style="{ width: `${completionRate}%` }"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
