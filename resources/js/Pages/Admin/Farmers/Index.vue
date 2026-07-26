<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import RegistryFilters from '../../../Components/Admin/Farmers/RegistryFilters.vue';
import RegistryHero from '../../../Components/Admin/Farmers/RegistryHero.vue';
import RegistrySummaryGrid from '../../../Components/Admin/Farmers/RegistrySummaryGrid.vue';
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

const frozenSummary = {
    total: props.summary.total,
    active: props.summary.active,
    inactive: props.summary.inactive,
    deceased: props.summary.deceased,
    duplicates: props.summary.duplicates,
    incompleteProfiles: props.summary.incompleteProfiles,
    invalidMobileNumbers: props.summary.invalidMobileNumbers,
    barangayAssociationMismatches: props.summary.barangayAssociationMismatches,
    inactiveForReview: props.summary.inactiveForReview,
};

const form = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    barangay_id: props.filters.barangay_id || '',
    member_type_id: props.filters.member_type_id || '',
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
    form.member_type_id = '';
    form.quality = '';
    applyFilters();
}

const activeFilterCount = computed(() => [form.search, form.status, form.barangay_id, form.member_type_id, form.quality].filter(Boolean).length);
const totalPages = computed(() => props.farmers.last_page || 1);
const selectedCount = computed(() => selectedIds.value.length);
const filteredTargetCount = computed(() => props.farmers.total || 0);
const allCurrentPageSelected = computed(() => props.farmers.data.length > 0 && props.farmers.data.every((farmer) => selectedIds.value.includes(farmer.id)));

const qualityCards = computed(() => [
    { value: 'duplicate', label: 'Duplicate Detection', count: frozenSummary.duplicates, tone: 'border-[#ead7b2] bg-[#fff8ea] text-[#996515]' },
    { value: 'incomplete_profile', label: 'Incomplete Profiles', count: frozenSummary.incompleteProfiles, tone: 'border-[#d5e4da] bg-[#f3faf6] text-[#245342]' },
    { value: 'invalid_mobile', label: 'Invalid Mobile Numbers', count: frozenSummary.invalidMobileNumbers, tone: 'border-[#edd5cf] bg-[#fff4f1] text-[#a44d3f]' },
    { value: 'barangay_association_mismatch', label: 'Barangay Mismatch', count: frozenSummary.barangayAssociationMismatches, tone: 'border-[#d8daf5] bg-[#f4f5ff] text-[#4655a4]' },
    { value: 'inactive_review', label: 'Inactive Needing Review', count: frozenSummary.inactiveForReview, tone: 'border-[#e2d4db] bg-[#fbf5f8] text-[#8d4663]' },
]);

const assignmentAssociations = computed(() => {
    if (!assignmentForm.barangay_id) {
        return props.filterOptions.associations || [];
    }

    return (props.filterOptions.associations || []).filter((association) => String(association.barangay_id) === String(assignmentForm.barangay_id));
});

function setQualityFilter(value) {
    form.quality = form.quality === value ? '' : value;
    applyFilters();
}

function setStatusFilter(value) {
    form.status = value || '';
    applyFilters();
}

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
        <div class="space-y-7">
            <RegistryHero
                :total="frozenSummary.total"
                :create-url="createApplicationUrl"
                @export="openExportModal"
            />

            <RegistrySummaryGrid :summary="frozenSummary" @filter-status="setStatusFilter" />

            <RegistryFilters
                :form="form"
                :filter-options="filterOptions"
                :active-filter-count="activeFilterCount"
                :total-pages="totalPages"
                :filtered-target-count="filteredTargetCount"
                :quality-cards="qualityCards"
                @apply="applyFilters"
                @reset="resetFilters"
                @open-export="openExportModal"
                @set-quality-filter="setQualityFilter"
            />

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

        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/45 px-4 py-6" @click.self="closeExportModal">
            <section class="w-full max-w-6xl rounded-[28px] border border-[#dbe2de] bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.22)] sm:p-6">
                <div class="flex flex-col gap-3 border-b border-[#e4ebe7] pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Export Columns</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-sm text-[#697772]">{{ selectedExportColumns.length }} column{{ selectedExportColumns.length === 1 ? '' : 's' }} selected</p>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-[#d7e0db] text-[#66756f] transition hover:bg-[#f5f8f6]" @click="closeExportModal">
                            <span class="text-lg leading-none">&times;</span>
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <label
                        v-for="column in availableExportColumns"
                        :key="column.value"
                        class="flex items-center gap-3 rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420]"
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

        <div v-if="bulkModal" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/45 px-4 py-6" @click.self="closeBulkModal">
            <section class="w-full max-w-3xl rounded-[28px] border border-[#dbe2de] bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.22)]">
                <div class="flex items-start justify-between gap-4 border-b border-[#e4ebe7] pb-4">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Batch Operation</p>
                        <h2 class="mt-1 text-xl font-bold text-[#1a2420]">
                            {{
                                bulkModal === 'notify' ? 'Notify farmers'
                                    : bulkModal === 'follow_up' ? 'Mark for follow-up'
                                        : bulkModal === 'status' ? 'Status review'
                                        : bulkModal === 'assign' ? 'Assign records'
                                            : 'Archive old records'
                            }}
                        </h2>
                        <p class="mt-2 text-sm text-[#697772]">
                            Targeting {{ bulkScope === 'selected' ? `${selectedCount} selected` : `${filteredTargetCount} filtered` }} record{{ (bulkScope === 'selected' ? selectedCount : filteredTargetCount) === 1 ? '' : 's' }}.
                        </p>
                    </div>
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-[#d7e0db] text-[#66756f] transition hover:bg-[#f5f8f6]" @click="closeBulkModal">
                        <span class="text-lg leading-none">&times;</span>
                    </button>
                </div>

                <div v-if="bulkModal === 'notify'" class="mt-5 space-y-4">
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Subject</span>
                        <input v-model="notifyForm.subject" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Message</span>
                        <textarea v-model="notifyForm.message" rows="5" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none"></textarea>
                    </label>
                </div>

                <div v-else-if="bulkModal === 'follow_up'" class="mt-5 space-y-4">
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Follow-up Note</span>
                        <textarea v-model="followUpForm.note" rows="5" placeholder="Waiting for barangay confirmation, verify missing contact details, call back this week..." class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none"></textarea>
                    </label>
                </div>

                <div v-else-if="bulkModal === 'status'" class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Review Status</span>
                        <select v-model="statusReviewForm.review_status" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                            <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Inactive Reason</span>
                        <input v-model="statusReviewForm.inactive_reason" type="text" placeholder="Required for inactive or deceased reviews" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                    </label>
                </div>

                <div v-else-if="bulkModal === 'assign'" class="mt-5 grid gap-4 md:grid-cols-3">
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Barangay</span>
                        <select v-model="assignmentForm.barangay_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                            <option value="">Keep current</option>
                            <option v-for="barangay in filterOptions.barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Association</span>
                        <select v-model="assignmentForm.association_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                            <option value="">Keep current</option>
                            <option v-for="association in assignmentAssociations" :key="association.id" :value="String(association.id)">{{ association.name }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Member Type</span>
                        <select v-model="assignmentForm.member_type_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                            <option value="">Keep current</option>
                            <option v-for="memberType in filterOptions.memberTypes" :key="memberType.id" :value="String(memberType.id)">{{ memberType.code }} - {{ memberType.name }}</option>
                        </select>
                    </label>
                </div>

                <div v-else class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Registered Before</span>
                        <input v-model="archiveForm.registered_before" type="date" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Archive Reason</span>
                        <input v-model="archiveForm.archive_reason" type="text" placeholder="Archived old registry record." class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                    </label>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-5 py-3 text-sm font-bold text-[#697772] transition hover:bg-[#f4f7f5]" @click="closeBulkModal">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-2xl bg-[#003629] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637] disabled:cursor-not-allowed disabled:opacity-60"
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
