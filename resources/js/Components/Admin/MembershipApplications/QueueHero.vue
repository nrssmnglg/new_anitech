<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: { type: String, required: true },
    createUrl: { type: String, required: true },
});
</script>

<template>
    <section class="rounded-[10px] bg-[#00513f] px-5 py-4 text-white sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-[#b9dccc]">Farmer Management</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ pageTitle }}</h1>
            </div>

            <Link
                :href="createUrl"
                class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-white px-4 text-xs font-semibold text-[#003e32] transition hover:bg-[#f2f7f5]"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>
                New Application
            </Link>
        </div>
    </section>
</template>
