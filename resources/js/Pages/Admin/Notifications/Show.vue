<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    dispatch: { type: Object, required: true },
    recipients: { type: Array, required: true },
    urls: { type: Object, required: true },
});

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function deliveryTone(label) {
    if (label === 'Failed') {
        return 'bg-rose-50 text-rose-700 border-rose-200';
    }
    if (label === 'Delivered') {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }
    if (label === 'Read') {
        return 'bg-sky-50 text-sky-700 border-sky-200';
    }
    return 'bg-amber-50 text-amber-700 border-amber-200';
}

function deliveryDotTone(label) {
    if (label === 'Failed') return 'bg-rose-500';
    if (label === 'Delivered') return 'bg-emerald-500';
    if (label === 'Read') return 'bg-sky-500';
    return 'bg-amber-500';
}

function recipientTone(status) {
    if (status === 'failed') {
        return 'bg-rose-50 text-rose-700 border-rose-200';
    }
    if (status === 'delivered') {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }
    if (status === 'read') {
        return 'bg-sky-50 text-sky-700 border-sky-200';
    }
    return 'bg-amber-50 text-amber-700 border-amber-200';
}

function resendDispatch() {
    if (!window.confirm('Resend this notification dispatch?')) {
        return;
    }

    router.post(props.dispatch.resend_url, {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Notification Detail" />

    <AdminLayout title="Notification Detail">
        <div class="notification-show space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-24 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
                                <path d="M10 21a2 2 0 0 0 4 0" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Dispatch Record</p>
                                <span class="rounded bg-[#7ddfb8]/20 px-2 py-0.5 text-[0.6rem] font-bold text-[#7ddfb8] border border-[#7ddfb8]/30">
                                    {{ dispatch.module_label }}
                                </span>
                            </div>
                            <h1 class="mt-0.5 truncate text-xl font-bold tracking-[-0.02em]">{{ dispatch.type_label }}</h1>
                            <p class="mt-0.5 truncate text-xs text-white/70">{{ dispatch.subject }}</p>
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
                            Back to Center
                        </Link>
                        <button
                            type="button"
                            class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-xs font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                            @click="resendDispatch"
                        >
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#003629]" fill="currentColor">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.75a.75.75 0 0 0-.75.75v4.482a.75.75 0 0 0 1.5 0v-2.09l.348.347a7 7 0 0 0 11.705-3.136.75.75 0 0 0-1.241-.508ZM4.688 8.576a5.5 5.5 0 0 1 9.201-2.466l.312.311H11.77a.75.75 0 0 0 0 1.5h4.48a.75.75 0 0 0 .75-.75V2.689a.75.75 0 0 0-1.5 0v2.09l-.348-.347A7 7 0 0 0 3.447 7.568a.75.75 0 1 0 1.241.508Z" clip-rule="evenodd" />
                            </svg>
                            Resend Dispatch
                        </button>
                    </div>
                </div>
            </section>

            <!-- Metrics Cards Grid -->
            <section class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <article class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                    <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Total Recipients</span>
                    <p class="mt-2 text-xl font-bold text-[#0f172a]">{{ dispatch.recipient_count }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-[#64748b]">Total targeted</p>
                </article>

                <article class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                    <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Delivered</span>
                    <p class="mt-2 text-xl font-bold text-emerald-700">{{ dispatch.delivered_count }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-[#64748b]">Confirmed arrival</p>
                </article>

                <article class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                    <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Failed</span>
                    <p class="mt-2 text-xl font-bold text-rose-700">{{ dispatch.failed_count }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-[#64748b]">Undelivered</p>
                </article>

                <article class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                    <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Read / Opened</span>
                    <p class="mt-2 text-xl font-bold text-sky-700">{{ dispatch.read_count }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-[#64748b]">Opened by user</p>
                </article>

                <article class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                    <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Delivery State</span>
                    <div class="mt-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[0.65rem] font-bold" :class="deliveryTone(dispatch.delivery_label)">
                            <span class="h-1.5 w-1.5 rounded-full" :class="deliveryDotTone(dispatch.delivery_label)"></span>
                            {{ dispatch.delivery_label }}
                        </span>
                    </div>
                    <p class="mt-1 text-[0.68rem] text-[#64748b]">Queued {{ formatDate(dispatch.queued_at || dispatch.created_at) }}</p>
                </article>
            </section>

            <!-- Message Card -->
            <section v-if="dispatch.message" class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="border-b border-[#edf2ee] px-5 py-3.5">
                    <h2 class="text-sm font-bold text-[#0f172a]">Broadcast Message Content</h2>
                </div>
                <div class="p-5">
                    <p class="text-xs sm:text-sm leading-relaxed text-[#334155] whitespace-pre-wrap">{{ dispatch.message }}</p>
                </div>
            </section>

            <!-- Recipients Table Card -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="border-b border-[#edf2ee] px-5 py-3.5">
                    <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Recipient Breakdown ({{ recipients.length }})</h2>
                    <p class="mt-0.5 text-[0.68rem] text-[#64748b]">Per-recipient delivery status, timestamps, and failure logs</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-[#edf2ee] bg-[#f8faf9] text-[0.65rem] font-bold uppercase tracking-wider text-[#64748b]">
                            <tr>
                                <th class="px-5 py-3">Recipient</th>
                                <th class="px-5 py-3">Address</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Delivered</th>
                                <th class="px-5 py-3">Read</th>
                                <th class="px-5 py-3">Failed</th>
                                <th class="px-5 py-3">Failure Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee] text-[#0f172a]">
                            <tr v-for="recipient in recipients" :key="recipient.id" class="transition hover:bg-[#f8fbf9]">
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-[#0f172a]">{{ recipient.user_name || recipient.farmer_code || `Recipient #${recipient.id}` }}</p>
                                    <p v-if="recipient.farmer_code" class="text-[0.65rem] text-[#64748b] font-mono">{{ recipient.farmer_code }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-[#64748b] font-mono">{{ recipient.recipient_address || 'No address' }}</td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[0.65rem] font-bold" :class="recipientTone(recipient.status)">
                                        {{ recipient.status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-[#64748b] whitespace-nowrap">{{ formatDate(recipient.delivered_at) }}</td>
                                <td class="px-5 py-3.5 text-[#64748b] whitespace-nowrap">{{ formatDate(recipient.read_at) }}</td>
                                <td class="px-5 py-3.5 text-[#64748b] whitespace-nowrap">{{ formatDate(recipient.failed_at) }}</td>
                                <td class="px-5 py-3.5 text-xs text-rose-700 max-w-xs">{{ recipient.failure_reason || '-' }}</td>
                            </tr>
                            <tr v-if="recipients.length === 0">
                                <td colspan="7" class="py-12 text-center text-xs text-[#94a3b8]">
                                    No recipients found for this notification.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
