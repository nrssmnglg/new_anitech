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

function notificationStatusTone(notification) {
    return notification.read_at
        ? 'bg-[#f1f5f9] text-[#64748b] border-[#cbd5e1]'
        : 'bg-[#dcfce7] text-[#15803d] border-[#bbf7d0]';
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
    <Head title="Notification Center" />

    <AdminLayout title="Notification Center">
        <div class="notification-management space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-24 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
                                <path d="M10 21a2 2 0 0 0 4 0" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Communication</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Notification Center</h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Summary Pills -->
                        <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                            <article class="px-3.5 py-2 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Dispatches</p>
                                <p class="mt-0.5 text-base font-bold leading-none">{{ managementSummary.totalDispatches }}</p>
                            </article>
                            <article class="border-x border-white/[0.08] px-3.5 py-2 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Queued</p>
                                <p class="mt-0.5 text-base font-bold leading-none text-[#fbbf24]">{{ managementSummary.queuedDispatches }}</p>
                            </article>
                            <article class="border-r border-white/[0.08] px-3.5 py-2 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Delivered</p>
                                <p class="mt-0.5 text-base font-bold leading-none text-[#7ddfb8]">{{ managementSummary.deliveredRecipients }}</p>
                            </article>
                            <article class="px-3.5 py-2 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Failed</p>
                                <p class="mt-0.5 text-base font-bold leading-none text-rose-300">{{ managementSummary.failedRecipients }}</p>
                            </article>
                        </div>

                        <!-- Action Button -->
                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                            @click="markAllAsRead"
                        >
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#003629]" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                            </svg>
                            Mark All Read ({{ unreadCount }})
                        </button>
                        <div
                            v-else
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 bg-white/10 px-3.5 text-[0.7rem] font-semibold text-white/70 backdrop-blur-sm"
                        >
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#7ddfb8]" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                            </svg>
                            All Caught Up
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filters Bar -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                <form class="grid gap-3 sm:grid-cols-3 xl:grid-cols-[1fr_1fr_1fr_auto] xl:items-end" @submit.prevent="applyFilters">
                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Module</span>
                        <select
                            v-model="form.module"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option v-for="(label, value) in moduleOptions" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select
                            v-model="form.state"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option v-for="(label, value) in stateOptions" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Delivery</span>
                        <select
                            v-model="form.delivery"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option v-for="option in deliveryOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <div class="flex items-center gap-2 xl:justify-end">
                        <button
                            type="submit"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97]"
                        >
                            Apply
                        </button>
                        <button
                            v-if="form.module !== 'all' || form.state !== 'all' || form.delivery !== 'all'"
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-3.5 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]"
                            @click="resetFilters"
                        >
                            Reset
                        </button>
                    </div>
                </form>
            </section>

            <!-- Navigation Tabs -->
            <div class="flex items-center gap-1.5 rounded-xl border border-[#dde4de] bg-white p-1.5 shadow-sm">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all duration-200"
                    :class="activeSection === tab.key ? 'bg-[#014d3c] text-white shadow-xs' : 'text-[#64748b] hover:bg-[#f4f7f5] hover:text-[#0f172a]'"
                    @click="setActiveSection(tab.key)"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.key === 'inbox' && summary.unread > 0"
                        class="rounded-full px-1.5 py-0.2 text-[0.6rem] font-bold"
                        :class="activeSection === tab.key ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'"
                    >
                        {{ summary.unread }} unread
                    </span>
                    <span
                        v-else-if="tab.key === 'dispatches'"
                        class="rounded-full px-1.5 py-0.2 text-[0.6rem] font-bold"
                        :class="activeSection === tab.key ? 'bg-white/20 text-white' : 'bg-[#edf2ee] text-[#64748b]'"
                    >
                        {{ dispatches.total }}
                    </span>
                    <span
                        v-else-if="tab.key === 'issues' && (failedRecipients.length > 0 || scheduledReminders.length > 0)"
                        class="rounded-full px-1.5 py-0.2 text-[0.6rem] font-bold"
                        :class="activeSection === tab.key ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800'"
                    >
                        {{ failedRecipients.length + scheduledReminders.length }}
                    </span>
                </button>
            </div>

            <!-- Tab 1: Inbox Notifications -->
            <section v-if="activeSection === 'inbox'" class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                    <div>
                        <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Inbox Notifications</h2>
                        <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ notifications.total }} total inbox alerts found</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-[#edf2ee] bg-[#f8faf9] text-[0.65rem] font-bold uppercase tracking-wider text-[#64748b]">
                            <tr>
                                <th class="px-5 py-3">Type & Module</th>
                                <th class="px-5 py-3">Subject</th>
                                <th class="px-5 py-3">Message</th>
                                <th class="px-5 py-3">Received</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee] text-[#0f172a]">
                            <tr v-for="notification in notifications.data" :key="notification.recipient_id" class="transition hover:bg-[#f8fbf9]">
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-[#0f172a]">{{ notification.type_label }}</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <span class="rounded bg-[#014d3c]/10 px-1.5 py-0.5 text-[0.58rem] font-bold uppercase tracking-wider text-[#014d3c]">
                                            {{ notification.module_label }}
                                        </span>
                                        <span v-if="notification.source_label" class="rounded bg-[#f1f5f3] px-1.5 py-0.5 text-[0.58rem] font-semibold text-[#64748b]">
                                            {{ notification.source_label }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 font-bold text-[#0f172a]">{{ notification.subject }}</td>
                                <td class="px-5 py-3.5 text-[#64748b] max-w-sm">{{ notification.message }}</td>
                                <td class="px-5 py-3.5 text-[#64748b] whitespace-nowrap">{{ formatDate(notification.created_at) }}</td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[0.65rem] font-bold" :class="notificationStatusTone(notification)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="notification.read_at ? 'bg-[#94a3b8]' : 'bg-[#22c55e]'"></span>
                                        {{ notification.read_at ? 'Read' : 'Unread' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="notifications.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-xs text-[#94a3b8]">
                                    No notifications found matching the selected filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Numbered Pagination -->
                <div v-if="notifications.links?.length > 3" class="flex flex-col items-center justify-between gap-3 border-t border-[#edf2ee] px-5 py-3 sm:flex-row">
                    <p class="text-[0.68rem] text-[#64748b]">
                        Showing {{ notifications.from || 0 }} to {{ notifications.to || 0 }} of {{ notifications.total }} notifications
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in notifications.links" :key="index">
                            <span
                                v-if="!link.url"
                                class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-md border border-[#e5ebe6] px-2 text-[0.68rem] font-medium text-[#94a3b8]"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-md border px-2 text-[0.68rem] font-semibold transition-all duration-150"
                                :class="link.active ? 'border-[#014d3c] bg-[#014d3c] text-white shadow-xs' : 'border-[#dbe3dd] bg-white text-[#475569] hover:bg-[#f4f7f5] hover:text-[#0f172a]'"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>

            <!-- Tab 2: Dispatch History -->
            <section v-if="activeSection === 'dispatches'" class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                    <div>
                        <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Dispatch History</h2>
                        <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ dispatches.total }} total dispatch broadcasts logged</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-[#edf2ee] bg-[#f8faf9] text-[0.65rem] font-bold uppercase tracking-wider text-[#64748b]">
                            <tr>
                                <th class="px-5 py-3">Type & Module</th>
                                <th class="px-5 py-3">Recipients Breakdown</th>
                                <th class="px-5 py-3">Delivery Status</th>
                                <th class="px-5 py-3">Queued At</th>
                                <th class="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee] text-[#0f172a]">
                            <tr v-for="dispatch in dispatches.data" :key="dispatch.id" class="transition hover:bg-[#f8fbf9]">
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-[#0f172a]">{{ dispatch.type_label }}</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <span class="rounded bg-[#014d3c]/10 px-1.5 py-0.5 text-[0.58rem] font-bold uppercase tracking-wider text-[#014d3c]">
                                            {{ dispatch.module_label }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-[0.68rem] text-[#64748b] max-w-sm">{{ dispatch.subject }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="rounded bg-[#f1f5f3] px-2 py-0.5 text-[0.65rem] font-bold text-[#0f172a]">
                                            Total: {{ dispatch.recipient_count }}
                                        </span>
                                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-[0.65rem] font-bold text-emerald-700">
                                            Delivered: {{ dispatch.delivered_count }}
                                        </span>
                                        <span v-if="dispatch.failed_count > 0" class="rounded bg-rose-50 px-2 py-0.5 text-[0.65rem] font-bold text-rose-700">
                                            Failed: {{ dispatch.failed_count }}
                                        </span>
                                        <span v-if="dispatch.read_count > 0" class="rounded bg-sky-50 px-2 py-0.5 text-[0.65rem] font-bold text-sky-700">
                                            Read: {{ dispatch.read_count }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[0.65rem] font-bold" :class="deliveryTone(dispatch.delivery_label)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="deliveryDotTone(dispatch.delivery_label)"></span>
                                        {{ dispatch.delivery_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-[#64748b] whitespace-nowrap">{{ formatDate(dispatch.queued_at || dispatch.created_at) }}</td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <Link :href="dispatch.show_url" class="inline-flex h-7 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-3 text-[0.68rem] font-bold text-[#014d3c] transition hover:bg-[#eef7f2] hover:border-[#014d3c]/30">
                                        View Details
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="dispatches.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-xs text-[#94a3b8]">
                                    No notification dispatches found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Numbered Pagination -->
                <div v-if="dispatches.links?.length > 3" class="flex flex-col items-center justify-between gap-3 border-t border-[#edf2ee] px-5 py-3 sm:flex-row">
                    <p class="text-[0.68rem] text-[#64748b]">
                        Showing {{ dispatches.from || 0 }} to {{ dispatches.to || 0 }} of {{ dispatches.total }} dispatches
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in dispatches.links" :key="index">
                            <span
                                v-if="!link.url"
                                class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-md border border-[#e5ebe6] px-2 text-[0.68rem] font-medium text-[#94a3b8]"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-md border px-2 text-[0.68rem] font-semibold transition-all duration-150"
                                :class="link.active ? 'border-[#014d3c] bg-[#014d3c] text-white shadow-xs' : 'border-[#dbe3dd] bg-white text-[#475569] hover:bg-[#f4f7f5] hover:text-[#0f172a]'"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>

            <!-- Tab 3: Failures & Reminders -->
            <section v-if="activeSection === 'issues'" class="grid gap-4 xl:grid-cols-2">
                <!-- Failed Notifications Card -->
                <div class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                    <div class="flex items-center gap-2.5 border-b border-[#edf2ee] px-5 py-3.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Failed Notifications</h2>
                            <p class="text-[0.65rem] text-[#64748b]">Dispatches requiring delivery review</p>
                        </div>
                    </div>

                    <div class="p-5 space-y-3">
                        <article
                            v-for="failure in failedRecipients"
                            :key="failure.recipient_id"
                            class="rounded-xl border border-rose-200 bg-rose-50/50 p-4 text-xs"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-bold text-rose-900">{{ failure.type_label }}</p>
                                <span class="rounded bg-rose-100 px-2 py-0.5 text-[0.62rem] font-bold text-rose-800">Failed</span>
                            </div>
                            <p class="mt-1 font-medium text-[#334155]">{{ failure.subject }}</p>
                            <p class="mt-2 text-[#64748b]">Recipient: <span class="font-semibold text-[#0f172a]">{{ failure.recipient_address || 'No address' }}</span></p>
                            <p class="mt-1 text-rose-700 font-medium">Reason: {{ failure.failure_reason || 'No failure reason recorded.' }}</p>
                            <p class="mt-2 text-[0.65rem] text-[#94a3b8]">{{ formatDate(failure.failed_at) }}</p>
                        </article>

                        <div v-if="failedRecipients.length === 0" class="py-8 text-center text-xs text-[#94a3b8]">
                            No failed notifications found.
                        </div>
                    </div>
                </div>

                <!-- Scheduled Reminders Card -->
                <div class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                    <div class="flex items-center gap-2.5 border-b border-[#edf2ee] px-5 py-3.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Scheduled Reminders</h2>
                            <p class="text-[0.65rem] text-[#64748b]">Automated alerts queued for dispatch</p>
                        </div>
                    </div>

                    <div class="p-5 space-y-3">
                        <article
                            v-for="reminder in scheduledReminders"
                            :key="reminder.id"
                            class="rounded-xl border border-sky-200 bg-sky-50/50 p-4 text-xs"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-bold text-sky-900">{{ reminder.type_label }}</p>
                                    <p class="mt-0.5 font-medium text-[#334155]">{{ reminder.subject }}</p>
                                </div>
                                <Link :href="reminder.show_url" class="inline-flex h-6 items-center rounded px-2 text-[0.68rem] font-bold text-[#014d3c] transition hover:bg-[#014d3c]/10">
                                    View
                                </Link>
                            </div>
                            <p class="mt-2 text-[#64748b]">Target Recipients: <span class="font-semibold text-[#0f172a]">{{ reminder.recipient_count }}</span></p>
                            <p class="mt-1 text-[0.65rem] text-[#94a3b8]">Queued at {{ formatDate(reminder.queued_at || reminder.created_at) }}</p>
                        </article>

                        <div v-if="scheduledReminders.length === 0" class="py-8 text-center text-xs text-[#94a3b8]">
                            No scheduled reminders found.
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
