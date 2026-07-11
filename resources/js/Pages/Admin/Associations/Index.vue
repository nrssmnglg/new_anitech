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

const summaryCards = [
    { label: 'Total Associations', value: props.summary.total ?? 0 },
    { label: 'Active', value: props.summary.active ?? 0, tone: 'bg-[#d9f4c2] text-[#50761b]' },
    { label: 'Inactive', value: props.summary.inactive ?? 0, tone: 'bg-[#eceff1] text-[#5e6c74]' },
];
</script>

<template>
    <AdminLayout>
        <Head title="Associations" />

        <div class="space-y-6">
            <section class="rounded-[28px] border border-[#dde4de] bg-white p-6 shadow-[0_20px_45px_rgba(15,23,42,0.06)]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <h1 class="text-3xl font-semibold tracking-[-0.03em] text-[#12372a]">Association Management</h1>
                        <p class="text-sm text-[#6b7280]">Maintain association profiles and barangay assignments.</p>
                    </div>
                    <Link
                        :href="urls.create"
                        class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#01392d]"
                    >
                        Add Association
                    </Link>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-[24px] border border-[#dde4de] bg-white p-5 shadow-[0_16px_32px_rgba(15,23,42,0.05)]"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#7b8b80]">{{ card.label }}</p>
                    <div class="mt-4 inline-flex rounded-full px-3 py-1 text-sm font-semibold" :class="card.tone || 'bg-[#eef3ef] text-[#12372a]'">
                        {{ card.value }}
                    </div>
                </article>
            </section>

            <section class="rounded-[28px] border border-[#dde4de] bg-white p-6 shadow-[0_20px_45px_rgba(15,23,42,0.06)]">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_220px_200px_auto]">
                    <label class="space-y-2 text-sm font-medium text-[#3d4c43]">
                        <span>Search</span>
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Search association"
                            class="w-full rounded-2xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#12372a] outline-none transition focus:border-[#014d3c]"
                        />
                    </label>

                    <label class="space-y-2 text-sm font-medium text-[#3d4c43]">
                        <span>Barangay</span>
                        <select
                            v-model="form.barangay_id"
                            class="w-full rounded-2xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#12372a] outline-none transition focus:border-[#014d3c]"
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

                    <label class="space-y-2 text-sm font-medium text-[#3d4c43]">
                        <span>Status</span>
                        <select
                            v-model="form.status"
                            class="w-full rounded-2xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#12372a] outline-none transition focus:border-[#014d3c]"
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

                    <div class="flex items-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#01392d]"
                            @click="applyFilters"
                        >
                            Apply
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-[#dbe3dd] px-4 py-3 text-sm font-semibold text-[#5f6f65] transition hover:border-[#b9c5bc] hover:text-[#12372a]"
                            @click="clearFilters"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[28px] border border-[#dde4de] bg-white shadow-[0_20px_45px_rgba(15,23,42,0.06)]">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#edf2ee]">
                        <thead class="bg-[#fbfcfb]">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.14em] text-[#7b8b80]">
                                <th class="px-6 py-4">Association</th>
                                <th class="px-6 py-4">Barangay</th>
                                <th class="px-6 py-4">Members</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee]">
                            <tr v-for="association in associations.data" :key="association.id" class="align-top">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-[#12372a]">{{ association.name }}</div>
                                    <div class="mt-1 text-sm text-[#6b7280]">{{ association.code || 'No code' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-[#3d4c43]">{{ association.barangay?.name || 'Unassigned' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-[#12372a]">{{ association.farmersCount ?? 0 }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="association.status?.value === 'active' ? 'bg-[#d9f4c2] text-[#50761b]' : 'bg-[#eceff1] text-[#5e6c74]'"
                                    >
                                        {{ association.status?.label || 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-4 text-sm font-semibold">
                                        <Link :href="association.actions.showUrl" class="text-[#014d3c] hover:text-[#01392d]">View</Link>
                                        <Link :href="association.actions.editUrl" class="text-[#5f6f65] hover:text-[#12372a]">Edit</Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!associations.data.length">
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-[#6b7280]">
                                    No associations found for the selected filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
