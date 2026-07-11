<script setup>
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import QueueFilters from '../../../Components/Admin/MembershipApplications/QueueFilters.vue';
import QueueHero from '../../../Components/Admin/MembershipApplications/QueueHero.vue';
import QueueSummaryGrid from '../../../Components/Admin/MembershipApplications/QueueSummaryGrid.vue';
import QueueTable from '../../../Components/Admin/MembershipApplications/QueueTable.vue';
import { usePersistentObject, readStoredValue, persistValue } from '../../../Composables/usePersistentUiState';

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
    source: props.filters.source || '',
    status: props.filters.status || '',
    year: props.filters.year || '',
    barangay_id: props.filters.barangay_id || '',
});
const compactMode = ref(Boolean(readStoredValue('staff.membership-applications.compact-mode', false)));

usePersistentObject('staff.membership-applications.filters', form);
persistValue('staff.membership-applications.compact-mode', compactMode);

function applyFilters() {
    router.get(props.resetUrl, { ...form }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
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
        <div class="space-y-7 bg-[radial-gradient(circle_at_top_left,_rgba(186,238,217,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(165,213,119,0.12),_transparent_22%)]">
            <QueueHero
                :page-title="pageTitle"
                :create-url="createUrl"
            />

            <QueueSummaryGrid :summary="summary" />

            <QueueFilters
                :form="form"
                :filter-options="filterOptions"
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <QueueTable :applications="applications" :quick-action-url="quickActionUrl" :compact-mode="compactMode" @toggle-compact="compactMode = !compactMode" />
        </div>
    </AdminLayout>
</template>
