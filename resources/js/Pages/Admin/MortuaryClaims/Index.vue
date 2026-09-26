<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import ClaimsTable from '../../../Components/Admin/MortuaryClaims/ClaimsTable.vue';
import IndexHero from '../../../Components/Admin/MortuaryClaims/IndexHero.vue';
import RecordsFilters from '../../../Components/Admin/MortuaryClaims/RecordsFilters.vue';
import SectionTabs from '../../../Components/Admin/MortuaryClaims/SectionTabs.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const queueExportColumns = [
    { value: 'farmer_code', label: 'Farmer Code' },
    { value: 'full_name', label: 'Full Name' },
    { value: 'barangay', label: 'Barangay' },
    { value: 'association', label: 'Association' },
    { value: 'member_type', label: 'Member Type' },
    { value: 'ledger_year', label: 'Ledger Year' },
    { value: 'contribution_years', label: 'Contribution Years' },
    { value: 'claim_amount', label: 'Expected Claim' },
    { value: 'status', label: 'Status' },
];

const recordsExportColumns = [
    { value: 'claim_reference', label: 'Reference' },
    { value: 'claim_date', label: 'Claim Date' },
    { value: 'full_name', label: 'Full Name' },
    { value: 'farmer_code', label: 'Farmer Code' },
    { value: 'barangay', label: 'Barangay' },
    { value: 'member_type', label: 'Member Type' },
    { value: 'ledger_year', label: 'Ledger Year' },
    { value: 'payment_status', label: 'Payment Status' },
    { value: 'claim_amount', label: 'Claim Amount' },
    { value: 'status', label: 'Status' },
    { value: 'filed_by', label: 'Filed By' },
];

const props = defineProps({
    activeSection: { type: String, required: true },
    claims: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
    pageTitle: { type: String, required: true },
    pageSubtitle: { type: String, required: true },
});

const form = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    year: props.filters.year || '',
    barangay_id: props.filters.barangay_id || '',
});

const exportModalOpen = ref(false);
const exportFormat = ref('pdf');
const selectedExportColumns = reactive(
    props.activeSection === 'records'
        ? ['claim_reference', 'claim_date', 'full_name', 'claim_amount', 'status']
        : ['farmer_code', 'full_name', 'barangay', 'claim_amount', 'status']
);

const activeExportColumns = computed(() => props.activeSection === 'records' ? recordsExportColumns : queueExportColumns);
const exportPreviewUrl = computed(() => {
    const url = new URL(exportReportUrl.value);
    url.searchParams.set('preview', '1');
    return url.toString();
});
const selectedExportColumnLabels = computed(() => activeExportColumns.value.filter((column) => selectedExportColumns.includes(column.value)).map((column) => column.label));

const exportReportUrl = computed(() => {
    const url = new URL(props.urls.report, window.location.origin);

    url.searchParams.set('section', props.activeSection);

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

function applyFilters() {
    router.get(props.activeSection === 'records' ? props.urls.records : props.urls.queue, { ...form }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    form.search = '';
    form.status = '';
    form.year = '';
    form.barangay_id = '';
    applyFilters();
}

function openExportModal(format = 'pdf') {
    exportFormat.value = format;
    syncExportColumns();
    exportModalOpen.value = true;
}

function closeExportModal() {
    exportModalOpen.value = false;
}

function setExportFormat(format) {
    exportFormat.value = format;
}

function syncExportColumns() {
    const allowed = activeExportColumns.value.map((column) => column.value);
    const next = selectedExportColumns.filter((column) => allowed.includes(column));

    if (next.length === 0) {
        next.push(...allowed.slice(0, Math.min(allowed.length, 5)));
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
        <div class="mx-auto max-w-[1536px] space-y-4">
            <IndexHero :page-title="pageTitle" :page-subtitle="pageSubtitle" :summary="summary" :urls="urls" @export="openExportModal" />

            <div class="space-y-3">
                <SectionTabs :active-section="activeSection" :urls="urls" :summary="summary" />
                <RecordsFilters :form="form" :filter-options="filterOptions" :active-section="activeSection" :urls="urls" @apply="applyFilters" @reset="resetFilters" @export="openExportModal" />
                <ClaimsTable :claims="claims" :active-section="activeSection" />
            </div>
        </div>

        <!-- Modernized Export Modal -->
        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4" @click.self="closeExportModal">
            <section class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-[#dde4de] bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between border-b border-[#f1f5f9] pb-3.5">
                    <div class="flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-[#014d3c]"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-[#0f172a]">Export Mortuary Report</h3>
                    </div>
                    <button type="button" aria-label="Close export dialog" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-[#dde4de] text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]" @click="closeExportModal">
                        <span class="text-base leading-none">&times;</span>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <!-- Format Pills -->
                    <div>
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b] block mb-1.5">File Format</span>
                        <div class="inline-flex rounded-lg border border-[#dde4de] bg-[#f8fafc] p-0.5 text-xs font-semibold">
                            <button
                                type="button"
                                class="rounded-md px-3.5 py-1.5 transition"
                                :class="exportFormat === 'pdf' ? 'bg-[#014d3c] font-bold text-white shadow-xs' : 'text-[#64748b] hover:text-[#0f172a]'"
                                @click="setExportFormat('pdf')"
                            >
                                PDF Document
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3.5 py-1.5 transition"
                                :class="exportFormat === 'xlsx' ? 'bg-[#014d3c] font-bold text-white shadow-xs' : 'text-[#64748b] hover:text-[#0f172a]'"
                                @click="setExportFormat('xlsx')"
                            >
                                Excel Spreadsheet
                            </button>
                        </div>
                    </div>

                    <!-- Column Selector -->
                    <div class="rounded-xl border border-[#dde4de] bg-[#f8fafc] p-3.5">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-[#0f172a]">Included Columns</p>
                            <p class="text-[0.68rem] text-[#64748b]">{{ selectedExportColumns.length }} selected</p>
                        </div>
                        <div class="mt-2.5 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            <label
                                v-for="column in activeExportColumns"
                                :key="column.value"
                                class="flex h-9 cursor-pointer items-center gap-2 rounded-lg border bg-white px-3 text-xs transition"
                                :class="selectedExportColumns.includes(column.value) ? 'border-[#014d3c]/40 bg-[#f0fdf4]/30 text-[#014d3c]' : 'border-[#dde4de] text-[#475569] hover:border-[#cbd5e1]'"
                            >
                                <input
                                    :checked="selectedExportColumns.includes(column.value)"
                                    type="checkbox"
                                    class="h-3.5 w-3.5 rounded border-slate-300 text-[#014d3c] focus:ring-[#014d3c]"
                                    @change="toggleExportColumn(column.value)"
                                >
                                <span class="font-medium text-xs truncate">{{ column.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Preview Frame -->
                    <div class="rounded-xl border border-[#dde4de] bg-[#f8fafc] p-3.5">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-xs font-bold text-[#0f172a]">Report Preview</p>
                            <a :href="exportPreviewUrl" target="_blank" rel="noopener" class="text-xs font-semibold text-[#014d3c] hover:underline">Open Full Tab &nearr;</a>
                        </div>
                        <iframe v-if="exportFormat === 'pdf'" :src="exportPreviewUrl" title="Mortuary report preview" class="h-60 w-full rounded-lg border border-[#dde4de] bg-white"></iframe>
                        <div v-else class="rounded-lg border border-[#dde4de] bg-white p-4 text-xs text-[#475569]">
                            <p class="font-bold text-[#0f172a]">Excel Export Configuration</p>
                            <p class="mt-1">The spreadsheet will include the filtered records with columns:</p>
                            <p class="mt-1 font-mono text-[0.68rem] text-[#014d3c]">{{ selectedExportColumnLabels.join(', ') }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2 border-t border-[#f1f5f9] pt-4">
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-4 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]" @click="closeExportModal">
                        Cancel
                    </button>
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97]" @click="startExport">
                        Download {{ exportFormat === 'pdf' ? 'PDF' : 'Excel' }}
                    </button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
