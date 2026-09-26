<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    application: { type: Object, required: true },
    farmer: { type: Object, required: true },
    flow: { type: Object, required: true },
    urls: { type: Object, required: true },
});

function statusBadge(value) {
    if (value === 'approved') return 'bg-emerald-500/20 text-[#a3f0cb] border border-emerald-400/30';
    if (value === 'rejected') return 'bg-rose-500/20 text-[#fca5a5] border border-rose-400/30';
    return 'bg-amber-500/20 text-[#fde68a] border border-amber-400/30';
}

function statusDot(value) {
    if (value === 'approved') return 'bg-[#7ddfb8]';
    if (value === 'rejected') return 'bg-[#fca5a5]';
    return 'bg-amber-300 animate-pulse';
}
</script>

<template>
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15 sm:px-6">
        <!-- Ambient decorative shapes -->
        <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
        <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
        <div class="pointer-events-none absolute right-32 top-2 h-24 w-24 rounded-full bg-white/[0.02]"></div>

        <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3.5 sm:gap-4.5">
                <Link
                    :href="urls.index"
                    class="group mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white/80 backdrop-blur-sm transition-all duration-200 hover:bg-white/20 hover:text-white active:scale-95"
                    title="Back to Application Queue"
                >
                    <svg viewBox="0 0 20 20" class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="currentColor">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                    </svg>
                </Link>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-[#7ddfb8]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#7ddfb8] animate-pulse"></span>
                            Queue Review
                        </span>
                        <span class="text-xs text-white/40">/</span>
                        <span class="font-mono text-xs font-bold text-white/90">{{ application.applicationNo }}</span>
                    </div>

                    <h1 class="mt-1 text-xl font-bold tracking-tight text-white sm:text-2xl">
                        {{ farmer.fullName }}
                    </h1>

                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-white/70">
                        <span class="font-mono text-[0.7rem] text-[#7ddfb8]">{{ farmer.farmerCode }}</span>
                        <span>•</span>
                        <span class="rounded bg-white/10 px-1.5 py-0.5 text-[0.62rem] font-medium text-white/90">
                            {{ application.sourceLabel }}
                        </span>
                        <span>•</span>
                        <span class="text-[0.68rem] text-white/70">
                            Submitted {{ application.submittedAt || application.createdAt || 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 sm:self-center">
                <!-- Status Badge -->
                <span
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wider"
                    :class="statusBadge(application.status.value)"
                >
                    <span class="h-2 w-2 rounded-full" :class="statusDot(application.status.value)"></span>
                    {{ application.status.label }}
                </span>

                <!-- Step Counter Pill -->
                <div class="inline-flex items-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/90 backdrop-blur-sm">
                    <span class="text-[0.65rem] uppercase tracking-wider text-white/60">Stage</span>
                    <span class="font-bold text-[#7ddfb8]">Step {{ flow.step }}</span>
                    <span class="text-white/50">of 4</span>
                </div>

                <!-- Back link button -->
                <Link
                    :href="urls.index"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-white px-3.5 text-xs font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-95"
                >
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                    </svg>
                    <span>Back to Queue</span>
                </Link>
            </div>
        </div>
    </section>
</template>
