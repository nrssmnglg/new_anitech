<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    activeSection: { type: String, required: true },
    urls: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="flex items-center gap-2 border-b border-[#dde4de] pb-1">
        <Link
            :href="urls.queue"
            class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-xs font-bold transition-all duration-200"
            :class="activeSection === 'queue'
                ? 'bg-[#014d3c] text-white shadow-xs'
                : 'text-[#64748b] hover:bg-[#f1f5f3] hover:text-[#0f172a]'"
        >
            <span>Claim Queue</span>
            <span
                v-if="summary?.queueCount !== undefined"
                class="rounded-full px-1.5 py-0.2 text-[0.62rem] font-bold"
                :class="activeSection === 'queue' ? 'bg-white/20 text-white' : 'bg-[#e2e8f0] text-[#475569]'"
            >
                {{ summary.queueCount }}
            </span>
        </Link>
        <Link
            :href="urls.records"
            class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-xs font-bold transition-all duration-200"
            :class="activeSection === 'records'
                ? 'bg-[#014d3c] text-white shadow-xs'
                : 'text-[#64748b] hover:bg-[#f1f5f3] hover:text-[#0f172a]'"
        >
            <span>Mortuary Records</span>
            <span
                v-if="summary?.recordsCount !== undefined"
                class="rounded-full px-1.5 py-0.2 text-[0.62rem] font-bold"
                :class="activeSection === 'records' ? 'bg-white/20 text-white' : 'bg-[#e2e8f0] text-[#475569]'"
            >
                {{ summary.recordsCount }}
            </span>
        </Link>
    </div>
</template>
