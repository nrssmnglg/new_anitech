<script setup>
import { computed, ref } from 'vue';

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

const previewUrl = computed(() => previewDocument.value?.actions?.viewUrl || '');
const previewMimeType = computed(() => previewDocument.value?.previewMimeType || '');
const previewIsImage = computed(() => {
    return previewMimeType.value.startsWith('image/');
});
const previewIsPdf = computed(() => {
    return previewMimeType.value === 'application/pdf';
});

function openPreview(document) {
    previewDocument.value = document;
    previewLoading.value = true;
}

function closePreview() {
    previewDocument.value = null;
    previewLoading.value = false;
}

function markPreviewLoaded() {
    previewLoading.value = false;
}

function documentBadge(value) {
    if (value === 'verified') return 'bg-[#eef7e3] text-[#416918]';
    if (value === 'rejected') return 'bg-[#ffdad6] text-[#93000a]';
    return 'bg-[#fff3dc] text-[#a86100]';
}

function presenceBadge(document, source) {
    if (document.verificationStatus.value === 'verified') {
        return {
            label: source === 'walk_in' ? 'Confirmed' : 'Reviewed',
            className: 'bg-[#eef7e3] text-[#416918]',
        };
    }

    if (document.uploadPresent) {
        return {
            label: source === 'walk_in' ? 'Received' : 'Uploaded',
            className: 'bg-[#edf1ef] text-[#48615a]',
        };
    }

    return {
        label: 'Waiting',
        className: 'bg-[#edf1ef] text-[#697772]',
    };
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

    if (!document.uploadPresent) {
        return {
            label: 'Missing Scan',
            className: 'bg-[#edf1ef] text-[#697772]',
        };
    }

    return null;
}
</script>

<template>
    <section class="rounded-[24px] border border-[#dbe2de] bg-white shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
        <div class="flex items-center justify-between border-b border-[#e4ebe7] px-5 py-4">
            <div>
                <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Step 2</p>
                <h2 class="mt-1 text-base font-bold text-[#1a2420]">Document checklist</h2>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-black" :class="flow.documentsComplete ? 'bg-[#eef7e3] text-[#416918]' : 'bg-[#fff3dc] text-[#a86100]'">
                {{ flow.verifiedCount }}/{{ flow.requiredCount }} verified
            </span>
        </div>

        <div class="space-y-4 px-5 py-5">
            <div
                v-if="flow.canInitializeChecklist"
                class="rounded-[20px] border border-[#dbe2de] bg-[#fbfdfc] px-4 py-5"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-bold text-[#1a2420]">Document checklist has not started yet.</p>
                        <p class="mt-1 text-sm text-[#5f6c67]">Start intake first to generate the required document rows for this walk-in application.</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl bg-[#003629] px-4 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]"
                        @click="$emit('initialize-checklist')"
                    >
                        Start Intake Checklist
                    </button>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-[18px] border border-[#dfe5e1] bg-[#f7faf8] px-4 py-4">
                    <p class="text-[0.62rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">Missing Documents</p>
                    <p class="mt-1.5 text-[1.8rem] font-black text-[#1a2420]">{{ flow.missingCount || 0 }}</p>
                </div>
                <div class="rounded-[18px] border border-[#f2dfb2] bg-[#fffaf0] px-4 py-4">
                    <p class="text-[0.62rem] font-black uppercase tracking-[0.16em] text-[#9d6b00]">Expired Flags</p>
                    <p class="mt-1.5 text-[1.8rem] font-black text-[#9d6b00]">{{ flow.expiredCount || 0 }}</p>
                </div>
                <div class="rounded-[18px] border border-[#f4cfd6] bg-[#fff7f8] px-4 py-4">
                    <p class="text-[0.62rem] font-black uppercase tracking-[0.16em] text-[#b42341]">Needs Re-submission</p>
                    <p class="mt-1.5 text-[1.8rem] font-black text-[#b42341]">{{ flow.resubmissionCount || 0 }}</p>
                </div>
            </div>

            <div
                v-if="(flow.missingCount || 0) > 0 || (flow.expiredCount || 0) > 0 || (flow.resubmissionCount || 0) > 0"
                class="rounded-[20px] border border-[#f0d9aa] bg-[#fffaf0] px-4 py-4 text-sm text-[#7a5a16]"
            >
                Resolve all missing, expired, or rejected documents before final approval or payment completion.
            </div>

            <div
                v-if="application.source === 'walk_in'"
                class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] px-4 py-4 text-sm text-[#5f6c67]"
            >
                Walk-in processing uses checklist.
            </div>

            <article
                v-for="document in documents"
                v-show="flow.checklistInitialized"
                :key="document.id"
                class="rounded-[24px] border border-[#e3eae6] bg-[linear-gradient(135deg,_#ffffff_0%,_#f8fbf9_100%)] p-4 shadow-[0_8px_22px_rgba(15,23,42,0.04)]"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-base font-bold text-[#191c1c]">{{ document.label }}</p>
                            <span class="inline-flex rounded-full bg-[#edf1ef] px-2.5 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em] text-[#697772]">
                                {{ document.isRequired ? 'Required' : 'Optional' }}
                            </span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black" :class="presenceBadge(document, application.source).className">
                                {{ presenceBadge(document, application.source).label }}
                            </span>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black" :class="documentBadge(document.verificationStatus.value)">
                                {{ verificationLabel(document) }}
                            </span>
                            <span
                                v-if="alertBadge(document)"
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-black"
                                :class="alertBadge(document).className"
                            >
                                {{ alertBadge(document).label }}
                            </span>
                        </div>
                        <div class="mt-3 space-y-2">
                            <p class="text-sm text-[#78857f]">{{ document.remarks || 'No validation remarks yet.' }}</p>
                            <ul v-if="document.validationNotes?.length" class="space-y-1 text-xs text-[#5f6c67]">
                                <li v-for="(note, index) in document.validationNotes" :key="`${document.id}-note-${index}`">
                                    {{ note }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-3 lg:max-w-[320px]">
                        <input
                            v-model="documentRemarks[document.id]"
                            type="text"
                            placeholder="Optional remarks"
                            class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"
                        >
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-[#003629] px-3 py-2.5 text-xs font-extrabold text-white transition hover:bg-[#0d4637]"
                                :disabled="application.source !== 'walk_in' && !document.uploadPresent"
                                @click="$emit('document-action', document, document.verificationStatus.value === 'verified' ? 'unreceive' : 'receive')"
                            >
                                {{ document.verificationStatus.value === 'verified' ? 'Undo Confirmation' : 'Confirm Document' }}
                            </button>
                            <button
                                v-if="document.uploadPresent"
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-[#f4cfd6] bg-[#fff7f8] px-3 py-2.5 text-xs font-bold text-[#b42341] transition hover:bg-[#fff0f2]"
                                @click="$emit('document-action', document, 'reject')"
                            >
                                Reject Document
                            </button>
                            <button
                                v-if="features.walkInAttachScanEnabled && application.source === 'walk_in'"
                                type="button"
                                class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-4 py-3 text-sm font-bold text-[#5f6c67] transition hover:bg-[#f4f7f5]"
                            >
                                Attach Scan
                            </button>
                            <button
                                v-if="document.actions.viewUrl"
                                type="button"
                                class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-4 py-3 text-sm font-bold text-[#5f6c67] transition hover:bg-[#f4f7f5]"
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
            class="fixed inset-0 z-[90] flex items-center justify-center bg-[#09110d]/70 px-4 py-6"
            @click.self="closePreview"
        >
            <div class="flex max-h-full w-full max-w-5xl flex-col overflow-hidden rounded-[28px] bg-white shadow-[0_24px_64px_rgba(15,23,42,0.22)]">
                <div class="flex items-center justify-between border-b border-[#e4ebe7] px-5 py-4">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">File Preview</p>
                        <h3 class="mt-1 text-lg font-bold text-[#1a2420]">{{ previewDocument.label }}</h3>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#d7e0db] text-lg font-bold text-[#5f6c67] transition hover:bg-[#f4f7f5]"
                        @click="closePreview"
                    >
                        ×
                    </button>
                </div>

                <div class="min-h-[65vh] overflow-auto bg-[#f5f7f6] p-4">
                    <div
                        v-if="previewLoading"
                        class="absolute inset-x-0 top-[88px] bottom-0 flex items-center justify-center bg-[#f5f7f6]/78 backdrop-blur-[1px]"
                    >
                        <div class="flex flex-col items-center gap-3 text-[#31584a]">
                            <span class="h-10 w-10 animate-spin rounded-full border-4 border-[#d7e0db] border-t-[#0f5b46]"></span>
                            <p class="text-sm font-bold">Loading file preview...</p>
                        </div>
                    </div>
                    <img
                        v-if="previewIsImage"
                        :src="previewUrl"
                        :alt="previewDocument.label"
                        class="mx-auto max-h-[70vh] w-auto max-w-full rounded-[20px] bg-white object-contain shadow-[0_12px_32px_rgba(15,23,42,0.08)]"
                        @load="markPreviewLoaded"
                        @error="markPreviewLoaded"
                    >
                    <iframe
                        v-else-if="previewIsPdf"
                        :src="previewUrl"
                        class="h-[70vh] w-full rounded-[20px] border border-[#d7e0db] bg-white"
                        title="Uploaded file preview"
                        @load="markPreviewLoaded"
                    ></iframe>
                    <div
                        v-else
                        class="flex h-[70vh] items-center justify-center rounded-[20px] border border-[#d7e0db] bg-white px-6 text-center"
                    >
                        <div class="max-w-md space-y-3">
                            <h4 class="text-lg font-bold text-[#1a2420]">Preview not available</h4>
                            <p class="text-sm text-[#5f6c67]">
                                This file type cannot be displayed inside the modal. Convert it to an image or PDF if you need inline preview.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
