<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import QueueTable from '../../../Components/Admin/Renewals/QueueTable.vue';
import RecordsFilters from '../../../Components/Admin/Renewals/RecordsFilters.vue';
import RecordsTable from '../../../Components/Admin/Renewals/RecordsTable.vue';
import IndexHero from '../../../Components/Admin/Renewals/IndexHero.vue';
import IndexTabs from '../../../Components/Admin/Renewals/IndexTabs.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { usePersistentObject, readStoredValue, persistValue } from '../../../Composables/usePersistentUiState';

const summaryExportColumns = [
    { value: 'barangay', label: 'Barangay' },
    { value: 'farmer_count', label: 'No. of Farmers' },
    { value: 'annual_due', label: 'Annual Dues' },
    { value: 'mortuary_fee', label: 'Mortuary' },
    { value: 'membership_fee', label: 'Membership (New)' },
    { value: 'total_amount', label: 'Total Amount' },
    { value: 'membership_count', label: 'NM' },
    { value: 'without_mortuary_count', label: 'W/O M' },
    { value: 'female_count', label: 'Female' },
    { value: 'male_count', label: 'Male' },
];

const masterlistExportColumns = [
    { value: 'name', label: 'Name' },
    { value: 'annual_due', label: 'Annual Dues' },
    { value: 'mortuary_fee', label: 'Mortuary' },
    { value: 'membership_fee', label: 'Membership' },
    { value: 'total_amount', label: 'Total' },
    { value: 'remarks', label: 'Remarks' },
];

const props = defineProps({
    activeSection: { type: String, required: true },
    renewals: { type: Object, default: null },
    renewalRecords: { type: Object, default: null },
    recordFilters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
    pageTitle: { type: String, required: true },
    pageSubtitle: { type: String, required: true },
});

const form = reactive({
    record_search: props.recordFilters.record_search || '',
    record_year: props.recordFilters.record_year || '',
    record_barangay_id: props.recordFilters.record_barangay_id || '',
    record_source: props.recordFilters.record_source || '',
    record_status: props.recordFilters.record_status || '',
});
const exportModalOpen = ref(false);
const exportFormat = ref('pdf');
const compactMode = ref(Boolean(readStoredValue('staff.renewals.compact-mode', false)));
const selectedExportColumns = reactive([
    'barangay',
    'farmer_count',
    'annual_due',
    'mortuary_fee',
    'membership_fee',
    'total_amount',
]);

usePersistentObject('staff.renewals.record-filters', form);
persistValue('staff.renewals.compact-mode', compactMode);
watch(() => props.activeSection, (value) => {
    if (typeof window !== 'undefined') {
        window.localStorage.setItem('staff.renewals.last-section', JSON.stringify(value));
    }
}, { immediate: true });

onMounted(() => {
    const storedSection = readStoredValue('staff.renewals.last-section', 'queue');
    const currentUrl = new URL(window.location.href);

    if (!currentUrl.searchParams.get('section') && storedSection && storedSection !== props.activeSection) {
        router.get(storedSection === 'records' ? props.urls.records : props.urls.queue, {}, {
            replace: true,
            preserveState: true,
            preserveScroll: true,
        });
    }
});

function applyRecordFilters() {
    router.get(props.urls.records, { ...form }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetRecordFilters() {
    form.record_search = '';
    form.record_year = '';
    form.record_barangay_id = '';
    form.record_source = '';
    form.record_status = '';
    applyRecordFilters();
}

const exportReportUrl = computed(() => {
    const url = new URL(props.urls.report, window.location.origin);

    Object.entries(form).forEach(([key, value]) => {
        if (value) {
            url.searchParams.set(key, value);
        }
    });

    selectedExportColumns.forEach((column) => {
        url.searchParams.append('columns[]', column);
    });

    url.searchParams.set('format', exportFormat.value);

    return url.toString();
});

const exportModeLabel = computed(() => form.record_barangay_id ? 'Barangay Masterlist' : 'Renewal Summary');
const activeExportColumns = computed(() => form.record_barangay_id ? masterlistExportColumns : summaryExportColumns);

function openExportModal(format = 'pdf') {
    exportFormat.value = format;
    syncExportColumnsForMode();
    exportModalOpen.value = true;
}

function closeExportModal() {
    exportModalOpen.value = false;
}

function setExportFormat(format) {
    exportFormat.value = format;
}

function syncExportColumnsForMode() {
    const allowed = activeExportColumns.value.map((column) => column.value);
    const next = selectedExportColumns.filter((column) => allowed.includes(column));

    if (next.length === 0) {
        next.push(...allowed.slice(0, Math.min(allowed.length, 4)));
    }

    selectedExportColumns.splice(0, selectedExportColumns.length, ...next);
}

function toggleExportColumn(column) {
    const index = selectedExportColumns.indexOf(column);

    if (index >= 0) {
        if (selectedExportColumns.length === 1) {
            return;
        }

        selectedExportColumns.splice(index, 1);
        return;
    }

    selectedExportColumns.push(column);
}

function startExport() {
    window.location.href = exportReportUrl.value;
    closeExportModal();
}
</script>

<template>
    <Head :title="pageTitle" />

    <AdminLayout :title="pageTitle">
        <div class="space-y-8 bg-[radial-gradient(circle_at_top_left,_rgba(186,238,217,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(165,213,119,0.12),_transparent_22%)]">
            <IndexHero :page-title="pageTitle" :page-subtitle="pageSubtitle" :summary="summary" :urls="urls" @export="openExportModal" />

            <div class="space-y-6">
                <IndexTabs :active-section="activeSection" :urls="urls" />

                <QueueTable v-if="activeSection === 'queue' && renewals" :renewals="renewals" :compact-mode="compactMode" @toggle-compact="compactMode = !compactMode" />

                <template v-else-if="activeSection === 'records' && renewalRecords">
                    <RecordsFilters :form="form" :filter-options="filterOptions" :urls="urls" @apply="applyRecordFilters" @reset="resetRecordFilters" @export="openExportModal" />
                    <RecordsTable :renewal-records="renewalRecords" :quick-action-url="urls.quickAction" :compact-mode="compactMode" @toggle-compact="compactMode = !compactMode" />
                </template>
            </div>
        </div>

        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/45 px-4 py-6" @click.self="closeExportModal">
            <section class="w-full max-w-3xl rounded-[28px] border border-[#dbe2de] bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.22)] sm:p-6">
                <div class="flex flex-col gap-3 border-b border-[#e4ebe7] pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Export Report</p>
                        <h2 class="mt-1 text-xl font-bold text-[#1a2420]">Generate {{ exportModeLabel }}</h2>
                    </div>
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-[#d7e0db] text-[#66756f] transition hover:bg-[#f5f8f6]" @click="closeExportModal">
                        <span class="text-lg leading-none">&times;</span>
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-2xl border px-5 py-3 text-sm font-extrabold transition"
                            :class="exportFormat === 'pdf' ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#c8d8cf] bg-white text-[#003629] hover:bg-[#f4f7f5]'"
                            @click="setExportFormat('pdf')"
                        >
                            PDF
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-2xl border px-5 py-3 text-sm font-extrabold transition"
                            :class="exportFormat === 'xlsx' ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#c8d8cf] bg-white text-[#003629] hover:bg-[#f4f7f5]'"
                            @click="setExportFormat('xlsx')"
                        >
                            Excel
                        </button>
                    </div>

                    <div class="rounded-2xl border border-[#d7e0db] bg-[#f8faf9] p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm font-bold text-[#1a2420]">Columns to include</p>
                            <p class="text-sm text-[#697772]">{{ selectedExportColumns.length }} column{{ selectedExportColumns.length === 1 ? '' : 's' }} selected</p>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <label
                                v-for="column in activeExportColumns"
                                :key="column.value"
                                class="flex items-center gap-3 rounded-2xl border border-[#d7e0db] bg-white px-4 py-3 text-sm text-[#1a2420]"
                            >
                                <input
                                    :checked="selectedExportColumns.includes(column.value)"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-[#003629] focus:ring-[#003629]"
                                    @change="toggleExportColumn(column.value)"
                                >
                                <span class="font-medium">{{ column.label }}</span>
                            </label>
                        </div>
                    </div>

                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-5 py-3 text-sm font-bold text-[#697772] transition hover:bg-[#f4f7f5]" @click="closeExportModal">
                        Cancel
                    </button>
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl bg-[#003629] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]" @click="startExport">
                        Export {{ exportFormat === 'pdf' ? 'PDF' : 'Excel' }}
                    </button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
