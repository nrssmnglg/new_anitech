<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import InternalNotesPanel from '../../../Components/Admin/InternalNotesPanel.vue';

const props = defineProps({
    queryRecord: { type: Object, required: true },
    internalNotes: { type: Array, required: true },
    responseTemplates: { type: Array, required: true },
    urls: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const flashError = computed(() => page.props.flash?.error || '');
const actionError = ref('');

const form = useForm({
    message: '',
    attachments: [],
});

const acting = ref(false);
const previewAttachment = ref(null);

const statusLabel = computed(() => {
    if (props.queryRecord.status === 'In Progress') return 'In Progress';
    if (props.queryRecord.status === 'Resolved') return 'Resolved';
    if (props.queryRecord.status === 'Escalated') return 'Escalated';
    return 'New';
});

function applyTemplate(template) {
    form.message = template.message;
}

function onAttachmentChange(event) {
    form.attachments = Array.from(event.target.files ?? []);
}

function submitResponse() {
    if (form.processing || acting.value || props.queryRecord.status === 'Resolved') {
        return;
    }

    actionError.value = '';

    form.transform((data) => ({
        ...data,
        attachments: data.attachments,
    })).post(props.urls.respond, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: (err) => {
            actionError.value = typeof err === 'string' ? err : Object.values(err)[0] || 'Failed to submit response.';
        },
    });
}

function closeInquiry() {
    if (acting.value || props.queryRecord.status === 'Resolved') {
        return;
    }

    if (!window.confirm('Mark this inquiry as resolved?')) {
        return;
    }

    acting.value = true;
    actionError.value = '';

    router.post(props.urls.close, {}, {
        preserveScroll: true,
        onError: (err) => {
            actionError.value = typeof err === 'string' ? err : Object.values(err)[0] || 'Failed to resolve inquiry.';
        },
        onFinish: () => {
            acting.value = false;
        },
    });
}

function reopenInquiry() {
    if (acting.value || props.queryRecord.status !== 'Resolved') {
        return;
    }

    if (!window.confirm('Reopen this inquiry?')) {
        return;
    }

    acting.value = true;
    actionError.value = '';

    router.post(props.urls.reopen, {}, {
        preserveScroll: true,
        onError: (err) => {
            actionError.value = typeof err === 'string' ? err : Object.values(err)[0] || 'Failed to reopen inquiry.';
        },
        onFinish: () => {
            acting.value = false;
        },
    });
}

function escalateInquiry() {
    if (acting.value || props.queryRecord.status === 'Resolved' || props.queryRecord.status === 'Escalated') {
        return;
    }

    if (!window.confirm('Escalate this inquiry to admin?')) {
        return;
    }

    acting.value = true;
    actionError.value = '';

    router.post(props.urls.escalate, {}, {
        preserveScroll: true,
        onError: (err) => {
            actionError.value = typeof err === 'string' ? err : Object.values(err)[0] || 'Failed to escalate inquiry.';
        },
        onFinish: () => {
            acting.value = false;
        },
    });
}

function farmerInitials(name) {
    return String(name || 'Farmer')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('') || 'FM';
}

function statusTone(status) {
    if (status === 'In Progress') {
        return 'bg-[#dcfce7] text-[#15803d] border-[#bbf7d0]';
    }

    if (status === 'Resolved') {
        return 'bg-[#f1f5f9] text-[#475569] border-[#cbd5e1]';
    }

    if (status === 'Escalated') {
        return 'bg-[#fff1f2] text-[#e11d48] border-[#fecdd3]';
    }

    return 'bg-[#fffbeb] text-[#d97706] border-[#fde68a]';
}

function statusDotTone(status) {
    if (status === 'In Progress') return 'bg-[#22c55e]';
    if (status === 'Resolved') return 'bg-[#64748b]';
    if (status === 'Escalated') return 'bg-[#f43f5e]';
    return 'bg-[#f59e0b]';
}

function isImageAttachment(attachment) {
    return /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(String(attachment?.name ?? ''));
}

function openAttachment(attachment) {
    if (isImageAttachment(attachment)) {
        previewAttachment.value = attachment;
        return;
    }

    window.open(attachment.url, '_blank', 'noopener');
}

function closeAttachmentPreview() {
    previewAttachment.value = null;
}
</script>

<template>
    <Head title="Inquiry Thread" />

    <AdminLayout title="Inquiry Thread">
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3.5">
                        <Link :href="urls.index" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/20 text-white transition hover:bg-white/10" aria-label="Back to inquiry queue">
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.47 4.47a.75.75 0 1 1-1.06 1.06l-5.75-5.75a.75.75 0 0 1 0-1.06l5.75-5.75a.75.75 0 1 1 1.06 1.06L5.61 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/>
                            </svg>
                        </Link>
                        <div class="min-w-0">
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Communication</p>
                            <h1 class="truncate text-xl font-bold tracking-[-0.02em]">{{ queryRecord.subject }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <span class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs font-bold" :class="statusTone(queryRecord.status)">
                            <span class="h-2 w-2 rounded-full" :class="statusDotTone(queryRecord.status)"></span>
                            {{ statusLabel }}
                        </span>
                        <Link :href="urls.index" class="inline-flex h-9 items-center gap-1 rounded-lg border border-white/20 px-3 text-xs font-semibold text-white/90 transition hover:bg-white/10 hover:text-white">
                            Queue
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Flash & Error alerts -->
            <div v-if="flashSuccess" class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-sm">
                <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-emerald-600" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                <span>{{ flashSuccess }}</span>
            </div>

            <div v-if="flashError || actionError" class="flex items-center gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-800 shadow-sm">
                <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-rose-600" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                <span>{{ flashError || actionError }}</span>
            </div>

            <!-- Main Layout Grid -->
            <div class="grid gap-4 xl:grid-cols-[18rem_minmax(0,1fr)] items-start">
                <!-- Left Sidebar Column -->
                <div class="space-y-4">
                    <!-- Farmer Info Card -->
                    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-sm font-bold text-[#0f6b45] shadow-sm">
                                {{ farmerInitials(queryRecord.farmer.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-[#0f172a]">{{ queryRecord.farmer.name }}</p>
                                <p class="text-[0.68rem] font-mono text-[#64748b]">{{ queryRecord.farmer.code }}</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2 border-t border-[#edf2ee] pt-3 text-xs">
                            <div class="rounded-lg bg-[#f8faf9] p-2.5">
                                <p class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-[#94a3b8]">Barangay</p>
                                <p class="mt-0.5 font-bold text-[#0f172a]">{{ queryRecord.farmer.barangay }}</p>
                            </div>
                            <div class="rounded-lg bg-[#f8faf9] p-2.5">
                                <p class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-[#94a3b8]">Association</p>
                                <p class="mt-0.5 font-bold text-[#0f172a]">{{ queryRecord.farmer.association }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Thread Controls Card -->
                    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                        <h2 class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Thread Controls</h2>

                        <div class="mt-3 space-y-2">
                            <button
                                v-if="queryRecord.status === 'Resolved'"
                                type="button"
                                class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.98] disabled:opacity-60"
                                :disabled="acting || form.processing"
                                @click="reopenInquiry"
                            >
                                Reopen Inquiry
                            </button>
                            <button
                                v-else
                                type="button"
                                class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.98] disabled:opacity-60"
                                :disabled="acting || form.processing"
                                @click="closeInquiry"
                            >
                                Mark as Resolved
                            </button>

                            <button
                                v-if="queryRecord.canEscalateToAdmin && queryRecord.status !== 'Resolved' && queryRecord.status !== 'Escalated'"
                                type="button"
                                class="inline-flex h-9 w-full items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-4 text-xs font-bold text-rose-700 transition hover:bg-rose-100 disabled:opacity-60"
                                :disabled="acting || form.processing"
                                @click="escalateInquiry"
                            >
                                Escalate to Admin
                            </button>

                            <Link
                                :href="urls.index"
                                class="inline-flex h-9 w-full items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-4 text-xs font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]"
                            >
                                Back to Queue
                            </Link>
                        </div>
                    </section>

                    <!-- Accountability Card -->
                    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                        <h2 class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Accountability</h2>
                        <div class="mt-3 rounded-lg bg-[#f8faf9] p-3 text-xs">
                            <p class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-[#94a3b8]">Last Updated By</p>
                            <p class="mt-0.5 font-bold text-[#0f172a]">{{ queryRecord.accountability?.lastUpdatedBy || 'System' }}</p>
                            <p class="mt-0.5 text-[0.65rem] text-[#64748b]">{{ queryRecord.accountability?.lastUpdatedAt || 'Not recorded' }}</p>
                        </div>
                    </section>
                </div>

                <!-- Right Thread Discussion Column -->
                <div class="space-y-4">
                    <!-- Message Discussion Stream -->
                    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                        <h2 class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b] mb-4">Conversation Thread</h2>

                        <div class="space-y-4">
                            <!-- Farmer's Initial Inquiry -->
                            <article class="flex justify-start">
                                <div class="max-w-2xl rounded-2xl border border-[#fde68a] bg-[#fffbeb] p-4 text-[#0f172a] shadow-sm">
                                    <div class="flex items-center justify-between gap-4 border-b border-[#fde68a]/60 pb-2 text-[0.68rem] text-[#92400e]">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#fde68a] text-[0.6rem] font-bold text-[#78350f]">F</span>
                                            <span class="font-bold">{{ queryRecord.farmer.name }}</span>
                                        </div>
                                        <time class="text-[#78350f]">{{ queryRecord.submittedAt }}</time>
                                    </div>

                                    <p class="mt-3 whitespace-pre-line text-xs leading-relaxed text-[#334155]">{{ queryRecord.message }}</p>

                                    <!-- Attachments -->
                                    <div v-if="queryRecord.attachments.length" class="mt-3 flex flex-wrap gap-2 border-t border-[#fde68a]/60 pt-2.5">
                                        <button
                                            v-for="attachment in queryRecord.attachments"
                                            :key="attachment.id"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-[#fde68a] bg-white px-2.5 py-1 text-xs font-semibold text-[#b45309] transition hover:bg-[#fef3c7]"
                                            @click="openAttachment(attachment)"
                                        >
                                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                                <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.595 5.52l3.246-3.246a.75.75 0 011.06 1.06l-3.245 3.247a1.125 1.125 0 101.59 1.591l3.456-3.554a3 3 0 00-.081-4.24z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ attachment.name }}</span>
                                        </button>
                                    </div>
                                </div>
                            </article>

                            <!-- Staff Responses -->
                            <article v-for="response in queryRecord.responses" :key="response.id" class="flex justify-end">
                                <div class="max-w-2xl rounded-2xl border border-[#d1fae5] bg-[#f0fdf4] p-4 text-[#0f172a] shadow-sm">
                                    <div class="flex items-center justify-between gap-4 border-b border-[#d1fae5] pb-2 text-[0.68rem] text-[#065f46]">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-[#10b981] text-[0.6rem] font-bold text-white">S</span>
                                            <span class="font-bold">AniTech Support ({{ response.responder }})</span>
                                        </div>
                                        <time class="text-[#047857]">{{ response.respondedAt }}</time>
                                    </div>

                                    <p class="mt-3 whitespace-pre-line text-xs leading-relaxed text-[#334155]">{{ response.message }}</p>

                                    <!-- Attachments -->
                                    <div v-if="response.attachments.length" class="mt-3 flex flex-wrap gap-2 border-t border-[#d1fae5] pt-2.5">
                                        <button
                                            v-for="attachment in response.attachments"
                                            :key="attachment.id"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-[#a7f3d0] bg-white px-2.5 py-1 text-xs font-semibold text-[#047857] transition hover:bg-[#ecfdf5]"
                                            @click="openAttachment(attachment)"
                                        >
                                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                                <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.595 5.52l3.246-3.246a.75.75 0 011.06 1.06l-3.245 3.247a1.125 1.125 0 101.59 1.591l3.456-3.554a3 3 0 00-.081-4.24z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ attachment.name }}</span>
                                        </button>
                                    </div>
                                </div>
                            </article>

                            <!-- Empty thread state -->
                            <div v-if="queryRecord.responses.length === 0" class="rounded-xl border border-dashed border-[#dde4de] bg-[#fbfcfb] p-6 text-center text-xs text-[#94a3b8]">
                                <svg viewBox="0 0 24 24" class="mx-auto h-7 w-7 text-[#cbd5e1]" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="mt-2 font-medium">No response has been recorded yet. Compose a reply below.</p>
                            </div>
                        </div>
                    </section>

                    <!-- Response Composer -->
                    <section v-if="queryRecord.status !== 'Resolved'" class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                        <form class="space-y-4" @submit.prevent="submitResponse">
                            <!-- Quick Templates -->
                            <div v-if="responseTemplates.length" class="space-y-2 rounded-xl border border-[#e2eae4] bg-[#f8faf9] p-3.5">
                                <p class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Quick Response Templates</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="template in responseTemplates"
                                        :key="template.key"
                                        type="button"
                                        class="inline-flex items-center rounded-lg border border-[#dbe3dd] bg-white px-2.5 py-1 text-xs font-semibold text-[#0f6b45] shadow-xs transition hover:border-[#014d3c] hover:bg-[#e6f5ec]"
                                        @click="applyTemplate(template)"
                                    >
                                        {{ template.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Message Textarea -->
                            <div class="space-y-1.5">
                                <label class="block text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Your Response</label>
                                <textarea
                                    v-model="form.message"
                                    rows="4"
                                    class="w-full rounded-xl border border-[#dbe3dd] bg-[#f9fbfa] p-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                    :placeholder="`Compose response to ${queryRecord.farmer.name}...`"
                                />
                                <p v-if="form.errors.message" class="text-xs font-medium text-rose-600">{{ form.errors.message }}</p>
                            </div>

                            <!-- Footer controls -->
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-[#edf2ee] pt-3.5">
                                <div class="flex items-center gap-3">
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-[#dbe3dd] bg-white px-3 py-1.5 text-xs font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]">
                                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                            <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.595 5.52l3.246-3.246a.75.75 0 011.06 1.06l-3.245 3.247a1.125 1.125 0 101.59 1.591l3.456-3.554a3 3 0 00-.081-4.24z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Attach Files</span>
                                        <input type="file" multiple class="hidden" @change="onAttachmentChange">
                                    </label>
                                    <span v-if="form.attachments.length" class="text-xs font-semibold text-[#0f6b45]">
                                        {{ form.attachments.length }} file{{ form.attachments.length === 1 ? '' : 's' }} selected
                                    </span>
                                </div>

                                <button
                                    type="submit"
                                    class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97] disabled:opacity-60"
                                    :disabled="form.processing || acting"
                                >
                                    {{ form.processing ? 'Sending...' : 'Send Response' }}
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Internal Notes -->
                    <InternalNotesPanel :notes="internalNotes" :submit-url="urls.storeInternalNote" title="Internal Discussion Notes" />
                </div>
            </div>
        </div>

        <!-- Attachment Lightbox Modal -->
        <div
            v-if="previewAttachment"
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/80 p-4 backdrop-blur-sm"
            @click.self="closeAttachmentPreview"
        >
            <div class="relative w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                    <p class="truncate text-xs font-bold text-[#0f172a]">{{ previewAttachment.name }}</p>
                    <div class="flex items-center gap-2">
                        <a
                            :href="previewAttachment.url"
                            :download="previewAttachment.name"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dbe3dd] text-[#0f6b45] transition hover:bg-[#f4f7f5]"
                            aria-label="Download attachment"
                        >
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dbe3dd] text-[#64748b] transition hover:bg-[#f4f7f5]"
                            @click="closeAttachmentPreview"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <div class="flex max-h-[75vh] items-center justify-center bg-[#f8faf9] p-4">
                    <img
                        :src="previewAttachment.url"
                        :alt="previewAttachment.name"
                        class="max-h-[70vh] w-auto max-w-full rounded-xl object-contain shadow-sm"
                    >
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
