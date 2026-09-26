<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { useAdminDashboardFilters } from '../../../Composables/Admin/Dashboard/useAdminDashboardFilters';

const props = defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
});

const isAdmin = computed(() => Boolean(props.dashboard.permissions?.isAdmin));

const { apply, selectedBarangayName, state } = useAdminDashboardFilters(props.dashboard.filters);
const selectedYearLabel = computed(() => props.dashboard.filters.selectedYear ? String(props.dashboard.filters.selectedYear) : 'All years');

function resetFilters() {
    state.year = '';
    state.barangayId = '';
    apply();
}

const exportModalOpen = ref(false);
const exportSections = reactive({
    summary: true,
    collections: true,
    payment_breakdown: true,
    member_types: false,
    barangay_totals: false,
    recent_farmers: false,
    application_records: false,
    renewal_records: false,
    mortuary_records: false,
});

const numberFormatter = new Intl.NumberFormat('en-PH');
const currencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
});
const dateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
});

function formatNumber(value) {
    return numberFormatter.format(Number(value || 0));
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const normalized = String(value).replace(' ', 'T');
    const date = new Date(normalized);

    return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
}

function openExportModal() {
    exportModalOpen.value = true;
}

function closeExportModal() {
    exportModalOpen.value = false;
}

function exportDashboardReport() {
    const url = new URL(props.dashboard.actions.exportDashboardUrl, window.location.origin);

    if (state.year) {
        url.searchParams.set('year', state.year);
    }

    if (state.barangayId) {
        url.searchParams.set('barangay_id', state.barangayId);
    }

    Object.entries(exportSections).forEach(([key, enabled]) => {
        if (enabled) {
            url.searchParams.append('sections[]', key);
        }
    });

    window.location.assign(url.toString());
    closeExportModal();
}

function initials(name) {
    return String(name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'AN';
}

function iconPath(icon) {
    const paths = {
        agriculture: ['M4 18h16', 'M7 18v-4a3 3 0 0 1 3-3h4v7', 'M10 11V7a2 2 0 0 1 2-2h1', 'M16 11l2-2', 'M18 9l2 2', 'M5 14h2', 'M17 14h2'],
        check: ['M20 6 9 17l-5-5'],
        pending: ['M12 7v5l3 3', 'M21 12a9 9 0 1 1-9-9'],
        inactive: ['M8 8l8 8', 'M16 8l-8 8', 'M21 12a9 9 0 1 1-9-9'],
        map: ['M9 18 3 20V6l6-2 6 2 6-2v14l-6 2-6-2Z', 'M9 4v14', 'M15 6v14'],
        groups: ['M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2', 'M10 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8', 'M20 21v-2a4 4 0 0 0-3-3.87', 'M16 3.13a4 4 0 0 1 0 7.75'],
        office: ['M9 3h6l4 4v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7l4-4Z', 'M9 3v4h6', 'M12 12v4', 'M10 14h4'],
        fee: ['M3 7h18', 'M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z', 'M7 15h6', 'M7 11h10'],
        filter: ['M4 6h16', 'M7 12h10', 'M10 18h4'],
        shield: ['M12 3l7 3v5c0 5-3.5 8-7 10-3.5-2-7-5-7-10V6l7-3Z', 'm9.5 11.5 1.8 1.8 3.7-4.1'],
        trend: ['M4 16c8-1 10-8 16-10', 'M16 6h4v4'],
        chart: ['M5 16V9', 'M12 16V5', 'M19 16v-3'],
        pie: ['M12 3a9 9 0 1 0 9 9h-9Z', 'M12 3v9h9'],
    };

    return paths[icon] || paths.chart;
}

const summaryCards = computed(() => {
    const cards = [
        {
            key: 'totalFarmers',
            label: 'Registered Farmers',
            value: props.dashboard.summary.totalFarmers,
            icon: 'agriculture',
            iconBg: 'bg-gradient-to-br from-[#c9f4db] to-[#a8e8c6]',
            iconColor: 'text-[#006c57]',
            progress: 'bg-[#22c55e]',
            meta: `All registry records${selectedBarangayName.value !== 'All barangays' ? ` in ${selectedBarangayName.value}` : ''}`,
            href: props.dashboard.summaryCardLinks?.totalFarmers ?? null,
        },
        {
            key: 'activeFarmers',
            label: 'Active Farmers',
            value: props.dashboard.summary.activeFarmers,
            icon: 'check',
            iconBg: 'bg-gradient-to-br from-[#e8f7b9] to-[#d4ed91]',
            iconColor: 'text-[#5d8e16]',
            progress: 'bg-[#84cc16]',
            meta: 'Current members with active status',
            href: props.dashboard.summaryCardLinks?.activeFarmers ?? null,
        },
        {
            key: 'pendingApplications',
            label: 'Pending Applications',
            value: props.dashboard.summary.pendingApplications,
            icon: 'pending',
            iconBg: 'bg-gradient-to-br from-[#ffefb8] to-[#ffe08a]',
            iconColor: 'text-[#c76900]',
            progress: 'bg-[#f59e0b]',
            meta: `Queued application reviews for ${selectedYearLabel.value.toLowerCase()}`,
            href: props.dashboard.summaryCardLinks?.pendingApplications ?? null,
        },
        {
            key: 'inactiveFarmers',
            label: 'Inactive Farmers',
            value: props.dashboard.summary.inactiveFarmers,
            icon: 'inactive',
            iconBg: 'bg-gradient-to-br from-[#ffd9dd] to-[#ffc0c7]',
            iconColor: 'text-[#d81f46]',
            progress: 'bg-[#ef4444]',
            meta: 'Registry records marked inactive or deceased',
            href: props.dashboard.summaryCardLinks?.inactiveFarmers ?? null,
        },
    ];

    if (isAdmin.value) {
        cards.push(
            {
                key: 'activeBarangays',
                label: 'Active Barangays',
                value: props.dashboard.summary.activeBarangays,
                icon: 'map',
                iconBg: 'bg-gradient-to-br from-[#dff0ff] to-[#bde0ff]',
                iconColor: 'text-[#0270b8]',
                progress: 'bg-[#3b82f6]',
                meta: selectedBarangayName.value === 'All barangays' ? 'Barangays with active master records' : `Selected barangay: ${selectedBarangayName.value}`,
                href: props.dashboard.summaryCardLinks?.activeBarangays ?? null,
            },
            {
                key: 'activeAssociations',
                label: 'Active Associations',
                value: props.dashboard.summary.activeAssociations,
                icon: 'groups',
                iconBg: 'bg-gradient-to-br from-[#ece3ff] to-[#d9ccff]',
                iconColor: 'text-[#6f3cf0]',
                progress: 'bg-[#8b5cf6]',
                meta: 'Associations currently marked active',
                href: props.dashboard.summaryCardLinks?.activeAssociations ?? null,
            },
            {
                key: 'activeOfficeUsers',
                label: 'Office Users',
                value: props.dashboard.summary.activeOfficeUsers,
                icon: 'office',
                iconBg: 'bg-gradient-to-br from-[#f2f2f2] to-[#e5e5e5]',
                iconColor: 'text-[#384152]',
                progress: 'bg-[#6b7280]',
                meta: 'Active admin and staff accounts',
                href: props.dashboard.summaryCardLinks?.activeOfficeUsers ?? null,
            },
        );
    }

    return cards;
});

const feeScheduleRows = computed(() => props.dashboard.breakdowns.feeSchedules.slice(0, 3).map((row) => ({
    name: row.memberType,
    description: `Membership ${currencyFormatter.format(Number(row.membershipFee || 0))} • Annual ${currencyFormatter.format(Number(row.annualDue || 0))}`,
    amount: currencyFormatter.format(Number(row.membershipFee || 0) + Number(row.annualDue || 0) + Number(row.mortuaryFee || 0)),
    frequency: row.isActive ? 'ACTIVE' : 'INACTIVE',
    secondaryAmount: currencyFormatter.format(Number(row.mortuaryFee || 0)),
})));

const memberTypeRows = computed(() => {
    const labels = ['High', 'Stable', 'High', 'Steady', 'Tracking'];
    const icons = ['agriculture', 'groups', 'map', 'chart', 'pie'];
    const bgColors = ['bg-gradient-to-br from-[#ecfbf3] to-[#dcf5e7]', 'bg-gradient-to-br from-[#fff7e5] to-[#ffefc4]', 'bg-gradient-to-br from-[#ebf7ff] to-[#d4edff]', 'bg-gradient-to-br from-[#eef0ff] to-[#dde0ff]', 'bg-gradient-to-br from-[#f0f5ec] to-[#e2ebd9]'];
    const textColors = ['text-[#06a06e]', 'text-[#d88400]', 'text-[#0c7cc2]', 'text-[#4a56ff]', 'text-[#507d1f]'];

    return props.dashboard.breakdowns.memberTypes.slice(0, 4).map((row, index) => ({
        ...row,
        badge: labels[index % labels.length],
        icon: icons[index % icons.length],
        bgColor: bgColors[index % bgColors.length],
        textColor: textColors[index % textColors.length],
    }));
});

const topBarangays = computed(() => {
    const max = Math.max(...props.dashboard.breakdowns.topBarangays.map((row) => Number(row.total || 0)), 0);

    return props.dashboard.breakdowns.topBarangays.slice(0, 4).map((row, index) => ({
        ...row,
        width: max ? `${Math.max((Number(row.total || 0) / max) * 100, 20)}%` : '0%',
        rank: index + 1,
    }));
});

const recentFarmers = computed(() => props.dashboard.recentFarmers.slice(0, 5).map((row) => ({
    ...row,
    shortDate: formatDate(row.registeredAt),
})));

const adminOperations = computed(() => props.dashboard.adminOperations ?? { queueCards: [], recentPayments: { totalCollectedToday: 0, items: [] }, alerts: [] });
const adminQueueCards = computed(() => adminOperations.value.queueCards ?? []);
const recentPayments = computed(() => adminOperations.value.recentPayments?.items ?? []);
const todayCollectionTotal = computed(() => adminOperations.value.recentPayments?.totalCollectedToday ?? 0);
const collections = computed(() => props.dashboard.collections ?? {
    totals: { overall: 0, applications: 0, renewals: 0, mortuary: 0 },
    counts: { applicationPayments: 0, renewalPayments: 0, mortuaryClaims: 0 },
    breakdown: { membershipFee: 0, annualDue: 0, mortuaryContribution: 0 },
    links: {},
});
const renewalStatistics = computed(() => props.dashboard.renewalStatistics ?? {
    year: new Date().getFullYear(),
    eligibleFarmers: 0,
    renewedFarmers: 0,
    unrenewedFarmers: 0,
    complianceRate: 0,
    previousYearComplianceRate: 0,
    yearOverYearChange: 0,
    pendingRequests: 0,
    rejectedRequests: 0,
    lateRenewals: 0,
    collectionAmount: 0,
    paymentCount: 0,
    yearlyTrend: [],
    barangayPriorities: [],
    links: {},
});
const renewalPriorityBarangays = computed(() => renewalStatistics.value.barangayPriorities ?? []);
const renewalYearlyTrend = computed(() => renewalStatistics.value.yearlyTrend ?? []);
const maximumYearlyRenewals = computed(() => Math.max(
    ...renewalYearlyTrend.value.map((row) => Number(row.renewed || 0)),
    1,
));
const renewalDecisionMessage = computed(() => {
    const unrenewed = Number(renewalStatistics.value.unrenewedFarmers || 0);
    const pending = Number(renewalStatistics.value.pendingRequests || 0);

    if (unrenewed === 0 && Number(renewalStatistics.value.eligibleFarmers || 0) > 0) {
        return 'All eligible farmers have a recorded renewal for this year.';
    }

    if (pending > 0) {
        return `${formatNumber(pending)} submitted renewal request${pending === 1 ? '' : 's'} should be reviewed before outreach begins.`;
    }

    if (unrenewed > 0) {
        return `${formatNumber(unrenewed)} eligible farmer${unrenewed === 1 ? '' : 's'} still need renewal follow-up.`;
    }

    return 'No eligible renewal population is available for the selected filter.';
});

function renewalChangeLabel(row) {
    if (row.direction === 'baseline' || row.renewedChange === null) {
        return 'Baseline';
    }

    const change = Number(row.renewedChange || 0);

    if (change > 0) {
        return `Increased by ${formatNumber(change)}`;
    }

    if (change < 0) {
        return `Decreased by ${formatNumber(Math.abs(change))}`;
    }

    return 'No change';
}

function renewalChangeTone(direction) {
    return {
        increased: 'bg-[#dcfce7] text-[#15803d]',
        decreased: 'bg-[#fef2f2] text-[#dc2626]',
        unchanged: 'bg-[#f1f5f9] text-[#64748b]',
        baseline: 'bg-[#eff6ff] text-[#2563eb]',
    }[direction] ?? 'bg-[#f1f5f9] text-[#64748b]';
}

function operationTone(tone) {
    const tones = {
        emerald: {
            badge: 'bg-[#dcfce7] text-[#15803d]',
            value: 'text-[#166534]',
            border: 'border-[#bbf7d0]',
            bg: 'hover:bg-[#f0fdf4]',
        },
        lime: {
            badge: 'bg-[#ecfccb] text-[#4d7c0f]',
            value: 'text-[#3f6212]',
            border: 'border-[#d9f99d]',
            bg: 'hover:bg-[#f7fee7]',
        },
        sky: {
            badge: 'bg-[#e0f2fe] text-[#0369a1]',
            value: 'text-[#075985]',
            border: 'border-[#bae6fd]',
            bg: 'hover:bg-[#f0f9ff]',
        },
        rose: {
            badge: 'bg-[#ffe4e6] text-[#be123c]',
            value: 'text-[#9f1239]',
            border: 'border-[#fecdd3]',
            bg: 'hover:bg-[#fff1f2]',
        },
    };

    return tones[tone] ?? tones.emerald;
}

const registrationTrend = computed(() => props.dashboard.breakdowns.registrationTrend ?? {
    series: [],
    currentYearTotal: 0,
    previousYearTotal: 0,
    yearOverYearPercent: 0,
});

const linePoints = computed(() => {
    const values = (registrationTrend.value.series || []).map((row) => Number(row.total || 0));

    if (!values.length) {
        return '0,84 20,84 40,84 60,84 80,84 100,84';
    }

    const max = Math.max(...values, 1);

    return values
        .map((value, index) => {
            const x = values.length === 1 ? 0 : index * (100 / (values.length - 1));
            const y = 84 - ((value / max) * 54);
            return `${x},${y.toFixed(2)}`;
        })
        .join(' ');
});
</script>

<template>
    <div class="dashboard-compact mx-auto w-full max-w-[1536px] space-y-4 pb-8">
        <!-- Hero header -->
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-6 py-5 text-white shadow-lg shadow-[#003629]/15">
            <div class="pointer-events-none absolute -right-12 -top-12 h-48 w-48 rounded-full bg-white/[0.04]"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-16 h-56 w-56 rounded-full bg-white/[0.03]"></div>
            <div class="pointer-events-none absolute right-32 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

            <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                        <svg viewBox="0 0 24 24" class="h-6 w-6 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
                    </div>
                    <div>
                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Registry Overview</p>
                        <h2 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Dashboard for {{ selectedYearLabel }}</h2>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button type="button" class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]" @click="openExportModal">
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
                        Generate Report
                    </button>

                    <form class="flex items-center gap-2 rounded-xl border border-white/[0.12] bg-white/[0.06] p-2 backdrop-blur-sm" @submit.prevent="apply">
                        <select v-model="state.year" class="h-8 rounded-lg border-0 bg-white/10 px-3 text-xs font-medium text-white outline-none ring-1 ring-white/[0.12] transition-colors focus:ring-white/30">
                            <option value="" class="text-[#0f172a]">All years</option>
                            <option v-for="year in dashboard.filters.availableYears" :key="year" :value="String(year)" class="text-[#0f172a]">{{ year }}</option>
                        </select>
                        <select v-model="state.barangayId" class="h-8 rounded-lg border-0 bg-white/10 px-3 text-xs font-medium text-white outline-none ring-1 ring-white/[0.12] transition-colors focus:ring-white/30">
                            <option value="" class="text-[#0f172a]">All Barangays</option>
                            <option v-for="barangay in dashboard.filters.barangays" :key="barangay.id" :value="String(barangay.id)" class="text-[#0f172a]">
                                {{ barangay.name }}
                            </option>
                        </select>
                        <button type="submit" class="flex h-8 w-9 items-center justify-center rounded-lg bg-[#7ddfb8] text-[#003629] transition-all duration-200 hover:bg-[#a0e8cc] active:scale-95">
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Active filters -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Filters:</span>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2.5 py-1.5 text-xs font-medium text-[#334155]">
                <svg viewBox="0 0 16 16" class="h-3.5 w-3.5 text-[#014d3c]" fill="currentColor"><path d="M5.75 7.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5ZM5 3.75a.75.75 0 0 0 1.5 0V2.75a.75.75 0 0 0-1.5 0v1ZM5.75 12a.75.75 0 0 0-.75.75v1.5a.75.75 0 0 0 1.5 0v-1.5a.75.75 0 0 0-.75-.75ZM10.25 7.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5ZM9.5 3.75a.75.75 0 0 0 1.5 0V2.75a.75.75 0 0 0-1.5 0v1ZM10.25 12a.75.75 0 0 0-.75.75v1.5a.75.75 0 0 0 1.5 0v-1.5a.75.75 0 0 0-.75-.75Z"/></svg>
                {{ selectedYearLabel }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2.5 py-1.5 text-xs font-medium text-[#334155]">
                <svg viewBox="0 0 16 16" class="h-3.5 w-3.5 text-[#014d3c]" fill="currentColor"><path fill-rule="evenodd" d="M8 1a4.5 4.5 0 0 0-4.5 4.5c0 1.657.895 3.592 2.072 5.187.59.8 1.214 1.477 1.716 1.948.252.237.457.403.605.503.073.05.119.074.14.084.022-.01.068-.035.141-.084a8.898 8.898 0 0 0 .605-.503c.502-.47 1.126-1.148 1.716-1.948C11.605 9.092 12.5 7.157 12.5 5.5A4.5 4.5 0 0 0 8 1ZM8 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" clip-rule="evenodd"/></svg>
                {{ selectedBarangayName }}
            </span>
            <button type="button" class="ml-auto inline-flex items-center gap-1 text-xs font-semibold text-[#014d3c] transition-colors hover:text-[#01362a] hover:underline" @click="resetFilters">
                <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/></svg>
                Clear Filters
            </button>
        </div>

        <!-- Summary cards -->
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            <component
                :is="card.href ? Link : 'article'"
                v-for="card in summaryCards"
                :key="card.key"
                :href="card.href || undefined"
                class="group relative overflow-hidden rounded-xl border border-[#e2e8f0] bg-white p-3.5 shadow-sm transition-all duration-200"
                :class="card.href ? 'cursor-pointer hover:-translate-y-0.5 hover:border-[#cbd5e1] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#014d3c]/20' : ''"
            >
                <div class="pointer-events-none absolute -right-3 -top-3 h-16 w-16 rounded-full opacity-30" :class="card.iconBg"></div>
                <div class="flex items-start justify-between gap-3">
                    <div :class="['flex h-9 w-9 items-center justify-center rounded-lg', card.iconBg]">
                        <svg viewBox="0 0 24 24" :class="['h-4 w-4', card.iconColor]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path v-for="path in iconPath(card.icon)" :key="path" :d="path" />
                        </svg>
                    </div>
                </div>
                <p class="mt-2.5 min-h-8 text-[0.68rem] font-medium leading-4 text-[#64748b]">{{ card.label }}</p>
                <p class="mt-1 text-xl font-bold tracking-tight text-[#0f172a]">{{ formatNumber(card.value) }}</p>
                <div class="mt-2.5 h-1 overflow-hidden rounded-full bg-[#f1f5f9]">
                    <div :class="['h-1 rounded-full transition-all duration-500', card.progress]" :style="{ width: card.value > 0 ? '100%' : '8%', opacity: card.value > 0 ? 1 : 0.3 }"></div>
                </div>
            </component>
        </section>

        <!-- Renewal performance + Priority barangays -->
        <section class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                <div class="border-b border-[#f1f5f9] px-5 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Renewal Performance for CY {{ renewalStatistics.year }}</h3>
                            <p class="mt-1 max-w-2xl text-xs leading-5 text-[#64748b]">{{ renewalDecisionMessage }}</p>
                        </div>
                        <Link :href="renewalStatistics.links.records || dashboard.actions.viewRenewalsUrl" class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg bg-[#014d3c] px-3 text-[0.68rem] font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97]">
                            View Renewals
                        </Link>
                    </div>
                </div>

                <div class="grid gap-px bg-[#f1f5f9] sm:grid-cols-3">
                    <div class="bg-white px-5 py-4">
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Compliance</p>
                        <p class="mt-2 text-2xl font-bold text-[#15803d]">{{ renewalStatistics.complianceRate }}%</p>
                        <p class="mt-1.5 text-[0.68rem] text-[#94a3b8]">{{ renewalStatistics.yearOverYearChange >= 0 ? '+' : '' }}{{ renewalStatistics.yearOverYearChange }} pts vs previous</p>
                    </div>
                    <div class="bg-white px-5 py-4">
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Renewed</p>
                        <p class="mt-2 text-2xl font-bold text-[#0f172a]">{{ formatNumber(renewalStatistics.renewedFarmers) }}</p>
                        <p class="mt-1.5 text-[0.68rem] text-[#94a3b8]">of {{ formatNumber(renewalStatistics.eligibleFarmers) }} eligible</p>
                    </div>
                    <Link :href="renewalStatistics.links.queue" class="bg-white px-5 py-4 transition-colors hover:bg-[#fffbeb]">
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#b45309]">Still Unrenewed</p>
                        <p class="mt-2 text-2xl font-bold text-[#d97706]">{{ formatNumber(renewalStatistics.unrenewedFarmers) }}</p>
                        <p class="mt-1.5 text-[0.68rem] text-[#94a3b8]">Need follow-up</p>
                    </Link>
                </div>
            </article>

            <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                <div class="border-b border-[#f1f5f9] px-5 py-3.5">
                    <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Barangays Needing Follow-up</h3>
                </div>

                <div v-if="renewalPriorityBarangays.length" class="divide-y divide-[#f1f5f9]">
                    <Link v-for="row in renewalPriorityBarangays" :key="row.id" :href="row.href" class="block px-5 py-3.5 transition-colors hover:bg-[#f8fafc]">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-[#0f172a]">{{ row.barangay }}</p>
                                <p class="mt-1 text-[0.68rem] text-[#94a3b8]">{{ formatNumber(row.renewed) }} of {{ formatNumber(row.eligible) }} renewed</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-[#d97706]">{{ row.complianceRate }}%</p>
                                <p class="mt-1 text-[0.68rem] font-semibold text-[#ea580c]">{{ formatNumber(row.unrenewed) }} pending</p>
                            </div>
                        </div>
                        <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-[#f1f5f9]">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#86efac] to-[#22c55e] transition-all" :style="{ width: `${Math.min(Math.max(Number(row.complianceRate || 0), 0), 100)}%` }"></div>
                        </div>
                    </Link>
                </div>
                <div v-else class="px-5 py-12 text-center">
                    <div class="mx-auto flex max-w-xs flex-col items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f0fdf4]">
                            <svg viewBox="0 0 24 24" class="h-6 w-6 text-[#86efac]" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </div>
                        <p class="mt-2 text-xs text-[#94a3b8]">No barangay renewal workload available.</p>
                    </div>
                </div>
            </article>
        </section>

        <!-- Renewal yearly trend -->
        <section class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-[#f1f5f9] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Renewal Trend by Year</h3>
                <span class="inline-flex w-fit items-center rounded-lg bg-[#f0fdf4] px-2.5 py-1 text-[0.68rem] font-semibold text-[#15803d]">Through CY {{ renewalStatistics.year }}</span>
            </div>

            <div v-if="renewalYearlyTrend.length" class="overflow-x-auto">
                <table class="min-w-[820px] w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[#e2e8f0] bg-gradient-to-b from-[#f8fafc] to-[#f1f5f9] text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                            <th class="px-5 py-3">Year</th>
                            <th class="px-5 py-3">Volume</th>
                            <th class="px-5 py-3 text-center">Eligible</th>
                            <th class="px-5 py-3 text-center">Renewed</th>
                            <th class="px-5 py-3 text-center">Unrenewed</th>
                            <th class="px-5 py-3 text-center">Compliance</th>
                            <th class="px-5 py-3 text-right">Change</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f1f5f9]">
                        <tr v-for="row in renewalYearlyTrend" :key="row.year" class="transition-colors hover:bg-[#f8fafc]">
                            <td class="px-5 py-3">
                                <span class="text-sm font-bold text-[#0f172a]">{{ row.year }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 min-w-[120px] flex-1 overflow-hidden rounded-full bg-[#f1f5f9]">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="row.direction === 'decreased' ? 'bg-gradient-to-r from-[#fca5a5] to-[#ef4444]' : 'bg-gradient-to-r from-[#86efac] to-[#22c55e]'"
                                            :style="{ width: `${Math.max((Number(row.renewed || 0) / maximumYearlyRenewals) * 100, row.renewed > 0 ? 8 : 0)}%` }"
                                        ></div>
                                    </div>
                                    <span class="w-10 text-right text-xs font-bold text-[#334155]">{{ formatNumber(row.renewed) }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center font-semibold text-[#475569]">{{ formatNumber(row.eligible) }}</td>
                            <td class="px-5 py-3 text-center font-bold text-[#15803d]">{{ formatNumber(row.renewed) }}</td>
                            <td class="px-5 py-3 text-center font-bold text-[#d97706]">{{ formatNumber(row.unrenewed) }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="font-bold text-[#0f172a]">{{ row.complianceRate }}%</span>
                                <span v-if="row.complianceChange !== null" class="ml-1 text-[0.65rem]" :class="row.complianceChange >= 0 ? 'text-[#15803d]' : 'text-[#dc2626]'">
                                    ({{ row.complianceChange >= 0 ? '+' : '' }}{{ row.complianceChange }} pts)
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span :class="['inline-flex rounded-lg px-2.5 py-1 text-[0.62rem] font-bold', renewalChangeTone(row.direction)]">
                                    {{ renewalChangeLabel(row) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="px-5 py-12 text-center text-xs text-[#94a3b8]">
                No annual renewal history is available.
            </div>
        </section>

        <!-- Admin operations + Collections today -->
        <section v-if="isAdmin" class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                <div class="border-b border-[#f1f5f9] px-5 py-3.5">
                    <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Pending Queues</h3>
                </div>
                <div class="grid grid-cols-2 gap-px bg-[#f1f5f9] xl:grid-cols-4">
                    <Link
                        v-for="item in adminQueueCards"
                        :key="item.key"
                        :href="item.href"
                        :class="['bg-white p-4 transition-colors', operationTone(item.tone).bg]"
                    >
                        <span :class="['inline-flex rounded-lg px-2 py-1 text-[0.55rem] font-bold uppercase tracking-[0.05em]', operationTone(item.tone).badge]">
                            {{ item.label }}
                        </span>
                        <p :class="['mt-2.5 text-xl font-bold leading-none', operationTone(item.tone).value]">
                            {{ formatNumber(item.value) }}
                        </p>
                    </Link>
                </div>
            </article>

            <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
                    <div>
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Collections Today</p>
                        <h3 class="mt-1 text-lg font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(todayCollectionTotal || 0)) }}</h3>
                    </div>
                    <Link :href="adminOperations.recentPayments?.href" class="text-[0.68rem] font-semibold text-[#014d3c] transition-colors hover:underline">
                        View Analytics
                    </Link>
                </div>

                <div v-if="recentPayments.length" class="divide-y divide-[#f1f5f9]">
                    <div v-for="payment in recentPayments" :key="payment.id" class="flex items-start justify-between gap-4 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-[#0f172a]">{{ payment.farmerName }}</p>
                            <p class="mt-0.5 text-[0.65rem] text-[#94a3b8]">{{ payment.farmerCode || 'No code' }} · {{ payment.paidAt }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-bold text-[#15803d]">{{ currencyFormatter.format(Number(payment.amount || 0)) }}</span>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center text-xs text-[#94a3b8]">
                    No payments collected yet today.
                </div>
            </article>
        </section>

        <!-- Collections + Payment breakdown -->
        <section class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-4">
                    <div>
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Filtered Collections</p>
                        <h3 class="mt-1 text-lg font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(collections.totals.overall || 0)) }}</h3>
                    </div>
                    <Link :href="collections.links.analytics" class="text-[0.68rem] font-semibold text-[#014d3c] transition-colors hover:underline">
                        View Analytics
                    </Link>
                </div>

                <div class="grid gap-px bg-[#f1f5f9] md:grid-cols-3">
                    <Link :href="collections.links.applications" class="bg-white px-5 py-4 transition-colors hover:bg-[#f8fafc]">
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Applications</p>
                        <p class="mt-2 text-lg font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(collections.totals.applications || 0)) }}</p>
                        <p class="mt-1 text-[0.68rem] text-[#94a3b8]">{{ formatNumber(collections.counts.applicationPayments) }} payment records</p>
                    </Link>
                    <Link :href="collections.links.renewals" class="bg-white px-5 py-4 transition-colors hover:bg-[#f8fafc]">
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Renewals</p>
                        <p class="mt-2 text-lg font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(collections.totals.renewals || 0)) }}</p>
                        <p class="mt-1 text-[0.68rem] text-[#94a3b8]">{{ formatNumber(collections.counts.renewalPayments) }} payment records</p>
                    </Link>
                    <Link :href="collections.links.mortuary" class="bg-white px-5 py-4 transition-colors hover:bg-[#f8fafc]">
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Mortuary</p>
                        <p class="mt-2 text-lg font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(collections.totals.mortuary || 0)) }}</p>
                        <p class="mt-1 text-[0.68rem] text-[#94a3b8]">{{ formatNumber(collections.counts.mortuaryClaims) }} claim records</p>
                    </Link>
                </div>
            </article>

            <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                <div class="border-b border-[#f1f5f9] px-5 py-4">
                    <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Payment Breakdown</h3>
                </div>
                <div class="divide-y divide-[#f1f5f9]">
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-xs font-semibold text-[#475569]">Membership Fees</span>
                        <span class="text-xs font-bold text-[#15803d]">{{ currencyFormatter.format(Number(collections.breakdown.membershipFee || 0)) }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-xs font-semibold text-[#475569]">Annual Due</span>
                        <span class="text-xs font-bold text-[#15803d]">{{ currencyFormatter.format(Number(collections.breakdown.annualDue || 0)) }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-xs font-semibold text-[#475569]">Mortuary Contribution</span>
                        <span class="text-xs font-bold text-[#15803d]">{{ currencyFormatter.format(Number(collections.breakdown.mortuaryContribution || 0)) }}</span>
                    </div>
                </div>
            </article>
        </section>

        <!-- Registration trend -->
        <section class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <div class="h-5 w-1.5 rounded-full bg-gradient-to-b from-[#014d3c] to-[#22c55e]"></div>
                    <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Farmer Registration Activity</h3>
                </div>
                <span class="text-[0.68rem] font-medium text-[#64748b]">Monthly ({{ selectedYearLabel }})</span>
            </div>

            <div class="relative px-5 pb-3 pt-4">
                <div class="h-36">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
                        <defs>
                            <linearGradient id="dashboard-trend-fill" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#014d3c" stop-opacity="0.15" />
                                <stop offset="100%" stop-color="#014d3c" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <polyline :points="linePoints" fill="none" stroke="#014d3c" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        <path :d="`M${linePoints} L100,100 L0,100 Z`" fill="url(#dashboard-trend-fill)" />
                    </svg>
                </div>
                <div class="flex justify-between px-1 text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#94a3b8]">
                    <span v-for="row in registrationTrend.series.filter((_, index) => [0, 2, 5, 8, 11].includes(index))" :key="row.month">
                        {{ row.label.toUpperCase() }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-4 border-t border-[#f1f5f9] px-5 py-3">
                <div class="flex items-center gap-2 text-[0.68rem] font-medium text-[#334155]">
                    <span class="h-2.5 w-2.5 rounded-full bg-[#014d3c]"></span>
                    New Registrations
                </div>
                <span class="ml-auto text-[0.68rem] font-bold" :class="registrationTrend.yearOverYearPercent >= 0 ? 'text-[#15803d]' : 'text-[#dc2626]'">
                    {{ registrationTrend.yearOverYearPercent >= 0 ? '+' : '' }}{{ registrationTrend.yearOverYearPercent }}% vs LY
                </span>
            </div>
        </section>

        <!-- Member types + Recent farmers + Top barangays + Fee schedules -->
        <section class="grid grid-cols-1 items-start gap-4 xl:grid-cols-12">
            <div class="space-y-4 xl:col-span-8">
                <!-- Member types -->
                <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="h-5 w-1.5 rounded-full bg-gradient-to-b from-[#84cc16] to-[#22c55e]"></div>
                            <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Farmers by Member Type</h3>
                        </div>
                    </div>

                    <div v-if="memberTypeRows.length" class="grid gap-px bg-[#f1f5f9] sm:grid-cols-2">
                        <div v-for="row in memberTypeRows" :key="row.label" class="flex items-center gap-3 bg-white px-5 py-3.5">
                            <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', row.bgColor]">
                                <svg viewBox="0 0 24 24" :class="['h-4 w-4', row.textColor]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path v-for="path in iconPath(row.icon)" :key="path" :d="path" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-bold text-[#0f172a]">{{ row.label }}</p>
                                <p class="text-[0.68rem] text-[#94a3b8]">{{ formatNumber(row.total) }} registered</p>
                            </div>
                            <span class="rounded-lg bg-[#f1f5f9] px-2 py-1 text-[0.6rem] font-bold text-[#475569]">{{ row.badge }}</span>
                        </div>
                    </div>
                    <div v-else class="px-5 py-12 text-center text-xs text-[#94a3b8]">
                        No member type distribution available.
                    </div>
                </article>

                <!-- Recent farmers -->
                <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
                        <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Recent Farmer Registry Entries</h3>
                        <Link :href="dashboard.actions.viewFarmersUrl" class="text-[0.68rem] font-semibold text-[#014d3c] transition-colors hover:underline">
                            View All
                        </Link>
                    </div>

                    <div v-if="recentFarmers.length" class="overflow-x-auto">
                        <table class="min-w-[680px] w-full table-fixed text-left text-xs">
                            <thead>
                                <tr class="border-b border-[#e2e8f0] bg-gradient-to-b from-[#f8fafc] to-[#f1f5f9] text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                                    <th class="w-[46%] px-5 py-2.5">Farmer Name</th>
                                    <th class="w-[18%] px-5 py-2.5">Barangay</th>
                                    <th class="w-[20%] px-5 py-2.5">Status</th>
                                    <th class="w-[16%] px-5 py-2.5 text-right">Date Applied</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f1f5f9]">
                                <tr v-for="farmer in recentFarmers" :key="farmer.id" class="transition-colors hover:bg-[#f8fafc]">
                                    <td class="px-5 py-3">
                                        <div class="flex min-w-0 items-center gap-2.5">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[0.6rem] font-bold text-[#0f6b45]">
                                                {{ initials(farmer.fullName) }}
                                            </span>
                                            <span class="truncate text-xs font-semibold text-[#0f172a]" :title="farmer.fullName">{{ farmer.fullName }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-[#64748b]">{{ farmer.barangay || '-' }}</td>
                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2 py-1 text-[0.62rem] font-bold"
                                            :class="{
                                                'bg-[#dcfce7] text-[#15803d]': farmer.farmerStatus === 'active',
                                                'bg-[#fef3c7] text-[#b45309]': farmer.membershipStatus === 'pending_application' || farmer.membershipStatus === 'pending_documents',
                                                'bg-[#dbeafe] text-[#2563eb]': farmer.membershipStatus === 'pending_verification' || farmer.membershipStatus === 'pending_payment',
                                                'bg-[#fef2f2] text-[#dc2626]': farmer.farmerStatus === 'inactive' || farmer.farmerStatus === 'deceased',
                                            }"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="{
                                                'bg-[#22c55e]': farmer.farmerStatus === 'active',
                                                'bg-[#f59e0b]': farmer.membershipStatus === 'pending_application' || farmer.membershipStatus === 'pending_documents',
                                                'bg-[#3b82f6]': farmer.membershipStatus === 'pending_verification' || farmer.membershipStatus === 'pending_payment',
                                                'bg-[#ef4444]': farmer.farmerStatus === 'inactive' || farmer.farmerStatus === 'deceased',
                                            }"></span>
                                            {{ farmer.membershipStatusLabel || 'Not set' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-[#64748b]">{{ farmer.shortDate }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-5 py-12 text-center text-xs text-[#94a3b8]">
                        No recent farmer records found for this filter.
                    </div>
                </article>
            </div>

            <div class="space-y-4 xl:col-span-4">
                <!-- Top barangays -->
                <article v-if="isAdmin" class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                    <div class="border-b border-[#f1f5f9] px-5 py-3.5">
                        <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Top Barangays</h3>
                    </div>
                    <div v-if="topBarangays.length" class="divide-y divide-[#f1f5f9]">
                        <div v-for="row in topBarangays" :key="row.label" class="px-5 py-3.5">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-semibold text-[#0f172a]">{{ row.label }}</span>
                                <span class="text-xs font-bold text-[#014d3c]">{{ formatNumber(row.total) }}</span>
                            </div>
                            <div class="relative h-5 overflow-hidden rounded-lg bg-[#f1f5f9]">
                                <div class="h-full rounded-lg bg-gradient-to-r from-[#bef2d6] to-[#86cfac] transition-all" :style="{ width: row.width }"></div>
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[0.55rem] font-bold uppercase tracking-wider text-[#475569]">Rank #{{ row.rank }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-5 py-12 text-center text-xs text-[#94a3b8]">
                        No barangay totals available.
                    </div>
                </article>

                <!-- Fee schedules -->
                <article v-if="isAdmin" class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
                        <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Fee Schedules</h3>
                        <span class="rounded-lg bg-[#014d3c] px-2.5 py-1 text-[0.62rem] font-bold text-white">{{ selectedYearLabel }}</span>
                    </div>

                    <div v-if="feeScheduleRows.length" class="divide-y divide-[#f1f5f9]">
                        <div v-for="row in feeScheduleRows" :key="row.name" class="px-5 py-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-[#0f172a]">{{ row.name }}</p>
                                    <p class="mt-0.5 text-[0.68rem] leading-4 text-[#94a3b8]">{{ row.description }}</p>
                                    <p class="text-[0.62rem] text-[#94a3b8]">Mortuary {{ row.secondaryAmount }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-xs font-bold text-[#15803d]">{{ row.amount }}</p>
                                    <p class="mt-0.5 text-[0.55rem] font-bold uppercase tracking-wider text-[#94a3b8]">{{ row.frequency }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-[#f8fafc] px-5 py-3">
                            <Link :href="dashboard.actions.manageFeeSchedulesUrl" class="block w-full rounded-lg border border-[#e2e8f0] bg-white py-2 text-center text-[0.68rem] font-bold text-[#014d3c] transition-all duration-200 hover:border-[#cbd5e1] hover:bg-[#f8fafc]">
                                Manage Schedules
                            </Link>
                        </div>
                    </div>
                    <div v-else class="px-5 py-12 text-center text-xs text-[#94a3b8]">
                        No fee schedules exist for this year.
                    </div>
                </article>
            </div>
        </section>

        <!-- FAB -->
        <Link
            :href="dashboard.actions.createMembershipApplicationUrl"
            class="fixed bottom-6 right-6 z-50 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#014d3c] to-[#003629] text-white shadow-lg shadow-[#003629]/30 transition-all duration-200 hover:scale-110 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-[#014d3c]/20"
        >
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14" />
                <path d="M5 12h14" />
            </svg>
        </Link>

        <!-- Export modal -->
        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f172a]/50 px-4 py-5 backdrop-blur-sm" @click.self="closeExportModal">
            <section class="w-full max-w-2xl overflow-hidden rounded-2xl border border-[#e2e8f0] bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-[#f1f5f9] px-6 py-4">
                    <div>
                        <h2 class="text-sm font-bold text-[#0f172a]">Dashboard Export</h2>
                        <p class="mt-1 text-xs text-[#94a3b8]">Select sections for {{ selectedYearLabel }} and {{ selectedBarangayName }}.</p>
                    </div>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#94a3b8] transition-colors hover:bg-[#f1f5f9] hover:text-[#64748b]" aria-label="Close export dialog" @click="closeExportModal">
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    </button>
                </div>

                <div class="grid gap-2 px-6 py-4 sm:grid-cols-2">
                    <label v-for="(enabled, key) in exportSections" :key="key" class="flex min-h-10 cursor-pointer items-center gap-2.5 rounded-xl border border-[#e2e8f0] bg-[#f8fafc] px-3.5 py-2.5 text-xs text-[#0f172a] transition-all hover:border-[#cbd5e1] hover:bg-[#f1f5f9]">
                        <input v-model="exportSections[key]" type="checkbox" class="h-3.5 w-3.5 rounded border-slate-300 text-[#014d3c] focus:ring-[#014d3c]">
                        <span class="font-semibold leading-4">
                            {{
                                key === 'summary' ? 'Summary cards'
                                    : key === 'collections' ? 'Collections totals'
                                    : key === 'payment_breakdown' ? 'Payment breakdown'
                                    : key === 'member_types' ? 'Member types'
                                    : key === 'barangay_totals' ? 'All barangay farmer totals'
                                    : key === 'recent_farmers' ? 'Recent farmers'
                                    : key === 'application_records' ? 'Application records list'
                                    : key === 'renewal_records' ? 'Renewal records list'
                                    : 'Mortuary records list'
                            }}
                        </span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 border-t border-[#f1f5f9] bg-[#f8fafc] px-6 py-3.5">
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-[#e2e8f0] bg-white px-4 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:bg-[#f1f5f9]" @click="closeExportModal">
                        Cancel
                    </button>
                    <button type="button" class="inline-flex h-9 items-center gap-1.5 justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97]" @click="exportDashboardReport">
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
                        Export CSV
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>
