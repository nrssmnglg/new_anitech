<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    activeSection: { type: String, required: true },
    urls: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="flex items-center gap-1 border-b border-[#dde4de]">
        <Link
            :href="urls.queue"
            class="inline-flex items-center gap-2 border-b-2 px-3 py-2.5 text-xs font-bold transition"
            :class="activeSection === 'queue' ? 'border-[#003629] text-[#003629]' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300'"
        >
            <span>Active Queue</span>
            <span
                v-if="summary?.queueCount !== undefined"
                class="rounded-full px-2 py-0.5 text-[0.62rem] font-bold transition"
                :class="activeSection === 'queue' ? 'bg-[#003629] text-white' : 'bg-slate-100 text-slate-600'"
            >
                {{ summary.queueCount }}
            </span>
        </Link>

        <Link
            :href="urls.records"
            class="inline-flex items-center gap-2 border-b-2 px-3 py-2.5 text-xs font-bold transition"
            :class="activeSection === 'records' ? 'border-[#003629] text-[#003629]' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300'"
        >
            <span>Renewal Records</span>
            <span
                v-if="summary?.recordsCount !== undefined"
                class="rounded-full px-2 py-0.5 text-[0.62rem] font-bold transition"
                :class="activeSection === 'records' ? 'bg-[#003629] text-white' : 'bg-slate-100 text-slate-600'"
            >
                {{ summary.recordsCount }}
            </span>
        </Link>
    </div>
</template>
