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
        <div class="space-y-3">
            <section class="flex flex-col gap-3 rounded-xl bg-[#003629] p-4 text-white md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-5">
                    <div>
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/65">Location management</p>
                        <h1 class="mt-0.5 text-xl font-semibold">Barangays</h1>
                    </div>
                    <div class="grid grid-cols-3 overflow-hidden rounded-lg border border-white/15 bg-white/[0.08]">
                <article class="px-4 py-2 text-center">
                    <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-white/60">Total</p>
                    <h2 class="mt-0.5 text-base font-semibold">{{ summary.total }}</h2>
                </article>
                <article class="border-x border-white/10 px-4 py-2 text-center">
                    <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-white/60">Active</p>
                    <h2 class="mt-0.5 text-base font-semibold text-[#c0f190]">{{ summary.active }}</h2>
                </article>
                <article class="px-4 py-2 text-center">
                    <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-white/60">Associated</p>
                    <h2 class="mt-0.5 text-base font-semibold">{{ summary.with_association }}</h2>
                </article>
                    </div>
                </div>
                <Link :href="urls.create" class="inline-flex h-8 items-center justify-center rounded-md bg-white px-3 text-[0.68rem] font-semibold text-[#003629] transition hover:bg-[#f1f7f3]">
                    Add Barangay
                </Link>
            </section>

            <section class="rounded-lg border border-[#dde4de] bg-[#f7f9f8] p-3">
                <form class="grid items-end gap-2.5 md:grid-cols-[1.3fr_0.8fr_1fr_auto]" @submit.prevent="applyFilters">
                    <label class="space-y-1">
                        <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Search</span>
                        <input v-model="form.search" type="text" placeholder="Barangay name or code" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                    </label>
                    <label class="space-y-1">
                        <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select v-model="form.status" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                            <option value="">All statuses</option>
                            <option v-for="item in filterOptions.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Association</span>
                        <select v-model="form.association" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                            <option value="">All associations</option>
                            <option v-for="item in filterOptions.associations" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]" @click="resetFilters">Reset</button>
                        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#014d3c] px-3.5 text-xs font-semibold text-white transition hover:bg-[#01362a]">Apply</button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-lg border border-[#dde4de] bg-white">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-4 py-3">
                    <div><h2 class="text-sm font-semibold text-[#0f172a]">Registered barangays</h2><p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ barangays.total }} location{{ barangays.total === 1 ? '' : 's' }}</p></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead class="bg-[#f9fbfa]">
                            <tr class="border-b border-[#e8eeea] text-left text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">
                                <th class="px-4 py-2.5">Barangay</th><th class="px-4 py-2.5">Association</th><th class="px-4 py-2.5 text-center">Farmers</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="barangay in barangays.data" :key="barangay.id" class="border-b border-[#edf2ee] transition hover:bg-[#fcfefd]">
                                <td class="px-4 py-2.5">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-[#0f172a]">{{ barangay.name }}</span>
                                        <span class="mt-0.5 font-mono text-[0.6rem] text-[#64748b]">{{ barangay.code || 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div v-if="barangay.association" class="flex flex-col">
                                        <span class="text-[#0f172a]">{{ barangay.association.name }}</span>
                                        <span class="mt-0.5 text-[0.6rem] text-[#64748b]">{{ barangay.association.code || 'N/A' }}</span>
                                    </div>
                                    <span v-else class="italic text-[#64748b]">None Assigned</span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="rounded-md px-2 py-1 text-[0.62rem] font-semibold" :class="barangay.farmersCount > 0 ? 'bg-[#fff4dc] text-[#b46d00]' : 'bg-[#eceff1] text-[#5e6c74]'">{{ barangay.farmersCount }}</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="rounded-md px-2 py-1 text-[0.62rem] font-semibold" :class="barangay.status.value === 'active' ? 'bg-[#d9f4c2] text-[#50761b]' : 'bg-[#eceff1] text-[#5e6c74]'">{{ barangay.status.label }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link :href="barangay.actions.showUrl" aria-label="View barangay" title="View" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d6dfda] text-[#014d3c] hover:bg-[#f3f8f5]"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg></Link>
                                        <Link :href="barangay.actions.editUrl" aria-label="Edit barangay" title="Edit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d6dfda] text-[#334155] hover:bg-[#f3f8f5]"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></Link>
                                        <button type="button" :aria-label="barangay.status.value === 'active' ? 'Deactivate barangay' : 'Activate barangay'" :title="barangay.status.value === 'active' ? 'Deactivate' : 'Activate'" class="inline-flex h-8 w-8 items-center justify-center rounded-md border transition" :class="barangay.status.value === 'active' ? 'border-[#f2d4c8] bg-[#fff7f3] text-[#c05c3c] hover:bg-[#ffede6]' : 'border-[#d5e5c4] bg-[#f5faef] text-[#50761b] hover:bg-[#ebf5df]'" @click="toggleStatus(barangay)"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v10"/><path d="M18.4 6.6a9 9 0 1 1-12.8 0"/></svg></button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="barangays.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-xs text-[#64748b]">No barangay records found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-2 border-t border-[#edf2ee] bg-[#fbfcfb] px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.68rem] text-[#64748b]">Showing {{ barangays.from || 0 }}-{{ barangays.to || 0 }} of {{ barangays.total }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in barangays.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-[#dbe3dd] px-2.5 text-[0.68rem] text-[#9aa6a1]" v-html="link.label" />
                            <Link v-else :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2.5 text-[0.68rem] font-semibold transition" :class="link.active ? 'border-[#014d3c] bg-[#014d3c] text-white' : 'border-[#dbe3dd] bg-white text-[#64748b] hover:bg-[#f4f7f5]'" preserve-scroll preserve-state v-html="link.label" />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
