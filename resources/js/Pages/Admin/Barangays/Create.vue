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
        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">Location management</p>
                            <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">Add Barangay</h1>
                        </div>
                        <Link :href="urls.index" class="inline-flex h-8 items-center justify-center rounded-md border border-white/25 px-3 text-[0.68rem] font-semibold text-white transition hover:bg-white/10">
                            Barangays
                        </Link>
                    </div>
            </section>

            <section class="rounded-lg border border-[#dde4de] bg-white p-4">
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <div class="mb-3 border-b border-[#edf2ee] pb-2.5">
                            <h2 class="text-sm font-semibold text-[#0f172a]">Barangay information</h2>
                        </div>
                        <FormFields :form="form" :status-options="statusOptions" />
                    </div>

                    <div class="flex gap-2 border-t border-[#edf2ee] pt-3 sm:justify-end">
                            <Link :href="urls.index" class="inline-flex h-9 items-center justify-center rounded-md border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]">
                                Cancel
                            </Link>
                            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#014d3c] px-4 text-xs font-semibold text-white transition hover:bg-[#01362a] disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Save Barangay' }}
                            </button>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
