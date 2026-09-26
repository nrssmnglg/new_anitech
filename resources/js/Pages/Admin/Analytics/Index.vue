<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    analytics: {
        type: Object,
        required: true,
    },
});

const maxTrend = computed(() => {
    if (!props.analytics.trend?.length) return 1;
    return Math.max(...props.analytics.trend.map((row) => Math.max(row.pageViews || 0, row.searches || 0)), 1);
});

const maxApplicationTrend = computed(() => {
    if (!props.analytics.applicationTrend?.length) return 1;
    return Math.max(
        ...props.analytics.applicationTrend.map((row) => Math.max(row.created || 0, row.approved || 0, row.rejected || 0)),
        1,
    );
});

const maxRenewalTrend = computed(() => {
    if (!props.analytics.renewalTrend?.length) return 1;
    return Math.max(
        ...props.analytics.renewalTrend.map((row) => Math.max(row.created || 0, row.approved || 0, row.rejected || 0)),
        1,
    );
});

const maxPaymentTrend = computed(() => {
    if (!props.analytics.paymentTrend?.length) return 1;
    return Math.max(
        ...props.analytics.paymentTrend.map((row) => Math.max(row.applications || 0, row.renewals || 0)),
        1,
    );
});

const maxBarangayStatus = computed(() => {
    if (!props.analytics.activeInactiveByBarangay?.length) return 1;
    return Math.max(
        ...props.analytics.activeInactiveByBarangay.map((row) => Math.max(row.active || 0, row.inactive || 0)),
        1,
    );
});

const maxRenewalCompliance = computed(() => {
    if (!props.analytics.renewalCompliance?.length) return 100;
    return Math.max(
        ...props.analytics.renewalCompliance.map((row) => row.rate || 0),
        100,
    );
});

const maxInquiryTopics = computed(() => {
    if (!props.analytics.inquiryTopics?.length) return 1;
    return Math.max(
        ...props.analytics.inquiryTopics.map((row) => row.value || 0),
        1,
    );
});

const maxRejectionReasons = computed(() => {
    if (!props.analytics.rejectionReasons?.length) return 1;
    return Math.max(
        ...props.analytics.rejectionReasons.map((row) => row.value || 0),
        1,
    );
});

const maxDocumentIssues = computed(() => {
    if (!props.analytics.documentIssues?.length) return 1;
    return Math.max(
        ...props.analytics.documentIssues.map((row) => row.value || 0),
        1,
    );
});

const currencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
});

function money(value) {
    return currencyFormatter.format(Number(value || 0));
}

function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

const filters = reactive({
    days: props.analytics.filters.days,
    date_from: props.analytics.filters.dateFrom,
    date_to: props.analytics.filters.dateTo,
});

const exportModalOpen = ref(false);

function applyDays(days) {
    filters.days = days;
    router.get(props.analytics.filters.baseUrl, {
        days,
        date_from: filters.date_from,
        date_to: filters.date_to,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function applyDateRange() {
    router.get(props.analytics.filters.baseUrl, {
        days: filters.days,
        date_from: filters.date_from,
        date_to: filters.date_to,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function openExportModal() {
    exportModalOpen.value = true;
}

function closeExportModal() {
    exportModalOpen.value = false;
}

function exportAnalytics(format) {
    const url = new URL(props.analytics.urls.export, window.location.origin);
    url.searchParams.set('days', String(filters.days));
    url.searchParams.set('date_from', filters.date_from);
    url.searchParams.set('date_to', filters.date_to);
    url.searchParams.set('format', format);
    window.location.href = url.toString();
    closeExportModal();
}
</script>

<template>
    <Head title="Analytics" />

    <AdminLayout title="Analytics">
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-24 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Intelligence & Performance</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Operations Analytics</h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Presets Selector -->
                        <div class="flex items-center gap-1 overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] p-1 backdrop-blur-sm">
                            <button
                                v-for="days in analytics.filters.options"
                                :key="days"
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-200"
                                :class="analytics.filters.days === days ? 'bg-white text-[#003629] shadow-sm' : 'text-white/80 hover:bg-white/10 hover:text-white'"
                                @click="applyDays(days)"
                            >
                                {{ days }}D
                            </button>
                        </div>

                        <!-- Export Button -->
                        <button
                            type="button"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-xs font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                            @click="openExportModal"
                        >
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#003629]" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm4.75 6.75a.75.75 0 011.5 0v3.69l1.22-1.22a.75.75 0 111.06 1.06l-2.5 2.5a.75.75 0 01-1.06 0l-2.5-2.5a.75.75 0 111.06-1.06l1.22 1.22V8.75z" clip-rule="evenodd" />
                            </svg>
                            Export Report
                        </button>
                    </div>
                </div>
            </section>

            <!-- Date Range Filter Bar -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-2 text-xs text-[#475569]">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="font-medium text-[#64748b]">Active Window:</span>
                        <span class="font-bold text-[#0f172a]">{{ formatDate(analytics.filters.dateFrom) }} &mdash; {{ formatDate(analytics.filters.dateTo) }}</span>
                        <span class="rounded-full bg-[#f1f5f9] px-2 py-0.5 text-[0.68rem] font-semibold text-[#64748b]">({{ analytics.filters.days }} Days)</span>
                    </div>

                    <form class="flex flex-wrap items-center gap-2" @submit.prevent="applyDateRange">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[0.68rem] font-bold uppercase tracking-wider text-[#64748b]">From</span>
                            <input
                                v-model="filters.date_from"
                                type="date"
                                class="h-8.5 rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-2.5 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[0.68rem] font-bold uppercase tracking-wider text-[#64748b]">To</span>
                            <input
                                v-model="filters.date_to"
                                type="date"
                                class="h-8.5 rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-2.5 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </div>
                        <button
                            type="submit"
                            class="inline-flex h-8.5 items-center justify-center rounded-lg bg-[#014d3c] px-3.5 text-xs font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[#013b2e] active:scale-[0.98]"
                        >
                            Apply Range
                        </button>
                    </form>
                </div>
            </section>

            <!-- Mobile Service & Operations KPI Cards -->
            <section class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 xl:grid-cols-6">
                <!-- Total Mobile Users -->
                <article class="rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm transition-all duration-200 hover:border-[#014d3c]/30 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Total Mobile Users</span>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path d="M7 1a2 2 0 00-2 2v14a2 2 0 002 2h6a2 2 0 002-2V3a2 2 0 00-2-2H7zm3 15a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-black tracking-tight text-[#0f172a]">{{ analytics.mobileMonitoring.summary.total_mobile_users }}</p>
                </article>

                <!-- Active In Range -->
                <article class="rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm transition-all duration-200 hover:border-[#014d3c]/30 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Active In Range</span>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#0f766e]/10 text-[#0f766e]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-black tracking-tight text-[#014d3c]">{{ analytics.mobileMonitoring.summary.active_users_in_range }}</p>
                </article>

                <!-- Failed Login Issues -->
                <article class="rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm transition-all duration-200 hover:border-rose-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Failed Logins</span>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-black tracking-tight text-rose-600">{{ analytics.mobileMonitoring.summary.failed_login_issues }}</p>
                </article>

                <!-- Failed OTP Issues -->
                <article class="rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm transition-all duration-200 hover:border-amber-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Failed OTP</span>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-black tracking-tight text-amber-600">{{ analytics.mobileMonitoring.summary.failed_otp_issues }}</p>
                </article>

                <!-- Farmer Inquiries -->
                <article class="rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm transition-all duration-200 hover:border-[#014d3c]/30 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Mobile Inquiries</span>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M3.43 2.524A41.29 41.29 0 0110 2c2.236 0 4.43.18 6.57.524 1.437.231 2.43 1.507 2.43 2.961v6.03c0 1.455-.993 2.73-2.43 2.96-1.596.257-3.225.412-4.88.464v2.521a.75.75 0 01-1.28.53l-3.213-3.213a17.29 17.29 0 01-3.197-.302c-1.437-.23-2.43-1.505-2.43-2.96V5.485c0-1.454.993-2.73 2.43-2.961z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-black tracking-tight text-indigo-600">{{ analytics.mobileMonitoring.summary.submitted_inquiries_via_mobile }}</p>
                </article>

                <!-- Mobile Renewals -->
                <article class="rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm transition-all duration-200 hover:border-[#014d3c]/30 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Mobile Renewals</span>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.45a.75.75 0 000-1.5H4.5a.75.75 0 00-.75.75v3.75a.75.75 0 001.5 0v-2.128l.45.45a7 7 0 0011.838-3.177.75.75 0 00-1.226-.75zm-10.624-2.85a5.5 5.5 0 019.201-2.465l.312.311h-2.45a.75.75 0 000 1.5H15.5a.75.75 0 00.75-.75V3.42a.75.75 0 00-1.5 0v2.128l-.45-.45A7 7 0 002.462 8.275a.75.75 0 001.226.75z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-black tracking-tight text-purple-600">{{ analytics.mobileMonitoring.summary.mobile_renewal_requests }}</p>
                </article>
            </section>

            <!-- Notification Engagement -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                            </svg>
                        </div>
                        <h2 class="text-sm font-bold text-[#0f172a]">Notification Engagement</h2>
                    </div>
                    <span class="rounded-full bg-[#014d3c]/10 px-2.5 py-0.5 text-xs font-bold text-[#014d3c]">
                        {{ analytics.mobileMonitoring.notificationEngagement.read_rate }}% Read Rate
                    </span>
                </div>

                <div class="mt-3.5 grid grid-cols-2 gap-3 md:grid-cols-5">
                    <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3 text-center">
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-[#64748b]">Sent</span>
                        <p class="mt-1 text-xl font-black text-[#0f172a]">{{ analytics.mobileMonitoring.notificationEngagement.sent }}</p>
                    </div>
                    <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3 text-center">
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-[#64748b]">Delivered</span>
                        <p class="mt-1 text-xl font-black text-[#0f172a]">{{ analytics.mobileMonitoring.notificationEngagement.delivered }}</p>
                    </div>
                    <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3 text-center">
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-[#64748b]">Read</span>
                        <p class="mt-1 text-xl font-black text-[#014d3c]">{{ analytics.mobileMonitoring.notificationEngagement.read }}</p>
                    </div>
                    <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3 text-center">
                        <span class="text-[0.65rem] font-bold uppercase tracking-wider text-[#64748b]">Failed</span>
                        <p class="mt-1 text-xl font-black text-rose-600">{{ analytics.mobileMonitoring.notificationEngagement.failed }}</p>
                    </div>
                    <div class="col-span-2 rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] p-3 md:col-span-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.65rem] font-bold uppercase tracking-wider text-[#15803d]">Engagement</span>
                            <span class="text-xs font-black text-[#15803d]">{{ analytics.mobileMonitoring.notificationEngagement.read_rate }}%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-[#dcfce7]">
                            <div
                                class="h-2 rounded-full bg-[#15803d] transition-all duration-500"
                                :style="{ width: `${Math.min(analytics.mobileMonitoring.notificationEngagement.read_rate, 100)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Registry & Compliance Grid -->
            <section class="grid gap-4 xl:grid-cols-2">
                <!-- Active vs Inactive Farmers by Barangay -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Active vs Inactive by Barangay</h2>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-semibold">
                            <span class="inline-flex items-center gap-1.5 text-[#014d3c]">
                                <span class="h-2 w-2 rounded-full bg-[#014d3c]"></span> Active
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-rose-500">
                                <span class="h-2 w-2 rounded-full bg-rose-500"></span> Inactive
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex-1 space-y-3.5">
                        <div
                            v-for="row in analytics.activeInactiveByBarangay"
                            :key="row.barangay"
                            class="rounded-xl border border-[#edf2ef] bg-[#fbfdfc] p-3 transition-colors hover:border-[#d5e0d8]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#0f172a]">{{ row.barangay }}</span>
                                <span class="rounded-full bg-[#edf2ef] px-2 py-0.5 text-[0.65rem] font-bold text-[#475569]">{{ row.total }} farmers</span>
                            </div>
                            <div class="mt-2.5 grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <div class="flex items-center justify-between text-[0.7rem]">
                                        <span class="font-medium text-[#64748b]">Active</span>
                                        <strong class="font-bold text-[#014d3c]">{{ row.active }}</strong>
                                    </div>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-[#e2ebe6]">
                                        <div
                                            class="h-1.5 rounded-full bg-[#014d3c]"
                                            :style="{ width: `${(row.active / maxBarangayStatus) * 100}%` }"
                                        ></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between text-[0.7rem]">
                                        <span class="font-medium text-[#64748b]">Inactive</span>
                                        <strong class="font-bold text-rose-600">{{ row.inactive }}</strong>
                                    </div>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-rose-100">
                                        <div
                                            class="h-1.5 rounded-full bg-rose-500"
                                            :style="{ width: `${(row.inactive / maxBarangayStatus) * 100}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="!analytics.activeInactiveByBarangay?.length" class="py-8 text-center text-xs text-[#94a3b8]">
                            No barangay data available.
                        </div>
                    </div>
                </article>

                <!-- Renewal Compliance Rate per Year -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Renewal Compliance Rate per Year</h2>
                        </div>
                    </div>

                    <!-- Visual Column Bars -->
                    <div class="mt-4 flex h-36 items-end justify-around gap-2 rounded-xl border border-[#edf2ef] bg-[#fbfdfc] px-4 pb-3 pt-6">
                        <div
                            v-for="row in analytics.renewalCompliance"
                            :key="row.year"
                            class="flex flex-col items-center gap-1.5"
                        >
                            <span class="text-[0.68rem] font-bold text-[#014d3c]">{{ row.rate }}%</span>
                            <div class="flex h-20 w-8 items-end justify-center rounded-lg bg-[#e2ebe6]/50 p-0.5">
                                <div
                                    class="w-full rounded-md bg-gradient-to-t from-[#003629] to-[#014d3c] transition-all duration-300"
                                    :style="{ height: `${Math.max((row.rate / maxRenewalCompliance) * 100, 4)}%` }"
                                ></div>
                            </div>
                            <span class="text-[0.7rem] font-bold text-[#475569]">{{ row.year }}</span>
                        </div>

                        <div v-if="!analytics.renewalCompliance?.length" class="flex h-full w-full items-center justify-center text-xs text-[#94a3b8]">
                            No compliance records available.
                        </div>
                    </div>

                    <!-- Breakdown Rows -->
                    <div class="mt-4 space-y-2">
                        <div
                            v-for="row in analytics.renewalCompliance"
                            :key="`compliance-${row.year}`"
                            class="flex items-center justify-between rounded-lg border border-[#dde4de] bg-[#f8faf9] px-3.5 py-2 text-xs"
                        >
                            <span class="font-bold text-[#0f172a]">{{ row.year }}</span>
                            <span class="text-[#64748b]">{{ row.compliant }} of {{ row.eligible }} compliant</span>
                            <span class="rounded-full bg-[#014d3c]/10 px-2 py-0.5 text-[0.68rem] font-bold text-[#014d3c]">{{ row.rate }}%</span>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Operations & Volume Trends -->
            <section class="grid gap-4 xl:grid-cols-2">
                <!-- Membership Applications by Status -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Membership Applications Trend</h2>
                        </div>
                        <div class="flex items-center gap-3 text-[0.7rem] font-semibold text-[#64748b]">
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#86efac]"></span> Created</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#014d3c]"></span> Approved</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span> Rejected</span>
                        </div>
                    </div>

                    <div class="mt-4 flex h-48 items-end gap-2 overflow-x-auto rounded-xl border border-[#edf2ef] bg-[#fbfdfc] px-3 pb-3 pt-6">
                        <div
                            v-for="row in analytics.applicationTrend"
                            :key="row.date"
                            class="flex flex-1 flex-col items-center gap-1.5"
                        >
                            <div class="flex h-32 w-full items-end justify-center gap-1">
                                <div
                                    class="w-1.5 rounded-full bg-[#86efac] transition-all"
                                    :title="`Created: ${row.created}`"
                                    :style="{ height: `${Math.max((row.created / maxApplicationTrend) * 100, 2)}%` }"
                                ></div>
                                <div
                                    class="w-1.5 rounded-full bg-[#014d3c] transition-all"
                                    :title="`Approved: ${row.approved}`"
                                    :style="{ height: `${Math.max((row.approved / maxApplicationTrend) * 100, 2)}%` }"
                                ></div>
                                <div
                                    class="w-1.5 rounded-full bg-rose-500 transition-all"
                                    :title="`Rejected: ${row.rejected}`"
                                    :style="{ height: `${Math.max((row.rejected / maxApplicationTrend) * 100, 2)}%` }"
                                ></div>
                            </div>
                            <span class="text-[0.65rem] font-bold text-[#64748b]">{{ row.label }}</span>
                        </div>

                        <div v-if="!analytics.applicationTrend?.length" class="flex h-full w-full items-center justify-center text-xs text-[#94a3b8]">
                            No application trend records for this window.
                        </div>
                    </div>
                </article>

                <!-- Renewals by Status -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.45a.75.75 0 000-1.5H4.5a.75.75 0 00-.75.75v3.75a.75.75 0 001.5 0v-2.128l.45.45a7 7 0 0011.838-3.177.75.75 0 00-1.226-.75zm-10.624-2.85a5.5 5.5 0 019.201-2.465l.312.311h-2.45a.75.75 0 000 1.5H15.5a.75.75 0 00.75-.75V3.42a.75.75 0 00-1.5 0v2.128l-.45-.45A7 7 0 002.462 8.275a.75.75 0 001.226.75z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Renewals Trend</h2>
                        </div>
                        <div class="flex items-center gap-3 text-[0.7rem] font-semibold text-[#64748b]">
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-sky-400"></span> Created</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#014d3c]"></span> Approved</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span> Rejected</span>
                        </div>
                    </div>

                    <div class="mt-4 flex h-48 items-end gap-2 overflow-x-auto rounded-xl border border-[#edf2ef] bg-[#fbfdfc] px-3 pb-3 pt-6">
                        <div
                            v-for="row in analytics.renewalTrend"
                            :key="row.date"
                            class="flex flex-1 flex-col items-center gap-1.5"
                        >
                            <div class="flex h-32 w-full items-end justify-center gap-1">
                                <div
                                    class="w-1.5 rounded-full bg-sky-400 transition-all"
                                    :title="`Created: ${row.created}`"
                                    :style="{ height: `${Math.max((row.created / maxRenewalTrend) * 100, 2)}%` }"
                                ></div>
                                <div
                                    class="w-1.5 rounded-full bg-[#014d3c] transition-all"
                                    :title="`Approved: ${row.approved}`"
                                    :style="{ height: `${Math.max((row.approved / maxRenewalTrend) * 100, 2)}%` }"
                                ></div>
                                <div
                                    class="w-1.5 rounded-full bg-rose-500 transition-all"
                                    :title="`Rejected: ${row.rejected}`"
                                    :style="{ height: `${Math.max((row.rejected / maxRenewalTrend) * 100, 2)}%` }"
                                ></div>
                            </div>
                            <span class="text-[0.65rem] font-bold text-[#64748b]">{{ row.label }}</span>
                        </div>

                        <div v-if="!analytics.renewalTrend?.length" class="flex h-full w-full items-center justify-center text-xs text-[#94a3b8]">
                            No renewal trend records for this window.
                        </div>
                    </div>
                </article>
            </section>

            <!-- Inquiries & Payments -->
            <section class="grid gap-4 xl:grid-cols-2">
                <!-- Most Common Inquiry Topics -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Most Common Inquiry Topics</h2>
                        </div>
                        <span class="text-xs font-semibold text-[#64748b]">{{ analytics.inquiryTopics?.length || 0 }} Topics</span>
                    </div>

                    <div class="mt-4 flex-1 space-y-3">
                        <div
                            v-for="row in analytics.inquiryTopics"
                            :key="row.label"
                            class="rounded-xl border border-[#edf2ef] bg-[#fbfdfc] p-3 transition-colors hover:border-[#d5e0d8]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#0f172a]">{{ row.label }}</span>
                                <span class="rounded-full bg-[#014d3c]/10 px-2 py-0.5 text-xs font-black text-[#014d3c]">{{ row.value }}</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#e2ebe6]">
                                <div
                                    class="h-1.5 rounded-full bg-gradient-to-r from-[#003629] to-[#014d3c]"
                                    :style="{ width: `${(row.value / maxInquiryTopics) * 100}%` }"
                                ></div>
                            </div>
                        </div>

                        <div v-if="!analytics.inquiryTopics?.length" class="py-8 text-center text-xs text-[#94a3b8]">
                            No inquiry topics logged in this period.
                        </div>
                    </div>
                </article>

                <!-- Payments Recorded -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2.5 4A1.5 1.5 0 001 5.5V6h18v-.5A1.5 1.5 0 0017.5 4h-15zM19 8.5H1v6A1.5 1.5 0 002.5 16h15a1.5 1.5 0 001.5-1.5v-6zM3 13.25a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm4.75-.75a.75.75 0 000 1.5h3.5a.75.75 0 000-1.5h-3.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Payments Recorded</h2>
                        </div>
                        <div class="flex items-center gap-3 text-[0.7rem] font-semibold text-[#64748b]">
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#10b981]"></span> Applications</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#6366f1]"></span> Renewals</span>
                        </div>
                    </div>

                    <div class="mt-4 flex h-48 items-end gap-2 overflow-x-auto rounded-xl border border-[#edf2ef] bg-[#fbfdfc] px-3 pb-3 pt-6">
                        <div
                            v-for="row in analytics.paymentTrend"
                            :key="row.date"
                            class="flex flex-1 flex-col items-center gap-1.5"
                        >
                            <div class="flex h-32 w-full items-end justify-center gap-1">
                                <div
                                    class="w-2 rounded-full bg-[#10b981] transition-all"
                                    :title="`Applications: ${row.applications}`"
                                    :style="{ height: `${Math.max((row.applications / maxPaymentTrend) * 100, 2)}%` }"
                                ></div>
                                <div
                                    class="w-2 rounded-full bg-[#6366f1] transition-all"
                                    :title="`Renewals: ${row.renewals}`"
                                    :style="{ height: `${Math.max((row.renewals / maxPaymentTrend) * 100, 2)}%` }"
                                ></div>
                            </div>
                            <span class="text-[0.65rem] font-bold text-[#64748b]">{{ row.label }}</span>
                        </div>

                        <div v-if="!analytics.paymentTrend?.length" class="flex h-full w-full items-center justify-center text-xs text-[#94a3b8]">
                            No payments recorded in this period.
                        </div>
                    </div>
                </article>
            </section>

            <!-- Queue Health & Turnaround -->
            <section class="grid gap-4 xl:grid-cols-2">
                <!-- Queue Aging -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Queue Aging</h2>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <!-- Applications Queue -->
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-4">
                            <p class="text-[0.65rem] font-black uppercase tracking-[0.14em] text-[#014d3c]">Applications</p>
                            <div class="mt-3 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">1–3 Days</span>
                                    <span class="rounded-md bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800">{{ analytics.queueAging.applications.oneToThreeDays }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">4–7 Days</span>
                                    <span class="rounded-md bg-amber-100 px-2 py-0.5 font-bold text-amber-800">{{ analytics.queueAging.applications.fourToSevenDays }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">8+ Days</span>
                                    <span class="rounded-md bg-rose-100 px-2 py-0.5 font-bold text-rose-800">{{ analytics.queueAging.applications.eightPlusDays }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Renewals Queue -->
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-4">
                            <p class="text-[0.65rem] font-black uppercase tracking-[0.14em] text-[#014d3c]">Renewals</p>
                            <div class="mt-3 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">1–3 Days</span>
                                    <span class="rounded-md bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800">{{ analytics.queueAging.renewals.oneToThreeDays }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">4–7 Days</span>
                                    <span class="rounded-md bg-amber-100 px-2 py-0.5 font-bold text-amber-800">{{ analytics.queueAging.renewals.fourToSevenDays }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">8+ Days</span>
                                    <span class="rounded-md bg-rose-100 px-2 py-0.5 font-bold text-rose-800">{{ analytics.queueAging.renewals.eightPlusDays }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Average Turnaround Time -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Average Turnaround Time</h2>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <!-- Applications Turnaround -->
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-4">
                            <p class="text-[0.65rem] font-black uppercase tracking-[0.14em] text-[#014d3c]">Applications</p>
                            <div class="mt-3 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">Approved</span>
                                    <span class="font-black text-[#014d3c]">{{ analytics.turnaround.applications.approvedHours }} hrs</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">Rejected</span>
                                    <span class="font-black text-rose-600">{{ analytics.turnaround.applications.rejectedHours }} hrs</span>
                                </div>
                            </div>
                        </div>

                        <!-- Renewals Turnaround -->
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-4">
                            <p class="text-[0.65rem] font-black uppercase tracking-[0.14em] text-[#014d3c]">Renewals</p>
                            <div class="mt-3 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">Approved</span>
                                    <span class="font-black text-[#014d3c]">{{ analytics.turnaround.renewals.approvedHours }} hrs</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">Rejected</span>
                                    <span class="font-black text-rose-600">{{ analytics.turnaround.renewals.rejectedHours }} hrs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Financials & Claims -->
            <section class="grid gap-4 xl:grid-cols-2">
                <!-- Collections Summary -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M1 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H2a1 1 0 01-1-1V4zm1 4v7a2 2 0 002 2h12a2 2 0 002-2V8H2zm12 3a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Collections Summary</h2>
                        </div>
                    </div>

                    <!-- Total Collected Banner -->
                    <div class="mt-4 rounded-xl bg-gradient-to-r from-[#003629] via-[#00483a] to-[#005a45] p-4 text-white">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.14em] text-[#7ddfb8]">Total Collected</span>
                        <p class="mt-1 text-2xl font-black tracking-tight">{{ money(analytics.collections.total) }}</p>
                    </div>

                    <!-- Split Grid -->
                    <div class="mt-3.5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3.5">
                            <span class="text-[0.65rem] font-bold uppercase tracking-wider text-[#64748b]">Application Payments</span>
                            <p class="mt-1 text-base font-black text-[#014d3c]">{{ money(analytics.collections.applicationPayments) }}</p>
                        </div>
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3.5">
                            <span class="text-[0.65rem] font-bold uppercase tracking-wider text-[#64748b]">Renewal Payments</span>
                            <p class="mt-1 text-base font-black text-[#014d3c]">{{ money(analytics.collections.renewalPayments) }}</p>
                        </div>
                    </div>

                    <!-- Fee Mix -->
                    <div class="mt-3.5 space-y-2 rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3.5 text-xs">
                        <div class="flex items-center justify-between border-b border-[#edf2ef] pb-1.5">
                            <span class="text-[#64748b]">Membership Fees</span>
                            <strong class="font-bold text-[#0f172a]">{{ money(analytics.collections.membershipFees) }}</strong>
                        </div>
                        <div class="flex items-center justify-between border-b border-[#edf2ef] pb-1.5">
                            <span class="text-[#64748b]">Annual Due</span>
                            <strong class="font-bold text-[#0f172a]">{{ money(analytics.collections.annualDue) }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[#64748b]">Mortuary Fee</span>
                            <strong class="font-bold text-[#0f172a]">{{ money(analytics.collections.mortuaryFee) }}</strong>
                        </div>
                    </div>
                </article>

                <!-- Mortuary Assistance Summary -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Mortuary Assistance Summary</h2>
                        </div>
                    </div>

                    <!-- 4 Metric Cards -->
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Total Claims</span>
                            <p class="mt-1 text-xl font-black text-[#0f172a]">{{ analytics.mortuaryAssistanceSummary.totalClaims }}</p>
                        </div>
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Total Assistance</span>
                            <p class="mt-1 text-xl font-black text-[#014d3c]">{{ money(analytics.mortuaryAssistanceSummary.totalAmount) }}</p>
                        </div>
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Released Claims</span>
                            <p class="mt-1 text-xl font-black text-emerald-600">{{ analytics.mortuaryAssistanceSummary.releasedClaims }}</p>
                        </div>
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Average Claim</span>
                            <p class="mt-1 text-xl font-black text-[#0f172a]">{{ money(analytics.mortuaryAssistanceSummary.averageAmount) }}</p>
                        </div>
                    </div>

                    <!-- Status Breakdown -->
                    <div class="mt-3.5 grid grid-cols-3 gap-2 rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3 text-center text-xs">
                        <div>
                            <span class="text-[0.65rem] font-bold uppercase text-amber-600">Pending</span>
                            <p class="mt-0.5 text-sm font-black text-amber-700">{{ analytics.mortuaryAssistanceSummary.pendingClaims }}</p>
                        </div>
                        <div>
                            <span class="text-[0.65rem] font-bold uppercase text-emerald-600">Approved</span>
                            <p class="mt-0.5 text-sm font-black text-emerald-700">{{ analytics.mortuaryAssistanceSummary.approvedClaims }}</p>
                        </div>
                        <div>
                            <span class="text-[0.65rem] font-bold uppercase text-rose-600">Rejected</span>
                            <p class="mt-0.5 text-sm font-black text-rose-700">{{ analytics.mortuaryAssistanceSummary.rejectedClaims }}</p>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Mortuary Assistance by Barangay & Operations Issues -->
            <section class="grid gap-4 xl:grid-cols-2">
                <!-- Mortuary Assistance by Barangay -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Mortuary Assistance by Barangay</h2>
                        </div>
                        <span class="text-xs font-semibold text-[#64748b]">{{ analytics.mortuaryAssistanceSummary.byBarangay?.length || 0 }} Areas</span>
                    </div>

                    <div class="mt-4 flex-1 space-y-2.5">
                        <div
                            v-for="row in analytics.mortuaryAssistanceSummary.byBarangay"
                            :key="row.label"
                            class="flex items-center justify-between rounded-xl border border-[#edf2ef] bg-[#fbfdfc] px-3.5 py-2.5 text-xs transition-colors hover:border-[#d5e0d8]"
                        >
                            <div>
                                <p class="font-bold text-[#0f172a]">{{ row.label }}</p>
                                <span class="text-[0.68rem] text-[#64748b]">{{ row.count }} claims</span>
                            </div>
                            <span class="font-black text-[#014d3c]">{{ money(row.amount) }}</span>
                        </div>

                        <div v-if="!analytics.mortuaryAssistanceSummary.byBarangay?.length" class="py-8 text-center text-xs text-[#94a3b8]">
                            No mortuary claims recorded by barangay in this period.
                        </div>
                    </div>
                </article>

                <!-- Rejections & Document Issues -->
                <article class="flex flex-col rounded-xl border border-[#dde4de] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f3] pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Rejections & Document Issues</h2>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <!-- Rejection Reasons -->
                        <div>
                            <p class="text-[0.65rem] font-black uppercase tracking-wider text-[#64748b]">Rejection Reasons</p>
                            <div class="mt-2.5 space-y-2">
                                <div
                                    v-for="row in analytics.rejectionReasons"
                                    :key="row.label"
                                    class="flex items-center justify-between rounded-lg border border-[#edf2ef] bg-[#fbfdfc] px-3 py-2 text-xs"
                                >
                                    <span class="truncate font-semibold text-[#0f172a]" :title="row.label">{{ row.label }}</span>
                                    <span class="rounded bg-rose-50 px-1.5 py-0.5 text-[0.68rem] font-black text-rose-600">{{ row.value }}</span>
                                </div>

                                <div v-if="!analytics.rejectionReasons?.length" class="py-4 text-center text-xs text-[#94a3b8]">
                                    None recorded.
                                </div>
                            </div>
                        </div>

                        <!-- Document Issues -->
                        <div>
                            <p class="text-[0.65rem] font-black uppercase tracking-wider text-[#64748b]">Document Issues</p>
                            <div class="mt-2.5 space-y-2">
                                <div
                                    v-for="row in analytics.documentIssues"
                                    :key="row.label"
                                    class="flex items-center justify-between rounded-lg border border-[#edf2ef] bg-[#fbfdfc] px-3 py-2 text-xs"
                                >
                                    <span class="truncate font-semibold text-[#0f172a]" :title="row.label">{{ row.label }}</span>
                                    <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[0.68rem] font-black text-amber-600">{{ row.value }}</span>
                                </div>

                                <div v-if="!analytics.documentIssues?.length" class="py-4 text-center text-xs text-[#94a3b8]">
                                    None recorded.
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Export Modal -->
            <div
                v-if="exportModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/50 p-4 backdrop-blur-sm"
                @click.self="closeExportModal"
            >
                <section class="w-full max-w-lg overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-2xl transition-all">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-[#edf2ef] px-5 py-4">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm4.75 6.75a.75.75 0 011.5 0v3.69l1.22-1.22a.75.75 0 111.06 1.06l-2.5 2.5a.75.75 0 01-1.06 0l-2.5-2.5a.75.75 0 111.06-1.06l1.22 1.22V8.75z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-base font-bold text-[#0f172a]">Export Analytics Report</h2>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#64748b] transition hover:bg-[#f1f5f9] hover:text-[#0f172a]"
                            @click="closeExportModal"
                        >
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-5">
                        <div class="rounded-xl border border-[#dde4de] bg-[#f8faf9] p-3 text-xs text-[#64748b]">
                            Exporting operations data for window:
                            <strong class="text-[#0f172a]">{{ formatDate(filters.date_from) }}</strong> to
                            <strong class="text-[#0f172a]">{{ formatDate(filters.date_to) }}</strong>
                            ({{ filters.days }} days)
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <!-- CSV -->
                            <button
                                type="button"
                                class="flex flex-col items-center gap-2 rounded-xl border border-[#dde4de] bg-white p-4 text-center transition-all duration-200 hover:border-[#014d3c] hover:bg-[#f0faf5] hover:shadow-md"
                                @click="exportAnalytics('csv')"
                            >
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-[#0f172a]">CSV Data</span>
                                <span class="text-[0.62rem] text-[#64748b]">Raw tabular data</span>
                            </button>

                            <!-- PDF -->
                            <button
                                type="button"
                                class="flex flex-col items-center gap-2 rounded-xl border border-[#dde4de] bg-white p-4 text-center transition-all duration-200 hover:border-[#014d3c] hover:bg-[#f0faf5] hover:shadow-md"
                                @click="exportAnalytics('pdf')"
                            >
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-[#0f172a]">PDF Report</span>
                                <span class="text-[0.62rem] text-[#64748b]">Formatted print doc</span>
                            </button>

                            <!-- Excel -->
                            <button
                                type="button"
                                class="flex flex-col items-center gap-2 rounded-xl border border-[#dde4de] bg-white p-4 text-center transition-all duration-200 hover:border-[#014d3c] hover:bg-[#f0faf5] hover:shadow-md"
                                @click="exportAnalytics('xlsx')"
                            >
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-[#0f172a]">Excel (XLSX)</span>
                                <span class="text-[0.62rem] text-[#64748b]">Structured sheets</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end border-t border-[#edf2ef] bg-[#fbfdfc] px-5 py-3">
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-4 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f9] hover:text-[#0f172a]"
                            @click="closeExportModal"
                        >
                            Cancel
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
