<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AssociationFormFields from '@/Components/Admin/Associations/FormFields.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    association: {
        type: Object,
        required: true,
    },
    barangays: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Object,
        required: true,
    },
    presidentCandidates: {
        type: Array,
        default: () => [],
    },
    urls: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    barangay_id: props.association.barangay_id ?? '',
    name: props.association.name ?? '',
    code: props.association.code ?? '',
    president_name: props.association.president_name ?? '',
    president_farmer_id: props.association.president_farmer_id ?? '',
    status: props.association.status ?? 'active',
});

const submit = () => {
    form.put(props.urls.update);
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Edit ${association.name}`" />

        <div class="space-y-4">
            <!-- Hero header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] text-lg font-bold backdrop-blur-sm">
                            {{ association.name?.charAt(0)?.toUpperCase() || 'A' }}
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Location Management</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Edit {{ association.name }}</h1>
                        </div>
                    </div>
                    <Link :href="urls.show" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]">
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                        View
                    </Link>
                </div>
            </section>

            <!-- Form card -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <form class="divide-y divide-[#edf2ee]" @submit.prevent="submit">
                    <div class="px-5 py-4">
                        <div class="flex items-center gap-3 pb-4">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#0f6b45]" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Association Information</h2>
                        </div>
                        <AssociationFormFields :form="form" :barangays="barangays" :status-options="statusOptions" :president-candidates="presidentCandidates" />
                    </div>

                    <div class="flex flex-wrap gap-2 bg-[#fbfcfb] px-5 py-3.5 sm:justify-end">
                        <Link
                            :href="urls.show"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-4 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
