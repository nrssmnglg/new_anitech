<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    associations: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    urls: {
        type: Object,
        required: true,
    },
    filterOptions: {
        type: Object,
        default: () => ({}),
    },
});

const form = reactive({
    search: props.filters.search ?? '',
    barangay_id: props.filters.barangay_id ?? '',
    status: props.filters.status ?? '',
});

const showFilters = ref(!!(form.search || form.barangay_id || form.status));

const applyFilters = () => {
    router.get(props.urls.index, form, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.barangay_id = '';
    form.status = '';
    applyFilters();
};

const activeFilterCount = () => [form.search, form.barangay_id, form.status].filter(Boolean).length;
</script>

<template>
    <AdminLayout>
        <Head title="Associations" />

        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-20 top-4 h-20 w-20 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Location Management</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Associations</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Summary pills -->
                        <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Total</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.total ?? 0 }}</p>
                            </article>
                            <article class="border-x border-white/[0.08] px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Active</p>
                                <p class="mt-0.5 text-lg font-bold leading-none text-[#7ddfb8]">{{ summary.active ?? 0 }}</p>
                            </article>
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Inactive</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.inactive ?? 0 }}</p>
                            </article>
                        </div>

                        <Link :href="urls.create" class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-90" fill="currentColor"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                            Add Association
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Filter bar -->
            <section class="rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <button
                    type="button"
                    class="flex w-full items-center justify-between px-4 py-3 text-left transition-colors hover:bg-[#f9fbfa]"
                    @click="showFilters = !showFilters"
                >
                    <div class="flex items-center gap-2">
                        <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#64748b]" fill="currentColor"><path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd"/></svg>
                        <span class="text-xs font-semibold text-[#334155]">Filters</span>
                        <span v-if="activeFilterCount()" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#014d3c] px-1.5 text-[0.6rem] font-bold text-white">{{ activeFilterCount() }}</span>
                    </div>
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#94a3b8] transition-transform duration-200" :class="showFilters ? 'rotate-180' : ''" fill="currentColor"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                </button>
                <div v-show="showFilters" class="border-t border-[#edf2ee] px-4 pb-4 pt-3">
                    <div class="grid items-end gap-3 lg:grid-cols-[minmax(0,1.2fr)_220px_200px_auto]">
                        <label class="space-y-1.5">
                            <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Search</span>
                            <div class="relative">
                                <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#94a3b8]" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                                <input
                                    v-model="form.search"
                                    type="text"
                                    placeholder="Search association"
                                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-3 text-xs text-[#12372a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                />
                            </div>
                        </label>

                        <label class="space-y-1.5">
                            <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Barangay</span>
                            <select
                                v-model="form.barangay_id"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#12372a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                                <option value="">All Barangays</option>
                                <option
                                    v-for="barangay in filterOptions.barangays || []"
                                    :key="barangay.value"
                                    :value="barangay.value"
                                >
                                    {{ barangay.label }}
                                </option>
                            </select>
                        </label>

                        <label class="space-y-1.5">
                            <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                            <select
                                v-model="form.status"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#12372a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                                <option value="">All</option>
                                <option
                                    v-for="status in filterOptions.statuses || []"
                                    :key="status.value"
                                    :value="status.value"
                                >
                                    {{ status.label }}
                                </option>
                            </select>
                        </label>

                        <div class="flex items-end gap-2">
                            <button
                                type="button"
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]"
                                @click="clearFilters"
                            >
                                Reset
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97]"
                                @click="applyFilters"
                            >
                                Apply
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Data table -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                    <div>
                        <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Registered Associations</h2>
                        <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ associations.total }} association{{ associations.total === 1 ? '' : 's' }} found</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#edf2ee] text-xs">
                        <thead>
                            <tr class="bg-gradient-to-b from-[#f8faf9] to-[#f3f6f4] text-left text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#7b8b80]">
                                <th class="px-5 py-3">Association</th>
                                <th class="px-5 py-3">Barangay</th>
                                <th class="px-5 py-3">Members</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee]">
                            <tr v-for="association in associations.data" :key="association.id" class="group align-top transition-all duration-150 hover:bg-[#f6faf8]">
                                <td class="px-5 py-3">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[0.65rem] font-bold text-[#0f6b45]">
                                            {{ association.name?.charAt(0)?.toUpperCase() || 'A' }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-[#12372a]">{{ association.name }}</div>
                                            <div class="mt-0.5 font-mono text-[0.6rem] text-[#94a3b8]">{{ association.code || 'No code' }}</div>
                                            <div v-if="association.president_name" class="mt-1 flex items-center gap-1.5 text-[0.6rem] text-[#7b8b80]">
                                                <svg viewBox="0 0 16 16" class="h-3 w-3 shrink-0" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12.74 13c.93 0 1.41-1.14.72-1.77a8.01 8.01 0 0 0-10.92 0c-.69.63-.2 1.77.72 1.77h9.48Z"/></svg>
                                                {{ association.president_name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span v-if="association.barangay?.name" class="text-[#3d4c43]">{{ association.barangay.name }}</span>
                                    <span v-else class="inline-flex items-center gap-1 text-[#94a3b8]">
                                        <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14Zm0-1.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11ZM6.75 5a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5Z"/></svg>
                                        Unassigned
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg px-2 text-[0.62rem] font-bold transition-colors"
                                        :class="(association.farmersCount ?? 0) > 0 ? 'bg-[#eff6ff] text-[#1d4ed8]' : 'bg-[#f1f5f9] text-[#94a3b8]'"
                                    >{{ association.farmersCount ?? 0 }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[0.62rem] font-bold"
                                        :class="association.status?.value === 'active' ? 'bg-[#dcfce7] text-[#15803d]' : 'bg-[#f1f5f9] text-[#64748b]'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="association.status?.value === 'active' ? 'bg-[#22c55e]' : 'bg-[#94a3b8]'"></span>
                                        {{ association.status?.label || 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex justify-end gap-1 opacity-60 transition-opacity duration-200 group-hover:opacity-100">
                                        <Link :href="association.actions.showUrl" aria-label="View association" title="View" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#014d3c] transition-all duration-150 hover:bg-[#e6f5ec]">
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </Link>
                                        <Link :href="association.actions.editUrl" aria-label="Edit association" title="Edit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#475569] transition-all duration-150 hover:bg-[#f1f5f9]">
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="!associations.data.length">
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-xs flex-col items-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0faf5]">
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-[#334155]">No associations found</p>
                                        <p class="mt-1 text-xs text-[#94a3b8]">Try adjusting your filters or add a new association.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="associations.last_page > 1" class="flex flex-col gap-2 border-t border-[#edf2ee] bg-gradient-to-b from-[#fbfcfb] to-[#f8faf9] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.68rem] text-[#6b7280]">
                        Page <span class="font-semibold text-[#334155]">{{ associations.current_page }}</span> of <span class="font-semibold text-[#334155]">{{ associations.last_page }}</span>
                    </p>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="associations.prev_page_url || '#'"
                            class="inline-flex h-8 items-center justify-center gap-1 rounded-lg border px-3 text-[0.68rem] font-semibold transition-all duration-200"
                            :class="associations.prev_page_url ? 'border-[#dbe3dd] text-[#334155] hover:border-[#b9c5bc] hover:bg-[#f4f7f5]' : 'pointer-events-none cursor-not-allowed border-[#eef2ee] text-[#cbd5e1]'"
                        >
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd"/></svg>
                            Previous
                        </Link>
                        <Link
                            :href="associations.next_page_url || '#'"
                            class="inline-flex h-8 items-center justify-center gap-1 rounded-lg border px-3 text-[0.68rem] font-semibold transition-all duration-200"
                            :class="associations.next_page_url ? 'border-[#014d3c] bg-[#014d3c] text-white shadow-sm hover:bg-[#01392d] hover:shadow-md' : 'pointer-events-none cursor-not-allowed border-[#eef2ee] bg-[#eef2ee] text-[#cbd5e1]'"
                        >
                            Next
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
