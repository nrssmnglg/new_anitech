<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    association: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    farmers: {
        type: Array,
        default: () => [],
    },
    urls: {
        type: Object,
        required: true,
    },
});

const statCards = [
    {
        label: 'Members',
        value: props.stats.farmers_count ?? 0,
        icon: 'farmers',
        tone: 'from-[#eff6ff] to-[#dbeafe]',
        textTone: 'text-[#1d4ed8]',
        href: props.urls.farmers,
    },
    {
        label: 'Barangay',
        value: props.association.barangay?.name ?? 'Unassigned',
        icon: 'location',
        tone: 'from-[#e6f5ec] to-[#d4eddd]',
        textTone: 'text-[#0f6b45]',
        href: props.urls.barangay,
    },
    {
        label: 'Status',
        value: props.association.is_active ? 'Active' : 'Inactive',
        icon: 'status',
        tone: props.association.is_active ? 'from-[#dcfce7] to-[#bbf7d0]' : 'from-[#f1f5f9] to-[#e2e8f0]',
        textTone: props.association.is_active ? 'text-[#15803d]' : 'text-[#64748b]',
    },
];
</script>

<template>
    <AdminLayout>
        <Head :title="association.name" />

        <div class="space-y-4">
            <!-- Hero header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] text-lg font-bold backdrop-blur-sm">
                            {{ association.name?.charAt(0)?.toUpperCase() || 'A' }}
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Association Record</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">{{ association.name }}</h1>
                        </div>
                    </div>

                    <Link
                        :href="urls.edit"
                        class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                    >
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        Edit Association
                    </Link>
                </div>
            </section>

            <!-- Stat cards -->
            <section class="grid gap-3 md:grid-cols-3">
                <component
                    v-for="card in statCards"
                    :key="card.label"
                    :is="card.href ? Link : 'article'"
                    :href="card.href"
                    class="group relative overflow-hidden rounded-xl border border-[#dde4de] bg-white px-5 py-4 shadow-sm transition-all duration-200"
                    :class="card.href ? 'cursor-pointer hover:border-[#b5c5ba] hover:shadow-md' : ''"
                >
                    <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-gradient-to-br opacity-40" :class="card.tone"></div>
                    <p class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#7b8b80]">{{ card.label }}</p>
                    <div class="mt-2 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r px-3 py-1.5 text-sm font-bold" :class="[card.tone, card.textTone]">
                        {{ card.value }}
                    </div>
                    <svg v-if="card.href" viewBox="0 0 20 20" class="absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#cbd5e1] transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-[#94a3b8]" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                </component>
            </section>

            <!-- Association details -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="border-b border-[#edf2ee] px-5 py-3.5">
                    <h2 class="text-[0.8rem] font-bold text-[#12372a]">Association Details</h2>
                </div>
                <dl class="grid sm:grid-cols-2">
                    <div class="border-b border-[#edf2ee] px-5 py-4 sm:border-r">
                        <dt class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#7b8b80]">Association Name</dt>
                        <dd class="mt-1.5 text-xs font-medium text-[#12372a]">{{ association.name }}</dd>
                    </div>
                    <div class="border-b border-[#edf2ee] px-5 py-4">
                        <dt class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#7b8b80]">Code</dt>
                        <dd class="mt-1.5 font-mono text-xs font-medium text-[#12372a]">{{ association.code || 'Not set' }}</dd>
                    </div>
                    <div class="border-b border-[#edf2ee] px-5 py-4 sm:border-r">
                        <dt class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#7b8b80]">President</dt>
                        <dd class="mt-1.5 text-xs font-medium text-[#12372a]">
                            <Link v-if="association.president" :href="association.president.showUrl" class="inline-flex items-center gap-1.5 font-semibold text-[#006b52] transition-colors hover:text-[#005a45] hover:underline">
                                <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12.74 13c.93 0 1.41-1.14.72-1.77a8.01 8.01 0 0 0-10.92 0c-.69.63-.2 1.77.72 1.77h9.48Z"/></svg>
                                {{ association.president.name }}
                            </Link>
                            <span v-else>{{ association.president_name || 'Not set' }}</span>
                        </dd>
                        <dd class="mt-1 text-[0.68rem] text-[#94a3b8]">{{ association.president?.contactNumber || 'No contact number recorded' }}</dd>
                    </div>
                    <div class="border-b border-[#edf2ee] px-5 py-4">
                        <dt class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#7b8b80]">Barangay</dt>
                        <dd class="mt-1.5 text-xs font-medium text-[#12372a]">{{ association.barangay?.name || 'Unassigned' }}</dd>
                    </div>
                    <div class="px-5 py-4 sm:col-span-2">
                        <dt class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#7b8b80]">Notes</dt>
                        <dd class="mt-1.5 text-xs leading-5 text-[#4b5563]">{{ association.notes || 'No notes recorded.' }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Farmers table -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                    <h2 class="text-[0.8rem] font-bold text-[#12372a]">Association Farmers</h2>
                    <span class="inline-flex items-center rounded-lg bg-[#eff6ff] px-2.5 py-1 text-[0.62rem] font-bold text-[#1d4ed8]">{{ farmers.length }} member{{ farmers.length === 1 ? '' : 's' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#e8eeea] bg-gradient-to-b from-[#f8faf9] to-[#f3f6f4] text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#7b8b80]">
                                <th class="px-5 py-3">Farmer</th>
                                <th class="px-5 py-3">Member Type</th>
                                <th class="px-5 py-3">Contact</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee]">
                            <tr v-for="farmer in farmers" :key="farmer.id" class="group transition-all duration-150 hover:bg-[#f6faf8]">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[0.6rem] font-bold text-[#0f6b45]">
                                            {{ farmer.fullName?.charAt(0)?.toUpperCase() || 'F' }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-[#12372a]">{{ farmer.fullName }}</p>
                                            <p class="mt-0.5 font-mono text-[0.6rem] text-[#94a3b8]">{{ farmer.farmerCode }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-[#4b5563]">{{ farmer.memberType }}</td>
                                <td class="px-5 py-3 text-[#4b5563]">{{ farmer.mobileNumber }}</td>
                                <td class="px-5 py-3">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[0.62rem] font-bold"
                                        :class="farmer.statusValue === 'active' ? 'bg-[#dcfce7] text-[#15803d]' : farmer.statusValue === 'deceased' ? 'bg-[#f5f0ed] text-[#735b52]' : 'bg-[#fef3c7] text-[#b45309]'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="farmer.statusValue === 'active' ? 'bg-[#22c55e]' : farmer.statusValue === 'deceased' ? 'bg-[#a78b82]' : 'bg-[#f59e0b]'"></span>
                                        {{ farmer.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <Link :href="farmer.showUrl" class="inline-flex h-8 items-center rounded-lg px-3 text-[0.68rem] font-bold text-[#014d3c] transition-all duration-150 hover:bg-[#e6f5ec]">View</Link>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="farmers.length === 0">
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-xs flex-col items-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0faf5]">
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-[#334155]">No members yet</p>
                                        <p class="mt-1 text-xs text-[#94a3b8]">No farmers are assigned to this association.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
