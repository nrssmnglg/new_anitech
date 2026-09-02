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

        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div>
                            <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">Association record</p>
                            <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">{{ association.name }}</h1>
                        </div>
                    </div>

                    <Link
                        :href="urls.edit"
                        class="inline-flex h-8 items-center justify-center rounded-md bg-white px-3 text-[0.68rem] font-semibold text-[#003629] transition hover:bg-[#f1f7f3]"
                    >
                        Edit Association
                    </Link>
                </div>
            </section>

            <section class="grid overflow-hidden rounded-lg border border-[#dde4de] bg-white md:grid-cols-3">
                <article
                    v-for="card in statCards"
                    :key="card.label"
                    class="border-b border-[#edf2ee] px-4 py-3 last:border-b-0 md:border-b-0 md:border-r md:last:border-r-0"
                >
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">{{ card.label }}</p>
                    <div class="mt-1.5 inline-flex rounded-md px-2 py-1 text-xs font-semibold" :class="card.tone">
                        {{ card.value }}
                    </div>
                </article>
            </section>

            <section class="rounded-lg border border-[#dde4de] bg-white">
                    <div class="border-b border-[#edf2ee] px-4 py-2.5"><h2 class="text-sm font-semibold text-[#12372a]">Association details</h2></div>
                    <dl class="grid sm:grid-cols-2">
                        <div class="border-b border-[#edf2ee] px-4 py-3 sm:border-r">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">Association Name</dt>
                            <dd class="mt-1 text-xs font-medium text-[#12372a]">{{ association.name }}</dd>
                        </div>
                        <div class="border-b border-[#edf2ee] px-4 py-3">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">Code</dt>
                            <dd class="mt-1 text-xs font-medium text-[#12372a]">{{ association.code || 'Not set' }}</dd>
                        </div>
                        <div class="border-b border-[#edf2ee] px-4 py-3 sm:border-r">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">President</dt>
                            <dd class="mt-1 text-xs font-medium text-[#12372a]">{{ association.president_name || 'Not set' }}</dd>
                        </div>
                        <div class="border-b border-[#edf2ee] px-4 py-3">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">Barangay</dt>
                            <dd class="mt-1 text-xs font-medium text-[#12372a]">{{ association.barangay?.name || 'Unassigned' }}</dd>
                        </div>
                        <div class="px-4 py-3 sm:col-span-2">
                            <dt class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7b8b80]">Notes</dt>
                            <dd class="mt-1 text-xs leading-5 text-[#4b5563]">{{ association.notes || 'No notes recorded.' }}</dd>
                        </div>
                    </dl>
            </section>
        </div>
    </AdminLayout>
</template>
