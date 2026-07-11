<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import FormFields from '../../../Components/Admin/Barangays/FormFields.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    barangay: { type: Object, required: true },
    dependencyWarnings: { type: Array, required: true },
    statusOptions: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const form = useForm({
    name: props.barangay.name || '',
    code: props.barangay.code || '',
    status: props.barangay.status || 'active',
});

function submit() {
    form.post(props.urls.store, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Add Barangay" />

    <AdminLayout title="Add Barangay">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[1.35rem] border border-[#dde4de] bg-white shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <div class="border-b border-[#edf2ee] bg-[#fbfcfb] px-6 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <h1 class="text-2xl font-black tracking-[-0.03em] text-[#0f172a]">Add Barangay</h1>
                            <p class="mt-2 text-sm text-[#64748b]">Create a barangay record before assigning it to farmers and associations.</p>
                        </div>
                        <Link :href="urls.index" class="inline-flex items-center justify-center rounded-xl border border-[#dbe3dd] bg-white px-4 py-2.5 text-sm font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]">
                            Back to Barangays
                        </Link>
                    </div>
                </div>

                <form class="space-y-6 p-6" @submit.prevent="submit">
                    <div class="rounded-[1.2rem] border border-[#e9efeb] bg-[#fbfcfb] p-6">
                        <div class="mb-6">
                            <h2 class="text-lg font-black text-[#0f172a]">Barangay Information</h2>
                            <p class="mt-2 text-sm text-[#64748b]">Create a location record that can be assigned to farmers and linked to one association.</p>
                        </div>
                        <FormFields :form="form" :status-options="statusOptions" />
                    </div>

                    <div class="flex flex-col gap-3 rounded-2xl border border-[#edf2ee] bg-[#fbfcfb] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-[#64748b]">This record will be available immediately across farmer and association modules.</p>
                        <div class="flex gap-3">
                            <Link :href="urls.index" class="inline-flex items-center justify-center rounded-xl border border-[#dbe3dd] bg-white px-5 py-3 text-sm font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]">
                                Cancel
                            </Link>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#01362a] disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Save Barangay' }}
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
