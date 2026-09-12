<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import InternalNotesPanel from '../../../Components/Admin/InternalNotesPanel.vue';

const props = defineProps({
    queryRecord: { type: Object, required: true },
    internalNotes: { type: Array, required: true },
    responseTemplates: { type: Array, required: true },
    urls: { type: Object, required: true },
});

const form = useForm({
    message: '',
    attachments: [],
});

const acting = ref(false);
const previewAttachment = ref(null);

const statusLabel = computed(() => {
    if (props.queryRecord.status === 'In Progress') {
        return 'In Progress';
    }

    if (props.queryRecord.status === 'Resolved') {
        return 'Resolved';
    }

    if (props.queryRecord.status === 'Escalated') {
        return 'Escalated';
    }

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

    form.transform((data) => ({
        ...data,
        attachments: data.attachments,
    })).post(props.urls.respond, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

function closeInquiry() {
    if (acting.value || props.queryRecord.status === 'Resolved') {
        return;
    }

    if (!window.confirm('Close this inquiry?')) {
        return;
    }

    acting.value = true;

    router.post(props.urls.close, {}, {
        preserveScroll: true,
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

    router.post(props.urls.reopen, {}, {
        preserveScroll: true,
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

    router.post(props.urls.escalate, {}, {
        preserveScroll: true,
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

function responderInitials(name) {
    return String(name || 'Support')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('') || 'SP';
}

function statusTone(status) {
    if (status === 'In Progress') {
        return 'bg-[#e4f4c8] text-[#4b7517]';
    }

    if (status === 'Resolved') {
        return 'bg-[#d9e5de] text-[#4a5b53]';
    }

    if (status === 'Escalated') {
        return 'bg-[#ffe4e7] text-[#cf3657]';
    }

    return 'bg-[#fff1cd] text-[#c07a00]';
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
        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <Link :href="urls.index" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-white/20 text-white transition hover:bg-white/10" aria-label="Back to inquiry queue">
                            <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2.2">
                                <path d="M15 18 9 12l6-6" />
                            </svg>
                        </Link>
                        <div class="min-w-0">
                            <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">Inquiry thread</p>
                            <h1 class="truncate text-lg font-semibold text-white">{{ queryRecord.subject }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <span class="rounded-full px-2.5 py-1 text-[0.65rem] font-semibold" :class="statusTone(queryRecord.status)">
                            {{ statusLabel }}
                        </span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-[0.65rem] font-semibold text-white">
                            {{ responderInitials('AniTech Support') }}
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-3 xl:grid-cols-[16rem_minmax(0,1fr)]">
                <div class="space-y-3">
                    <section class="rounded-lg border border-[#d9e2dc] bg-white p-3">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-[#0f5b46] text-xs font-semibold text-white">
                                {{ farmerInitials(queryRecord.farmer.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold leading-4 text-[#0f172a]">{{ queryRecord.farmer.name }}</p>
                                <p class="mt-0.5 text-[0.65rem] text-[#52626b]">{{ queryRecord.farmer.code }}</p>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            <div class="rounded-md bg-[#f4f6f4] px-3 py-2">
                                <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-[#71808b]">Barangay</p>
                                <p class="mt-0.5 text-[0.68rem] font-semibold text-[#0f172a]">{{ queryRecord.farmer.barangay }}</p>
                            </div>
                            <div class="rounded-md bg-[#f4f6f4] px-3 py-2">
                                <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-[#71808b]">Association</p>
                                <p class="mt-0.5 text-[0.68rem] font-semibold text-[#0f172a]">{{ queryRecord.farmer.association }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-[#d9e2dc] bg-white p-3">
                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#52626b]">Thread controls</p>

                        <div class="mt-2 space-y-2">
                            <button
                                v-if="queryRecord.status === 'Resolved'"
                                type="button"
                                class="inline-flex h-9 w-full items-center justify-center rounded-md bg-[#0f5b46] px-3 text-xs font-semibold text-white transition hover:bg-[#0b4938] disabled:opacity-60"
                                :disabled="acting || form.processing"
                                @click="reopenInquiry"
                            >
                                Reopen Inquiry
                            </button>
                            <button
                                v-else
                                type="button"
                                class="inline-flex h-9 w-full items-center justify-center rounded-md bg-[#0f5b46] px-3 text-xs font-semibold text-white transition hover:bg-[#0b4938] disabled:opacity-60"
                                :disabled="acting || form.processing"
                                @click="closeInquiry"
                            >
                                Mark as Resolved
                            </button>
                            <button
                                v-if="queryRecord.canEscalateToAdmin && queryRecord.status !== 'Resolved' && queryRecord.status !== 'Escalated'"
                                type="button"
                                class="inline-flex h-9 w-full items-center justify-center rounded-md border border-[#f0c6cf] bg-[#fff5f7] px-3 text-xs font-semibold text-[#b73d59] transition hover:bg-[#ffedf1] disabled:opacity-60"
                                :disabled="acting || form.processing"
                                @click="escalateInquiry"
                            >
                                Escalate to Admin
                            </button>

                            <Link
                                :href="urls.index"
                                class="inline-flex h-9 w-full items-center justify-center rounded-md border border-[#e58e8e] bg-white px-3 text-xs font-semibold text-[#c94f4f] transition hover:bg-[#fff6f6]"
                            >
                                Back to Queue
                            </Link>
                        </div>

                    </section>

                    <section class="rounded-lg border border-[#d9e2dc] bg-white p-3">
                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#52626b]">Accountability</p>
                        <div class="mt-2 space-y-2">
                            <div class="rounded-md bg-[#f4f6f4] px-3 py-2">
                                <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-[#71808b]">Last record edited by</p>
                                <p class="mt-0.5 text-[0.68rem] font-semibold text-[#0f172a]">{{ queryRecord.accountability?.lastUpdatedBy || 'No staff update recorded' }}</p>
                                <p class="mt-0.5 text-[0.6rem] text-[#71808b]">{{ queryRecord.accountability?.lastUpdatedAt || 'Not recorded' }}</p>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-3">
                    <section class="rounded-lg border border-[#d9e2dc] bg-white p-3">
                        <div class="space-y-3">
                            <article class="flex justify-start">
                                <div class="max-w-[46rem] rounded-lg border border-[#e6f1d8] bg-[#fff9ec] px-3 py-2.5 text-[#3b3120]">
                                    <div class="flex items-center justify-between gap-4 text-[0.65rem] text-[#7d6a43]">
                                        <span class="font-semibold">{{ queryRecord.farmer.name }}</span>
                                        <span>{{ queryRecord.submittedAt }}</span>
                                    </div>
                                    <p class="mt-1.5 whitespace-pre-line text-xs leading-5">{{ queryRecord.message }}</p>

                                    <div v-if="queryRecord.attachments.length" class="mt-4 flex flex-wrap gap-2">
                                        <button
                                            v-for="attachment in queryRecord.attachments"
                                            :key="attachment.id"
                                            type="button"
                                            class="inline-flex items-center rounded-full border border-[#ecdca6] bg-white px-3 py-2 text-xs font-bold text-[#8a6218] transition hover:bg-[#fffef8]"
                                            @click="openAttachment(attachment)"
                                        >
                                            {{ attachment.name }}
                                        </button>
                                    </div>
                                </div>
                            </article>

                            <article v-for="response in queryRecord.responses" :key="response.id" class="flex justify-end">
                                <div class="max-w-[46rem] rounded-lg border border-[#e3e7e5] bg-white px-3 py-2.5 text-[#22343b]">
                                    <div class="flex items-center justify-between gap-4 text-[0.65rem] text-[#52626b]">
                                        <span class="font-semibold">AniTech Support ({{ response.responder }})</span>
                                        <span>{{ response.respondedAt }}</span>
                                    </div>
                                    <p class="mt-1.5 whitespace-pre-line text-xs leading-5">{{ response.message }}</p>

                                    <div v-if="response.attachments.length" class="mt-4 flex flex-wrap gap-2">
                                        <button
                                            v-for="attachment in response.attachments"
                                            :key="attachment.id"
                                            type="button"
                                            class="inline-flex items-center rounded-full border border-[#d9e2dc] bg-[#f8fbf9] px-3 py-2 text-xs font-bold text-[#0f5b46] transition hover:bg-[#eef5f1]"
                                            @click="openAttachment(attachment)"
                                        >
                                            {{ attachment.name }}
                                        </button>
                                    </div>
                                </div>
                            </article>

                            <div v-if="queryRecord.responses.length === 0" class="rounded-[1.5rem] border border-dashed border-[#d9e2dc] bg-[#fbfdfc] px-5 py-8 text-center text-sm text-[#71808b]">
                                No response has been recorded yet. Send the first reply below.
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-[#d9e2dc] bg-white p-3">
                        <form v-if="queryRecord.status !== 'Resolved'" class="space-y-3" @submit.prevent="submitResponse">
                            <div class="space-y-2 rounded-md border border-[#d9e2dc] bg-[#fbfdfc] p-3">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#52626b]">Response templates</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="template in responseTemplates"
                                        :key="template.key"
                                        type="button"
                                        class="inline-flex items-center rounded-full border border-[#d9e2dc] bg-white px-3 py-2 text-xs font-black text-[#36554a] transition hover:bg-[#f4f7f5]"
                                        @click="applyTemplate(template)"
                                    >
                                        {{ template.label }}
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-md bg-[#f5f7f6] p-2">
                                <textarea
                                    v-model="form.message"
                                    rows="4"
                                    class="w-full resize-none bg-transparent px-2 py-2 text-xs text-[#0f172a] outline-none"
                                    :placeholder="`Type your response to ${queryRecord.farmer.name}...`"
                                />
                            </div>
                            <p v-if="form.errors.message" class="text-sm font-medium text-[#c94f4f]">{{ form.errors.message }}</p>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex flex-wrap items-center gap-3 text-sm text-[#52626b]">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-[#d9e2dc] px-3 py-2 transition hover:bg-[#f5f7f6]">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2">
                                            <path d="M21.4 11.1 12.3 20a5 5 0 1 1-7.1-7.1l9.2-9.2a3.5 3.5 0 1 1 5 5l-9.5 9.5a2 2 0 0 1-2.8-2.8l8.5-8.5" />
                                        </svg>
                                        <span class="font-medium">Attach Files</span>
                                        <input type="file" multiple class="hidden" @change="onAttachmentChange">
                                    </label>
                                    <span class="text-xs">{{ queryRecord.status === 'Escalated' ? 'Escalated priority' : 'Standard priority' }}</span>
                                </div>

                                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#0f5b46] px-4 text-xs font-semibold text-white transition hover:bg-[#0b4938] disabled:opacity-60" :disabled="form.processing || acting">
                                    {{ form.processing ? 'Sending...' : 'Send Response' }}
                                </button>
                            </div>

                            <p v-if="form.attachments.length" class="text-xs text-[#52626b]">
                                {{ form.attachments.length }} file{{ form.attachments.length === 1 ? '' : 's' }} selected
                            </p>
                            <p v-if="form.errors.attachments" class="text-sm font-medium text-[#c94f4f]">{{ form.errors.attachments }}</p>
                            <p v-if="form.errors['attachments.0']" class="text-sm font-medium text-[#c94f4f]">{{ form.errors['attachments.0'] }}</p>
                        </form>
                    </section>

                    <InternalNotesPanel :notes="internalNotes" :submit-url="urls.storeInternalNote" title="Inquiry Internal Notes" />
                </div>
            </div>
        </div>

        <div
            v-if="previewAttachment"
            class="fixed inset-0 z-[90] flex items-center justify-center bg-[#061510]/80 p-6"
            @click.self="closeAttachmentPreview"
        >
            <div class="relative w-full max-w-5xl overflow-hidden rounded-[1.75rem] bg-white shadow-[0_24px_60px_rgba(0,0,0,0.28)]">
                <div class="flex items-center justify-between border-b border-[#e5ece8] px-5 py-4">
                    <p class="truncate pr-4 text-sm font-bold text-[#0f172a]">{{ previewAttachment.name }}</p>
                    <div class="flex items-center gap-3">
                        <a
                            :href="previewAttachment.url"
                            :download="previewAttachment.name"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#d9e2dc] text-[#0f5b46] transition hover:bg-[#f5f7f6]"
                            aria-label="Download attachment"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <path d="M12 4v10" />
                                <path d="m8 10 4 4 4-4" />
                                <path d="M5 19h14" />
                            </svg>
                        </a>
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#d9e2dc] text-[#52626b] transition hover:bg-[#f5f7f6]"
                            aria-label="Close preview"
                            @click="closeAttachmentPreview"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <path d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex max-h-[80vh] items-center justify-center bg-[#f7faf8] p-4">
                    <img
                        :src="previewAttachment.url"
                        :alt="previewAttachment.name"
                        class="max-h-[72vh] w-auto max-w-full rounded-2xl object-contain"
                    >
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
