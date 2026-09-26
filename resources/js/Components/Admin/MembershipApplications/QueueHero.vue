<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    pageTitle: { type: String, required: true },
    createUrl: { type: String, required: true },
});
</script>

<template>
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15 sm:px-6">
        <!-- Ambient decorative shapes -->
        <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
        <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
        <div class="pointer-events-none absolute right-32 top-2 h-24 w-24 rounded-full bg-white/[0.02]"></div>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4 sm:gap-5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] shadow-inner backdrop-blur-sm">
                    <svg viewBox="0 0 24 24" class="h-6 w-6 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                        <rect x="9" y="3" width="6" height="4" rx="2" />
                        <path d="m9 14 2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/[0.1] px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-[#7ddfb8]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#7ddfb8] animate-pulse"></span>
                            Farmer Management
                        </span>
                        <span class="text-[0.65rem] text-white/50">/</span>
                        <span class="text-[0.65rem] font-medium text-white/70">Application Queue</span>
                    </div>
                    <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">{{ pageTitle }}</h1>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <Link
                    :href="createUrl"
                    class="group inline-flex h-9 items-center gap-2 rounded-xl bg-white px-4 text-xs font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                >
                    <svg viewBox="0 0 20 20" class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90" fill="currentColor">
                        <path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z" />
                    </svg>
                    <span>New Application</span>
                </Link>
            </div>
        </div>
    </section>
</template>
