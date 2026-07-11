<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    notifications: { type: Object, required: true },
    summary: { type: Object, required: true },
    filters: { type: Object, required: true },
    moduleOptions: { type: Object, required: true },
    stateOptions: { type: Object, required: true },
    urls: { type: Object, required: true },
    managementSummary: { type: Object, required: true },
    dispatches: { type: Object, required: true },
    failedRecipients: { type: Array, required: true },
    scheduledReminders: { type: Array, required: true },
});

const form = reactive({
    module: props.filters.module ?? 'all',
    state: props.filters.state ?? 'all',
    delivery: props.filters.delivery ?? 'all',
});
const activeSection = ref('inbox');

const deliveryOptions = [
    { value: 'all', label: 'All Delivery States' },
    { value: 'queued', label: 'Queued' },
    { value: 'delivered', label: 'Delivered' },
    { value: 'failed', label: 'Failed' },
    { value: 'read', label: 'Read' },
];

const unreadCount = computed(() => Number(props.summary.unread || 0));
const tabs = [
    { key: 'inbox', label: 'Inbox Notifications' },
    { key: 'dispatches', label: 'Dispatch History' },
    { key: 'issues', label: 'Failures & Reminders' },
];

function applyFilters() {
    router.get(props.urls.index, {
        module: form.module !== 'all' ? form.module : undefined,
        state: form.state !== 'all' ? form.state : undefined,
        delivery: form.delivery !== 'all' ? form.delivery : undefined,
        page: undefined,
        dispatch_page: undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    form.module = 'all';
    form.state = 'all';
    form.delivery = 'all';
    applyFilters();
}

function markAllAsRead() {
    router.post(props.urls.readAll, {}, {
        preserveScroll: true,
    });
}

function deliveryTone(label) {
    if (label === 'Failed') {
        return 'bg-[#ffe6ea] text-[#b42341]';
    }

    if (label === 'Delivered') {
        return 'bg-[#e7f7ea] text-[#166534]';
    }

    if (label === 'Read') {
        return 'bg-[#e8f0ff] text-[#1d4ed8]';
    }

    return 'bg-[#fff4db] text-[#b56a00]';
}

function notificationStatusTone(notification) {
    return notification.read_at ? 'bg-[#edf1ef] text-[#5f6c66]' : 'bg-[#d8f5e5] text-[#0f5b46]';
}

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

function setActiveSection(section) {
    activeSection.value = section;

    if (typeof window !== 'undefined') {
        window.location.hash = section;
    }
}

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    const hash = window.location.hash.replace('#', '');

    if (tabs.some((tab) => tab.key === hash)) {
        activeSection.value = hash;
    }
});
</script>

<template>
    <Head title="Notifications" />

    <AdminLayout title="Notifications">
        <div class="space-y-8 bg-[radial-gradient(circle_at_top_left,_rgba(186,238,217,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(165,213,119,0.12),_transparent_22%)]">
            <section class="relative overflow-hidden rounded-[28px] bg-[#002117] px-6 py-6 text-white shadow-[0_22px_50px_rgba(15,23,42,0.14)] sm:px-7 lg:px-8">
                <div class="absolute inset-0 bg-[radial-gradient(at_0%_0%,_#1b4d3e_0%,_transparent_50%),radial-gradient(at_100%_0%,_#376757_0%,_transparent_48%),radial-gradient(at_100%_100%,_#16332c_0%,_transparent_50%),radial-gradient(at_0%_100%,_#003629_0%,_transparent_48%)]"></div>
                <div class="absolute -right-14 top-[-52px] h-60 w-60 rounded-full bg-[#a5d577]/10 blur-[90px]"></div>
                <div class="absolute -bottom-24 left-[18%] h-72 w-72 rounded-full bg-white/10 blur-[110px]"></div>

                <div class="relative z-10 space-y-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <div class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-md">
                                <span class="h-2.5 w-2.5 rounded-full bg-[#c0f190] shadow-[0_0_16px_rgba(192,241,144,0.8)]"></span>
                                <span class="text-[0.68rem] font-black uppercase tracking-[0.28em] text-white/85">Communication Module</span>
                            </div>

                            <h1 class="mt-4 text-4xl font-black tracking-[-0.045em] sm:text-[2.85rem]">Notification Management</h1>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button
                                v-if="unreadCount > 0"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full bg-[#c0f190] px-5 py-3 text-sm font-extrabold text-[#2a5000] transition hover:scale-[1.02]"
                                @click="markAllAsRead"
                            >
                                Mark All as Read
                            </button>
                            <button
                                v-else
                                type="button"
                                class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm font-extrabold text-white/75 backdrop-blur transition"
                                disabled
                            >
                                All Caught Up
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        <div class="rounded-[22px] border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Total Dispatches</p>
                            <div class="mt-2.5 flex items-end gap-3">
                                <span class="text-3xl font-black tracking-[-0.04em]">{{ managementSummary.totalDispatches }}</span>
                                <span class="pb-1 text-xs font-black uppercase tracking-[0.18em] text-[#c0f190]">All Time</span>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full w-[82%] rounded-full bg-[#c0f190]"></div>
                            </div>
                        </div>
                        <div class="rounded-[22px] border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Queued</p>
                            <div class="mt-2.5 flex items-end gap-3">
                                <span class="text-3xl font-black tracking-[-0.04em]">{{ managementSummary.queuedDispatches }}</span>
                                <span class="pb-1 text-xs font-black uppercase tracking-[0.18em] text-[#ffe08a]">Pending Send</span>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full w-[54%] rounded-full bg-[#ffe08a]"></div>
                            </div>
                        </div>
                        <div class="rounded-[22px] border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Delivered</p>
                            <div class="mt-2.5 flex items-end gap-3">
                                <span class="text-3xl font-black tracking-[-0.04em]">{{ managementSummary.deliveredRecipients }}</span>
                                <span class="pb-1 text-xs font-black uppercase tracking-[0.18em] text-[#baeed9]">Recipients</span>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full w-[74%] rounded-full bg-[#baeed9]"></div>
                            </div>
                        </div>
                        <div class="rounded-[22px] border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Failed</p>
                            <div class="mt-2.5 flex items-end gap-3">
                                <span class="text-3xl font-black tracking-[-0.04em]">{{ managementSummary.failedRecipients }}</span>
                                <span class="pb-1 text-xs font-black uppercase tracking-[0.18em] text-[#ffb5c2]">Needs Review</span>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full w-[33%] rounded-full bg-[#ffb5c2]"></div>
                            </div>
                        </div>
                        <div class="rounded-[22px] border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-white/60">Scheduled Reminders</p>
                            <div class="mt-2.5 flex items-end gap-3">
                                <span class="text-3xl font-black tracking-[-0.04em]">{{ managementSummary.scheduledReminders }}</span>
                                <span class="pb-1 text-xs font-black uppercase tracking-[0.18em] text-[#b7d8ff]">Auto Queue</span>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full w-[46%] rounded-full bg-[#b7d8ff]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[24px] border border-[#dfe5e1] bg-[#f2f4f3] p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
                <form class="grid gap-4 xl:grid-cols-[1fr_1fr_1fr_auto]" @submit.prevent="applyFilters">
                    <label class="space-y-2">
                        <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Module</span>
                        <select v-model="form.module" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                            <option v-for="(label, value) in moduleOptions" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Status</span>
                        <select v-model="form.state" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                            <option v-for="(label, value) in stateOptions" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Delivery</span>
                        <select v-model="form.delivery" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                            <option v-for="option in deliveryOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
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
            </section>

            <section class="flex justify-center">
                <div class="inline-flex flex-wrap items-center gap-2 rounded-full border border-[#dfe5e1] bg-[#eceeed] p-1.5 shadow-[inset_0_1px_2px_rgba(15,23,42,0.05)]">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-extrabold transition"
                        :class="activeSection === tab.key ? 'bg-white text-[#003629] shadow-sm' : 'text-[#66756f] hover:text-[#1a2420]'"
                        @click="setActiveSection(tab.key)"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </section>

            <section v-if="activeSection === 'inbox'" class="overflow-hidden rounded-[24px] border border-[#dfe5e1] bg-white shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
                <div class="border-b border-[#e6ece8] px-5 py-5 sm:px-6">
                    <div class="flex items-center gap-3">
                        <h2 class="text-[1.55rem] font-black tracking-[-0.04em] text-[#0f172a]">Inbox Notifications</h2>
                        <span class="rounded-full bg-[#d8f5e5] px-3 py-1 text-xs font-black uppercase tracking-[0.08em] text-[#0f7d5a]">{{ summary.total }} Total</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#e5ece8] text-sm">
                        <thead class="bg-[#f2f4f3]">
                            <tr class="text-left text-[0.72rem] font-black uppercase tracking-[0.16em] text-[#6d7873]">
                                <th class="px-5 py-4 sm:px-6">Type</th>
                                <th class="px-5 py-4 sm:px-6">Subject</th>
                                <th class="px-5 py-4 sm:px-6">Message</th>
                                <th class="px-5 py-4 sm:px-6">Received</th>
                                <th class="px-5 py-4 sm:px-6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ef]">
                            <tr v-for="notification in notifications.data" :key="notification.recipient_id" class="align-top transition hover:bg-[#fbfdfc]">
                                <td class="px-5 py-5 sm:px-6">
                                    <p class="font-black text-[#0f172a]">{{ notification.type_label }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-[#edf3ef] px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em] text-[#4e655a]">
                                            {{ notification.module_label }}
                                        </span>
                                        <span v-if="notification.source_label" class="rounded-full bg-[#f4f6f5] px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em] text-stone-500">
                                            {{ notification.source_label }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-5 font-semibold text-[#0f172a] sm:px-6">{{ notification.subject }}</td>
                                <td class="px-5 py-5 text-sm text-[#52626b] sm:px-6">{{ notification.message }}</td>
                                <td class="px-5 py-5 text-sm text-[#52626b] sm:px-6">{{ formatDate(notification.created_at) }}</td>
                                <td class="px-5 py-5 sm:px-6">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]" :class="notificationStatusTone(notification)">
                                        {{ notification.read_at ? 'Read' : 'Unread' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="notifications.data.length === 0">
                                <td colspan="5" class="px-6 py-14 text-center text-sm text-[#71808b]">
                                    No notifications found for the selected filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-[#e4ebe7] bg-[#f8faf9] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <span class="text-sm text-[#65736d]">Showing {{ notifications.from || 0 }}-{{ notifications.to || 0 }} of {{ notifications.total }} notifications</span>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in notifications.links" :key="link.label">
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
                </div>
            </section>

            <section v-if="activeSection === 'dispatches'" class="overflow-hidden rounded-[24px] border border-[#dfe5e1] bg-white shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
                <div class="border-b border-[#e6ece8] px-5 py-5 sm:px-6">
                    <h2 class="text-[1.55rem] font-black tracking-[-0.04em] text-[#0f172a]">Dispatch History</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#e5ece8] text-sm">
                        <thead class="bg-[#f2f4f3]">
                            <tr class="text-left text-[0.72rem] font-black uppercase tracking-[0.16em] text-[#6d7873]">
                                <th class="px-5 py-4 sm:px-6">Type</th>
                                <th class="px-5 py-4 sm:px-6">Recipients</th>
                                <th class="px-5 py-4 sm:px-6">Delivery</th>
                                <th class="px-5 py-4 sm:px-6">Queued</th>
                                <th class="px-5 py-4 text-right sm:px-6">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ef]">
                            <tr v-for="dispatch in dispatches.data" :key="dispatch.id" class="align-top transition hover:bg-[#fbfdfc]">
                                <td class="px-5 py-5 sm:px-6">
                                    <p class="font-black text-[#0f172a]">{{ dispatch.type_label }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-[#edf3ef] px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em] text-[#4e655a]">
                                            {{ dispatch.module_label }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-[#52626b]">{{ dispatch.subject }}</p>
                                </td>
                                <td class="px-5 py-5 text-sm text-[#52626b] sm:px-6">
                                    <p>Total: {{ dispatch.recipient_count }}</p>
                                    <p class="mt-1 text-[#166534]">Delivered: {{ dispatch.delivered_count }}</p>
                                    <p class="text-[#be123c]">Failed: {{ dispatch.failed_count }}</p>
                                    <p class="text-[#1d4ed8]">Read: {{ dispatch.read_count }}</p>
                                </td>
                                <td class="px-5 py-5 sm:px-6">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]" :class="deliveryTone(dispatch.delivery_label)">
                                        {{ dispatch.delivery_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-5 text-sm text-[#52626b] sm:px-6">{{ formatDate(dispatch.queued_at || dispatch.created_at) }}</td>
                                <td class="px-5 py-5 text-right sm:px-6">
                                    <Link :href="dispatch.show_url" class="inline-flex text-sm font-black text-[#014d3c] transition hover:text-[#022f25] hover:underline">
                                        View Details
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="dispatches.data.length === 0">
                                <td colspan="5" class="px-6 py-14 text-center text-sm text-[#71808b]">
                                    No notification dispatches found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-[#e4ebe7] bg-[#f8faf9] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <span class="text-sm text-[#65736d]">Showing {{ dispatches.from || 0 }}-{{ dispatches.to || 0 }} of {{ dispatches.total }} dispatches</span>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in dispatches.links" :key="`dispatch-${link.label}`">
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
                </div>
            </section>

            <section v-if="activeSection === 'issues'" class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[2rem] border border-[#d9e2dc] bg-white p-6 shadow-[0_16px_40px_rgba(15,91,70,0.06)]">
                    <h2 class="text-[1.45rem] font-black tracking-[-0.04em] text-[#0f172a]">Failed Notifications</h2>
                    <div class="mt-5 space-y-4">
                        <article v-for="failure in failedRecipients" :key="failure.recipient_id" class="rounded-[1.6rem] border border-[#fecdd3] bg-[#fff1f2] p-4">
                            <p class="font-black text-[#881337]">{{ failure.type_label }}</p>
                            <p class="mt-1 text-sm text-[#475569]">{{ failure.subject }}</p>
                            <p class="mt-3 text-sm text-[#334155]">Recipient: {{ failure.recipient_address || 'No address' }}</p>
                            <p class="mt-2 text-sm text-[#9f1239]">{{ failure.failure_reason || 'No failure reason recorded.' }}</p>
                            <p class="mt-2 text-xs text-[#64748b]">{{ formatDate(failure.failed_at) }}</p>
                        </article>
                        <p v-if="failedRecipients.length === 0" class="text-sm text-[#71808b]">No failed notifications found.</p>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-[#d9e2dc] bg-white p-6 shadow-[0_16px_40px_rgba(15,91,70,0.06)]">
                    <h2 class="text-[1.45rem] font-black tracking-[-0.04em] text-[#0f172a]">Scheduled Reminders</h2>
                    <div class="mt-5 space-y-4">
                        <article v-for="reminder in scheduledReminders" :key="reminder.id" class="rounded-[1.6rem] border border-[#bfdbfe] bg-[#eff6ff] p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-black text-[#1d4ed8]">{{ reminder.type_label }}</p>
                                    <p class="mt-1 text-sm text-[#475569]">{{ reminder.subject }}</p>
                                </div>
                                <Link :href="reminder.show_url" class="text-sm font-black text-[#014d3c] transition hover:underline">
                                    View
                                </Link>
                            </div>
                            <p class="mt-3 text-sm text-[#334155]">Recipients: {{ reminder.recipient_count }}</p>
                            <p class="mt-2 text-xs text-[#64748b]">Queued at {{ formatDate(reminder.queued_at || reminder.created_at) }}</p>
                        </article>
                        <p v-if="scheduledReminders.length === 0" class="text-sm text-[#71808b]">No scheduled reminders found.</p>
                    </div>
                </article>
            </section>
        </div>
    </AdminLayout>
</template>
