<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    reference: { type: Object, required: true },
    heading: { type: String, required: true },
    description: { type: String, default: '' },
    submitLabel: { type: String, required: true },
    cancelHref: { type: String, required: true },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'removeAttachment']);

const previewAttachment = ref(null);

const showBarangay = computed(() => props.form.audience_type === 'barangay');
const showMemberType = computed(() => props.form.audience_type === 'group');

const attachmentErrors = computed(() => Object.entries(props.form.errors)
    .filter(([key]) => key === 'attachments' || key.startsWith('attachments.'))
    .map(([, message]) => message));

const generalErrors = computed(() => Object.entries(props.form.errors)
    .filter(([key]) => !['title', 'content', 'audience_type', 'barangay_id', 'member_type_id'].includes(key)
        && key !== 'attachments' && !key.startsWith('attachments.'))
    .map(([, message]) => message));

const audienceCards = computed(() => ([
    {
        value: 'all',
        title: 'Global / All Farmers',
        description: 'Notify all registered farmers across all barangays',
    },
    {
        value: 'barangay',
        title: 'Specific Barangay',
        description: 'Broadcast specifically to farmers in a selected barangay',
    },
    {
        value: 'group',
        title: 'Member Group',
        description: 'Target specific farmer classifications or types',
    },
]));

function updateAttachments(event) {
    const newFiles = Array.from(event.target.files || []);
    if (!props.form.attachments) {
        props.form.attachments = [];
    }
    props.form.attachments = [...props.form.attachments, ...newFiles];
    event.target.value = '';
}

function removeQueuedAttachment(index) {
    props.form.attachments.splice(index, 1);
}

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function getFileExtension(filename) {
    if (!filename) return 'FILE';
    return filename.slice(((filename.lastIndexOf('.') - 1) >>> 0) + 2).toUpperCase() || 'FILE';
}

function isImageAttachment(attachment) {
    return /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(String(attachment?.name ?? ''));
}

function isPdfAttachment(attachment) {
    return /\.pdf$/i.test(String(attachment?.name ?? ''));
}

function openAttachmentPreview(attachment) {
    previewAttachment.value = attachment;
}

function openQueuedAttachmentPreview(file) {
    const objectUrl = URL.createObjectURL(file);
    previewAttachment.value = {
        name: file.name,
        downloadUrl: objectUrl,
        uploadedAt: `Queued (${formatFileSize(file.size)})`,
        isLocal: true,
    };
}

function closeAttachmentPreview() {
    if (previewAttachment.value?.isLocal && previewAttachment.value?.downloadUrl) {
        URL.revokeObjectURL(previewAttachment.value.downloadUrl);
    }
    previewAttachment.value = null;
}

function handleKeydown(e) {
    if (e.key === 'Escape' && previewAttachment.value) {
        closeAttachmentPreview();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div class="advisory-form space-y-4">
        <!-- Hero Header -->
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
            <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
            <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
            <div class="pointer-events-none absolute right-24 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 10a6 6 0 00-6-6v12a6 6 0 006-6v0z" />
                            <path d="M18 10l3 2v-4l-3 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Communication</p>
                        <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">{{ heading }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="cancelHref"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 bg-white/10 px-3.5 text-xs font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20 active:scale-[0.98]"
                    >
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                        </svg>
                        Back to Advisories
                    </a>
                    <button
                        type="button"
                        :disabled="disabled"
                        class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-4 text-xs font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60"
                        @click="$emit('submit')"
                    >
                        <svg v-if="disabled" class="h-3.5 w-3.5 animate-spin text-[#003629]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#003629]" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                        </svg>
                        {{ disabled ? 'Saving...' : submitLabel }}
                    </button>
                </div>
            </div>
        </section>

        <!-- General Error Banner -->
        <div v-if="generalErrors.length" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-medium text-red-700 shadow-sm">
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-red-600" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <div class="space-y-1">
                    <p v-for="message in generalErrors" :key="message">{{ message }}</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="$emit('submit')">
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                <!-- Main Form Column -->
                <div class="space-y-4">
                    <!-- Basic Information Card -->
                    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="flex items-center gap-2.5 border-b border-[#edf2ee] px-5 py-3.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="1.8">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-bold text-[#0f172a]">Basic Information</h2>
                        </div>

                        <div class="space-y-4 p-5">
                            <label class="block space-y-1.5">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Advisory Title <span class="text-rose-500">*</span></span>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    placeholder="e.g. Seasonal Irrigation Guidelines for Q3"
                                    class="h-10 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3.5 text-xs text-[#0f172a] placeholder-[#94a3b8] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                    :disabled="disabled"
                                >
                                <p v-if="form.errors.title" class="text-xs font-medium text-rose-600">{{ form.errors.title }}</p>
                            </label>

                            <label class="block space-y-1.5">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Advisory Content <span class="text-rose-500">*</span></span>
                                <div class="overflow-hidden rounded-lg border border-[#dbe3dd] bg-white transition-all focus-within:border-[#014d3c] focus-within:ring-2 focus-within:ring-[#014d3c]/10">
                                    <div class="flex items-center justify-between border-b border-[#edf2ee] bg-[#f9fbfa] px-3.5 py-2 text-[0.65rem] font-bold text-[#64748b]">
                                        <span class="text-[0.6rem] uppercase tracking-wider text-[#94a3b8]">Broadcast Message Body</span>
                                        <span class="text-[0.65rem] text-[#64748b]">{{ form.content?.length || 0 }} characters</span>
                                    </div>
                                    <textarea
                                        v-model="form.content"
                                        rows="12"
                                        placeholder="Type your advisory announcement, instructions, or recommendations for farmers in detail..."
                                        class="w-full resize-y border-0 bg-transparent p-3.5 text-xs leading-relaxed text-[#0f172a] placeholder-[#94a3b8] outline-none"
                                        :disabled="disabled"
                                    />
                                </div>
                                <p v-if="form.errors.content" class="text-xs font-medium text-rose-600">{{ form.errors.content }}</p>
                            </label>
                        </div>
                    </section>

                    <!-- Attachments Card -->
                    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="1.8">
                                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                                    </svg>
                                </span>
                                <h2 class="text-sm font-bold text-[#0f172a]">Attachments & Documents</h2>
                            </div>
                            <span class="text-[0.68rem] text-[#64748b]">Optional</span>
                        </div>

                        <div class="space-y-4 p-5">
                            <label class="group relative flex min-h-[130px] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-[#cfdad3] bg-[#fbfcfb] px-6 py-6 text-center transition-all duration-200 hover:border-[#014d3c]/40 hover:bg-[#f4f8f6]">
                                <input
                                    type="file"
                                    multiple
                                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                    class="hidden"
                                    :disabled="disabled"
                                    @change="updateAttachments"
                                >
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#014d3c]/10 text-[#014d3c] transition-transform duration-200 group-hover:scale-105">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                                        <path d="M7 16a4 4 0 0 1-.88-7.903A5 5 0 1 1 15.9 6L16 6a5 5 0 0 1 1 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </span>
                                <p class="mt-2 text-xs font-bold text-[#0f172a]">Click to select files or drag and drop</p>
                                <p class="mt-0.5 text-[0.68rem] text-[#64748b]">PDF, DOC, DOCX, PNG, JPG (up to 10MB per file)</p>
                            </label>

                            <!-- Attachment Error Messages -->
                            <p v-for="(message, index) in attachmentErrors" :key="index" role="alert" class="text-xs font-medium text-rose-600">{{ message }}</p>

                            <!-- Queued Files -->
                            <div v-if="form.attachments?.length" class="space-y-2 pt-1">
                                <p class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Queued For Upload ({{ form.attachments.length }})</p>
                                <div class="space-y-1.5">
                                    <div
                                        v-for="(file, index) in form.attachments"
                                        :key="`${file.name}-${file.size}-${index}`"
                                        class="flex items-center justify-between rounded-lg border border-[#e2e8e3] bg-[#f9fbfa] px-3 py-2 text-xs transition-colors hover:border-[#cbd5ce]"
                                    >
                                        <button
                                            type="button"
                                            class="group flex items-center gap-2.5 min-w-0 text-left cursor-pointer"
                                            @click="openQueuedAttachmentPreview(file)"
                                        >
                                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded bg-[#014d3c]/10 text-[0.6rem] font-bold text-[#014d3c] transition-colors group-hover:bg-[#014d3c] group-hover:text-white">
                                                {{ getFileExtension(file.name) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate font-semibold text-[#0f172a] transition-colors group-hover:text-[#014d3c]">{{ file.name }}</p>
                                                <p class="text-[0.65rem] text-[#64748b]">{{ formatFileSize(file.size) }}</p>
                                            </div>
                                        </button>
                                        <div class="ml-2 flex shrink-0 items-center gap-1.5">
                                            <button
                                                type="button"
                                                class="inline-flex h-6 items-center gap-1 rounded px-2 text-[0.65rem] font-bold text-[#014d3c] transition hover:bg-[#014d3c]/10"
                                                @click="openQueuedAttachmentPreview(file)"
                                            >
                                                <svg viewBox="0 0 20 20" class="h-3 w-3" fill="currentColor">
                                                    <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                                    <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                                                </svg>
                                                View
                                            </button>
                                            <button
                                                type="button"
                                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[#94a3b8] transition hover:bg-rose-50 hover:text-rose-600"
                                                title="Remove"
                                                @click="removeQueuedAttachment(index)"
                                            >
                                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing Files -->
                            <div v-if="form.existingAttachments?.length" class="space-y-2 pt-1">
                                <p class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Current Attachments ({{ form.existingAttachments.length }})</p>
                                <div class="space-y-1.5">
                                    <div
                                        v-for="attachment in form.existingAttachments"
                                        :key="attachment.id"
                                        class="flex items-center justify-between rounded-lg border border-[#dbe3dd] bg-white px-3 py-2 text-xs shadow-xs transition-colors hover:border-[#cbd5ce]"
                                    >
                                        <button
                                            type="button"
                                            class="group flex items-center gap-2.5 min-w-0 text-left cursor-pointer"
                                            @click="openAttachmentPreview(attachment)"
                                        >
                                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded bg-[#014d3c]/10 text-[0.6rem] font-bold text-[#014d3c] transition-colors group-hover:bg-[#014d3c] group-hover:text-white">
                                                {{ getFileExtension(attachment.name) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate font-semibold text-[#0f172a] transition-colors group-hover:text-[#014d3c]">{{ attachment.name }}</p>
                                                <p class="text-[0.65rem] text-[#64748b]">{{ attachment.uploadedAt || 'Previously uploaded' }}</p>
                                            </div>
                                        </button>
                                        <div class="ml-2 flex shrink-0 items-center gap-1.5">
                                            <button
                                                type="button"
                                                class="inline-flex h-6 items-center gap-1 rounded px-2 text-[0.65rem] font-bold text-[#014d3c] transition hover:bg-[#014d3c]/10"
                                                @click="openAttachmentPreview(attachment)"
                                            >
                                                <svg viewBox="0 0 20 20" class="h-3 w-3" fill="currentColor">
                                                    <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                                    <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                                                </svg>
                                                View
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex h-6 items-center rounded px-2 text-[0.65rem] font-bold text-rose-600 transition hover:bg-rose-50"
                                                @click="$emit('removeAttachment', attachment)"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right Sidebar Column -->
                <aside class="space-y-4">
                    <!-- Audience Targeting Card -->
                    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="flex items-center gap-2.5 border-b border-[#edf2ee] px-5 py-3.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="1.8">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-bold text-[#0f172a]">Audience Targeting</h2>
                        </div>

                        <div class="space-y-3.5 p-5">
                            <div class="space-y-2">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Select Audience Type</span>

                                <button
                                    v-for="option in audienceCards"
                                    :key="option.value"
                                    type="button"
                                    class="flex w-full items-start gap-3 rounded-xl border p-3 text-left transition-all duration-200"
                                    :class="form.audience_type === option.value ? 'border-[#014d3c] bg-[#eef7f2] shadow-xs' : 'border-[#dbe3dd] bg-white hover:border-[#b8c7be] hover:bg-[#f9fbfa]'"
                                    :disabled="disabled"
                                    @click="form.audience_type = option.value"
                                >
                                    <span
                                        class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border transition-colors"
                                        :class="form.audience_type === option.value ? 'border-[#014d3c] bg-[#014d3c]' : 'border-[#cbd5cf] bg-white'"
                                    >
                                        <span v-if="form.audience_type === option.value" class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-xs font-bold text-[#0f172a]">{{ option.title }}</span>
                                        <span class="mt-0.5 block text-[0.68rem] text-[#64748b] leading-relaxed">{{ option.description }}</span>
                                    </span>
                                </button>

                                <p v-if="form.errors.audience_type" class="text-xs font-medium text-rose-600">{{ form.errors.audience_type }}</p>
                            </div>

                            <!-- Conditional: Barangay Select -->
                            <div v-if="showBarangay" class="space-y-1.5 pt-1">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Select Barangay <span class="text-rose-500">*</span></span>
                                <select
                                    v-model="form.barangay_id"
                                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                    :disabled="disabled"
                                >
                                    <option value="">-- Choose Barangay --</option>
                                    <option v-for="barangay in reference.barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                                </select>
                                <p v-if="form.errors.barangay_id" class="text-xs font-medium text-rose-600">{{ form.errors.barangay_id }}</p>
                            </div>

                            <!-- Conditional: Member Type Select -->
                            <div v-if="showMemberType" class="space-y-1.5 pt-1">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Select Member Group <span class="text-rose-500">*</span></span>
                                <select
                                    v-model="form.member_type_id"
                                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                    :disabled="disabled"
                                >
                                    <option value="">-- Choose Member Group --</option>
                                    <option v-for="memberType in reference.memberTypes" :key="memberType.id" :value="String(memberType.id)">{{ memberType.label }}</option>
                                </select>
                                <p v-if="form.errors.member_type_id" class="text-xs font-medium text-rose-600">{{ form.errors.member_type_id }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Actions Card -->
                    <div class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm space-y-2.5">
                        <button
                            type="submit"
                            :disabled="disabled"
                            class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg v-if="disabled" class="h-3.5 w-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ disabled ? 'Saving...' : submitLabel }}
                        </button>
                        <a
                            :href="cancelHref"
                            class="inline-flex h-9 w-full items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-4 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]"
                        >
                            Cancel
                        </a>
                        <p class="text-center text-[0.65rem] text-[#94a3b8] leading-tight pt-1">
                            Advisories are saved as drafts until published.
                        </p>
                    </div>
                </aside>
            </div>
        </form>

        <!-- Attachment Preview Modal -->
        <div
            v-if="previewAttachment"
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/80 p-4 sm:p-6 backdrop-blur-sm"
            @click.self="closeAttachmentPreview"
        >
            <div class="relative flex flex-col w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-2xl bg-white shadow-2xl border border-[#dde4de]">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5 bg-white">
                    <div class="flex items-center gap-2.5 min-w-0 pr-4">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[0.6rem] font-bold text-[#014d3c]">
                            {{ getFileExtension(previewAttachment.name) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-[#0f172a]">{{ previewAttachment.name }}</p>
                            <p class="text-[0.65rem] text-[#64748b]">{{ previewAttachment.uploadedAt || 'Attachment Preview' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            :href="previewAttachment.downloadUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#014d3c] transition hover:bg-[#f4f7f5]"
                            title="Open in new window"
                        >
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 0 0-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 0 0 .75-.75v-4a.75.75 0 0 1 1.5 0v4A2.25 2.25 0 0 1 12.75 17h-8.5A2.25 2.25 0 0 1 2 14.75v-8.5A2.25 2.25 0 0 1 4.25 4h4a.75.75 0 0 1 0 1.5h-4Z" clip-rule="evenodd" />
                                <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 0 0 1.06.053L16.5 4.44v2.81a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-.75-.75h-4.5a.75.75 0 0 0 0 1.5h2.553l-9.056 8.194a.75.75 0 0 0-.053 1.06Z" clip-rule="evenodd" />
                            </svg>
                            <span class="hidden sm:inline">New Tab</span>
                        </a>
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dbe3dd] text-[#64748b] transition hover:bg-[#f4f7f5] hover:text-[#0f172a]"
                            @click="closeAttachmentPreview"
                            aria-label="Close modal"
                        >
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body: Image Preview -->
                <div v-if="isImageAttachment(previewAttachment)" class="flex max-h-[75vh] flex-1 items-center justify-center overflow-auto bg-[#f8faf9] p-4">
                    <img
                        :src="previewAttachment.downloadUrl"
                        :alt="previewAttachment.name"
                        class="max-h-[70vh] w-auto max-w-full rounded-xl object-contain shadow-sm"
                    >
                </div>

                <!-- Modal Body: PDF Preview -->
                <div v-else-if="isPdfAttachment(previewAttachment)" class="h-[75vh] w-full bg-[#f8faf9] p-2">
                    <iframe
                        :src="previewAttachment.downloadUrl"
                        class="h-full w-full rounded-xl border border-[#dbe3dd] bg-white"
                        title="PDF Preview"
                    ></iframe>
                </div>

                <!-- Modal Body: Other files -->
                <div v-else class="flex flex-col items-center justify-center bg-[#f8faf9] py-16 px-6 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#014d3c]/10 text-[#014d3c]">
                        <svg viewBox="0 0 24 24" class="h-8 w-8 fill-none stroke-current" stroke-width="1.8">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-sm font-bold text-[#0f172a]">{{ previewAttachment.name }}</h3>
                    <p class="mt-1 max-w-sm text-xs text-[#64748b]">
                        This file format cannot be previewed directly in the browser window. You can open or download it to view with your local software.
                    </p>
                    <a
                        :href="previewAttachment.downloadUrl"
                        target="_blank"
                        rel="noopener"
                        class="mt-5 inline-flex h-9 items-center gap-2 rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a]"
                    >
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd"/>
                        </svg>
                        Download / Open File
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
