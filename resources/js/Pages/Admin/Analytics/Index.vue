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

const maxTrend = computed(() => Math.max(
    ...props.analytics.trend.map((row) => Math.max(row.pageViews, row.searches)),
    1,
));

const maxApplicationTrend = computed(() => Math.max(
    ...props.analytics.applicationTrend.map((row) => Math.max(row.created, row.approved, row.rejected)),
    1,
));

const maxRenewalTrend = computed(() => Math.max(
    ...props.analytics.renewalTrend.map((row) => Math.max(row.created, row.approved, row.rejected)),
    1,
));

const maxPaymentTrend = computed(() => Math.max(
    ...props.analytics.paymentTrend.map((row) => Math.max(row.applications, row.renewals)),
    1,
));

const maxAdvisoryTrend = computed(() => Math.max(
    ...props.analytics.advisoryMonthlyTrend.map((row) => row.published),
    1,
));

const maxBarangayStatus = computed(() => Math.max(
    ...props.analytics.activeInactiveByBarangay.map((row) => Math.max(row.active, row.inactive)),
    1,
));

const maxRenewalCompliance = computed(() => Math.max(
    ...props.analytics.renewalCompliance.map((row) => row.rate),
    1,
));

const maxApplicationDecisionTrend = computed(() => Math.max(
    ...props.analytics.applicationDecisionTrend.map((row) => Math.max(row.approved, row.rejected)),
    1,
));

const maxInquiryTopics = computed(() => Math.max(
    ...props.analytics.inquiryTopics.map((row) => row.value),
    1,
));

const currencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
});

function money(value) {
    return currencyFormatter.format(Number(value || 0));
}

function formatDateTime(value) {
    if (!value) {
        return 'No payments recorded';
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

const filters = reactive({
    days: props.analytics.filters.days,
    date_from: props.analytics.filters.dateFrom,
    date_to: props.analytics.filters.dateTo,
});
const exportModalOpen = ref(false);

function applyDays(days) {
    filters.days = days;
    router.get(props.analytics.filters.baseUrl, { days, date_from: filters.date_from, date_to: filters.date_to }, {
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
        <div class="space-y-7">
            <section class="rounded-[2rem] bg-[#0d4438] px-6 py-7 text-white shadow-[0_18px_60px_rgba(0,54,41,0.18)]">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#c7ead6]">System Analytics</p>
                        <h1 class="mt-3 text-3xl font-black tracking-[-0.04em]">Usage and operations overview</h1>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="days in analytics.filters.options"
                            :key="days"
                            type="button"
                            class="rounded-full px-4 py-2 text-sm font-bold transition"
                            :class="analytics.filters.days === days ? 'bg-white text-[#0d4438]' : 'bg-white/10 text-white hover:bg-white/20'"
                            @click="applyDays(days)"
                        >
                            {{ days }} days
                        </button>
                    </div>
                </div>
                <form class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-end" @submit.prevent="applyDateRange">
                    <label class="space-y-2">
                        <span class="block text-xs font-black uppercase tracking-[0.18em] text-[#c7ead6]">From</span>
                        <input v-model="filters.date_from" type="date" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm text-white outline-none">
                    </label>
                    <label class="space-y-2">
                        <span class="block text-xs font-black uppercase tracking-[0.18em] text-[#c7ead6]">To</span>
                        <input v-model="filters.date_to" type="date" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm text-white outline-none">
                    </label>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-xl bg-white px-4 py-2.5 text-sm font-black text-[#0d4438]">Apply Range</button>
                        <button type="button" class="rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-black text-white transition hover:bg-white/20" @click="openExportModal">
                            Export Report
                        </button>
                    </div>
                </form>
            </section>

            <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Page Views</p>
                    <p class="mt-3 text-4xl font-black tracking-[-0.04em] text-[#143c32]">{{ analytics.summary.pageViews }}</p>
                </article>
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Searches</p>
                    <p class="mt-3 text-4xl font-black tracking-[-0.04em] text-[#143c32]">{{ analytics.summary.searches }}</p>
                </article>
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Active Users</p>
                    <p class="mt-3 text-4xl font-black tracking-[-0.04em] text-[#143c32]">{{ analytics.summary.activeUsers }}</p>
                </article>
            </section>

            <section class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                <div class="flex flex-col gap-2 border-b border-[#e4ebe7] pb-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Mobile Service Monitoring</p>
                        <h2 class="mt-1 text-xl font-bold text-[#143c32]">Farmer mobile usage, access issues, requests, and notification engagement</h2>
                    </div>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article class="rounded-[1.4rem] bg-[#eef7f2] p-5">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#6f7e78]">Total Mobile Users</p>
                        <p class="mt-3 text-3xl font-black text-[#143c32]">{{ analytics.mobileMonitoring.summary.total_mobile_users }}</p>
                    </article>
                    <article class="rounded-[1.4rem] bg-[#f6f8f7] p-5">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#6f7e78]">Active In Range</p>
                        <p class="mt-3 text-3xl font-black text-[#143c32]">{{ analytics.mobileMonitoring.summary.active_users_in_range }}</p>
                    </article>
                    <article class="rounded-[1.4rem] bg-[#fff4f1] p-5">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#a44d3f]">Failed Login Issues</p>
                        <p class="mt-3 text-3xl font-black text-[#a44d3f]">{{ analytics.mobileMonitoring.summary.failed_login_issues }}</p>
                    </article>
                    <article class="rounded-[1.4rem] bg-[#fff7eb] p-5">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#a06a00]">Failed OTP Issues</p>
                        <p class="mt-3 text-3xl font-black text-[#a06a00]">{{ analytics.mobileMonitoring.summary.failed_otp_issues }}</p>
                    </article>
                    <article class="rounded-[1.4rem] bg-[#f4f5ff] p-5">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#4655a4]">Farmer Inquiries</p>
                        <p class="mt-3 text-3xl font-black text-[#4655a4]">{{ analytics.mobileMonitoring.summary.submitted_inquiries_via_mobile }}</p>
                    </article>
                    <article class="rounded-[1.4rem] bg-[#fbf5f8] p-5">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#8d4663]">Mobile Renewal Requests</p>
                        <p class="mt-3 text-3xl font-black text-[#8d4663]">{{ analytics.mobileMonitoring.summary.mobile_renewal_requests }}</p>
                    </article>
                </div>

                <div class="mt-6 grid gap-5 xl:grid-cols-2">
                    <article class="rounded-[1.4rem] bg-[#f6f8f7] p-5">
                        <h3 class="text-lg font-bold text-[#143c32]">Notification Engagement</h3>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="rounded-[1rem] bg-white px-4 py-3"><span class="text-sm text-[#5f6f67]">Sent</span><p class="mt-2 text-2xl font-black text-[#143c32]">{{ analytics.mobileMonitoring.notificationEngagement.sent }}</p></div>
                            <div class="rounded-[1rem] bg-white px-4 py-3"><span class="text-sm text-[#5f6f67]">Delivered</span><p class="mt-2 text-2xl font-black text-[#143c32]">{{ analytics.mobileMonitoring.notificationEngagement.delivered }}</p></div>
                            <div class="rounded-[1rem] bg-white px-4 py-3"><span class="text-sm text-[#5f6f67]">Read</span><p class="mt-2 text-2xl font-black text-[#143c32]">{{ analytics.mobileMonitoring.notificationEngagement.read }}</p></div>
                            <div class="rounded-[1rem] bg-white px-4 py-3"><span class="text-sm text-[#5f6f67]">Failed</span><p class="mt-2 text-2xl font-black text-[#a44d3f]">{{ analytics.mobileMonitoring.notificationEngagement.failed }}</p></div>
                        </div>
                    </article>

                    <article class="rounded-[1.4rem] bg-[#eef7f2] p-5">
                        <h3 class="text-lg font-bold text-[#143c32]">Read Rate</h3>
                        <p class="mt-4 text-5xl font-black tracking-[-0.04em] text-[#0f5b46]">{{ analytics.mobileMonitoring.notificationEngagement.read_rate }}%</p>
                        <p class="mt-3 text-sm text-[#5f6f67]">Share of farmer-targeted notifications that were opened or marked read.</p>
                    </article>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-[#143c32]">Daily trend</h2>
                            <p class="mt-1 text-sm text-[#5f6f67]">Page views, searches, and business events by day.</p>
                        </div>
                    </div>
                    <div class="mt-6">
                        <div class="grid grid-cols-[repeat(auto-fit,minmax(44px,1fr))] items-end gap-3">
                            <div v-for="row in analytics.trend" :key="row.date" class="space-y-2">
                                <div class="flex h-48 items-end justify-center gap-1">
                                    <div class="w-2 rounded-full bg-[#0f5b46]" :style="{ height: `${(row.pageViews / maxTrend) * 100}%` }"></div>
                                    <div class="w-2 rounded-full bg-[#86b049]" :style="{ height: `${(row.searches / maxTrend) * 100}%` }"></div>
                                </div>
                                <div class="text-center text-[0.68rem] font-bold uppercase tracking-[0.16em] text-[#7a8781]">{{ row.label }}</div>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-4 text-sm font-semibold text-[#44515d]">
                            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#0f5b46]"></span>Page views</span>
                            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#86b049]"></span>Searches</span>
                        </div>
                    </div>
                </article>

                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Top events</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Most frequent tracked actions.</p>
                    <div class="mt-6 space-y-4">
                        <div v-for="row in analytics.topEvents" :key="row.label" class="flex items-center justify-between gap-4 rounded-[1rem] bg-[#f6f8f7] px-4 py-3">
                            <span class="text-sm font-semibold text-[#20312b]">{{ row.label }}</span>
                            <span class="text-sm font-black text-[#0f5b46]">{{ row.value }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Active vs inactive farmers by barangay</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Current farmer registry distribution across barangays.</p>
                    <div class="mt-6 space-y-4">
                        <div v-for="row in analytics.activeInactiveByBarangay" :key="row.barangay" class="rounded-[1rem] bg-[#f6f8f7] px-4 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm font-semibold text-[#20312b]">{{ row.barangay }}</span>
                                <span class="text-xs font-black uppercase tracking-[0.14em] text-[#6f7e78]">{{ row.total }} farmers</span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-[#0f5b46]">Active</span>
                                        <strong class="text-[#0f5b46]">{{ row.active }}</strong>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-[#dfe9e3]">
                                        <div class="h-2 rounded-full bg-[#0f5b46]" :style="{ width: `${(row.active / maxBarangayStatus) * 100}%` }"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-[#d86c6c]">Inactive</span>
                                        <strong class="text-[#d86c6c]">{{ row.inactive }}</strong>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-[#f1dddd]">
                                        <div class="h-2 rounded-full bg-[#d86c6c]" :style="{ width: `${(row.inactive / maxBarangayStatus) * 100}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Renewal compliance rate per year</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Share of eligible farmers with recorded paid or settled renewal ledgers.</p>
                    <div class="mt-6 grid grid-cols-[repeat(auto-fit,minmax(52px,1fr))] items-end gap-3">
                        <div v-for="row in analytics.renewalCompliance" :key="row.year" class="space-y-2">
                            <div class="flex h-40 items-end justify-center">
                                <div class="w-5 rounded-full bg-[#0f5b46]" :style="{ height: `${(row.rate / maxRenewalCompliance) * 100}%` }"></div>
                            </div>
                            <div class="text-center text-[0.65rem] font-bold uppercase tracking-[0.14em] text-[#7a8781]">{{ row.year }}</div>
                            <div class="text-center text-xs font-black text-[#143c32]">{{ row.rate }}%</div>
                        </div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <div v-for="row in analytics.renewalCompliance" :key="`compliance-${row.year}`" class="flex items-center justify-between rounded-[1rem] bg-[#f6f8f7] px-4 py-3 text-sm">
                            <span class="font-semibold text-[#20312b]">{{ row.year }}</span>
                            <span class="text-[#5f6f67]">{{ row.compliant }} / {{ row.eligible }} compliant</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Membership applications by status</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Created, approved, and rejected applications over time.</p>
                    <div class="mt-6 grid grid-cols-[repeat(auto-fit,minmax(40px,1fr))] items-end gap-3">
                        <div v-for="row in analytics.applicationTrend" :key="row.date" class="space-y-2">
                            <div class="flex h-40 items-end justify-center gap-1">
                                <div class="w-2 rounded-full bg-[#cbdc6b]" :style="{ height: `${(row.created / maxApplicationTrend) * 100}%` }"></div>
                                <div class="w-2 rounded-full bg-[#0f5b46]" :style="{ height: `${(row.approved / maxApplicationTrend) * 100}%` }"></div>
                                <div class="w-2 rounded-full bg-[#d86c6c]" :style="{ height: `${(row.rejected / maxApplicationTrend) * 100}%` }"></div>
                            </div>
                            <div class="text-center text-[0.65rem] font-bold uppercase tracking-[0.14em] text-[#7a8781]">{{ row.label }}</div>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-4 text-sm font-semibold text-[#44515d]">
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#cbdc6b]"></span>Created</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#0f5b46]"></span>Approved</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#d86c6c]"></span>Rejected</span>
                    </div>
                </article>

                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Renewals by status</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Created, approved, and rejected renewal requests over time.</p>
                    <div class="mt-6 grid grid-cols-[repeat(auto-fit,minmax(40px,1fr))] items-end gap-3">
                        <div v-for="row in analytics.renewalTrend" :key="row.date" class="space-y-2">
                            <div class="flex h-40 items-end justify-center gap-1">
                                <div class="w-2 rounded-full bg-[#b7d8ff]" :style="{ height: `${(row.created / maxRenewalTrend) * 100}%` }"></div>
                                <div class="w-2 rounded-full bg-[#0f5b46]" :style="{ height: `${(row.approved / maxRenewalTrend) * 100}%` }"></div>
                                <div class="w-2 rounded-full bg-[#d86c6c]" :style="{ height: `${(row.rejected / maxRenewalTrend) * 100}%` }"></div>
                            </div>
                            <div class="text-center text-[0.65rem] font-bold uppercase tracking-[0.14em] text-[#7a8781]">{{ row.label }}</div>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-4 text-sm font-semibold text-[#44515d]">
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#b7d8ff]"></span>Created</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#0f5b46]"></span>Approved</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#d86c6c]"></span>Rejected</span>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Application approval and rejection trend</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Yearly decision volume for membership applications.</p>
                    <div class="mt-6 grid grid-cols-[repeat(auto-fit,minmax(52px,1fr))] items-end gap-3">
                        <div v-for="row in analytics.applicationDecisionTrend" :key="row.year" class="space-y-2">
                            <div class="flex h-40 items-end justify-center gap-1.5">
                                <div class="w-3 rounded-full bg-[#0f5b46]" :style="{ height: `${(row.approved / maxApplicationDecisionTrend) * 100}%` }"></div>
                                <div class="w-3 rounded-full bg-[#d86c6c]" :style="{ height: `${(row.rejected / maxApplicationDecisionTrend) * 100}%` }"></div>
                            </div>
                            <div class="text-center text-[0.65rem] font-bold uppercase tracking-[0.14em] text-[#7a8781]">{{ row.year }}</div>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-4 text-sm font-semibold text-[#44515d]">
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#0f5b46]"></span>Approved</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#d86c6c]"></span>Rejected</span>
                    </div>
                </article>

                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Most common inquiry topics</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Most frequent farmer concerns based on category or submitted subject.</p>
                    <div class="mt-6 space-y-4">
                        <div v-for="row in analytics.inquiryTopics" :key="row.label" class="rounded-[1rem] bg-[#f6f8f7] px-4 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm font-semibold text-[#20312b]">{{ row.label }}</span>
                                <span class="text-sm font-black text-[#0f5b46]">{{ row.value }}</span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-[#dfe9e3]">
                                <div class="h-2 rounded-full bg-[#86b049]" :style="{ width: `${(row.value / maxInquiryTopics) * 100}%` }"></div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Payments recorded</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Membership application and renewal payments recorded by day.</p>
                    <div class="mt-6 grid grid-cols-[repeat(auto-fit,minmax(40px,1fr))] items-end gap-3">
                        <div v-for="row in analytics.paymentTrend" :key="row.date" class="space-y-2">
                            <div class="flex h-40 items-end justify-center gap-1">
                                <div class="w-2 rounded-full bg-[#6cbf9f]" :style="{ height: `${(row.applications / maxPaymentTrend) * 100}%` }"></div>
                                <div class="w-2 rounded-full bg-[#3b82f6]" :style="{ height: `${(row.renewals / maxPaymentTrend) * 100}%` }"></div>
                            </div>
                            <div class="text-center text-[0.65rem] font-bold uppercase tracking-[0.14em] text-[#7a8781]">{{ row.label }}</div>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-4 text-sm font-semibold text-[#44515d]">
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#6cbf9f]"></span>Applications</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#3b82f6]"></span>Renewals</span>
                    </div>
                </article>

                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Advisory publishing by month</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Published advisory volume aggregated by month.</p>
                    <div class="mt-6 grid grid-cols-[repeat(auto-fit,minmax(52px,1fr))] items-end gap-3">
                        <div v-for="row in analytics.advisoryMonthlyTrend" :key="row.month" class="space-y-2">
                            <div class="flex h-40 items-end justify-center">
                                <div class="w-4 rounded-full bg-[#0f5b46]" :style="{ height: `${(row.published / maxAdvisoryTrend) * 100}%` }"></div>
                            </div>
                            <div class="text-center text-[0.65rem] font-bold uppercase tracking-[0.14em] text-[#7a8781]">{{ row.label }}</div>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-4 text-sm font-semibold text-[#44515d]">
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#0f5b46]"></span>Published</span>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Queue aging</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Pending items grouped by how long they have been waiting.</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-sm font-black uppercase tracking-[0.16em] text-[#6f7e78]">Applications</p>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between"><span>1-3 days</span><strong>{{ analytics.queueAging.applications.oneToThreeDays }}</strong></div>
                                <div class="flex justify-between"><span>4-7 days</span><strong>{{ analytics.queueAging.applications.fourToSevenDays }}</strong></div>
                                <div class="flex justify-between"><span>8+ days</span><strong>{{ analytics.queueAging.applications.eightPlusDays }}</strong></div>
                            </div>
                        </div>
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-sm font-black uppercase tracking-[0.16em] text-[#6f7e78]">Renewals</p>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between"><span>1-3 days</span><strong>{{ analytics.queueAging.renewals.oneToThreeDays }}</strong></div>
                                <div class="flex justify-between"><span>4-7 days</span><strong>{{ analytics.queueAging.renewals.fourToSevenDays }}</strong></div>
                                <div class="flex justify-between"><span>8+ days</span><strong>{{ analytics.queueAging.renewals.eightPlusDays }}</strong></div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Average turnaround time</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Average time from submission to review decision.</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-sm font-black uppercase tracking-[0.16em] text-[#6f7e78]">Applications</p>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between"><span>Approved</span><strong>{{ analytics.turnaround.applications.approvedHours }} hrs</strong></div>
                                <div class="flex justify-between"><span>Rejected</span><strong>{{ analytics.turnaround.applications.rejectedHours }} hrs</strong></div>
                            </div>
                        </div>
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-sm font-black uppercase tracking-[0.16em] text-[#6f7e78]">Renewals</p>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between"><span>Approved</span><strong>{{ analytics.turnaround.renewals.approvedHours }} hrs</strong></div>
                                <div class="flex justify-between"><span>Rejected</span><strong>{{ analytics.turnaround.renewals.rejectedHours }} hrs</strong></div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Application funnel</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Submission to completion flow for membership applications.</p>
                    <div class="mt-6 space-y-4">
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#f6f8f7] px-4 py-3"><span>Submitted</span><strong>{{ analytics.funnels.applications.submitted }}</strong></div>
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#f6f8f7] px-4 py-3"><span>Approved</span><strong>{{ analytics.funnels.applications.approved }}</strong></div>
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#f6f8f7] px-4 py-3"><span>Rejected</span><strong>{{ analytics.funnels.applications.rejected }}</strong></div>
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#f6f8f7] px-4 py-3"><span>Paid</span><strong>{{ analytics.funnels.applications.paid }}</strong></div>
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#eef7f2] px-4 py-3"><span>Completed</span><strong>{{ analytics.funnels.applications.completed }}</strong></div>
                    </div>
                </article>

                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Renewal funnel</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Submission to completion flow for renewals.</p>
                    <div class="mt-6 space-y-4">
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#f6f8f7] px-4 py-3"><span>Submitted</span><strong>{{ analytics.funnels.renewals.submitted }}</strong></div>
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#f6f8f7] px-4 py-3"><span>Approved</span><strong>{{ analytics.funnels.renewals.approved }}</strong></div>
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#f6f8f7] px-4 py-3"><span>Rejected</span><strong>{{ analytics.funnels.renewals.rejected }}</strong></div>
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#f6f8f7] px-4 py-3"><span>Paid</span><strong>{{ analytics.funnels.renewals.paid }}</strong></div>
                        <div class="flex items-center justify-between rounded-[1rem] bg-[#eef7f2] px-4 py-3"><span>Completed</span><strong>{{ analytics.funnels.renewals.completed }}</strong></div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Collections summary</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Recorded payment totals for the selected period.</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-[1rem] bg-[#eef7f2] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Total collected</p>
                            <p class="mt-3 text-2xl font-black text-[#143c32]">{{ money(analytics.collections.total) }}</p>
                        </div>
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Application payments</p>
                            <p class="mt-3 text-2xl font-black text-[#143c32]">{{ money(analytics.collections.applicationPayments) }}</p>
                        </div>
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Renewal payments</p>
                            <p class="mt-3 text-2xl font-black text-[#143c32]">{{ money(analytics.collections.renewalPayments) }}</p>
                        </div>
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Fee mix</p>
                            <div class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between"><span>Membership</span><strong>{{ money(analytics.collections.membershipFees) }}</strong></div>
                                <div class="flex justify-between"><span>Annual due</span><strong>{{ money(analytics.collections.annualDue) }}</strong></div>
                                <div class="flex justify-between"><span>Mortuary</span><strong>{{ money(analytics.collections.mortuaryFee) }}</strong></div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Payment collection summary</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Payment volume, average ticket size, latest collection, and payment methods.</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-[1rem] bg-[#eef7f2] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Verified payments</p>
                            <p class="mt-3 text-2xl font-black text-[#143c32]">{{ analytics.paymentCollectionSummary.verifiedCount }}</p>
                        </div>
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Average payment</p>
                            <p class="mt-3 text-2xl font-black text-[#143c32]">{{ money(analytics.paymentCollectionSummary.averagePayment) }}</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-[1rem] bg-[#f6f8f7] p-4">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Latest recorded payment</p>
                        <p class="mt-2 text-sm font-semibold text-[#20312b]">{{ formatDateTime(analytics.paymentCollectionSummary.latestPaidAt) }}</p>
                    </div>
                    <div class="mt-4 space-y-4">
                        <div v-for="row in analytics.paymentCollectionSummary.byMethod" :key="row.label" class="flex items-center justify-between gap-4 rounded-[1rem] bg-[#f6f8f7] px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-[#20312b]">{{ row.label }}</p>
                                <p class="mt-1 text-xs text-[#6f7e78]">{{ row.count }} payments</p>
                            </div>
                            <span class="text-sm font-black text-[#0f5b46]">{{ money(row.amount) }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Mortuary assistance summary</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Claim totals, status breakdown, and disbursed assistance value.</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-[1rem] bg-[#eef7f2] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Total claims</p>
                            <p class="mt-3 text-2xl font-black text-[#143c32]">{{ analytics.mortuaryAssistanceSummary.totalClaims }}</p>
                        </div>
                        <div class="rounded-[1rem] bg-[#eef7f2] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Total assistance</p>
                            <p class="mt-3 text-2xl font-black text-[#143c32]">{{ money(analytics.mortuaryAssistanceSummary.totalAmount) }}</p>
                        </div>
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Released</p>
                            <p class="mt-3 text-2xl font-black text-[#143c32]">{{ analytics.mortuaryAssistanceSummary.releasedClaims }}</p>
                        </div>
                        <div class="rounded-[1rem] bg-[#f6f8f7] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#6f7e78]">Average claim</p>
                            <p class="mt-3 text-2xl font-black text-[#143c32]">{{ money(analytics.mortuaryAssistanceSummary.averageAmount) }}</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-[1rem] bg-[#f6f8f7] p-4 text-sm">
                        <div class="flex justify-between"><span>Pending</span><strong>{{ analytics.mortuaryAssistanceSummary.pendingClaims }}</strong></div>
                        <div class="mt-2 flex justify-between"><span>Approved</span><strong>{{ analytics.mortuaryAssistanceSummary.approvedClaims }}</strong></div>
                        <div class="mt-2 flex justify-between"><span>Rejected</span><strong>{{ analytics.mortuaryAssistanceSummary.rejectedClaims }}</strong></div>
                    </div>
                </article>

                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Mortuary assistance by barangay</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Barangays with the highest recorded mortuary assistance activity.</p>
                    <div class="mt-6 space-y-4">
                        <div v-for="row in analytics.mortuaryAssistanceSummary.byBarangay" :key="row.label" class="flex items-center justify-between gap-4 rounded-[1rem] bg-[#f6f8f7] px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-[#20312b]">{{ row.label }}</p>
                                <p class="mt-1 text-xs text-[#6f7e78]">{{ row.count }} claims</p>
                            </div>
                            <span class="text-sm font-black text-[#0f5b46]">{{ money(row.amount) }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Rejection reasons</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Most common rejection causes in the selected period.</p>
                    <div class="mt-6 space-y-4">
                        <div v-for="row in analytics.rejectionReasons" :key="row.label" class="flex items-center justify-between gap-4 rounded-[1rem] bg-[#f6f8f7] px-4 py-3">
                            <span class="text-sm font-semibold text-[#20312b]">{{ row.label }}</span>
                            <span class="text-sm font-black text-[#d86c6c]">{{ row.value }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <h2 class="text-xl font-bold text-[#143c32]">Document issue breakdown</h2>
                    <p class="mt-1 text-sm text-[#5f6f67]">Documents most often rejected during review.</p>
                    <div class="mt-6 space-y-4">
                        <div v-for="row in analytics.documentIssues" :key="row.label" class="flex items-center justify-between gap-4 rounded-[1rem] bg-[#f6f8f7] px-4 py-3">
                            <span class="text-sm font-semibold text-[#20312b]">{{ row.label }}</span>
                            <span class="text-sm font-black text-[#d86c6c]">{{ row.value }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="rounded-[1.8rem] border border-[#dfe6e2] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                <h2 class="text-xl font-bold text-[#143c32]">Recent tracked activity</h2>
                <p class="mt-1 text-sm text-[#5f6f67]">Latest recorded usage and workflow events.</p>
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-left">
                        <thead class="border-b border-[#e4ebe7] text-[0.78rem] font-black uppercase tracking-[0.12em] text-[#6f7e78]">
                            <tr>
                                <th class="px-4 py-3">Event</th>
                                <th class="px-4 py-3">Module</th>
                                <th class="px-4 py-3">Actor</th>
                                <th class="px-4 py-3">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#eef2ef]">
                            <tr v-for="row in analytics.recentActivity" :key="row.id">
                                <td class="px-4 py-4 text-sm font-semibold text-[#20312b]">{{ row.eventName }}</td>
                                <td class="px-4 py-4 text-sm text-[#5f6f67]">{{ row.module }}</td>
                                <td class="px-4 py-4 text-sm text-[#5f6f67]">{{ row.actor }}<span v-if="row.actorRole"> | {{ row.actorRole }}</span></td>
                                <td class="px-4 py-4 text-sm text-[#5f6f67]">{{ row.occurredAt }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/45 px-4 py-6" @click.self="closeExportModal">
                <section class="w-full max-w-2xl rounded-[28px] border border-[#dbe2de] bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.22)] sm:p-6">
                    <div class="flex items-start justify-between gap-4 border-b border-[#e4ebe7] pb-4">
                        <div>
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Export Analytics</p>
                            <h2 class="mt-1 text-xl font-bold text-[#1a2420]">Download the analytics report</h2>
                            <p class="mt-2 text-sm text-[#697772]">Current range: {{ filters.date_from }} to {{ filters.date_to }}</p>
                        </div>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-[#d7e0db] text-[#66756f] transition hover:bg-[#f5f8f6]" @click="closeExportModal">
                            <span class="text-lg leading-none">&times;</span>
                        </button>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white px-5 py-4 text-sm font-extrabold text-[#003629] transition hover:bg-[#f4f7f5]" @click="exportAnalytics('csv')">
                            Export CSV
                        </button>
                        <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white px-5 py-4 text-sm font-extrabold text-[#003629] transition hover:bg-[#f4f7f5]" @click="exportAnalytics('pdf')">
                            Export PDF
                        </button>
                        <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white px-5 py-4 text-sm font-extrabold text-[#003629] transition hover:bg-[#f4f7f5]" @click="exportAnalytics('xlsx')">
                            Export Excel
                        </button>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-5 py-3 text-sm font-bold text-[#697772] transition hover:bg-[#f4f7f5]" @click="closeExportModal">
                            Cancel
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
