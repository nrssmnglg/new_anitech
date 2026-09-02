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
    status: props.association.status ?? 'active',
});

const submit = () => {
    form.put(props.urls.update);
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Edit ${association.name}`" />

        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">Location management</p>
                        <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">Edit {{ association.name }}</h1>
                    </div>
                    <Link :href="urls.show" class="inline-flex h-8 items-center rounded-md bg-white px-3 text-[0.68rem] font-semibold text-[#003629] hover:bg-[#f1f7f3]">View</Link>
                </div>
            </section>

            <form
                class="rounded-lg border border-[#dde4de] bg-white p-4"
                @submit.prevent="submit"
            >
                <div class="mb-3 border-b border-[#edf2ee] pb-2.5"><h2 class="text-sm font-semibold text-[#0f172a]">Association information</h2></div>
                <AssociationFormFields :form="form" :barangays="barangays" :status-options="statusOptions" />

                <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-[#edf2ee] pt-3">
                    <button
                        type="submit"
                        class="inline-flex h-9 items-center justify-center rounded-md bg-[#014d3c] px-4 text-xs font-semibold text-white transition hover:bg-[#01392d]"
                        :disabled="form.processing"
                    >
                        Save Changes
                    </button>
                    <Link
                        :href="urls.show"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-[#dbe3dd] px-3 text-xs font-semibold text-[#5f6f65] transition hover:bg-[#f4f7f5]"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
