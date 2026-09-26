<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import QueueFilters from '../../../Components/Admin/MembershipApplications/QueueFilters.vue';
import QueueSummaryGrid from '../../../Components/Admin/MembershipApplications/QueueSummaryGrid.vue';
import QueueTable from '../../../Components/Admin/MembershipApplications/QueueTable.vue';
import { readStoredValue, persistValue } from '../../../Composables/usePersistentUiState';

const props = defineProps({
    applications: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    pageTitle: { type: String, required: true },
    pageSubtitle: { type: String, required: true },
    createUrl: { type: String, required: true },
    resetUrl: { type: String, required: true },
    quickActionUrl: { type: String, required: true },
    summary: { type: Object, required: true },
});

const form = reactive({
    search: props.filters.search || '',
    source: props.filters.source || '',
    status: props.filters.status || '',
    year: props.filters.year || '',
    barangay_id: props.filters.barangay_id || '',
});

const compactMode = ref(Boolean(readStoredValue('staff.membership-applications.compact-mode', false)));

persistValue('staff.membership-applications.compact-mode', compactMode);

function applyFilters() {
    router.get(props.resetUrl, { ...form }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function handleFilterStatus(status) {
    form.status = status;
    applyFilters();
}

function resetFilters() {
    form.search = '';
    form.source = '';
    form.status = '';
    form.year = '';
    form.barangay_id = '';
    applyFilters();
}
</script>

<template>
    <Head :title="pageTitle" />

    <AdminLayout :title="pageTitle">
        <div class="mx-auto w-full max-w-[1536px] space-y-4">
            <!-- Page Header Row -->
            <div class="flex flex-col gap-3 px-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-slate-500">Farmer Management</p>
                    <h1 class="text-xl font-bold tracking-tight text-[#0f172a] sm:text-2xl">{{ pageTitle }}</h1>
                </div>

                <Link
                    :href="createUrl"
                    class="inline-flex h-9 items-center gap-2 self-start rounded-xl bg-[#003629] px-4 text-xs font-bold text-white shadow-xs transition-all duration-200 hover:bg-[#00483a] hover:shadow-md active:scale-95 sm:self-auto"
                >
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                        <path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z" />
                    </svg>
                    <span>New Application</span>
                </Link>
            </div>

            <QueueSummaryGrid
                :summary="summary"
                :current-status="form.status"
                @filter-status="handleFilterStatus"
            />

            <QueueFilters
                :form="form"
                :filter-options="filterOptions"
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <QueueTable
                :applications="applications"
                :quick-action-url="quickActionUrl"
                :compact-mode="compactMode"
                @toggle-compact="compactMode = !compactMode"
            />
        </div>
    </AdminLayout>
</template>
