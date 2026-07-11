<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
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

function toggleStatus(barangay) {
    router.patch(barangay.actions.toggleStatusUrl, {
        status: barangay.status.value === 'active' ? 'inactive' : 'active',
    }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Barangay Management" />

    <AdminLayout title="Barangay Management">
        <div class="space-y-5">
            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Total Barangays</p>
                    <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#0f172a]">{{ summary.total }}</h2>
                </article>
                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Active Barangays</p>
                    <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#0f5b46]">{{ summary.active }}</h2>
                </article>
                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">With Association</p>
                    <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#b46d00]">{{ summary.with_association }}</h2>
                </article>
            </section>

            <div class="flex justify-end">
                <Link :href="urls.create" class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-4 py-2.5 text-sm font-black text-white transition hover:bg-[#01362a]">
                    Add Barangay
                </Link>
            </div>

            <section class="rounded-[1.35rem] border border-[#dde4de] bg-white p-4 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <form class="grid items-end gap-4 md:grid-cols-4" @submit.prevent="applyFilters">
                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Search</span>
                        <input v-model="form.search" type="text" placeholder="Barangay name or code" class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                    </label>
                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select v-model="form.status" class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                            <option value="">All statuses</option>
                            <option v-for="item in filterOptions.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Association</span>
                        <select v-model="form.association" class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                            <option value="">All associations</option>
                            <option v-for="item in filterOptions.associations" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </label>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-[#014d3c] px-5 py-3 text-sm font-black text-white transition hover:bg-[#01362a]">Apply</button>
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-[#dbe3dd] px-4 py-3 text-sm font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]" @click="resetFilters">Reset</button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-[1.35rem] border border-[#dde4de] bg-white shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <div class="border-b border-[#edf2ee] bg-[#fbfcfb] px-6 py-4">
                    <h2 class="text-lg font-black text-[#0f172a]">Registered Locations</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-[#f9fbfa]">
                            <tr class="border-b border-[#e8eeea] text-left text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">
                                <th class="px-6 py-4">Barangay</th>
                                <th class="px-6 py-4">Association</th>
                                <th class="px-6 py-4 text-center">Farmers</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="barangay in barangays.data" :key="barangay.id" class="border-b border-[#edf2ee] transition hover:bg-[#fcfefd]">
                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-[#0f172a]">{{ barangay.name }}</span>
                                        <span class="font-mono text-xs text-[#64748b]">CODE: {{ barangay.code || 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div v-if="barangay.association" class="flex flex-col">
                                        <span class="text-[#0f172a]">{{ barangay.association.name }}</span>
                                        <span class="text-xs text-[#64748b]">ID: {{ barangay.association.code || 'N/A' }}</span>
                                    </div>
                                    <span v-else class="italic text-[#64748b]">None Assigned</span>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="barangay.farmersCount > 0 ? 'bg-[#fff4dc] text-[#b46d00]' : 'bg-[#eceff1] text-[#5e6c74]'">{{ barangay.farmersCount }}</span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="barangay.status.value === 'active' ? 'bg-[#d9f4c2] text-[#50761b]' : 'bg-[#eceff1] text-[#5e6c74]'">{{ barangay.status.label }}</span>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <div class="flex items-center justify-end gap-4 text-sm">
                                        <Link :href="barangay.actions.showUrl" class="font-medium text-[#014d3c] hover:underline">View</Link>
                                        <Link :href="barangay.actions.editUrl" class="font-medium text-[#1f2937] transition hover:underline">Edit</Link>
                                        <button type="button" class="font-medium transition hover:underline" :class="barangay.status.value === 'active' ? 'text-[#c05c3c]' : 'text-[#50761b]'" @click="toggleStatus(barangay)">
                                            {{ barangay.status.value === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="barangays.data.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center text-sm text-[#64748b]">No barangay records found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-4 border-t border-[#edf2ee] bg-[#fbfcfb] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[#64748b]">Showing {{ barangays.from || 0 }}-{{ barangays.to || 0 }} of {{ barangays.total }} entries</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in barangays.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-[#dbe3dd] px-3 text-sm text-[#9aa6a1]" v-html="link.label" />
                            <Link v-else :href="link.url" class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border px-3 text-sm font-bold transition" :class="link.active ? 'border-[#014d3c] bg-[#014d3c] text-white' : 'border-[#dbe3dd] bg-white text-[#64748b] hover:bg-[#f4f7f5]'" preserve-scroll preserve-state v-html="link.label" />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
