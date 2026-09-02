<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

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

</script>

<template>
    <AdminLayout>
        <Head title="Associations" />

        <div class="space-y-3">
            <section class="flex flex-col gap-3 rounded-xl bg-[#003629] p-4 text-white md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-5">
                        <div>
                            <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/65">Location management</p>
                            <h1 class="mt-0.5 text-xl font-semibold">Associations</h1>
                        </div>
                        <div class="grid grid-cols-3 overflow-hidden rounded-lg border border-white/15 bg-white/[0.08]">
                            <div class="px-4 py-2 text-center"><p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-white/60">Total</p><p class="mt-0.5 text-base font-semibold">{{ summary.total ?? 0 }}</p></div>
                            <div class="border-x border-white/10 px-4 py-2 text-center"><p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-white/60">Active</p><p class="mt-0.5 text-base font-semibold text-[#c0f190]">{{ summary.active ?? 0 }}</p></div>
                            <div class="px-4 py-2 text-center"><p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-white/60">Inactive</p><p class="mt-0.5 text-base font-semibold">{{ summary.inactive ?? 0 }}</p></div>
                        </div>
                    </div>
                    <Link
                        :href="urls.create"
                        class="inline-flex h-8 items-center justify-center rounded-md bg-white px-3 text-[0.68rem] font-semibold text-[#003629] transition hover:bg-[#f1f7f3]"
                    >
                        Add Association
                    </Link>
            </section>

            <section class="rounded-lg border border-[#dde4de] bg-[#f7f9f8] p-3">
                <div class="grid items-end gap-2.5 lg:grid-cols-[minmax(0,1.2fr)_220px_200px_auto]">
                    <label class="space-y-1">
                        <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Search</span>
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Search association"
                            class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#12372a] outline-none transition focus:border-[#014d3c]"
                        />
                    </label>

                    <label class="space-y-1">
                        <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Barangay</span>
                        <select
                            v-model="form.barangay_id"
                            class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#12372a] outline-none transition focus:border-[#014d3c]"
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

                    <label class="space-y-1">
                        <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select
                            v-model="form.status"
                            class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#12372a] outline-none transition focus:border-[#014d3c]"
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
                            class="inline-flex h-9 items-center justify-center rounded-md border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]"
                            @click="clearFilters"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-md bg-[#014d3c] px-3.5 text-xs font-semibold text-white transition hover:bg-[#01392d]"
                            @click="applyFilters"
                        >
                            Apply
                        </button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-[#dde4de] bg-white">
                <div class="border-b border-[#edf2ee] px-4 py-3"><h2 class="text-sm font-semibold text-[#0f172a]">Registered associations</h2><p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ associations.total }} association{{ associations.total === 1 ? '' : 's' }}</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#edf2ee] text-xs">
                        <thead class="bg-[#fbfcfb]">
                            <tr class="text-left text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">
                                <th class="px-4 py-2.5">Association</th><th class="px-4 py-2.5">Barangay</th><th class="px-4 py-2.5">Members</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee]">
                            <tr v-for="association in associations.data" :key="association.id" class="align-top">
                                <td class="px-4 py-2.5">
                                    <div class="font-semibold text-[#12372a]">{{ association.name }}</div>
                                    <div class="mt-0.5 font-mono text-[0.6rem] text-[#6b7280]">{{ association.code || 'No code' }}</div>
                                    <div class="mt-0.5 text-[0.6rem] text-[#7b8b80]">
                                        President: {{ association.president_name || 'Not set' }}
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-[#3d4c43]">{{ association.barangay?.name || 'Unassigned' }}</td>
                                <td class="px-4 py-2.5 font-medium text-[#12372a]">{{ association.farmersCount ?? 0 }}</td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-[0.62rem] font-semibold"
                                        :class="association.status?.value === 'active' ? 'bg-[#d9f4c2] text-[#50761b]' : 'bg-[#eceff1] text-[#5e6c74]'"
                                    >
                                        {{ association.status?.label || 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex justify-end gap-1.5">
                                        <Link :href="association.actions.showUrl" aria-label="View association" title="View" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d6dfda] text-[#014d3c] hover:bg-[#f3f8f5]"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg></Link>
                                        <Link :href="association.actions.editUrl" aria-label="Edit association" title="Edit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d6dfda] text-[#334155] hover:bg-[#f3f8f5]"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!associations.data.length">
                                <td colspan="5" class="px-4 py-10 text-center text-xs text-[#6b7280]">
                                    No associations found for the selected filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="associations.last_page > 1" class="flex flex-col gap-2 border-t border-[#edf2ee] bg-[#fbfcfb] px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.68rem] text-[#6b7280]">
                        Page {{ associations.current_page }} of {{ associations.last_page }}
                    </p>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="associations.prev_page_url || '#'"
                            class="inline-flex h-8 items-center justify-center rounded-md border px-3 text-[0.68rem] font-semibold transition"
                            :class="associations.prev_page_url ? 'border-[#dbe3dd] text-[#12372a] hover:border-[#b9c5bc]' : 'cursor-not-allowed border-[#eef2ee] text-[#a0aca5] pointer-events-none'"
                        >
                            Previous
                        </Link>
                        <Link
                            :href="associations.next_page_url || '#'"
                            class="inline-flex h-8 items-center justify-center rounded-md border px-3 text-[0.68rem] font-semibold transition"
                            :class="associations.next_page_url ? 'border-[#014d3c] bg-[#014d3c] text-white hover:bg-[#01392d]' : 'cursor-not-allowed border-[#eef2ee] bg-[#eef2ee] text-[#a0aca5] pointer-events-none'"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
