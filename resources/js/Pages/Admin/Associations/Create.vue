<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AssociationFormFields from '@/Components/Admin/Associations/FormFields.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
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
    barangay_id: '',
    name: '',
    code: '',
    president_name: '',
    status: 'active',
});

const submit = () => {
    form.post(props.urls.store);
};
</script>

<template>
    <AdminLayout>
        <Head title="Create Association" />

        <div class="space-y-6">
            <section class="rounded-[28px] border border-[#dde4de] bg-white p-6 shadow-[0_20px_45px_rgba(15,23,42,0.06)]">
                <div class="space-y-3">
                    <Link :href="urls.index" class="inline-flex items-center text-sm font-semibold text-[#5f6f65] transition hover:text-[#014d3c]">
                        Back to Associations
                    </Link>
                    <div>
                        <h1 class="text-3xl font-semibold tracking-[-0.03em] text-[#12372a]">Create Association</h1>
                        <p class="mt-2 text-sm text-[#6b7280]">Add a new association record and assign it to a barangay.</p>
                    </div>
                </div>
            </section>

            <form
                class="rounded-[28px] border border-[#dde4de] bg-white p-6 shadow-[0_20px_45px_rgba(15,23,42,0.06)]"
                @submit.prevent="submit"
            >
                <AssociationFormFields :form="form" :barangays="barangays" :status-options="statusOptions" />

                <div class="mt-8 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#01392d]"
                        :disabled="form.processing"
                    >
                        Save Association
                    </button>
                    <Link
                        :href="urls.index"
                        class="inline-flex items-center justify-center rounded-xl border border-[#dbe3dd] px-5 py-3 text-sm font-semibold text-[#5f6f65] transition hover:border-[#b9c5bc] hover:text-[#12372a]"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
