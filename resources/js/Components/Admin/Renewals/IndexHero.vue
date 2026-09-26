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
    <div class="flex flex-col gap-3 px-1 sm:flex-row sm:items-center sm:justify-between">
        <!-- Title and Category -->
        <div>
            <p class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-slate-500">Renewal Management</p>
            <h1 class="text-xl font-bold tracking-tight text-[#0f172a] sm:text-2xl">{{ pageTitle }}</h1>
        </div>

        <!-- Summary Metric Chips & Actions -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Stats Badges -->
            <div class="inline-flex items-center gap-1.5 rounded-lg border border-[#dde4de] bg-white px-3 py-1.5 text-xs shadow-xs">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-slate-400">Pending</span>
                <span class="font-bold text-[#0f172a]">{{ summary.queueCount }}</span>
            </div>

            <div class="inline-flex items-center gap-1.5 rounded-lg border border-[#dde4de] bg-white px-3 py-1.5 text-xs shadow-xs">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-slate-400">Records</span>
                <span class="font-bold text-[#0f172a]">{{ summary.recordsCount }}</span>
            </div>

            <div class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50/70 px-3 py-1.5 text-xs text-emerald-800 shadow-xs">
                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-emerald-600">Completion</span>
                <span class="font-bold font-mono">{{ collectionRate }}%</span>
            </div>

            <!-- Action Buttons -->
            <button
                type="button"
                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-[#dde4de] bg-white px-3.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 active:scale-95"
                @click="$emit('export')"
            >
                <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-500" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm4.75 6.75a.75.75 0 0 1 1.5 0v3.44l1.22-1.22a.75.75 0 1 1 1.06 1.06l-2.5 2.5a.75.75 0 0 1-1.06 0l-2.5-2.5a.75.75 0 1 1 1.06-1.06l1.22 1.22V8.75Z" clip-rule="evenodd" />
                </svg>
                <span>Export Summary</span>
            </button>

            <Link
                :href="urls.farmers"
                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#003629] px-4 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a] active:scale-95"
            >
                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                    <path d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM6 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM1.49 15.326a.78.78 0 0 1-.358-.442 3 3 0 0 1 4.308-3.516 6.484 6.484 0 0 0-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 0 1-2.07-.655ZM16.44 15.98a4.97 4.97 0 0 0 2.07-.654.78.78 0 0 0 .357-.442 3 3 0 0 0-4.308-3.517 6.484 6.484 0 0 1 1.907 3.96 2.32 2.32 0 0 1-.026.654ZM18 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM5.304 16.19a.844.844 0 0 1-.277-.71 5 5 0 0 1 9.946 0 .843.843 0 0 1-.277.71A6.975 6.975 0 0 1 10 18a6.974 6.974 0 0 1-4.696-1.81Z" />
                </svg>
                <span>Farmer List</span>
            </Link>
        </div>
    </div>
</template>
