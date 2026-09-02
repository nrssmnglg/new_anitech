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
        <div class="space-y-4">
            <IndexHero :page-title="pageTitle" :page-subtitle="pageSubtitle" :summary="summary" :urls="urls" @export="openExportModal" />

            <div class="space-y-3">
                <SectionTabs :active-section="activeSection" :urls="urls" />
                <RecordsFilters :form="form" :filter-options="filterOptions" :active-section="activeSection" :urls="urls" @apply="applyFilters" @reset="resetFilters" @export="openExportModal" />
                <ClaimsTable :claims="claims" :active-section="activeSection" />
            </div>
        </div>

        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/50 p-4" @click.self="closeExportModal">
            <section class="w-full max-w-2xl rounded-xl border border-[#dbe2de] bg-white p-4 shadow-[0_20px_55px_rgba(15,23,42,0.2)]">
                <div class="flex items-center justify-between border-b border-[#e4ebe7] pb-3">
                    <p class="text-[0.62rem] font-semibold uppercase tracking-[0.1em] text-[#7a8781]">Export report</p>
                    <button type="button" aria-label="Close export dialog" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d7e0db] text-[#66756f] transition hover:bg-[#f5f8f6]" @click="closeExportModal">
                        <span class="text-lg leading-none">&times;</span>
                    </button>
                </div>

                <div class="mt-3 space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="inline-flex h-8 items-center justify-center rounded-md border px-3 text-xs font-semibold transition"
                            :class="exportFormat === 'pdf' ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#c8d8cf] bg-white text-[#003629] hover:bg-[#f4f7f5]'"
                            @click="setExportFormat('pdf')"
                        >
                            PDF
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-8 items-center justify-center rounded-md border px-3 text-xs font-semibold transition"
                            :class="exportFormat === 'xlsx' ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#c8d8cf] bg-white text-[#003629] hover:bg-[#f4f7f5]'"
                            @click="setExportFormat('xlsx')"
                        >
                            Excel
                        </button>
                    </div>

                    <div class="rounded-lg border border-[#d7e0db] bg-[#f8faf9] p-3">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs font-semibold text-[#1a2420]">Columns</p>
                            <p class="text-[0.68rem] text-[#697772]">{{ selectedExportColumns.length }} selected</p>
                        </div>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            <label
                                v-for="column in activeExportColumns"
                                :key="column.value"
                                class="flex h-9 items-center gap-2 rounded-md border border-[#d7e0db] bg-white px-3 text-xs text-[#1a2420]"
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

                <div class="mt-4 flex gap-2 sm:justify-end">
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-[#d7e0db] px-3 text-xs font-semibold text-[#697772] transition hover:bg-[#f4f7f5]" @click="closeExportModal">
                        Cancel
                    </button>
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-md bg-[#003629] px-4 text-xs font-semibold text-white transition hover:bg-[#0d4637]" @click="startExport">
                        Export {{ exportFormat === 'pdf' ? 'PDF' : 'Excel' }}
                    </button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
