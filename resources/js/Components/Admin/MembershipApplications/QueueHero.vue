<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: { type: String, required: true },
    createUrl: { type: String, required: true },
});
</script>

<template>
    <section class="relative overflow-hidden rounded-[24px] bg-[#1b4d3e] px-5 py-6 text-white shadow-[0_14px_32px_rgba(15,23,42,0.07)] lg:px-7">
        <div class="pointer-events-none absolute inset-0 opacity-10">
            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
                <path d="M0 100 C 20 0 55 0 100 100 Z" fill="currentColor"></path>
            </svg>
        </div>

        <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-2 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-[#c0f190]"></span>
                    <span class="text-[0.62rem] font-black uppercase tracking-[0.22em] text-[#b9dccc]">System Operational</span>
                </div>
                <h1 class="text-[2.65rem] font-black leading-[0.95] tracking-[-0.05em] sm:text-[3.5rem]">{{ pageTitle }}</h1>
            </div>

            <Link
                :href="createUrl"
                class="inline-flex items-center justify-center gap-2 rounded-[1.15rem] bg-[#c0f190] px-5 py-3 text-[0.95rem] font-extrabold text-[#0e2000] shadow-[0_14px_24px_rgba(14,32,0,0.16)] transition hover:-translate-y-0.5 hover:bg-[#addd7d]"
            >
                <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>
                New Application
            </Link>
        </div>
    </section>
</template>
