<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    farmer: { type: Object, required: true },
    showUrl: { type: String, required: true },
    indexUrl: { type: String, default: '' },
});

function initials(name) {
    return String(name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || '?';
}
</script>

<template>
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
        <!-- Ambient shapes -->
        <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
        <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/[0.15] text-base font-bold text-white shadow-sm backdrop-blur-sm">
                    {{ initials(farmer.fullName) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Edit Profile</p>
                        <span class="rounded-md bg-white/10 px-2 py-0.5 font-mono text-[0.62rem] font-semibold text-white/90">
                            {{ farmer.farmerCode }}
                        </span>
                    </div>
                    <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em] text-white sm:text-2xl">{{ farmer.fullName }}</h1>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    :href="showUrl"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/[0.18] bg-white/[0.08] px-3.5 text-xs font-semibold text-white shadow-sm backdrop-blur-sm transition-all duration-200 hover:bg-white/15 active:scale-[0.97]"
                >
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#7ddfb8]" fill="currentColor">
                        <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                    </svg>
                    View Record
                </Link>
                <Link
                    v-if="indexUrl"
                    :href="indexUrl"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-xs font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                >
                    Registry List
                </Link>
            </div>
        </div>
    </section>
</template>
