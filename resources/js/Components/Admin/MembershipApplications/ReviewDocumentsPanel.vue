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
    if (value === 'verified') return 'bg-[#eef7e3] text-[#416918]';
    if (value === 'rejected') return 'bg-[#ffdad6] text-[#93000a]';
    return 'bg-[#fff3dc] text-[#a86100]';
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
            className: 'bg-[#ffe6ea] text-[#b42341]',
        };
    }

    if (document.isExpired) {
        return {
            label: 'Expired',
            className: 'bg-[#fff3dc] text-[#a86100]',
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
    <section class="rounded-lg border border-[#dbe2de] bg-white">
        <div class="flex items-center justify-between border-b border-[#e4ebe7] bg-[#f6f8f7] px-4 py-2.5">
            <div>
                <p class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Step 2</p>
                <h2 class="mt-0.5 text-xs font-semibold text-[#1a2420]">Document checklist</h2>
            </div>
            <span class="inline-flex rounded-md px-2 py-1 text-[0.62rem] font-semibold" :class="flow.documentsComplete ? 'bg-[#eef7e3] text-[#416918]' : 'bg-[#fff3dc] text-[#a86100]'">
                {{ flow.verifiedCount }}/{{ flow.requiredCount }} verified
            </span>
        </div>

        <div class="space-y-2 p-2.5">
            <div
                v-if="flow.canInitializeChecklist"
                class="rounded-md border border-[#dbe2de] bg-[#fbfdfc] px-3 py-2.5"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold text-[#1a2420]">Document checklist has not started yet.</p>
                        <p class="mt-0.5 text-[0.68rem] text-[#5f6c67]">Start intake to generate the required document rows.</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-md bg-[#003629] px-3 text-xs font-semibold text-white transition hover:bg-[#0d4637]"
                        @click="$emit('initialize-checklist')"
                    >
                        Start Intake Checklist
                    </button>
                </div>
            </div>

            <div
                v-if="(flow.missingCount || 0) > 0 || (flow.expiredCount || 0) > 0 || (flow.resubmissionCount || 0) > 0"
                class="rounded-md border border-[#f0d9aa] bg-[#fffaf0] px-3 py-2.5 text-xs text-[#7a5a16]"
            >
                Resolve all missing, expired, or rejected documents before final approval or payment completion.
            </div>

            <article
                v-for="document in documents"
                v-show="flow.checklistInitialized"
                :key="document.id"
                class="rounded-md border border-[#e3eae6] bg-[#fafcfb] p-2.5"
            >
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <p class="text-xs font-semibold text-[#191c1c]">{{ document.label }}</p>
                            <span class="inline-flex rounded-md bg-[#edf1ef] px-1.5 py-0.5 text-[0.56rem] font-semibold uppercase tracking-[0.05em] text-[#697772]">
                                {{ document.isRequired ? 'Required' : 'Optional' }}
                            </span>
                            <span class="inline-flex rounded-md px-1.5 py-0.5 text-[0.58rem] font-semibold" :class="documentBadge(document.verificationStatus.value)">
                                {{ verificationLabel(document) }}
                            </span>
                            <span
                                v-if="alertBadge(document)"
                                class="inline-flex rounded-md px-1.5 py-0.5 text-[0.58rem] font-semibold"
                                :class="alertBadge(document).className"
                            >
                                {{ alertBadge(document).label }}
                            </span>
                        </div>
                        <div v-if="document.remarks || visibleValidationNotes(document).length" class="mt-1 space-y-0.5">
                            <p v-if="document.remarks" class="text-[0.62rem] text-[#78857f]">{{ document.remarks }}</p>
                            <ul v-if="visibleValidationNotes(document).length" class="space-y-0.5 text-[0.62rem] text-[#5f6c67]">
                                <li v-for="(note, index) in visibleValidationNotes(document)" :key="`${document.id}-note-${index}`">
                                    {{ note }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex w-full gap-1.5 lg:max-w-[430px] lg:items-center">
                        <input
                            v-model="documentRemarks[document.id]"
                            type="text"
                            placeholder="Optional remarks"
                            class="h-8 min-w-0 flex-1 rounded-md border border-[#d7e0db] bg-[#f8faf9] px-2.5 text-[0.68rem] text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"
                        >
                        <div class="flex shrink-0 flex-wrap gap-1.5">
                            <button
                                type="button"
                                class="inline-flex h-8 items-center justify-center whitespace-nowrap rounded-md bg-[#003629] px-2.5 text-[0.65rem] font-semibold text-white transition hover:bg-[#0d4637]"
                                :disabled="application.source !== 'walk_in' && !document.uploadPresent"
                                @click="$emit('document-action', document, document.verificationStatus.value === 'verified' ? 'unreceive' : 'receive')"
                            >
                                {{ document.verificationStatus.value === 'verified' ? 'Undo Confirmation' : 'Confirm Document' }}
                            </button>
                            <button
                                v-if="document.uploadPresent"
                                type="button"
                                class="inline-flex h-8 items-center justify-center whitespace-nowrap rounded-md border border-[#f4cfd6] bg-[#fff7f8] px-2.5 text-[0.65rem] font-semibold text-[#b42341] transition hover:bg-[#fff0f2]"
                                @click="$emit('document-action', document, 'reject')"
                            >
                                Reject Document
                            </button>
                            <button
                                v-if="features.walkInAttachScanEnabled && application.source === 'walk_in'"
                                type="button"
                                class="inline-flex h-8 items-center justify-center rounded-md border border-[#d7e0db] px-2.5 text-[0.65rem] font-semibold text-[#5f6c67] transition hover:bg-[#f4f7f5]"
                            >
                                Attach Scan
                            </button>
                            <button
                                v-if="document.actions.viewUrl"
                                type="button"
                                class="inline-flex h-8 items-center justify-center rounded-md border border-[#d7e0db] px-2.5 text-[0.65rem] font-semibold text-[#5f6c67] transition hover:bg-[#f4f7f5]"
                                @click="openPreview(document)"
                            >
                                View File
                            </button>
                        </div>
                        <p
                            v-if="application.source !== 'walk_in' && !document.uploadPresent"
                            class="text-xs font-medium text-[#8a5b16]"
                        >
                            Wait for the farmer to upload this file before confirming it.
                        </p>
                    </div>
                </div>
            </article>
        </div>

        <div
            v-if="previewDocument"
            class="fixed inset-0 z-[90] flex items-center justify-center bg-[#09110d]/65 p-4"
            @click.self="closePreview"
        >
            <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl border border-[#dbe2de] bg-white shadow-[0_20px_50px_rgba(15,23,42,0.2)]">
                <div class="flex items-center justify-between border-b border-[#e4ebe7] px-4 py-2.5">
                    <div>
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.1em] text-[#7a8781]">File preview</p>
                        <h3 class="mt-0.5 text-sm font-semibold text-[#1a2420]">{{ previewDocument.label }}</h3>
                    </div>
                    <button
                        type="button"
                        aria-label="Close preview"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d7e0db] text-base text-[#5f6c67] transition hover:bg-[#f4f7f5]"
                        @click="closePreview"
                    >
                        ×
                    </button>
                </div>

                <div class="relative min-h-[360px] flex-1 overflow-auto bg-[#f5f7f6] p-3">
                    <div
                        v-if="previewLoading"
                        class="absolute inset-0 z-10 flex items-center justify-center bg-[#f5f7f6]/90"
                    >
                        <div class="flex items-center gap-2 text-[#31584a]">
                            <span class="h-6 w-6 animate-spin rounded-full border-[3px] border-[#d7e0db] border-t-[#0f5b46]"></span>
                            <p class="text-xs font-semibold">Loading preview...</p>
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
                    <div
                        v-else
                        class="flex min-h-[360px] items-center justify-center rounded-md border border-[#d7e0db] bg-white px-5 text-center"
                    >
                        <div class="max-w-sm space-y-2.5">
                            <h4 class="text-sm font-semibold text-[#1a2420]">Preview unavailable</h4>
                            <p class="text-xs leading-5 text-[#5f6c67]">
                                This browser cannot display the uploaded format here. Open the original file to view or download it.
                            </p>
                            <button
                                v-if="previewUrl"
                                type="button"
                                class="inline-flex h-8 items-center justify-center rounded-md bg-[#003629] px-3 text-[0.68rem] font-semibold text-white transition hover:bg-[#0d4637]"
                                @click="openPreviewInNewTab"
                            >
                                Open file in new tab
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
