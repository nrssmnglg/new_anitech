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
        return 'bg-[#ffe6ea] text-[#b42341]';
    }

    if (label === 'Delivered') {
        return 'bg-[#e7f7ea] text-[#166534]';
    }

    return 'bg-[#fff4db] text-[#b56a00]';
}

function recipientTone(status) {
    if (status === 'failed') {
        return 'bg-[#ffe6ea] text-[#b42341]';
    }

    if (status === 'delivered') {
        return 'bg-[#e7f7ea] text-[#166534]';
    }

    if (status === 'read') {
        return 'bg-[#e8f0ff] text-[#1d4ed8]';
    }

    return 'bg-[#fff4db] text-[#b56a00]';
}

function resendDispatch() {
    router.post(props.dispatch.resend_url, {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Notification Detail" />

    <AdminLayout title="Notification Detail">
        <div class="notification-show space-y-3">
            <section class="notification-show-hero relative overflow-hidden rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="absolute inset-0 bg-[radial-gradient(at_0%_0%,_#1b4d3e_0%,_transparent_50%),radial-gradient(at_100%_0%,_#376757_0%,_transparent_48%),radial-gradient(at_100%_100%,_#16332c_0%,_transparent_50%),radial-gradient(at_0%_100%,_#003629_0%,_transparent_48%)]"></div>
                <div class="absolute -right-14 top-[-52px] h-60 w-60 rounded-full bg-[#a5d577]/10 blur-[90px]"></div>
                <div class="absolute -bottom-24 left-[18%] h-72 w-72 rounded-full bg-white/10 blur-[110px]"></div>

                <div class="relative z-10 space-y-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <div class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-md">
                                <span class="h-2.5 w-2.5 rounded-full bg-[#c0f190] shadow-[0_0_16px_rgba(192,241,144,0.8)]"></span>
                                <span class="text-[0.68rem] font-black uppercase tracking-[0.28em] text-white/85">Dispatch Overview</span>
                            </div>

                            <h1 class="mt-4 text-4xl font-black tracking-[-0.045em] sm:text-[2.5rem]">{{ dispatch.type_label }}</h1>
                            <p class="mt-3 max-w-2xl text-sm text-white/75">{{ dispatch.subject }}</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <Link :href="urls.index" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm font-extrabold text-white backdrop-blur transition hover:bg-white/15">
                                Back to Notifications
                            </Link>
                            <button type="button" class="inline-flex items-center justify-center rounded-full bg-[#c0f190] px-5 py-3 text-sm font-extrabold text-[#2a5000] transition hover:scale-[1.02]" @click="resendDispatch">
                                Resend Notification
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        <article class="rounded-[1.7rem] border border-[#d9e2dc] bg-[#fbfcfb] p-5">
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-[#52626b]">Module</p>
                            <p class="mt-3 text-lg font-black text-[#111827]">{{ dispatch.module_label }}</p>
                        </article>
                        <article class="rounded-[1.7rem] border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Recipients</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.04em]">{{ dispatch.recipient_count }}</p>
                        </article>
                        <article class="rounded-[1.7rem] border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Delivered</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.04em]">{{ dispatch.delivered_count }}</p>
                        </article>
                        <article class="rounded-[1.7rem] border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Failed</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.04em]">{{ dispatch.failed_count }}</p>
                        </article>
                        <article class="rounded-[1.7rem] border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Read</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.04em]">{{ dispatch.read_count }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="notification-message rounded-lg border border-[#d9e2dc] bg-white p-3">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]" :class="deliveryTone(dispatch.delivery_label)">
                        {{ dispatch.delivery_label }}
                    </span>
                    <span class="text-sm text-[#64748b]">Queued {{ formatDate(dispatch.queued_at || dispatch.created_at) }}</span>
                </div>

                <div v-if="dispatch.message" class="mt-5 rounded-[1.6rem] border border-[#d9e2dc] bg-[#fbfcfb] p-5">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-[#52626b]">Message</p>
                    <p class="mt-3 text-sm leading-6 text-[#334155]">{{ dispatch.message }}</p>
                </div>
            </section>

            <section class="notification-recipients overflow-hidden rounded-lg border border-[#d9e2dc] bg-white">
                <div class="border-b border-[#e6ece8] px-5 py-5 sm:px-6">
                    <h2 class="text-[1.55rem] font-black tracking-[-0.04em] text-[#0f172a]">Recipients</h2>
                    <p class="mt-2 text-sm text-[#64748b]">Per-recipient delivery state, read activity, and failure details.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="border-b border-[#e6ece8] text-left text-[0.78rem] font-black uppercase tracking-[0.08em] text-[#334155]">
                                <th class="px-5 py-4 sm:px-6">Recipient</th>
                                <th class="px-5 py-4 sm:px-6">Address</th>
                                <th class="px-5 py-4 sm:px-6">Status</th>
                                <th class="px-5 py-4 sm:px-6">Delivered</th>
                                <th class="px-5 py-4 sm:px-6">Read</th>
                                <th class="px-5 py-4 sm:px-6">Failed</th>
                                <th class="px-5 py-4 sm:px-6">Failure Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="recipient in recipients" :key="recipient.id" class="border-b border-[#edf2ee] align-top transition hover:bg-[#fbfdfc]">
                                <td class="px-5 py-5 sm:px-6">
                                    <p class="font-black text-[#0f172a]">{{ recipient.user_name || recipient.farmer_code || `Recipient #${recipient.id}` }}</p>
                                    <p v-if="recipient.farmer_code" class="mt-1 text-sm text-[#64748b]">{{ recipient.farmer_code }}</p>
                                </td>
                                <td class="px-5 py-5 text-sm text-[#52626b] sm:px-6">{{ recipient.recipient_address || 'No address' }}</td>
                                <td class="px-5 py-5 sm:px-6">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]" :class="recipientTone(recipient.status)">
                                        {{ recipient.status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-5 text-sm text-[#52626b] sm:px-6">{{ formatDate(recipient.delivered_at) }}</td>
                                <td class="px-5 py-5 text-sm text-[#52626b] sm:px-6">{{ formatDate(recipient.read_at) }}</td>
                                <td class="px-5 py-5 text-sm text-[#52626b] sm:px-6">{{ formatDate(recipient.failed_at) }}</td>
                                <td class="px-5 py-5 text-sm text-[#52626b] sm:px-6">{{ recipient.failure_reason || '-' }}</td>
                            </tr>
                            <tr v-if="recipients.length === 0">
                                <td colspan="7" class="px-6 py-14 text-center text-sm text-[#71808b]">
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

<style scoped>
.notification-show-hero > div:not(.relative) {
    display: none;
}
.notification-show-hero .relative.space-y-6 {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}
.notification-show-hero .relative > div:first-child {
    align-items: center;
    gap: 0.75rem;
}
.notification-show-hero .inline-flex.rounded-full:first-child {
    border: 0;
    background: transparent;
    padding: 0;
}
.notification-show-hero .inline-flex.rounded-full:first-child > span:first-child {
    display: none;
}
.notification-show-hero .inline-flex.rounded-full:first-child > span:last-child {
    font-size: 0.58rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    color: rgb(255 255 255 / 60%);
}
.notification-show-hero h1 {
    margin-top: 0.125rem;
    font-size: 1.25rem;
    line-height: 1.5rem;
    font-weight: 600;
}
.notification-show-hero h1 + p {
    margin-top: 0.125rem;
    font-size: 0.68rem;
}
.notification-show-hero a,
.notification-show-hero button {
    min-height: 2rem;
    border-radius: 0.375rem;
    padding: 0 0.75rem;
    font-size: 0.68rem;
    font-weight: 600;
}
.notification-show-hero .grid {
    gap: 0.5rem;
}
.notification-show-hero .grid article {
    border-radius: 0.375rem;
    padding: 0.6rem 0.75rem;
    background: rgb(255 255 255 / 10%);
    color: white;
}
.notification-show-hero .grid article p:first-child {
    font-size: 0.58rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    color: rgb(255 255 255 / 60%);
}
.notification-show-hero .grid article p:last-child {
    margin-top: 0.2rem;
    font-size: 1.1rem;
    line-height: 1.25rem;
    font-weight: 600;
    color: white;
}
.notification-message > div:first-child {
    gap: 0.5rem;
}
.notification-message > div:first-child span {
    font-size: 0.62rem;
}
.notification-message > div:last-child {
    margin-top: 0.65rem;
    border-radius: 0.375rem;
    padding: 0.75rem;
}
.notification-message > div:last-child p:first-child {
    font-size: 0.58rem;
    font-weight: 600;
    letter-spacing: 0.08em;
}
.notification-message > div:last-child p:last-child {
    margin-top: 0.35rem;
    font-size: 0.75rem;
    line-height: 1.25rem;
}
.notification-recipients > div:first-child {
    padding: 0.75rem 1rem;
}
.notification-recipients h2 {
    font-size: 0.875rem;
    font-weight: 600;
}
.notification-recipients h2 + p {
    margin-top: 0.2rem;
    font-size: 0.65rem;
}
.notification-recipients th {
    padding: 0.625rem 1rem;
    font-size: 0.58rem;
    font-weight: 600;
    letter-spacing: 0.08em;
}
.notification-recipients td {
    padding: 0.75rem 1rem;
    font-size: 0.68rem;
}
.notification-recipients td p,
.notification-recipients td span {
    font-size: inherit;
}
</style>
