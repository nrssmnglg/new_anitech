<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pageTitle: { type: String, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
});

defineEmits(['export']);

const collectionRate = computed(() => {
    const queue = Number(props.summary.queueCount || 0);
    const records = Number(props.summary.recordsCount || 0);
    const total = queue + records;

    if (!total) return 0;

    return Math.round((records / total) * 100);
});
</script>

<template>
    <section class="relative overflow-hidden rounded-[20px] bg-[#002117] px-5 py-4 text-white shadow-[0_12px_30px_rgba(15,23,42,0.1)] sm:px-6">
        <div class="absolute inset-0 bg-[radial-gradient(at_0%_0%,_#1b4d3e_0%,_transparent_50%),radial-gradient(at_100%_0%,_#376757_0%,_transparent_48%),radial-gradient(at_100%_100%,_#16332c_0%,_transparent_50%),radial-gradient(at_0%_100%,_#003629_0%,_transparent_48%)]"></div>

        <div class="relative z-10 space-y-3">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 backdrop-blur-md">
                        <span class="h-2 w-2 rounded-full bg-[#c0f190]"></span>
                        <span class="text-[0.62rem] font-black uppercase tracking-[0.22em] text-white/80">System Operational</span>
                    </div>

                    <h1 class="mt-2 text-[1.75rem] font-black tracking-[-0.04em] sm:text-[2rem]">{{ pageTitle }}</h1>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="inline-flex items-center justify-center rounded-full bg-[#c0f190] px-4 py-2 text-sm font-extrabold text-[#2a5000] transition hover:scale-[1.02]" @click="$emit('export')">
                        Export Summary
                    </button>
                    <Link :href="urls.farmers" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-extrabold text-white backdrop-blur transition hover:bg-white/15">
                        Farmer List
                    </Link>
                </div>
            </div>

            <div class="grid gap-2 sm:grid-cols-3">
                <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                    <div>
                        <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/55">Pending</p>
                        <p class="mt-0.5 text-xs font-bold text-[#c0f190]">In queue</p>
                    </div>
                    <span class="text-[1.65rem] font-black tracking-[-0.04em]">{{ summary.queueCount }}</span>
                </div>

                <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                    <div>
                        <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/55">Settled</p>
                        <p class="mt-0.5 text-xs font-bold text-white/55">Archived</p>
                    </div>
                    <span class="text-[1.65rem] font-black tracking-[-0.04em]">{{ summary.recordsCount }}</span>
                </div>

                <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                    <div>
                        <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/55">Progress</p>
                        <p class="mt-0.5 text-xs font-bold text-[#c0f190]">Completion</p>
                    </div>
                    <span class="text-[1.65rem] font-black tracking-[-0.04em]">{{ collectionRate }}%</span>
                </div>
            </div>
        </div>
    </section>
</template>
