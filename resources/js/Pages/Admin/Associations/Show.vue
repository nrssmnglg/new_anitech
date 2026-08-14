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
    urls: {
        type: Object,
        required: true,
    },
});

const statCards = [
    {
        label: 'Members',
        value: props.stats.farmers_count ?? 0,
        tone: 'bg-[#eff6ff] text-[#1d4ed8]',
    },
    {
        label: 'Barangay',
        value: props.association.barangay?.name ?? 'Unassigned',
        tone: 'bg-[#eef8f0] text-[#0f6b45]',
    },
    {
        label: 'Status',
        value: props.association.is_active ? 'Active' : 'Inactive',
        tone: props.association.is_active ? 'bg-[#d9f4c2] text-[#50761b]' : 'bg-[#eceff1] text-[#5e6c74]',
    },
];
</script>

<template>
    <AdminLayout>
        <Head :title="association.name" />

        <div class="space-y-6">
            <section class="rounded-[28px] border border-[#dde4de] bg-white p-6 shadow-[0_20px_45px_rgba(15,23,42,0.06)]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-3">
                        <Link :href="urls.index" class="inline-flex items-center text-sm font-semibold text-[#5f6f65] transition hover:text-[#014d3c]">
                            Back to Associations
                        </Link>
                        <div class="space-y-2">
                            <h1 class="text-3xl font-semibold tracking-[-0.03em] text-[#12372a]">{{ association.name }}</h1>
                            <p class="text-sm text-[#6b7280]">Association profile, barangay assignment, and membership details.</p>
                        </div>
                    </div>

                    <Link
                        :href="urls.edit"
                        class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#01392d]"
                    >
                        Edit Association
                    </Link>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="card in statCards"
                    :key="card.label"
                    class="rounded-[24px] border border-[#dde4de] bg-white p-5 shadow-[0_16px_32px_rgba(15,23,42,0.05)]"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#7b8b80]">{{ card.label }}</p>
                    <div class="mt-4 inline-flex rounded-full px-3 py-1 text-sm font-semibold" :class="card.tone">
                        {{ card.value }}
                    </div>
                </article>
            </section>

            <section>
                <article class="rounded-[28px] border border-[#dde4de] bg-white p-6 shadow-[0_20px_45px_rgba(15,23,42,0.06)]">
                    <h2 class="text-lg font-semibold text-[#12372a]">Association Details</h2>
                    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-[#edf2ee] bg-[#fbfcfb] p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[#7b8b80]">Association Name</dt>
                            <dd class="mt-2 text-sm font-medium text-[#12372a]">{{ association.name }}</dd>
                        </div>
                        <div class="rounded-2xl border border-[#edf2ee] bg-[#fbfcfb] p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[#7b8b80]">Code</dt>
                            <dd class="mt-2 text-sm font-medium text-[#12372a]">{{ association.code || 'Not set' }}</dd>
                        </div>
                        <div class="rounded-2xl border border-[#edf2ee] bg-[#fbfcfb] p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[#7b8b80]">President</dt>
                            <dd class="mt-2 text-sm font-medium text-[#12372a]">{{ association.president_name || 'Not set' }}</dd>
                        </div>
                        <div class="rounded-2xl border border-[#edf2ee] bg-[#fbfcfb] p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[#7b8b80]">Barangay</dt>
                            <dd class="mt-2 text-sm font-medium text-[#12372a]">{{ association.barangay?.name || 'Unassigned' }}</dd>
                        </div>
                        <div class="rounded-2xl border border-[#edf2ee] bg-[#fbfcfb] p-4 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-[#7b8b80]">Notes</dt>
                            <dd class="mt-2 text-sm leading-6 text-[#4b5563]">{{ association.notes || 'No notes recorded.' }}</dd>
                        </div>
                    </dl>
                </article>
            </section>
        </div>
    </AdminLayout>
</template>
