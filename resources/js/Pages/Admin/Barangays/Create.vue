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
        <div class="space-y-4">
            <!-- Hero header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Location Management</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Add Barangay</h1>
                        </div>
                    </div>
                    <Link :href="urls.index" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 px-3.5 text-[0.7rem] font-semibold text-white/90 transition-all duration-200 hover:bg-white/10 hover:text-white">
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.47 4.47a.75.75 0 1 1-1.06 1.06l-5.75-5.75a.75.75 0 0 1 0-1.06l5.75-5.75a.75.75 0 1 1 1.06 1.06L5.61 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                        All Barangays
                    </Link>
                </div>
            </section>

            <!-- Form card -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <form class="divide-y divide-[#edf2ee]" @submit.prevent="submit">
                    <div class="px-5 py-4">
                        <div class="flex items-center gap-3 pb-4">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#0f6b45]" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Barangay Information</h2>
                        </div>
                        <FormFields :form="form" :status-options="statusOptions" />
                    </div>

                    <div class="flex gap-2 bg-[#fbfcfb] px-5 py-3.5 sm:justify-end">
                        <Link :href="urls.index" class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-4 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]">
                            Cancel
                        </Link>
                        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Barangay' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
