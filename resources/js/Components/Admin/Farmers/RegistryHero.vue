<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ createUrl: { type: String, required: true } });
defineEmits(['export']);
</script>

<template>
    <section class="rounded-[16px] border border-[#cfe2d8] bg-[linear-gradient(110deg,_#f0fff8_0%,_#f9fcfb_58%,_#eaf8f2_100%)] px-5 py-6 shadow-[0_2px_5px_rgba(15,23,42,0.03)] sm:px-7 sm:py-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[0.74rem] font-black uppercase tracking-[0.08em] text-[#263d35]">Farmer Management</p>
                <h1 class="mt-2 text-[2.15rem] font-black leading-none tracking-[-0.045em] text-[#003629] sm:text-[2.65rem]">Farmer Registry</h1>
            </div>
            <div class="flex flex-wrap gap-3">
                <Link :href="createUrl" class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[#003c2e] px-4 text-xs font-extrabold text-white shadow-[0_4px_8px_rgba(0,54,41,0.18)] transition hover:bg-[#0d4b3a]">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M3.5 19v-1.5A4.5 4.5 0 0 1 8 13h2a4.5 4.5 0 0 1 4.5 4.5V19M18 7v6M15 10h6"/></svg>
                    New Membership
                </Link>
                <button type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-[#c5ceca] bg-white px-4 text-xs font-extrabold text-[#111b18] transition hover:bg-[#f7faf8]" @click="$emit('export', 'pdf')">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#003629]" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l4 4v16H6z"/><path d="M15 2v5h5M8.5 15h7M8.5 18h5"/></svg>Export PDF
                </button>
                <button type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-[#c5ceca] bg-white px-4 text-xs font-extrabold text-[#111b18] transition hover:bg-[#f7faf8]" @click="$emit('export', 'xlsx')">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#003629]" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="16" height="16" rx="1"/><path d="M4 10h16M10 4v16M4 15h16"/></svg>Export Excel
                </button>
            </div>
        </div>
    </section>
</template>
