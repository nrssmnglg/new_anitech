<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import RegistryFilters from '../../../Components/Admin/Farmers/RegistryFilters.vue';
import RegistryHero from '../../../Components/Admin/Farmers/RegistryHero.vue';
import RegistryTable from '../../../Components/Admin/Farmers/RegistryTable.vue';

const availableExportColumns = [
    { value: 'farmer_code', label: 'Farmer Code' },
    { value: 'full_name', label: 'Full Name' },
    { value: 'status', label: 'Status' },
    { value: 'member_type', label: 'Member Type' },
    { value: 'barangay', label: 'Barangay' },
    { value: 'gender', label: 'Gender' },
    { value: 'birth_date', label: 'Birth Date' },
    { value: 'mobile_number', label: 'Mobile Number' },
    { value: 'address', label: 'Address' },
    { value: 'registered_at', label: 'Registered At' },
];

const props = defineProps({
    farmers: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    summary: { type: Object, required: true },
    createApplicationUrl: { type: String, required: true },
    exportBaseUrl: { type: String, required: true },
    resetUrl: { type: String, required: true },
    bulkActionUrls: { type: Object, required: true },
    bulkPermissions: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const flashError = computed(() => page.props.flash?.error || '');

const optionValueForId = (options, id) => {
    if (!id) {
        return '';
    }

    return String((options || []).find((option) => String(option.id) === String(id))?.key || id);
};

const form = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    barangay_id: optionValueForId(props.filterOptions.barangays, props.filters.barangay_id),
    association_id: optionValueForId(props.filterOptions.associations, props.filters.association_id),
    member_type_id: optionValueForId(props.filterOptions.memberTypes, props.filters.member_type_id),
    quality: props.filters.quality || '',
});

const selectedExportColumns = reactive([
    'farmer_code',
    'full_name',
    'status',
    'member_type',
    'barangay',
    'gender',
    'mobile_number',
    'registered_at',
]);
const exportModalOpen = ref(false);
const exportFormat = ref('pdf');
const selectedIds = ref([]);
const bulkModal = ref(null);
const bulkScope = ref('selected');
const processingBulk = ref(false);
const notifyForm = reactive({ subject: '', message: '' });
const statusReviewForm = reactive({ review_status: 'pending', inactive_reason: '' });
const assignmentForm = reactive({ barangay_id: '', association_id: '', member_type_id: '' });
const followUpForm = reactive({ note: '' });
const archiveForm = reactive({ registered_before: '', archive_reason: '' });

function applyFilters() {
    selectedIds.value = [];
    bulkScope.value = 'selected';

    router.get(props.resetUrl, { ...form }, {
        preserveState: false,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    form.search = '';
    form.status = '';
    form.barangay_id = '';
    form.association_id = '';
    form.member_type_id = '';
    form.quality = '';
    applyFilters();
}

const activeFilterCount = computed(() => [form.search, form.status, form.barangay_id, form.association_id, form.member_type_id, form.quality].filter(Boolean).length);
const totalPages = computed(() => props.farmers.last_page || 1);
const selectedCount = computed(() => selectedIds.value.length);
const filteredTargetCount = computed(() => props.farmers.total || 0);
const allCurrentPageSelected = computed(() => props.farmers.data.length > 0 && props.farmers.data.every((farmer) => selectedIds.value.includes(farmer.id)));
const exportPreviewUrl = computed(() => {
    const url = new URL(buildExportUrl(exportFormat.value));
    url.searchParams.set('preview', '1');
    return url.toString();
});
const selectedExportColumnLabels = computed(() => availableExportColumns.filter((column) => selectedExportColumns.includes(column.value)).map((column) => column.label));

const assignmentAssociations = computed(() => {
    if (!assignmentForm.barangay_id) {
        return props.filterOptions.associations || [];
    }

    return (props.filterOptions.associations || []).filter((association) => String(association.barangay_key || association.barangay_id) === String(assignmentForm.barangay_id));
});

function toggleSelection(id) {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((value) => value !== id);
        return;
    }

    selectedIds.value = [...selectedIds.value, id];
}

function toggleSelectAllCurrentPage() {
    if (allCurrentPageSelected.value) {
        const pageIds = props.farmers.data.map((farmer) => farmer.id);
        selectedIds.value = selectedIds.value.filter((id) => !pageIds.includes(id));
        return;
    }

    selectedIds.value = [...new Set([...selectedIds.value, ...props.farmers.data.map((farmer) => farmer.id)])];
}

function buildExportUrl(format) {
    const url = new URL(props.exportBaseUrl, window.location.origin);
    const params = {
        search: form.search || '',
        status: form.status || '',
        barangay_id: form.barangay_id || '',
        association_id: form.association_id || '',
        member_type_id: form.member_type_id || '',
        quality: form.quality || '',
        format,
    };

    Object.entries(params).forEach(([key, value]) => {
        if (value) {
            url.searchParams.set(key, value);
        }
    });

    selectedExportColumns.forEach((column) => {
        url.searchParams.append('columns[]', column);
    });

    return url.toString();
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

function openExportModal(format) {
    exportFormat.value = format;
    exportModalOpen.value = true;
}

function closeExportModal() {
    exportModalOpen.value = false;
}

function startExport() {
    window.location.href = buildExportUrl(exportFormat.value);
    closeExportModal();
}

function openBulkModal(type) {
    bulkModal.value = type;
}

function closeBulkModal() {
    bulkModal.value = null;
}

function bulkPayload(extra = {}) {
    return {
        scope: bulkScope.value,
        selected_ids: selectedIds.value,
        search: form.search || '',
        status: form.status || '',
        barangay_id: form.barangay_id || '',
        barangay_id_filter: form.barangay_id || '',
        association_id: form.association_id || '',
        association_id_filter: form.association_id || '',
        member_type_id: form.member_type_id || '',
        member_type_id_filter: form.member_type_id || '',
        quality: form.quality || '',
        ...extra,
    };
}

function submitBulk(url, extra) {
    processingBulk.value = true;

    router.post(url, bulkPayload(extra), {
        preserveScroll: true,
        onFinish: () => {
            processingBulk.value = false;
        },
        onSuccess: () => {
            selectedIds.value = [];
            closeBulkModal();
        },
    });
}

function submitBulkNotify() {
    submitBulk(props.bulkActionUrls.notify, {
        subject: notifyForm.subject,
        message: notifyForm.message,
    });
}

function submitBulkStatusReview() {
    submitBulk(props.bulkActionUrls.statusReview, {
        review_status: statusReviewForm.review_status,
        inactive_reason: statusReviewForm.inactive_reason,
    });
}

function submitBulkAssign() {
    submitBulk(props.bulkActionUrls.assign, {
        barangay_id: assignmentForm.barangay_id || null,
        association_id: assignmentForm.association_id || null,
        member_type_id: assignmentForm.member_type_id || null,
    });
}

function submitBulkFollowUp() {
    submitBulk(props.bulkActionUrls.followUp, {
        note: followUpForm.note,
    });
}

function submitBulkArchive() {
    submitBulk(props.bulkActionUrls.archive, {
        registered_before: archiveForm.registered_before || null,
        archive_reason: archiveForm.archive_reason,
    });
}
</script>

<template>
    <Head title="Farmer Registry" />

    <AdminLayout title="Farmer Registry">
        <div class="space-y-4">
            <!-- Flash Alerts -->
            <section v-if="flashSuccess" class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
                <svg viewBox="0 0 16 16" class="h-4 w-4 shrink-0 text-emerald-600" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.844-8.791a.75.75 0 0 0-1.188-.918l-3.7 4.79-1.649-1.833a.75.75 0 1 0-1.114 1.004l2.25 2.5a.75.75 0 0 0 1.151-.043l4.25-5.5Z" clip-rule="evenodd"/></svg>
                <span>{{ flashSuccess }}</span>
            </section>
            <section v-if="flashError" class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-800">
                <svg viewBox="0 0 16 16" class="h-4 w-4 shrink-0 text-rose-600" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14ZM8 4a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                <span>{{ flashError }}</span>
            </section>

            <!-- Hero Section -->
            <RegistryHero
                :create-url="createApplicationUrl"
                :summary="summary"
                :current-status="form.status"
                @export="openExportModal"
                @filter-status="(status) => { form.status = status; applyFilters(); }"
            />

            <!-- Filter Controls -->
            <RegistryFilters
                :form="form"
                :filter-options="filterOptions"
                :active-filter-count="activeFilterCount"
                :total-pages="totalPages"
                :filtered-target-count="filteredTargetCount"
                @apply="applyFilters"
                @reset="resetFilters"
                @open-export="openExportModal"
            />

            <!-- Data Table & Bulk Actions -->
            <RegistryTable
                :farmers="farmers"
                :total-pages="totalPages"
                :selected-ids="selectedIds"
                :all-current-page-selected="allCurrentPageSelected"
                :selected-count="selectedCount"
                :filtered-target-count="filteredTargetCount"
                :bulk-scope="bulkScope"
                :bulk-permissions="bulkPermissions"
                @toggle-selection="toggleSelection"
                @toggle-select-all="toggleSelectAllCurrentPage"
                @open-bulk-modal="openBulkModal"
                @update-bulk-scope="bulkScope = $event"
                @clear-selection="selectedIds = []"
            />
        </div>

        <!-- Export Modal -->
        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f172a]/50 px-4 py-5 backdrop-blur-sm" @click.self="closeExportModal">
            <section class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl border border-[#e2e8f0] bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-[#f1f5f9] pb-4">
                    <div>
                        <h2 class="text-sm font-bold text-[#0f172a]">Export Farmer Registry</h2>
                        <p class="mt-0.5 text-xs text-[#94a3b8]">{{ selectedExportColumns.length }} columns selected for {{ exportFormat === 'pdf' ? 'PDF report' : 'Excel spreadsheet' }}</p>
                    </div>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#94a3b8] transition hover:bg-[#f1f5f9] hover:text-[#64748b]" @click="closeExportModal">
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    </button>
                </div>

                <div class="mt-4">
                    <p class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Select Columns</p>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                        <label
                            v-for="column in availableExportColumns"
                            :key="column.value"
                            class="flex min-h-10 cursor-pointer items-center gap-2.5 rounded-xl border border-[#e2e8f0] bg-[#f8fafc] px-3 py-2 text-xs text-[#0f172a] transition-all hover:border-[#cbd5e1] hover:bg-[#f1f5f9]"
                        >
                            <input
                                :checked="selectedExportColumns.includes(column.value)"
                                type="checkbox"
                                class="h-3.5 w-3.5 rounded border-slate-300 text-[#014d3c] focus:ring-[#014d3c]"
                                @change="toggleExportColumn(column.value)"
                            >
                            <span class="font-semibold">{{ column.label }}</span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-xs font-bold text-[#0f172a]">Report Preview</p>
                        <a :href="exportPreviewUrl" target="_blank" rel="noopener" class="text-xs font-bold text-[#014d3c] hover:underline">Open in new tab</a>
                    </div>
                    <iframe v-if="exportFormat === 'pdf'" :src="exportPreviewUrl" title="Farmer registry report preview" class="h-64 w-full rounded-lg border border-[#e2e8f0] bg-white"></iframe>
                    <div v-else class="rounded-lg border border-[#e2e8f0] bg-white p-5 text-sm text-[#475569]">
                        <p class="font-bold text-[#0f172a]">Spreadsheet Export</p>
                        <p class="mt-1 text-xs text-[#64748b]">The generated file will include {{ filteredTargetCount }} filtered record{{ filteredTargetCount === 1 ? '' : 's' }}:</p>
                        <p class="mt-2 text-xs font-medium text-[#334155]">{{ selectedExportColumnLabels.join(', ') }}</p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t border-[#f1f5f9] pt-4">
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-[#e2e8f0] bg-white px-4 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f9]" @click="closeExportModal">
                        Cancel
                    </button>
                    <button type="button" class="inline-flex h-9 items-center gap-1.5 justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97]" @click="startExport">
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
                        Export {{ exportFormat === 'pdf' ? 'PDF' : 'Excel' }}
                    </button>
                </div>
            </section>
        </div>

        <!-- Bulk Action Modal -->
        <div v-if="bulkModal" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f172a]/50 px-4 py-6 backdrop-blur-sm" @click.self="closeBulkModal">
            <section class="w-full max-w-2xl rounded-2xl border border-[#e2e8f0] bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-[#f1f5f9] pb-4">
                    <div>
                        <span class="inline-flex rounded-lg bg-[#014d3c]/10 px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-wider text-[#014d3c]">Batch Operation</span>
                        <h2 class="mt-1.5 text-lg font-bold text-[#0f172a]">
                            {{
                                bulkModal === 'notify' ? 'Send Notification'
                                    : bulkModal === 'follow_up' ? 'Mark for Follow-up'
                                        : bulkModal === 'status' ? 'Review Membership Status'
                                            : bulkModal === 'assign' ? 'Assign Records'
                                                : 'Archive Records'
                            }}
                        </h2>
                        <p class="mt-1 text-xs text-[#64748b]">
                            Targeting <strong class="text-[#0f172a]">{{ bulkScope === 'selected' ? `${selectedCount} selected` : `${filteredTargetCount} filtered` }}</strong> record{{ (bulkScope === 'selected' ? selectedCount : filteredTargetCount) === 1 ? '' : 's' }}.
                        </p>
                    </div>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#94a3b8] transition hover:bg-[#f1f5f9] hover:text-[#64748b]" @click="closeBulkModal">
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    </button>
                </div>

                <div v-if="bulkModal === 'notify'" class="mt-4 space-y-3">
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Subject</span>
                        <input v-model="notifyForm.subject" type="text" placeholder="e.g. Important notice regarding renewal" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                    </label>
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Message Content</span>
                        <textarea v-model="notifyForm.message" rows="4" placeholder="Enter message to deliver to the selected farmers..." class="w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] p-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"></textarea>
                    </label>
                </div>

                <div v-else-if="bulkModal === 'follow_up'" class="mt-4 space-y-3">
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Follow-up Note</span>
                        <textarea v-model="followUpForm.note" rows="4" placeholder="e.g. Waiting for barangay confirmation, verify missing mobile number, callback scheduled..." class="w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] p-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"></textarea>
                    </label>
                </div>

                <div v-else-if="bulkModal === 'status'" class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Review Status</span>
                        <select v-model="statusReviewForm.review_status" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                            <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                    </label>
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Inactive Reason</span>
                        <input v-model="statusReviewForm.inactive_reason" type="text" placeholder="Required for inactive or deceased" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                    </label>
                </div>

                <div v-else-if="bulkModal === 'assign'" class="mt-4 grid gap-3 sm:grid-cols-3">
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Barangay</span>
                        <select v-model="assignmentForm.barangay_id" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                            <option value="">Keep current</option>
                            <option v-for="barangay in filterOptions.barangays" :key="barangay.key || barangay.id" :value="String(barangay.key || barangay.id)">{{ barangay.name }}</option>
                        </select>
                    </label>
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Association</span>
                        <select v-model="assignmentForm.association_id" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                            <option value="">Keep current</option>
                            <option v-for="association in assignmentAssociations" :key="association.key || association.id" :value="String(association.key || association.id)">{{ association.name }}</option>
                        </select>
                    </label>
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Member Type</span>
                        <select v-model="assignmentForm.member_type_id" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                            <option value="">Keep current</option>
                            <option v-for="memberType in filterOptions.memberTypes" :key="memberType.key || memberType.id" :value="String(memberType.key || memberType.id)">{{ memberType.code }} - {{ memberType.name }}</option>
                        </select>
                    </label>
                </div>

                <div v-else class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Registered Before</span>
                        <input v-model="archiveForm.registered_before" type="date" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                    </label>
                    <label class="block space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Archive Reason</span>
                        <input v-model="archiveForm.archive_reason" type="text" placeholder="e.g. Inactive for multiple years" class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10">
                    </label>
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t border-[#f1f5f9] pt-4">
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-[#e2e8f0] bg-white px-4 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f9]" @click="closeBulkModal">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="processingBulk || (bulkScope === 'selected' && selectedCount === 0)"
                        @click="bulkModal === 'notify' ? submitBulkNotify() : bulkModal === 'follow_up' ? submitBulkFollowUp() : bulkModal === 'status' ? submitBulkStatusReview() : bulkModal === 'assign' ? submitBulkAssign() : submitBulkArchive()"
                    >
                        {{ processingBulk ? 'Processing...' : 'Apply Action' }}
                    </button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
