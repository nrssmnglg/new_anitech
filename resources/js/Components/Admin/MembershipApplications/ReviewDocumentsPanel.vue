<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import DocumentPreview from '../DocumentPreview.vue';

defineProps({
    application: { type: Object, required: true },
    flow: { type: Object, required: true },
    documents: { type: Array, required: true },
    documentRemarks: { type: Object, required: true },
    features: { type: Object, required: true },
    urls: { type: Object, required: true },
});

defineEmits(['document-action', 'initialize-checklist']);

const previewDocument = ref(null);
const previewLoading = ref(false);
const previewFailed = ref(false);
let previewTimer = null;

const previewUrl = computed(() => previewDocument.value?.actions?.viewUrl || '');
const previewMimeType = computed(() => previewDocument.value?.previewMimeType || '');
const previewName = computed(() => String(previewDocument.value?.originalName || ''));
const previewExtension = computed(() => previewName.value.includes('.') ? previewName.value.split('.').pop().toLowerCase() : '');
const previewIsImage = computed(() => {
    return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(previewMimeType.value)
        || ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(previewExtension.value);
});
const previewIsPdf = computed(() => {
    return previewMimeType.value === 'application/pdf' || previewExtension.value === 'pdf';
});
const previewCanInline = computed(() => previewIsImage.value || previewIsPdf.value);

function clearPreviewTimer() {
    if (previewTimer) {
        window.clearTimeout(previewTimer);
        previewTimer = null;
    }
}

function openPreview(document) {
    clearPreviewTimer();
    previewDocument.value = document;
    previewFailed.value = false;
    previewLoading.value = previewCanInline.value;

    if (previewLoading.value) {
        previewTimer = window.setTimeout(() => {
            previewLoading.value = false;
            previewFailed.value = true;
        }, 10000);
    }
}

function closePreview() {
    clearPreviewTimer();
    previewDocument.value = null;
    previewLoading.value = false;
    previewFailed.value = false;
}

function markPreviewLoaded() {
    clearPreviewTimer();
    previewLoading.value = false;
}

function markPreviewFailed() {
    clearPreviewTimer();
    previewLoading.value = false;
    previewFailed.value = true;
}

function openPreviewInNewTab() {
    if (!previewUrl.value) {
        return;
    }
    window.open(previewUrl.value, '_blank', 'noopener');
}

onBeforeUnmount(clearPreviewTimer);

function documentBadge(value) {
    if (value === 'verified') return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    if (value === 'rejected') return 'bg-rose-50 text-rose-800 border-rose-200';
    return 'bg-amber-50 text-amber-800 border-amber-200';
}

function verificationLabel(document) {
    if (document.verificationStatus.value === 'pending' && document.uploadPresent) {
        return 'Pending Review';
    }
    return document.verificationStatus.label;
}

function alertBadge(document) {
    if (document.needsResubmission) {
        return {
            label: 'Re-submission Required',
            className: 'bg-rose-50 text-rose-700 border border-rose-200',
        };
    }
    if (document.isExpired) {
        return {
            label: 'Expired',
            className: 'bg-amber-50 text-amber-700 border border-amber-200',
        };
    }
    return null;
}

function visibleValidationNotes(document) {
    return (document.validationNotes || []).filter((note) => {
        return String(note).trim().toLowerCase() !== 'no scan uploaded yet.';
    });
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
        <div class="flex items-center justify-between border-b border-[#dde4de] bg-[#f9fbfa] px-4 py-3">
            <div class="flex items-center gap-2">
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-[#003629]/10 text-[#003629]">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm2.25 8.5a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Zm0 3a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xs font-bold text-[#0f172a]">Document Checklist & Verification</h2>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="rounded bg-[#003629]/10 px-2 py-0.5 text-[0.6rem] font-bold text-[#003629]">
                    Step 2
                </span>
                <span
                    class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[0.65rem] font-bold"
                    :class="flow.documentsComplete ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800'"
                >
                    <span class="h-1.5 w-1.5 rounded-full" :class="flow.documentsComplete ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'"></span>
                    {{ flow.verifiedCount }} / {{ flow.requiredCount }} verified
                </span>
            </div>
        </div>

        <div class="space-y-3 p-4">
            <!-- Checklist initialization if needed -->
            <div
                v-if="flow.canInitializeChecklist"
                class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold text-[#003629]">Document checklist has not been initialized yet.</p>
                        <p class="mt-0.5 text-xs text-slate-600">Start intake to generate the required document verification entries.</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-[#003629] px-4 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a]"
                        @click="$emit('initialize-checklist')"
                    >
                        Start Intake Checklist
                    </button>
                </div>
            </div>

            <!-- Documents List -->
            <article
                v-for="document in documents"
                v-show="flow.checklistInitialized"
                :key="document.id"
                class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-3.5 transition hover:border-[#b5c7bd] hover:bg-white"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-xs font-bold text-[#0f172a]">{{ document.label }}</h3>
                            <span class="inline-flex rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[0.6rem] font-semibold text-slate-600">
                                {{ document.isRequired ? 'Required' : 'Optional' }}
                            </span>
                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[0.6rem] font-bold" :class="documentBadge(document.verificationStatus.value)">
                                {{ verificationLabel(document) }}
                            </span>
                            <span
                                v-if="alertBadge(document)"
                                class="inline-flex rounded-full px-2 py-0.5 text-[0.6rem] font-bold"
                                :class="alertBadge(document).className"
                            >
                                {{ alertBadge(document).label }}
                            </span>
                        </div>

                        <p v-if="document.verificationStatus.value === 'verified'" class="mt-1 text-[0.68rem] text-slate-500">
                            Verified by <strong>{{ document.verifierName || 'Staff' }}</strong><span v-if="document.verifiedAt"> on {{ document.verifiedAt }}</span>
                        </p>

                        <div v-if="document.remarks || visibleValidationNotes(document).length" class="mt-1.5 space-y-0.5">
                            <p v-if="document.remarks" class="text-[0.68rem] italic text-slate-600">Note: {{ document.remarks }}</p>
                            <ul v-if="visibleValidationNotes(document).length" class="text-[0.68rem] text-rose-600">
                                <li v-for="(note, index) in visibleValidationNotes(document)" :key="`${document.id}-note-${index}`">
                                    • {{ note }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Action Controls -->
                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        <input
                            v-model="documentRemarks[document.id]"
                            type="text"
                            placeholder="Optional remarks..."
                            class="h-8 min-w-[140px] flex-1 rounded-lg border border-[#dde4de] bg-white px-2.5 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] sm:w-48 sm:flex-initial"
                        >

                        <button
                            type="button"
                            class="inline-flex h-8 items-center justify-center gap-1 whitespace-nowrap rounded-lg px-3 text-xs font-bold transition shadow-2xs"
                            :class="document.verificationStatus.value === 'verified'
                                ? 'border border-[#dde4de] bg-white text-slate-700 hover:bg-slate-50'
                                : 'bg-[#003629] text-white hover:bg-[#00483a]'"
                            :disabled="application.source !== 'walk_in' && !document.uploadPresent"
                            @click="$emit('document-action', document, document.verificationStatus.value === 'verified' ? 'unreceive' : 'receive')"
                        >
                            <svg v-if="document.verificationStatus.value !== 'verified'" viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ document.verificationStatus.value === 'verified' ? 'Undo Confirmation' : 'Confirm Document' }}</span>
                        </button>

                        <button
                            v-if="document.uploadPresent"
                            type="button"
                            class="inline-flex h-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-2.5 text-xs font-bold text-rose-700 transition hover:bg-rose-100"
                            @click="$emit('document-action', document, 'reject')"
                        >
                            Reject
                        </button>

                        <button
                            v-if="document.actions?.viewUrl"
                            type="button"
                            class="inline-flex h-8 items-center justify-center gap-1 rounded-lg border border-[#dde4de] bg-white px-2.5 text-xs font-medium text-slate-700 shadow-2xs transition hover:bg-slate-50"
                            @click="openPreview(document)"
                        >
                            <svg viewBox="0 0 16 16" class="h-3 w-3 text-slate-500" fill="currentColor">
                                <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                <path fill-rule="evenodd" d="M1.38 8.28a.87.87 0 0 1 0-.56C2.42 4.98 5.04 3 8 3s5.58 1.98 6.62 4.72c.07.18.07.38 0 .56C13.58 11.02 10.96 13 8 13s-5.58-1.98-6.62-4.72ZM8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                            </svg>
                            <span>View</span>
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <!-- Preview Modal -->
        <div
            v-if="previewDocument"
            class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
            @click.self="closePreview"
        >
            <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-[#dde4de] bg-[#f9fbfa] px-4 py-3">
                    <div>
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">File Preview</span>
                        <h3 class="text-sm font-bold text-[#0f172a]">{{ previewDocument.label }}</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="previewUrl"
                            type="button"
                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-[#dde4de] bg-white px-2.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50"
                            @click="openPreviewInNewTab"
                        >
                            <span>Open in tab</span>
                            <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.22 11.78a.75.75 0 0 1 0-1.06L9.44 5.5H5.75a.75.75 0 0 1 0-1.5h5.5a.75.75 0 0 1 .75.75v5.5a.75.75 0 0 1-1.5 0V6.56l-5.22 5.22a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dde4de] text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                            aria-label="Close"
                            @click="closePreview"
                        >
                            &times;
                        </button>
                    </div>
                </div>

                <div class="relative min-h-[380px] flex-1 overflow-auto bg-[#f8faf9] p-4">
                    <div v-if="previewLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/80">
                        <div class="flex items-center gap-2 text-[#003629]">
                            <span class="h-5 w-5 animate-spin rounded-full border-2 border-emerald-600 border-t-transparent"></span>
                            <p class="text-xs font-bold">Loading preview...</p>
                        </div>
                    </div>

                    <DocumentPreview
                        v-if="previewCanInline && !previewFailed"
                        :src="previewUrl"
                        :label="previewDocument.label"
                        :is-pdf="previewIsPdf"
                        @load="markPreviewLoaded"
                        @error="markPreviewFailed"
                    />

                    <div v-else class="flex min-h-[360px] flex-col items-center justify-center p-6 text-center">
                        <p class="text-sm font-bold text-[#0f172a]">Preview not available in this frame</p>
                        <p class="mt-1 text-xs text-slate-500">You can open the original file directly in a new browser tab.</p>
                        <button
                            v-if="previewUrl"
                            type="button"
                            class="mt-3 inline-flex h-9 items-center justify-center rounded-xl bg-[#003629] px-4 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a]"
                            @click="openPreviewInNewTab"
                        >
                            Open File in New Tab
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
