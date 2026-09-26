<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import QueueFilters from '../../../Components/Admin/Renewals/QueueFilters.vue';
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
    queueFilters: { type: Object, required: true },
    queueFilterOptions: { type: Object, required: true },
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
const queueForm = reactive({
    queue_search: props.queueFilters.queue_search || '',
    queue_year: props.queueFilters.queue_year || '',
    queue_barangay_id: props.queueFilters.queue_barangay_id || '',
    queue_member_type_id: props.queueFilters.queue_member_type_id || '',
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

function applyQueueFilters() {
    router.get(props.urls.queue, { ...queueForm }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetQueueFilters() {
    queueForm.queue_search = '';
    queueForm.queue_year = String(new Date().getFullYear());
    queueForm.queue_barangay_id = '';
    queueForm.queue_member_type_id = '';
    applyQueueFilters();
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

const activeExportColumns = computed(() => form.record_barangay_id ? masterlistExportColumns : summaryExportColumns);
const exportPreviewUrl = computed(() => {
    const url = new URL(exportReportUrl.value);
    url.searchParams.set('preview', '1');
    return url.toString();
});
const selectedExportColumnLabels = computed(() => activeExportColumns.value.filter((column) => selectedExportColumns.includes(column.value)).map((column) => column.label));

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
        <div class="mx-auto w-full max-w-[1536px] space-y-4 pb-10">
            <IndexHero :page-title="pageTitle" :page-subtitle="pageSubtitle" :summary="summary" :urls="urls" @export="openExportModal" />

            <div class="space-y-4">
                <IndexTabs :active-section="activeSection" :urls="urls" :summary="summary" />

                <template v-if="activeSection === 'queue' && renewals">
                    <QueueFilters :form="queueForm" :filter-options="queueFilterOptions" @apply="applyQueueFilters" @reset="resetQueueFilters" />
                    <QueueTable :renewals="renewals" :compact-mode="compactMode" @toggle-compact="compactMode = !compactMode" />
                </template>

                <template v-else-if="activeSection === 'records' && renewalRecords">
                    <RecordsFilters :form="form" :filter-options="filterOptions" :urls="urls" @apply="applyRecordFilters" @reset="resetRecordFilters" @export="openExportModal" />
                    <RecordsTable :renewal-records="renewalRecords" :quick-action-url="urls.quickAction" :compact-mode="compactMode" @toggle-compact="compactMode = !compactMode" />
                </template>
            </div>
        </div>

        <!-- Export Modal Dialog -->
        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs" @click.self="closeExportModal">
            <section class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                    <div>
                        <p class="text-[0.62rem] font-bold uppercase tracking-wider text-slate-400">Renewal Reporting</p>
                        <h3 class="text-sm font-bold text-slate-900">Export Renewal Summary</h3>
                    </div>
                    <button
                        type="button"
                        aria-label="Close export dialog"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        @click="closeExportModal"
                    >
                        <span class="text-lg leading-none">&times;</span>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <!-- Format Toggle -->
                    <div>
                        <span class="block text-[0.62rem] font-bold uppercase tracking-wider text-slate-500 mb-1.5">File Format</span>
                        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                            <button
                                type="button"
                                class="inline-flex h-8 items-center justify-center rounded-md px-4 text-xs font-bold transition"
                                :class="exportFormat === 'pdf' ? 'bg-[#003629] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                @click="setExportFormat('pdf')"
                            >
                                PDF Document
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-8 items-center justify-center rounded-md px-4 text-xs font-bold transition"
                                :class="exportFormat === 'xlsx' ? 'bg-[#003629] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                @click="setExportFormat('xlsx')"
                            >
                                Excel Spreadsheet
                            </button>
                        </div>
                    </div>

                    <!-- Column Selector -->
                    <div class="rounded-xl border border-slate-200 bg-[#f9fbfa] p-3.5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-800">Export Columns</span>
                            <span class="text-[0.68rem] text-slate-500">{{ selectedExportColumns.length }} selected</span>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <label
                                v-for="column in activeExportColumns"
                                :key="column.value"
                                class="flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-800 cursor-pointer transition hover:border-[#003629]/40"
                            >
                                <input
                                    :checked="selectedExportColumns.includes(column.value)"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-[#003629] focus:ring-[#003629]"
                                    @change="toggleExportColumn(column.value)"
                                >
                                <span class="font-medium truncate">{{ column.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Report Preview -->
                    <div class="rounded-xl border border-slate-200 bg-[#f9fbfa] p-3.5">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <span class="text-xs font-bold text-slate-800">Preview</span>
                            <a :href="exportPreviewUrl" target="_blank" rel="noopener" class="text-xs font-bold text-[#003629] hover:underline">Open in new tab &rarr;</a>
                        </div>
                        <iframe v-if="exportFormat === 'pdf'" :src="exportPreviewUrl" title="Renewal report preview" class="h-48 w-full rounded-lg border border-slate-200 bg-white"></iframe>
                        <div v-else class="rounded-lg border border-slate-200 bg-white p-4 text-xs text-slate-600">
                            <p class="font-bold text-slate-900">Excel Export Configuration</p>
                            <p class="mt-1">The spreadsheet will include all records matching your active filters and selected columns:</p>
                            <p class="mt-2 font-mono text-[0.7rem] text-slate-500">{{ selectedExportColumnLabels.join(', ') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-5 flex gap-2 justify-end border-t border-slate-100 pt-3.5">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                        @click="closeExportModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-[#003629] px-5 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a] active:scale-95"
                        @click="startExport"
                    >
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm4.75 6.75a.75.75 0 0 1 1.5 0v3.44l1.22-1.22a.75.75 0 1 1 1.06 1.06l-2.5 2.5a.75.75 0 0 1-1.06 0l-2.5-2.5a.75.75 0 1 1 1.06-1.06l1.22 1.22V8.75Z" clip-rule="evenodd" />
                        </svg>
                        <span>Export {{ exportFormat === 'pdf' ? 'PDF' : 'Excel' }}</span>
                    </button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
