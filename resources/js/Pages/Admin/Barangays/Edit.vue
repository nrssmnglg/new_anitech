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
    form.put(props.urls.update, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit Barangay" />

    <AdminLayout title="Edit Barangay">
        <div class="space-y-4">
            <!-- Hero header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] text-lg font-bold backdrop-blur-sm">
                            {{ barangay.name?.charAt(0)?.toUpperCase() || 'B' }}
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Location Management</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Edit {{ barangay.name }}</h1>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="urls.show" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </Link>
                        <Link :href="urls.index" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 px-3.5 text-[0.7rem] font-semibold text-white/90 transition-all duration-200 hover:bg-white/10 hover:text-white">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.47 4.47a.75.75 0 1 1-1.06 1.06l-5.75-5.75a.75.75 0 0 1 0-1.06l5.75-5.75a.75.75 0 1 1 1.06 1.06L5.61 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                            All Barangays
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Form card -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="divide-y divide-[#edf2ee]">
                    <!-- Warnings -->
                    <div v-if="dependencyWarnings.length" class="bg-[#fffbeb] px-5 py-3">
                        <div class="flex gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#fef3c7]">
                                <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#d97706]" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                            </div>
                            <div class="space-y-1">
                                <p v-for="warning in dependencyWarnings" :key="warning" class="text-xs font-medium text-[#92400e]">{{ warning }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form class="divide-y divide-[#edf2ee]" @submit.prevent="submit">
                        <div class="px-5 py-4">
                            <div class="flex items-center gap-3 pb-4">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd]">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#0f6b45]" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-[#0f172a]">Barangay Information</h2>
                                    <p class="mt-0.5 text-[0.68rem] text-[#94a3b8]">Update barangay details below.</p>
                                </div>
                            </div>
                            <FormFields :form="form" :status-options="statusOptions" />
                        </div>

                        <div class="flex gap-2 bg-[#fbfcfb] px-5 py-3.5 sm:justify-end">
                            <Link :href="urls.show" class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-4 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]">
                                Cancel
                            </Link>
                            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Update Barangay' }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
