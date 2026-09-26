<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    barangay: {
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
        label: 'Association',
        value: props.barangay.association?.name ?? 'Unassigned',
        tone: 'bg-[#eef8f0] text-[#0f6b45]',
        href: props.urls.association,
    },
    {
        label: 'Farmers',
        value: props.stats.farmers_count ?? 0,
        tone: 'bg-[#eff6ff] text-[#1d4ed8]',
        href: props.urls.farmers,
    },
];
</script>

<template>
    <AdminLayout>
        <Head :title="barangay.name" />

        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div>
                            <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">Barangay record</p>
                            <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">
                                {{ barangay.name }}
                            </h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="urls.edit"
                            class="inline-flex h-8 items-center justify-center rounded-md bg-white px-3 text-[0.68rem] font-semibold text-[#003629] transition hover:bg-[#f1f7f3]"
                        >
                            Edit Barangay
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid overflow-hidden rounded-lg border border-[#dde4de] bg-white md:grid-cols-2">
                <component
                    v-for="card in statCards"
                    :key="card.label"
                    :is="card.href ? Link : 'article'"
                    :href="card.href"
                    class="border-b border-[#edf2ee] px-4 py-3 last:border-b-0 md:border-b-0 md:border-r md:last:border-r-0"
                    :class="card.href ? 'block transition hover:bg-[#f6fbf8]' : ''"
                >
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">
                        {{ card.label }}
                    </p>
                    <div class="mt-1.5 inline-flex rounded-md px-2 py-1 text-xs font-semibold" :class="card.tone">
                        {{ card.value }}
                    </div>
                </component>
            </section>

            <section class="rounded-lg border border-[#dde4de] bg-white">
                <div class="border-b border-[#edf2ee] px-4 py-2.5"><h2 class="text-sm font-semibold text-[#12372a]">Location details</h2></div>
                    <dl class="grid sm:grid-cols-2">
                        <div class="border-b border-[#edf2ee] px-4 py-3 sm:border-r">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">Barangay Name</dt>
                            <dd class="mt-1 text-xs font-medium text-[#12372a]">{{ barangay.name }}</dd>
                        </div>
                        <div class="border-b border-[#edf2ee] px-4 py-3">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">Code</dt>
                            <dd class="mt-1 text-xs font-medium text-[#12372a]">{{ barangay.code || 'Not set' }}</dd>
                        </div>
                        <div class="border-b border-[#edf2ee] px-4 py-3 sm:border-r">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">Municipality</dt>
                            <dd class="mt-1 text-xs font-medium text-[#12372a]">{{ barangay.municipality || 'Not set' }}</dd>
                        </div>
                        <div class="border-b border-[#edf2ee] px-4 py-3">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">Province</dt>
                            <dd class="mt-1 text-xs font-medium text-[#12372a]">{{ barangay.province || 'Not set' }}</dd>
                        </div>
                        <div class="px-4 py-3 sm:col-span-2">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">Notes</dt>
                            <dd class="mt-1 text-xs leading-5 text-[#4b5563]">{{ barangay.notes || 'No notes recorded.' }}</dd>
                        </div>
                    </dl>
            </section>

            <section class="overflow-hidden rounded-lg border border-[#dde4de] bg-white">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-4 py-2.5">
                    <h2 class="text-sm font-semibold text-[#12372a]">Farmers in {{ barangay.name }}</h2>
                    <span class="rounded-md bg-[#eff6ff] px-2 py-1 text-[0.65rem] font-semibold text-[#1d4ed8]">{{ farmers.length }} farmers</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs">
                        <thead class="bg-[#f8faf9] text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">
                            <tr><th class="px-4 py-2.5">Farmer</th><th class="px-4 py-2.5">Association</th><th class="px-4 py-2.5">Member Type</th><th class="px-4 py-2.5">Contact</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee]">
                            <tr v-for="farmer in farmers" :key="farmer.id" class="hover:bg-[#f8fbf9]">
                                <td class="px-4 py-2.5"><p class="font-semibold text-[#12372a]">{{ farmer.fullName }}</p><p class="mt-0.5 text-[0.65rem] text-[#7b8b80]">{{ farmer.farmerCode }}</p></td>
                                <td class="px-4 py-2.5 text-[#4b5563]">{{ farmer.association }}</td>
                                <td class="px-4 py-2.5 text-[#4b5563]">{{ farmer.memberType }}</td>
                                <td class="px-4 py-2.5 text-[#4b5563]">{{ farmer.mobileNumber }}</td>
                                <td class="px-4 py-2.5"><span class="rounded-full px-2 py-1 text-[0.62rem] font-semibold" :class="farmer.statusValue === 'active' ? 'bg-[#e5f5dc] text-[#426719]' : farmer.statusValue === 'deceased' ? 'bg-[#f1e9e6] text-[#735b52]' : 'bg-[#fff3d6] text-[#8a5b00]'">{{ farmer.status }}</span></td>
                                <td class="px-4 py-2.5 text-right"><Link :href="farmer.showUrl" class="font-semibold text-[#006b52] hover:underline">View</Link></td>
                            </tr>
                            <tr v-if="farmers.length === 0"><td colspan="6" class="px-4 py-8 text-center text-[#7b8b80]">No farmers are assigned to this barangay.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
