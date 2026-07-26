<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const availableExportColumns = [
    { value: 'workflow', label: 'Workflow' },
    { value: 'farmer_name', label: 'Farmer Name' },
    { value: 'farmer_code', label: 'Farmer Code' },
    { value: 'document_label', label: 'Document' },
    { value: 'reference_label', label: 'Reference' },
    { value: 'source_label', label: 'Source' },
    { value: 'status', label: 'Status' },
    { value: 'flag', label: 'Flag' },
    { value: 'uploaded_at', label: 'Uploaded At' },
    { value: 'verified_at', label: 'Verified At' },
    { value: 'expires_at', label: 'Expires At' },
    { value: 'verifier_name', label: 'Verifier' },
    { value: 'remarks', label: 'Remarks' },
    { value: 'validation_notes', label: 'Validation Notes' },
];

const props = defineProps({
    documents: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const form = reactive({
    search: props.filters.search ?? '',
    workflow: props.filters.workflow ?? 'all',
    status: props.filters.status ?? 'all',
    flag: props.filters.flag ?? 'all',
});

const reviewRemarks = reactive(
    Object.fromEntries(props.documents.data.map((document) => [document.id, document.remarks || ''])),
);

const previewDocument = ref(null);
const exportModalOpen = ref(false);
const exportFormat = ref('pdf');
const selectedExportColumns = reactive([
    'workflow',
    'farmer_name',
    'document_label',
    'reference_label',
    'status',
    'flag',
    'uploaded_at',
    'validation_notes',
]);

function applyFilters() {
    router.get(props.urls.index, {
        search: form.search || undefined,
        workflow: form.workflow !== 'all' ? form.workflow : undefined,
        status: form.status !== 'all' ? form.status : undefined,
        flag: form.flag !== 'all' ? form.flag : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    form.search = '';
    form.workflow = 'all';
    form.status = 'all';
    form.flag = 'all';
    applyFilters();
}

function openExportModal(format) {
    exportFormat.value = format;
    exportModalOpen.value = true;
}

function closeExportModal() {
    exportModalOpen.value = false;
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

function buildExportUrl(format) {
    const url = new URL(props.urls.export, window.location.origin);
    const params = {
        search: form.search || '',
        workflow: form.workflow !== 'all' ? form.workflow : '',
        status: form.status !== 'all' ? form.status : '',
        flag: form.flag !== 'all' ? form.flag : '',
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

function startExport() {
    window.location.href = buildExportUrl(exportFormat.value);
    closeExportModal();
}

function submitAction(document, action) {
    router.post(document.actions.reviewUrl, {
        action,
        remarks: reviewRemarks[document.id] || '',
    }, {
        preserveScroll: true,
    });
}

function statusTone(value) {
    if (value === 'verified') return 'bg-[#eef7e3] text-[#416918]';
    if (value === 'rejected') return 'bg-[#ffdad6] text-[#93000a]';
    return 'bg-[#fff3dc] text-[#a86100]';
}

function flagTone(document) {
    if (document.needsResubmission) return 'bg-[#ffe6ea] text-[#b42341]';
    if (document.isExpired) return 'bg-[#fff3dc] text-[#a86100]';
    if (!document.uploadPresent) return 'bg-[#edf1ef] text-[#697772]';
    return 'bg-[#edf5ff] text-[#2f5da8]';
}

function flagLabel(document) {
    if (document.needsResubmission) return 'Re-submission';
    if (document.isExpired) return 'Expired';
    if (!document.uploadPresent) return 'Missing';
    if (document.readyForVerification) return 'Ready';
    return 'Waiting';
}

const previewUrl = computed(() => previewDocument.value?.actions?.viewUrl || '');
const previewMimeType = computed(() => previewDocument.value?.previewMimeType || '');
const previewName = computed(() => String(previewDocument.value?.originalName || ''));
const previewExtension = computed(() => previewName.value.includes('.') ? previewName.value.split('.').pop().toLowerCase() : '');
const previewIsImage = computed(() => previewMimeType.value.startsWith('image/') || ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'].includes(previewExtension.value));
const previewIsPdf = computed(() => previewMimeType.value === 'application/pdf' || previewExtension.value === 'pdf');

function openPreviewInNewTab() {
    if (!previewUrl.value) {
        return;
    }

    window.open(previewUrl.value, '_blank', 'noopener');
}
</script>

<template>
    <Head title="Document Verification Queue" />

    <AdminLayout title="Document Verification Queue">
        <div class="space-y-8 bg-[radial-gradient(circle_at_top_left,_rgba(186,238,217,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(165,213,119,0.12),_transparent_22%)]">
            <section class="relative overflow-hidden rounded-[28px] bg-[#002117] px-6 py-6 text-white shadow-[0_22px_50px_rgba(15,23,42,0.14)] sm:px-7 lg:px-8">
                <div class="absolute inset-0 bg-[radial-gradient(at_0%_0%,_#1b4d3e_0%,_transparent_50%),radial-gradient(at_100%_0%,_#376757_0%,_transparent_48%),radial-gradient(at_100%_100%,_#16332c_0%,_transparent_50%),radial-gradient(at_0%_100%,_#003629_0%,_transparent_48%)]"></div>
                <div class="relative z-10">
                    <div class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-md">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#c0f190] shadow-[0_0_16px_rgba(192,241,144,0.8)]"></span>
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.28em] text-white/85">Verification Workflow</span>
                    </div>
                    <h1 class="mt-4 text-4xl font-black tracking-[-0.045em] sm:text-[2.7rem]">Document Verification Queue</h1>
                    <p class="mt-3 max-w-3xl text-sm text-white/75">Review pending, missing, expired, and rejected documents for both membership applications and renewals from one queue.</p>
                </div>
            </section>

            <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Total Queue</p>
                    <p class="mt-3 text-4xl font-black tracking-[-0.04em] text-[#143c32]">{{ summary.total }}</p>
                </article>
                <article class="rounded-[1.8rem] border border-[#eadfc9] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Pending Review</p>
                    <p class="mt-3 text-4xl font-black tracking-[-0.04em] text-[#a86100]">{{ summary.pending }}</p>
                </article>
                <article class="rounded-[1.8rem] border border-[#dce7cf] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Ready</p>
                    <p class="mt-3 text-4xl font-black tracking-[-0.04em] text-[#416918]">{{ summary.ready }}</p>
                </article>
                <article class="rounded-[1.8rem] border border-[#f2d7df] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Rejected</p>
                    <p class="mt-3 text-4xl font-black tracking-[-0.04em] text-[#b42341]">{{ summary.rejected }}</p>
                </article>
                <article class="rounded-[1.8rem] border border-[#f2dfb2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Expired</p>
                    <p class="mt-3 text-4xl font-black tracking-[-0.04em] text-[#9d6b00]">{{ summary.expired }}</p>
                </article>
            </section>

            <section class="rounded-[24px] border border-[#dfe5e1] bg-[#f2f4f3] p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
                <form class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr_0.8fr_0.8fr_auto]" @submit.prevent="applyFilters">
                    <label class="space-y-2">
                        <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Search</span>
                        <input v-model="form.search" type="text" placeholder="Farmer, code, or document" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Workflow</span>
                        <select v-model="form.workflow" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                            <option v-for="option in filterOptions.workflows" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Status</span>
                        <select v-model="form.status" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                            <option v-for="option in filterOptions.statuses" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Flag</span>
                        <select v-model="form.flag" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                            <option v-for="option in filterOptions.flags" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>
                    <div class="flex items-end gap-2">
                        <button type="button" class="inline-flex h-[48px] items-center justify-center rounded-xl border border-[#cfd7d3] bg-white px-4 text-sm font-bold text-[#697772] transition hover:bg-[#f9fbfa]" @click="resetFilters">
                            Reset
                        </button>
                        <button type="submit" class="inline-flex h-[48px] items-center justify-center rounded-xl border border-[#003629] bg-white px-5 text-sm font-extrabold text-[#003629] transition hover:bg-[#edf5f2]">
                            Apply Filters
                        </button>
                    </div>
                </form>
                <div class="mt-4 flex justify-end">
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-[#003629] bg-white px-5 py-3 text-sm font-extrabold text-[#003629] transition hover:bg-[#edf5f2]" @click="openExportModal('pdf')">
                        Export Queue
                    </button>
                </div>
            </section>

            <section class="space-y-4">
                <article v-for="document in documents.data" :key="document.id" class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-[#edf1ef] px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em] text-[#4e655a]">{{ document.workflowLabel }}</span>
                                <span class="rounded-full px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em]" :class="statusTone(document.status.value)">{{ document.status.label }}</span>
                                <span class="rounded-full px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em]" :class="flagTone(document)">{{ flagLabel(document) }}</span>
                            </div>

                            <div>
                                <h2 class="text-xl font-black text-[#191c1c]">{{ document.documentLabel }}</h2>
                                <p class="mt-1 text-sm font-semibold text-[#3d4c46]">{{ document.farmerName }}<span class="ml-2 text-[#7a8781]">{{ document.farmerCode }}</span></p>
                                <p class="mt-1 text-sm text-[#697772]">{{ document.referenceLabel }} | {{ document.sourceLabel }}</p>
                            </div>

                            <div class="grid gap-3 md:grid-cols-3">
                                <div class="rounded-[18px] bg-[#f6f8f7] px-4 py-3 text-sm">
                                    <p class="font-black uppercase tracking-[0.12em] text-[#7a8781]">Uploaded</p>
                                    <p class="mt-2 text-[#20312b]">{{ document.uploadedAt || 'No upload yet' }}</p>
                                </div>
                                <div class="rounded-[18px] bg-[#f6f8f7] px-4 py-3 text-sm">
                                    <p class="font-black uppercase tracking-[0.12em] text-[#7a8781]">Verified</p>
                                    <p class="mt-2 text-[#20312b]">{{ document.verifiedAt || 'Not yet verified' }}</p>
                                </div>
                                <div class="rounded-[18px] bg-[#f6f8f7] px-4 py-3 text-sm">
                                    <p class="font-black uppercase tracking-[0.12em] text-[#7a8781]">Expiration</p>
                                    <p class="mt-2 text-[#20312b]">{{ document.expiresAtLabel || 'No expiry rule' }}</p>
                                </div>
                            </div>

                            <div class="rounded-[18px] border border-[#e3eae6] bg-[#fbfcfb] px-4 py-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Validation Notes</p>
                                <ul v-if="document.validationNotes.length" class="mt-3 space-y-2 text-sm text-[#5f6c67]">
                                    <li v-for="(note, index) in document.validationNotes" :key="`${document.id}-note-${index}`">{{ note }}</li>
                                </ul>
                                <p v-else class="mt-3 text-sm text-[#697772]">No validation notes.</p>
                            </div>
                        </div>

                        <div class="w-full max-w-[360px] space-y-3">
                            <textarea v-model="reviewRemarks[document.id]" rows="4" placeholder="Validation note or rejection reason" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></textarea>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <button type="button" class="inline-flex items-center justify-center rounded-2xl bg-[#003629] px-4 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]" :disabled="!document.readyForVerification" @click="submitAction(document, 'verify')">
                                    Verify
                                </button>
                                <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#f4cfd6] bg-[#fff7f8] px-4 py-3 text-sm font-extrabold text-[#b42341] transition hover:bg-[#fff0f3]" @click="submitAction(document, 'reject')">
                                    Reject
                                </button>
                                <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-4 py-3 text-sm font-bold text-[#5f6c67] transition hover:bg-[#f4f7f5]" @click="submitAction(document, document.status.value === 'verified' ? 'unreceive' : 'receive')">
                                    {{ document.status.value === 'verified' ? 'Undo Confirmation' : 'Confirm Received' }}
                                </button>
                                <button v-if="document.actions.viewUrl" type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-4 py-3 text-sm font-bold text-[#5f6c67] transition hover:bg-[#f4f7f5]" @click="previewDocument = document">
                                    Preview Scan
                                </button>
                            </div>
                            <Link :href="document.actions.showParentUrl" class="inline-flex text-sm font-black text-[#014d3c] transition hover:text-[#022f25] hover:underline">
                                Open Parent Record
                            </Link>
                        </div>
                    </div>
                </article>

                <section v-if="documents.data.length === 0" class="rounded-[24px] border border-dashed border-[#d7e0db] bg-[#fafcfb] px-6 py-16 text-center text-sm text-[#72817a]">
                    No documents matched the current verification filters.
                </section>
            </section>

            <section class="flex flex-col gap-4 rounded-[24px] border border-[#dfe5e1] bg-white px-6 py-4 shadow-[0_12px_30px_rgba(15,23,42,0.05)] sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-[#65736d]">Showing {{ documents.from || 0 }}-{{ documents.to || 0 }} of {{ documents.total }} documents</span>
                <div class="flex flex-wrap items-center gap-2">
                    <template v-for="link in documents.links" :key="link.label">
                        <span v-if="!link.url" class="inline-flex items-center rounded-xl border border-[#dbe2de] px-3 py-2 text-sm text-[#9aa6a1]" v-html="link.label" />
                        <Link
                            v-else
                            :href="link.url"
                            class="inline-flex items-center rounded-xl border px-3 py-2 text-sm font-bold transition"
                            :class="link.active ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#dbe2de] text-[#5f6b66] hover:bg-white'"
                            preserve-scroll
                            preserve-state
                            v-html="link.label"
                        />
                    </template>
                </div>
            </section>

            <div v-if="previewDocument" class="fixed inset-0 z-[90] flex items-center justify-center bg-[#09110d]/70 px-4 py-6" @click.self="previewDocument = null">
                <div class="flex max-h-full w-full max-w-5xl flex-col overflow-hidden rounded-[28px] bg-white shadow-[0_24px_64px_rgba(15,23,42,0.22)]">
                    <div class="flex items-center justify-between border-b border-[#e4ebe7] px-5 py-4">
                        <div>
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">File Preview</p>
                            <h3 class="mt-1 text-lg font-bold text-[#1a2420]">{{ previewDocument.documentLabel }}</h3>
                        </div>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#d7e0db] text-lg font-bold text-[#5f6c67] transition hover:bg-[#f4f7f5]" @click="previewDocument = null">
                            ×
                        </button>
                    </div>

                    <div class="min-h-[65vh] overflow-auto bg-[#f5f7f6] p-4">
                        <img v-if="previewIsImage" :src="previewUrl" :alt="previewDocument.documentLabel" class="mx-auto max-h-[70vh] w-auto max-w-full rounded-[20px] bg-white object-contain shadow-[0_12px_32px_rgba(15,23,42,0.08)]">
                        <iframe v-else-if="previewIsPdf" :src="previewUrl" class="h-[70vh] w-full rounded-[20px] border border-[#d7e0db] bg-white" title="Document preview"></iframe>
                        <div v-else class="flex h-[70vh] items-center justify-center rounded-[20px] border border-[#d7e0db] bg-white px-6 text-center">
                            <div class="max-w-md space-y-3">
                                <h4 class="text-lg font-bold text-[#1a2420]">Preview not available</h4>
                                <p class="text-sm text-[#5f6c67]">This file type cannot be displayed inline.</p>
                                <button
                                    v-if="previewUrl"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-4 py-3 text-sm font-bold text-[#31584a] transition hover:bg-[#f4f7f5]"
                                    @click="openPreviewInNewTab"
                                >
                                    Open file in new tab
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="exportModalOpen" class="fixed inset-0 z-[95] flex items-center justify-center bg-[#09110d]/45 px-4 py-6" @click.self="closeExportModal">
                <section class="w-full max-w-6xl rounded-[28px] border border-[#dbe2de] bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.22)] sm:p-6">
                    <div class="flex flex-col gap-3 border-b border-[#e4ebe7] pb-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Export Columns</p>
                            <h2 class="mt-1 text-xl font-bold text-[#1a2420]">Choose fields to include in {{ exportFormat === 'pdf' ? 'PDF' : 'Excel' }}</h2>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border px-4 py-2 text-sm font-extrabold transition"
                                    :class="exportFormat === 'pdf' ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#d7e0db] text-[#66756f] hover:bg-[#f5f8f6]'"
                                    @click="exportFormat = 'pdf'"
                                >
                                    PDF
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border px-4 py-2 text-sm font-extrabold transition"
                                    :class="exportFormat === 'xlsx' ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#d7e0db] text-[#66756f] hover:bg-[#f5f8f6]'"
                                    @click="exportFormat = 'xlsx'"
                                >
                                    Excel
                                </button>
                            </div>
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
        </div>
    </AdminLayout>
</template>
