<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    barangays: { type: Object, required: true },
    filters: { type: Object, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const form = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    association: props.filters.association || '',
});

const showFilters = ref(!!(form.search || form.status || form.association));

function applyFilters() {
    router.get(props.urls.index, { ...form }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    form.search = '';
    form.status = '';
    form.association = '';
    applyFilters();
}

function deleteBarangay(barangay) {
    if (!window.confirm(`Delete ${barangay.name}? This action cannot be undone.`)) {
        return;
    }

    router.delete(barangay.actions.deleteUrl, {
        preserveScroll: true,
    });
}

const activeFilterCount = () => [form.search, form.status, form.association].filter(Boolean).length;
</script>

<template>
    <Head title="Barangay Management" />

    <AdminLayout title="Barangay Management">
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <!-- Decorative pattern -->
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-20 top-4 h-20 w-20 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Location Management</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Barangays</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Summary pills -->
                        <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Total</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.total }}</p>
                            </article>
                            <article class="border-x border-white/[0.08] px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Active</p>
                                <p class="mt-0.5 text-lg font-bold leading-none text-[#7ddfb8]">{{ summary.active }}</p>
                            </article>
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Associated</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.with_association }}</p>
                            </article>
                        </div>

                        <Link :href="urls.create" class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-90" fill="currentColor"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                            Add Barangay
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
                    <form class="grid items-end gap-3 md:grid-cols-[1.3fr_0.8fr_1fr_auto]" @submit.prevent="applyFilters">
                        <label class="space-y-1.5">
                            <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Search</span>
                            <div class="relative">
                                <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#94a3b8]" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                                <input v-model="form.search" type="text" placeholder="Barangay name or code" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                            </div>
                        </label>
                        <label class="space-y-1.5">
                            <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                            <select v-model="form.status" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                                <option value="">All statuses</option>
                                <option v-for="item in filterOptions.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                        </label>
                        <label class="space-y-1.5">
                            <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Association</span>
                            <select v-model="form.association" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                                <option value="">All associations</option>
                                <option v-for="item in filterOptions.associations" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]" @click="resetFilters">Reset</button>
                            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97]">Apply</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Data table -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                    <div>
                        <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Registered Barangays</h2>
                        <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ barangays.total }} location{{ barangays.total === 1 ? '' : 's' }} found</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#e8eeea] bg-gradient-to-b from-[#f8faf9] to-[#f3f6f4] text-left text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                                <th class="px-5 py-3">Barangay</th>
                                <th class="px-5 py-3">Association</th>
                                <th class="px-5 py-3 text-center">Farmers</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="barangay in barangays.data" :key="barangay.id" class="group border-b border-[#edf2ee] transition-all duration-150 hover:bg-[#f6faf8]">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[0.65rem] font-bold text-[#0f6b45]">
                                            {{ barangay.name?.charAt(0)?.toUpperCase() || 'B' }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-[#0f172a]">{{ barangay.name }}</p>
                                            <p class="mt-0.5 font-mono text-[0.6rem] text-[#94a3b8]">{{ barangay.code || 'No code' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div v-if="barangay.association" class="flex flex-col">
                                        <span class="text-[#334155]">{{ barangay.association.name }}</span>
                                        <span class="mt-0.5 text-[0.6rem] text-[#94a3b8]">{{ barangay.association.code || '' }}</span>
                                    </div>
                                    <span v-else class="inline-flex items-center gap-1 text-[#94a3b8]">
                                        <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14Zm0-1.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11ZM6.75 5a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5Z"/></svg>
                                        None Assigned
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span
                                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg px-2 text-[0.62rem] font-bold transition-colors"
                                        :class="barangay.farmersCount > 0 ? 'bg-[#fef3c7] text-[#b45309]' : 'bg-[#f1f5f9] text-[#94a3b8]'"
                                    >{{ barangay.farmersCount }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[0.62rem] font-bold"
                                        :class="barangay.status.value === 'active' ? 'bg-[#dcfce7] text-[#15803d]' : 'bg-[#f1f5f9] text-[#64748b]'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="barangay.status.value === 'active' ? 'bg-[#22c55e]' : 'bg-[#94a3b8]'"></span>
                                        {{ barangay.status.label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-60 transition-opacity duration-200 group-hover:opacity-100">
                                        <Link :href="barangay.actions.showUrl" aria-label="View barangay" title="View" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#014d3c] transition-all duration-150 hover:bg-[#e6f5ec]">
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </Link>
                                        <Link :href="barangay.actions.editUrl" aria-label="Edit barangay" title="Edit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#475569] transition-all duration-150 hover:bg-[#f1f5f9]">
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </Link>
                                        <button type="button" aria-label="Delete barangay" title="Delete" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#dc2626] transition-all duration-150 hover:bg-[#fef2f2]" @click="deleteBarangay(barangay)">
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 10v6M14 10v6"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="barangays.data.length === 0">
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-xs flex-col items-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0faf5]">
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-[#334155]">No barangays found</p>
                                        <p class="mt-1 text-xs text-[#94a3b8]">Try adjusting your filters or add a new barangay.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col gap-2 border-t border-[#edf2ee] bg-gradient-to-b from-[#fbfcfb] to-[#f8faf9] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.68rem] text-[#64748b]">Showing <span class="font-semibold text-[#334155]">{{ barangays.from || 0 }}</span>–<span class="font-semibold text-[#334155]">{{ barangays.to || 0 }}</span> of <span class="font-semibold text-[#334155]">{{ barangays.total }}</span></p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template v-for="link in barangays.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] text-[#cbd5e1]" v-html="link.label" />
                            <Link v-else :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] font-semibold transition-all duration-200" :class="link.active ? 'bg-[#014d3c] text-white shadow-sm' : 'text-[#64748b] hover:bg-[#f1f5f9]'" preserve-scroll preserve-state v-html="link.label" />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
