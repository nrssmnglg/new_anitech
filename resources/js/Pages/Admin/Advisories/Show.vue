<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    advisory: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const busyAction = ref(null);
const previewAttachment = ref(null);

function publish() {
    if (!props.advisory.actions.publish || busyAction.value !== null) {
        return;
    }

    if (!window.confirm(`Publish "${props.advisory.title}" now?`)) {
        return;
    }

    busyAction.value = 'publish';
    router.post(props.advisory.actions.publish, {}, {
        preserveScroll: true,
        onFinish: () => {
            busyAction.value = null;
        },
    });
}

function unpublish() {
    if (!props.advisory.actions.unpublish || busyAction.value !== null) {
        return;
    }

    if (!window.confirm(`Move "${props.advisory.title}" back to draft?`)) {
        return;
    }

    busyAction.value = 'unpublish';
    router.post(props.advisory.actions.unpublish, {}, {
        preserveScroll: true,
        onFinish: () => {
            busyAction.value = null;
        },
    });
}

function removeAttachment(attachment) {
    if (!window.confirm(`Remove ${attachment.name}?`)) {
        return;
    }

    if (previewAttachment.value?.id === attachment.id) {
        previewAttachment.value = null;
    }

    router.delete(attachment.deleteUrl, {
        preserveScroll: true,
    });
}

function statusDotTone(status) {
    if (status === 'Published') return 'bg-[#22c55e]';
    if (status === 'Draft') return 'bg-[#f59e0b]';
    return 'bg-[#94a3b8]';
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

function closeAttachmentPreview() {
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
    <Head :title="advisory.title" />

    <AdminLayout title="Advisory Record">
        <div class="advisory-show space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-24 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 10a6 6 0 00-6-6v12a6 6 0 006-6v0z" />
                                <path d="M18 10l3 2v-4l-3 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Advisory Record</p>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[0.6rem] font-bold"
                                    :class="advisory.status === 'Published' ? 'bg-[#7ddfb8]/20 text-[#7ddfb8]' : advisory.status === 'Draft' ? 'bg-[#fbbf24]/20 text-[#fbbf24]' : 'bg-white/20 text-white/80'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="advisory.status === 'Published' ? 'bg-[#7ddfb8]' : advisory.status === 'Draft' ? 'bg-[#fbbf24]' : 'bg-white/80'"></span>
                                    {{ advisory.status }}
                                </span>
                            </div>
                            <h1 class="mt-0.5 truncate text-xl font-bold tracking-[-0.02em]">{{ advisory.title }}</h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <Link
                            :href="urls.index"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 bg-white/10 px-3.5 text-xs font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20 active:scale-[0.98]"
                        >
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                            </svg>
                            Advisories
                        </Link>
                        <Link
                            :href="urls.edit"
                            class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-xs font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                        >
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#003629]" fill="currentColor">
                                <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                            </svg>
                            Edit Advisory
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Metadata Metrics -->
            <section class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <!-- Status Card -->
                <article class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <span class="h-2 w-2 rounded-full" :class="statusDotTone(advisory.status)"></span>
                    </div>
                    <p class="mt-2 text-base font-bold text-[#0f172a]">{{ advisory.status }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ advisory.publishedAt ? `Published ${advisory.publishedAt}` : 'Pending publication' }}</p>
                </article>

                <!-- Audience Card -->
                <article class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Target Audience</span>
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#64748b]" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <p class="mt-2 text-base font-bold text-[#0f172a]">{{ advisory.audienceLabel }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-[#64748b]">Created {{ advisory.createdAt }}</p>
                </article>

                <!-- Publisher Card -->
                <article class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Publisher</span>
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#64748b]" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="12" r="10" />
                            <circle cx="12" cy="10" r="3" />
                            <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662" />
                        </svg>
                    </div>
                    <p class="mt-2 text-base font-bold text-[#0f172a]">{{ advisory.publisher || 'Not assigned' }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ advisory.status === 'Published' ? 'Official Broadcaster' : 'Staff Editor' }}</p>
                </article>

                <!-- Attachments Count Card -->
                <article class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Attached Files</span>
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#64748b]" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                        </svg>
                    </div>
                    <p class="mt-2 text-base font-bold text-[#0f172a]">{{ advisory.attachments?.length || 0 }} file{{ advisory.attachments?.length === 1 ? '' : 's' }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ advisory.attachments?.length > 0 ? 'Downloadable assets' : 'No attachments' }}</p>
                </article>
            </section>

            <!-- Main Content Grid -->
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                <!-- Left: Broadcast Message Card -->
                <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="1.8">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-bold text-[#0f172a]">Broadcast Message</h2>
                        </div>
                        <span class="text-[0.68rem] text-[#64748b]">{{ advisory.content?.length || 0 }} characters</span>
                    </div>

                    <div class="p-6">
                        <div class="whitespace-pre-wrap text-xs sm:text-sm leading-relaxed text-[#334155] font-normal">
                            {{ advisory.content }}
                        </div>
                    </div>
                </section>

                <!-- Right: Actions & Attachments Sidebar -->
                <aside class="space-y-4">
                    <!-- Publishing Actions Card -->
                    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="flex items-center gap-2.5 border-b border-[#edf2ee] px-5 py-3.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="1.8">
                                    <path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 10a6 6 0 00-6-6v12a6 6 0 006-6v0z" />
                                    <path d="M18 10l3 2v-4l-3 2z" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-bold text-[#0f172a]">Publishing Controls</h2>
                        </div>

                        <div class="space-y-3 p-5">
                            <!-- Publish Button -->
                            <button
                                v-if="advisory.actions?.publish"
                                type="button"
                                :disabled="busyAction !== null"
                                class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60"
                                @click="publish"
                            >
                                <svg v-if="busyAction === 'publish'" class="h-3.5 w-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else viewBox="0 0 20 20" class="h-3.5 w-3.5 text-white" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                                </svg>
                                {{ busyAction === 'publish' ? 'Publishing...' : 'Publish Advisory' }}
                            </button>

                            <!-- Unpublish Button -->
                            <button
                                v-if="advisory.actions?.unpublish"
                                type="button"
                                :disabled="busyAction !== null"
                                class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg border border-[#fde68a] bg-[#fffbeb] px-4 text-xs font-bold text-[#b45309] shadow-xs transition-all duration-200 hover:bg-[#fef3c7] hover:border-[#fcd34d] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60"
                                @click="unpublish"
                            >
                                <svg v-if="busyAction === 'unpublish'" class="h-3.5 w-3.5 animate-spin text-[#b45309]" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#b45309]" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 10a.75.75 0 0 1 .75-.75h10.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 10Z" clip-rule="evenodd" />
                                </svg>
                                {{ busyAction === 'unpublish' ? 'Updating...' : 'Move Back to Draft' }}
                            </button>

                            <p class="text-[0.68rem] text-[#64748b] leading-relaxed">
                                {{ advisory.status === 'Published' ? 'This advisory is currently live and viewable by farmers in their advisory alerts feed.' : 'This advisory is currently in draft state and is only visible to administrative staff.' }}
                            </p>
                        </div>
                    </section>

                    <!-- Attachments List Card -->
                    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="1.8">
                                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                                    </svg>
                                </span>
                                <h2 class="text-sm font-bold text-[#0f172a]">Attachments</h2>
                            </div>
                            <span class="text-[0.68rem] text-[#64748b]">{{ advisory.attachments?.length || 0 }} total</span>
                        </div>

                        <div class="space-y-2 p-4">
                            <div
                                v-for="attachment in advisory.attachments"
                                :key="attachment.id"
                                class="flex items-center justify-between rounded-lg border border-[#e2e8e3] bg-[#f9fbfa] px-3 py-2 text-xs transition-colors hover:border-[#cbd5ce]"
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
                                        <p class="text-[0.65rem] text-[#64748b]">{{ attachment.uploadedAt || 'Uploaded' }}</p>
                                    </div>
                                </button>
                                <div class="ml-2 flex shrink-0 items-center gap-1">
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
                                        @click="removeAttachment(attachment)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div v-if="!advisory.attachments || advisory.attachments.length === 0" class="py-6 text-center">
                                <p class="text-xs text-[#94a3b8]">No files attached to this advisory.</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>

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
                            <p class="text-[0.65rem] text-[#64748b]">{{ previewAttachment.uploadedAt || 'Advisory Attachment' }}</p>
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

                <!-- Modal Body: Unsupported file type for direct inline rendering -->
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
    </AdminLayout>
</template>
