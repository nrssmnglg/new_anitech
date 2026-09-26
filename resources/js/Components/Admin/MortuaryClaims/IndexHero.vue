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
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl font-bold tracking-tight text-[#0f172a] sm:text-2xl">{{ pageTitle }}</h1>
                <!-- Compact stats pill row -->
                <div class="flex items-center gap-1.5 rounded-lg border border-[#dde4de] bg-[#f8fafc] p-1 text-xs">
                    <span class="rounded-md bg-white px-2 py-0.5 font-bold text-[#64748b] shadow-xs">
                        {{ summary.queueCount }} <span class="font-normal text-[#94a3b8]">queue</span>
                    </span>
                    <span class="rounded-md bg-white px-2 py-0.5 font-bold text-[#014d3c] shadow-xs">
                        {{ summary.recordsCount }} <span class="font-normal text-[#94a3b8]">records</span>
                    </span>
                    <span class="rounded-md bg-[#e6f5ec] px-2 py-0.5 font-bold text-[#0f6b45]">
                        {{ completionRate }}% <span class="font-normal text-[#15803d]">released</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-[#dde4de] bg-white px-3.5 text-xs font-semibold text-[#64748b] shadow-xs transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                @click="$emit('export')"
            >
                <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#64748b]" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm4.75 6.75a.75.75 0 0 1 1.5 0v3.44l1.22-1.22a.75.75 0 1 1 1.06 1.06l-2.5 2.5a.75.75 0 0 1-1.06 0l-2.5-2.5a.75.75 0 1 1 1.06-1.06l1.22 1.22V8.75Z" clip-rule="evenodd" />
                </svg>
                <span>Export Report</span>
            </button>
        </div>
    </div>
</template>
