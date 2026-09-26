<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    createUrl: { type: String, required: true },
    summary: { type: Object, default: () => ({ total: 0, active: 0, inactive: 0, deceased: 0 }) },
    currentStatus: { type: String, default: '' },
});

defineEmits(['export', 'filter-status']);
</script>

<template>
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
        <!-- Decorative ambient circles -->
        <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
        <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
        <div class="pointer-events-none absolute right-24 top-4 h-20 w-20 rounded-full bg-white/[0.02]"></div>

        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-4 sm:gap-5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                    <svg viewBox="0 0 24 24" class="h-6 w-6 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M20 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>
                <div>
                    <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Farmer Management</p>
                    <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em] sm:text-2xl">Farmer Registry</h1>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Summary pills with direct filter capability -->
                <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                    <button
                        type="button"
                        class="px-3.5 py-2 text-center transition hover:bg-white/[0.08]"
                        :class="{ 'bg-white/[0.15] ring-1 ring-inset ring-white/30': !currentStatus }"
                        title="Show all farmers"
                        @click="$emit('filter-status', '')"
                    >
                        <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/60">Total</p>
                        <p class="mt-0.5 text-base font-bold leading-none sm:text-lg">{{ summary?.total ?? 0 }}</p>
                    </button>
                    <button
                        type="button"
                        class="border-x border-white/[0.08] px-3.5 py-2 text-center transition hover:bg-white/[0.08]"
                        :class="{ 'bg-white/[0.15] ring-1 ring-inset ring-white/30': currentStatus === 'active' }"
                        title="Filter by active status"
                        @click="$emit('filter-status', 'active')"
                    >
                        <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-[#7ddfb8]/90">Active</p>
                        <p class="mt-0.5 text-base font-bold leading-none text-[#7ddfb8] sm:text-lg">{{ summary?.active ?? 0 }}</p>
                    </button>
                    <button
                        type="button"
                        class="border-r border-white/[0.08] px-3.5 py-2 text-center transition hover:bg-white/[0.08]"
                        :class="{ 'bg-white/[0.15] ring-1 ring-inset ring-white/30': currentStatus === 'inactive' }"
                        title="Filter by inactive status"
                        @click="$emit('filter-status', 'inactive')"
                    >
                        <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/60">Inactive</p>
                        <p class="mt-0.5 text-base font-bold leading-none text-[#fca5a5] sm:text-lg">{{ summary?.inactive ?? 0 }}</p>
                    </button>
                    <button
                        type="button"
                        class="px-3.5 py-2 text-center transition hover:bg-white/[0.08]"
                        :class="{ 'bg-white/[0.15] ring-1 ring-inset ring-white/30': currentStatus === 'deceased' }"
                        title="Filter by deceased status"
                        @click="$emit('filter-status', 'deceased')"
                    >
                        <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/60">Deceased</p>
                        <p class="mt-0.5 text-base font-bold leading-none text-white/80 sm:text-lg">{{ summary?.deceased ?? 0 }}</p>
                    </button>
                </div>

                <!-- Export and Action buttons -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/[0.18] bg-white/[0.08] px-3 text-[0.7rem] font-semibold text-white shadow-sm backdrop-blur-sm transition-all duration-200 hover:bg-white/15 active:scale-[0.97]"
                        @click="$emit('export', 'pdf')"
                    >
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#7ddfb8]" fill="currentColor">
                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                        </svg>
                        PDF
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/[0.18] bg-white/[0.08] px-3 text-[0.7rem] font-semibold text-white shadow-sm backdrop-blur-sm transition-all duration-200 hover:bg-white/15 active:scale-[0.97]"
                        @click="$emit('export', 'xlsx')"
                    >
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#7ddfb8]" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm4.75 6.75a.75.75 0 0 1 1.5 0v2.546l.943-1.048a.75.75 0 0 1 1.114 1.004l-2.25 2.5a.75.75 0 0 1-1.114 0l-2.25-2.5a.75.75 0 1 1 1.114-1.004l.943 1.048V8.75Z" clip-rule="evenodd" />
                        </svg>
                        Excel
                    </button>
                    <Link
                        :href="createUrl"
                        class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                    >
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-90" fill="currentColor">
                            <path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z" />
                        </svg>
                        New Membership
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
