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
    membership_status: true,
    member_types: false,
    top_barangays: false,
    top_associations: false,
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
            iconBg: 'bg-[#c9f4db]',
            iconColor: 'text-[#006c57]',
            progress: 'bg-[#c7f0db]',
            meta: `All registry records${selectedBarangayName.value !== 'All barangays' ? ` in ${selectedBarangayName.value}` : ''}`,
            href: props.dashboard.summaryCardLinks?.totalFarmers ?? null,
        },
        {
            key: 'activeFarmers',
            label: 'Active Farmers',
            value: props.dashboard.summary.activeFarmers,
            icon: 'check',
            iconBg: 'bg-[#e8f7b9]',
            iconColor: 'text-[#5d8e16]',
            progress: 'bg-[#dff2a5]',
            meta: 'Current members with active status',
            href: props.dashboard.summaryCardLinks?.activeFarmers ?? null,
        },
        {
            key: 'pendingApplications',
            label: 'Pending Applications',
            value: props.dashboard.summary.pendingApplications,
            icon: 'pending',
            iconBg: 'bg-[#ffefb8]',
            iconColor: 'text-[#c76900]',
            progress: 'bg-[#ffe59a]',
            meta: `Queued application reviews for ${selectedYearLabel.value.toLowerCase()}`,
            href: props.dashboard.summaryCardLinks?.pendingApplications ?? null,
        },
        {
            key: 'inactiveFarmers',
            label: 'Inactive Farmers',
            value: props.dashboard.summary.inactiveFarmers,
            icon: 'inactive',
            iconBg: 'bg-[#ffd9dd]',
            iconColor: 'text-[#d81f46]',
            progress: 'bg-[#ffd7dc]',
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
                iconBg: 'bg-[#dff0ff]',
                iconColor: 'text-[#0270b8]',
                progress: 'bg-[#d4ebff]',
                meta: selectedBarangayName.value === 'All barangays' ? 'Barangays with active master records' : `Selected barangay: ${selectedBarangayName.value}`,
                href: props.dashboard.summaryCardLinks?.activeBarangays ?? null,
            },
            {
                key: 'activeAssociations',
                label: 'Active Associations',
                value: props.dashboard.summary.activeAssociations,
                icon: 'groups',
                iconBg: 'bg-[#ece3ff]',
                iconColor: 'text-[#6f3cf0]',
                progress: 'bg-[#e3d8ff]',
                meta: 'Associations currently marked active',
                href: props.dashboard.summaryCardLinks?.activeAssociations ?? null,
            },
            {
                key: 'activeOfficeUsers',
                label: 'Office Users',
                value: props.dashboard.summary.activeOfficeUsers,
                icon: 'office',
                iconBg: 'bg-[#f2f2f2]',
                iconColor: 'text-[#384152]',
                progress: 'bg-[#ececec]',
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
    const bgColors = ['bg-[#ecfbf3]', 'bg-[#fff7e5]', 'bg-[#ebf7ff]', 'bg-[#eef0ff]', 'bg-[#f0f5ec]'];
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
        increased: 'bg-[#e2f6e9] text-[#08724f]',
        decreased: 'bg-[#ffe5e7] text-[#b5364e]',
        unchanged: 'bg-[#eef1ef] text-[#5f6d67]',
        baseline: 'bg-[#e8eef6] text-[#536a82]',
    }[direction] ?? 'bg-[#eef1ef] text-[#5f6d67]';
}

function operationTone(tone) {
    const tones = {
        emerald: {
            badge: 'bg-[#dff6ea] text-[#0c7a58]',
            value: 'text-[#0b5c46]',
            border: 'border-[#d7ebe1]',
        },
        lime: {
            badge: 'bg-[#eef7d6] text-[#5f8418]',
            value: 'text-[#486814]',
            border: 'border-[#e0e9ca]',
        },
        sky: {
            badge: 'bg-[#e3f2ff] text-[#0c7cc2]',
            value: 'text-[#0b649e]',
            border: 'border-[#d7e8f4]',
        },
        rose: {
            badge: 'bg-[#ffe4e7] text-[#cf3657]',
            value: 'text-[#ae1d44]',
            border: 'border-[#edd8dc]',
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
    <div class="dashboard-compact mx-auto w-full max-w-[1536px] space-y-4 pb-6">
        <section class="overflow-hidden rounded-[10px] bg-[#00513f] px-5 py-4 text-white sm:px-7">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex max-w-3xl flex-wrap items-center gap-4">
                    <h2 class="text-[1.65rem] font-semibold tracking-tight text-white">Registry Overview for {{ selectedYearLabel }}</h2>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="inline-flex h-9 items-center justify-center rounded-md bg-white px-4 text-xs font-semibold text-[#003e32] transition hover:bg-[#f3f7f5]" @click="openExportModal">
                            Generate Report
                        </button>
                    </div>
                </div>

                <form class="grid w-full max-w-[480px] gap-2 rounded-lg border border-white/15 bg-[#004534] p-2.5 md:grid-cols-[1fr_1.2fr_auto]" @submit.prevent="apply">
                    <label class="space-y-1">
                        <span class="ml-1 block text-xs font-semibold text-white">Select Year</span>
                        <select v-model="state.year" class="w-full rounded-md border-0 bg-white/10 px-3 py-2 text-xs font-medium text-white outline-none ring-1 ring-white/15">
                            <option value="" class="text-stone-900">All years</option>
                            <option v-for="year in dashboard.filters.availableYears" :key="year" :value="String(year)" class="text-stone-900">{{ year }}</option>
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="ml-1 block text-xs font-semibold text-white">Barangay</span>
                        <select v-model="state.barangayId" class="w-full rounded-md border-0 bg-white/10 px-3 py-2 text-xs font-medium text-white outline-none ring-1 ring-white/15">
                            <option value="" class="text-stone-900">All Barangays</option>
                            <option v-for="barangay in dashboard.filters.barangays" :key="barangay.id" :value="String(barangay.id)" class="text-stone-900">
                                {{ barangay.name }}
                            </option>
                        </select>
                    </label>
                    <button type="submit" class="flex h-9 w-10 items-center justify-center self-end rounded-md bg-[#718700] text-white transition hover:bg-[#829900]">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 6h16" />
                            <path d="M7 12h10" />
                            <path d="M10 18h4" />
                        </svg>
                    </button>
                </form>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-[#344654]">Active Filters:</span>
            <span class="inline-flex items-center gap-1.5 rounded-md border border-[#d9dfdc] bg-[#eef1ef] px-2.5 py-1 text-xs font-normal text-[#102533]">
                <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#0f5b46]" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="5" width="18" height="16" rx="2" />
                    <path d="M16 3v4M8 3v4M3 11h18" />
                </svg>
                {{ selectedYearLabel }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-md border border-[#d9dfdc] bg-[#eef1ef] px-2.5 py-1 text-xs font-normal text-[#102533]">
                <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#0f5b46]" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z" />
                    <circle cx="12" cy="10" r="2.5" />
                </svg>
                {{ selectedBarangayName }}
            </span>
            <button type="button" class="ml-auto text-xs font-medium text-[#6c8900] hover:underline" @click="resetFilters">
                Clear All Filters
            </button>
        </div>

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            <component
                :is="card.href ? Link : 'article'"
                v-for="card in summaryCards"
                :key="card.key"
                :href="card.href || undefined"
                class="rounded-lg border border-[#e4e9e6] bg-white p-3 transition"
                :class="card.href ? 'group block hover:-translate-y-0.5 hover:border-[#cdd8d3] hover:shadow-[0_18px_48px_rgba(0,54,41,0.1)] focus:outline-none focus:ring-2 focus:ring-[#b8d9cf]' : ''"
            >
                <div class="flex items-start justify-between gap-3">
                    <div :class="['flex h-8 w-8 items-center justify-center rounded-md', card.iconBg]">
                        <svg viewBox="0 0 24 24" :class="['h-4 w-4', card.iconColor]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path v-for="path in iconPath(card.icon)" :key="path" :d="path" />
                        </svg>
                    </div>
                </div>
                <p class="mt-2 min-h-8 text-[0.68rem] font-normal leading-4 text-[#44515d]">{{ card.label }}</p>
                <p class="mt-0.5 text-lg font-medium tracking-tight text-[#14202c]">{{ formatNumber(card.value) }}</p>
                <div class="mt-2 h-1 rounded-full bg-[#e8ece9]">
                    <div :class="['h-1 rounded-full', card.progress]" :style="{ width: card.value > 0 ? '100%' : '12%' }"></div>
                </div>
            </component>
        </section>

        <section class="grid grid-cols-1 items-start gap-3 xl:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-lg border border-[#dce7e2] bg-white p-3.5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-[#142c24]">Renewal performance for CY {{ renewalStatistics.year }}</h3>
                        <p class="mt-1 max-w-2xl text-xs leading-5 text-[#62716a]">{{ renewalDecisionMessage }}</p>
                    </div>
                    <Link :href="renewalStatistics.links.records || dashboard.actions.viewRenewalsUrl" class="inline-flex h-8 shrink-0 items-center justify-center rounded-md bg-[#064f40] px-3 text-xs font-medium text-white transition hover:bg-[#003e32]">
                        View Renewal Records
                    </Link>
                </div>

                <div class="mt-3 grid gap-2 sm:grid-cols-3">
                    <div class="rounded-md bg-[#edf8f2] px-3 py-2.5">
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-[#547067]">Compliance</p>
                        <p class="mt-1.5 text-xl font-semibold text-[#075a46]">{{ renewalStatistics.complianceRate }}%</p>
                        <p class="mt-1 text-xs text-[#64736c]">{{ renewalStatistics.yearOverYearChange >= 0 ? '+' : '' }}{{ renewalStatistics.yearOverYearChange }} points vs previous year</p>
                    </div>
                    <div class="rounded-md bg-[#f4f7f5] px-3 py-2.5">
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-[#68756f]">Renewed</p>
                        <p class="mt-1.5 text-xl font-semibold text-[#172b24]">{{ formatNumber(renewalStatistics.renewedFarmers) }}</p>
                        <p class="mt-1 text-xs text-[#64736c]">of {{ formatNumber(renewalStatistics.eligibleFarmers) }} eligible farmers</p>
                    </div>
                    <Link :href="renewalStatistics.links.queue" class="rounded-md bg-[#fff5df] px-3 py-2.5 transition hover:bg-[#ffefd0]">
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-[#8a681d]">Still Unrenewed</p>
                        <p class="mt-1.5 text-xl font-semibold text-[#9b5f00]">{{ formatNumber(renewalStatistics.unrenewedFarmers) }}</p>
                        <p class="mt-1 text-xs text-[#7c6b45]">Farmers requiring follow-up</p>
                    </Link>
                </div>

                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        <div class="flex items-center justify-between rounded-md border border-[#e6ebe8] bg-[#fafcfb] px-3 py-2 text-xs">
                            <span class="text-[#65736d]">Pending review</span>
                            <strong class="text-[#a66a00]">{{ formatNumber(renewalStatistics.pendingRequests) }}</strong>
                        </div>
                        <div class="flex items-center justify-between rounded-md border border-[#e6ebe8] bg-[#fafcfb] px-3 py-2 text-xs">
                            <span class="text-[#65736d]">Late renewals</span>
                            <strong class="text-[#9b5f00]">{{ formatNumber(renewalStatistics.lateRenewals) }}</strong>
                        </div>
                        <div class="flex items-center justify-between rounded-md border border-[#e6ebe8] bg-[#fafcfb] px-3 py-2 text-xs">
                            <span class="text-[#65736d]">Rejected</span>
                            <strong class="text-[#b53d50]">{{ formatNumber(renewalStatistics.rejectedRequests) }}</strong>
                        </div>
                </div>
            </article>

            <article class="rounded-lg border border-[#e4e9e6] bg-white p-3.5">
                <div>
                    <h3 class="text-sm font-semibold text-[#142c24]">Barangays needing follow-up</h3>
                </div>

                <div v-if="renewalPriorityBarangays.length" class="mt-3 space-y-2">
                    <Link v-for="row in renewalPriorityBarangays" :key="row.id" :href="row.href" class="block rounded-md border border-[#e5eae7] px-3 py-2.5 transition hover:border-[#cbd9d2] hover:bg-[#f8fbf9]">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="truncate text-[1rem] font-semibold text-[#172b24]">{{ row.barangay }}</p>
                                <p class="mt-1 text-xs text-[#6a7771]">{{ formatNumber(row.renewed) }} of {{ formatNumber(row.eligible) }} renewed</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[1.05rem] font-black text-[#8c6100]">{{ row.complianceRate }}%</p>
                                <p class="mt-1 text-xs font-semibold text-[#a35f37]">{{ formatNumber(row.unrenewed) }} pending</p>
                            </div>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-[#ecefeb]">
                            <div class="h-full rounded-full bg-[#91a85b]" :style="{ width: `${Math.min(Math.max(Number(row.complianceRate || 0), 0), 100)}%` }"></div>
                        </div>
                    </Link>
                </div>
                <div v-else class="mt-7 rounded-[1.5rem] border border-dashed border-[#d8dfdb] bg-[#f8faf9] px-5 py-10 text-center text-sm text-[#6c757d]">
                    No barangay renewal workload is available for this filter.
                </div>
            </article>
        </section>

        <section class="rounded-lg border border-[#e1e8e4] bg-white p-3.5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-[#142c24]">Renewal increase or decrease by year</h3>
                </div>
                <span class="inline-flex w-fit rounded-md bg-[#edf4f0] px-2.5 py-1 text-xs font-medium text-[#315448]">
                    Through CY {{ renewalStatistics.year }}
                </span>
            </div>

            <div v-if="renewalYearlyTrend.length" class="mt-3 overflow-x-auto">
                <table class="min-w-[820px] w-full text-left">
                    <thead class="border-b border-[#dfe7e2] text-xs font-black uppercase tracking-[0.1em] text-[#66756e]">
                        <tr>
                            <th class="px-3 py-2">Year</th>
                            <th class="px-3 py-2">Renewal Volume</th>
                            <th class="px-3 py-2 text-center">Eligible</th>
                            <th class="px-3 py-2 text-center">Renewed</th>
                            <th class="px-3 py-2 text-center">Unrenewed</th>
                            <th class="px-3 py-2 text-center">Compliance</th>
                            <th class="px-3 py-2 text-right">Yearly Change</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf1ee]">
                        <tr v-for="row in renewalYearlyTrend" :key="row.year" class="transition hover:bg-[#f9fbfa]">
                            <td class="px-3 py-2.5">
                                <span class="text-[1.05rem] font-black text-[#18342b]">{{ row.year }}</span>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-2.5 min-w-[130px] flex-1 overflow-hidden rounded-full bg-[#e9eeeb]">
                                        <div
                                            class="h-full rounded-full"
                                            :class="row.direction === 'decreased' ? 'bg-[#d77a82]' : 'bg-[#4c8c70]'"
                                            :style="{ width: `${Math.max((Number(row.renewed || 0) / maximumYearlyRenewals) * 100, row.renewed > 0 ? 8 : 0)}%` }"
                                        ></div>
                                    </div>
                                    <span class="w-10 text-right text-sm font-bold text-[#31443d]">{{ formatNumber(row.renewed) }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-center font-semibold text-[#3f4e48]">{{ formatNumber(row.eligible) }}</td>
                            <td class="px-3 py-2.5 text-center font-bold text-[#0b684f]">{{ formatNumber(row.renewed) }}</td>
                            <td class="px-3 py-2.5 text-center font-bold text-[#a36420]">{{ formatNumber(row.unrenewed) }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="font-black text-[#244b3f]">{{ row.complianceRate }}%</span>
                                <span v-if="row.complianceChange !== null" class="ml-1 text-xs" :class="row.complianceChange >= 0 ? 'text-[#16805e]' : 'text-[#bd4658]'">
                                    ({{ row.complianceChange >= 0 ? '+' : '' }}{{ row.complianceChange }} pts)
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <span :class="['inline-flex rounded-full px-3 py-1.5 text-xs font-black', renewalChangeTone(row.direction)]">
                                    {{ renewalChangeLabel(row) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="mt-7 rounded-[1.5rem] border border-dashed border-[#d8dfdb] bg-[#f8faf9] px-5 py-10 text-center text-sm text-[#6c757d]">
                No annual renewal history is available.
            </div>
        </section>

        <section v-if="isAdmin" class="grid grid-cols-1 items-start gap-3 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-lg border border-[#e4e9e6] bg-white p-3">
                <div class="grid grid-cols-2 gap-2 xl:grid-cols-4">
                    <Link
                        v-for="item in adminQueueCards"
                        :key="item.key"
                        :href="item.href"
                        :class="['rounded-md border bg-white p-2.5 transition hover:bg-[#fafcfb]', operationTone(item.tone).border]"
                    >
                        <span :class="['inline-flex rounded px-2 py-0.5 text-[0.56rem] font-semibold uppercase tracking-[0.05em]', operationTone(item.tone).badge]">
                            {{ item.label }}
                        </span>
                        <p :class="['mt-2 text-lg font-medium leading-none', operationTone(item.tone).value]">
                            {{ formatNumber(item.value) }}
                        </p>
                    </Link>
                </div>
            </article>

            <div class="space-y-3">
                <article class="rounded-lg border border-[#e4e9e6] bg-white p-3.5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[0.78rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Collections Today</p>
                            <h3 class="mt-1 text-lg font-medium text-[#14202c]">{{ currencyFormatter.format(Number(todayCollectionTotal || 0)) }}</h3>
                        </div>
                        <Link :href="adminOperations.recentPayments?.href" class="text-xs font-medium text-[#003e32] transition hover:underline">
                            View Analytics
                        </Link>
                    </div>

                    <div v-if="recentPayments.length" class="mt-3 space-y-2">
                        <div v-for="payment in recentPayments" :key="payment.id" class="rounded-md bg-[#f4f7f5] px-3 py-2.5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate text-[1rem] font-semibold text-[#14202c]">{{ payment.farmerName }}</p>
                                    <p class="text-sm text-[#5d6973]">{{ payment.farmerCode || 'No farmer code' }}</p>
                                    <p class="mt-1 text-xs font-medium text-[#7a8781]">{{ payment.paidAt }}</p>
                                </div>
                                <span class="text-[1rem] font-bold text-[#0f5b46]">{{ currencyFormatter.format(Number(payment.amount || 0)) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="mt-3 rounded-md border border-dashed border-[#d8dfdb] bg-[#f8faf9] px-4 py-5 text-center text-xs text-[#6c757d]">
                        No payments collected yet today.
                    </div>
                </article>

            </div>
        </section>

        <section class="grid grid-cols-1 items-start gap-3 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-lg border border-[#e4e9e6] bg-white p-3.5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Filtered Collections</p>
                        <h3 class="mt-2 text-[1.2rem] font-medium text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.overall || 0)) }}</h3>
                    </div>
                    <Link :href="collections.links.analytics" class="text-xs font-medium text-[#003e32] transition hover:underline">
                        View Analytics
                    </Link>
                </div>

                <div class="mt-3 grid gap-2 md:grid-cols-3">
                    <Link :href="collections.links.applications" class="rounded-md border border-[#dce5df] bg-[#f7faf8] px-3 py-2.5 transition hover:bg-[#f1f7f3]">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Applications</p>
                        <p class="mt-2 text-[1.25rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.applications || 0)) }}</p>
                        <p class="mt-1 text-xs text-[#5d6973]">{{ formatNumber(collections.counts.applicationPayments) }} payment records</p>
                    </Link>
                    <Link :href="collections.links.renewals" class="rounded-md border border-[#dce5df] bg-[#f7faf8] px-3 py-2.5 transition hover:bg-[#f1f7f3]">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Renewals</p>
                        <p class="mt-2 text-[1.25rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.renewals || 0)) }}</p>
                        <p class="mt-1 text-xs text-[#5d6973]">{{ formatNumber(collections.counts.renewalPayments) }} payment records</p>
                    </Link>
                    <Link :href="collections.links.mortuary" class="rounded-md border border-[#dce5df] bg-[#f7faf8] px-3 py-2.5 transition hover:bg-[#f1f7f3]">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Mortuary</p>
                        <p class="mt-2 text-[1.25rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.mortuary || 0)) }}</p>
                        <p class="mt-1 text-xs text-[#5d6973]">{{ formatNumber(collections.counts.mortuaryClaims) }} claim records</p>
                    </Link>
                </div>
            </article>

            <article class="rounded-lg border border-[#e4e9e6] bg-white p-3.5">
                <h3 class="text-[1.1rem] font-medium text-[#14202c]">Payment Breakdown</h3>

                <div class="mt-3 space-y-2">
                    <div class="flex items-center justify-between rounded-md bg-[#f4f7f5] px-3 py-2">
                        <span class="text-sm font-semibold text-[#33424d]">Membership Fees</span>
                        <span class="text-sm font-bold text-[#0f5b46]">{{ currencyFormatter.format(Number(collections.breakdown.membershipFee || 0)) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md bg-[#f4f7f5] px-3 py-2">
                        <span class="text-sm font-semibold text-[#33424d]">Annual Due</span>
                        <span class="text-sm font-bold text-[#0f5b46]">{{ currencyFormatter.format(Number(collections.breakdown.annualDue || 0)) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md bg-[#f4f7f5] px-3 py-2">
                        <span class="text-sm font-semibold text-[#33424d]">Mortuary Contribution</span>
                        <span class="text-sm font-bold text-[#0f5b46]">{{ currencyFormatter.format(Number(collections.breakdown.mortuaryContribution || 0)) }}</span>
                    </div>
                </div>
            </article>
        </section>

        <section>
            <article class="rounded-lg border border-[#e4e9e6] bg-white p-3.5">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="flex items-center gap-2 text-sm font-medium text-[#14202c]">
                        <span class="h-5 w-1.5 rounded-full bg-[#003e32]"></span>
                        Farmer Registration Activity
                    </h3>
                    <div class="flex items-center gap-2 text-sm font-medium text-[#4d5963]">
                        <span>Monthly ({{ selectedYearLabel }})</span>
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 17 9 11l4 4 8-10" />
                        </svg>
                    </div>
                </div>

                <div class="relative mt-3 h-36">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
                        <defs>
                            <linearGradient id="dashboard-trend-fill" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#1b4d3e" stop-opacity="0.14" />
                                <stop offset="100%" stop-color="#1b4d3e" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <polyline :points="linePoints" fill="none" stroke="#1d5f4f" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        <path :d="`M${linePoints} L100,100 L0,100 Z`" fill="url(#dashboard-trend-fill)" />
                    </svg>
                    <div class="absolute inset-x-0 bottom-0 flex justify-between px-3 text-[0.72rem] font-bold tracking-[0.18em] text-[#a1a7a3]">
                        <span v-for="row in registrationTrend.series.filter((_, index) => [0, 2, 5, 8, 11].includes(index))" :key="row.month">
                            {{ row.label.toUpperCase() }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-3">
                    <div class="flex items-center gap-2 text-xs font-medium text-[#14202c]">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#003e32]"></span>
                        New Registrations
                    </div>
                    <span class="ml-auto text-xs font-semibold" :class="registrationTrend.yearOverYearPercent >= 0 ? 'text-[#678b1b]' : 'text-[#b44f4f]'">
                        {{ registrationTrend.yearOverYearPercent >= 0 ? '+' : '' }}{{ registrationTrend.yearOverYearPercent }}% vs LY
                    </span>
                </div>
            </article>

        </section>

        <section class="grid grid-cols-1 items-start gap-4 xl:grid-cols-12">
            <div class="space-y-4 xl:col-span-8">
                <article class="rounded-lg border border-[#dfe6e2] bg-white p-3.5">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="flex items-center gap-2.5 text-sm font-semibold text-[#14202c]">
                            <span class="h-5 w-1.5 rounded-full bg-[#5f8418]"></span>
                            Farmers by Member Type
                        </h3>
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#65716c]" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19h16" />
                            <path d="M7 15v-5" />
                            <path d="M12 15V8" />
                            <path d="M17 15V5" />
                        </svg>
                    </div>

                    <div v-if="memberTypeRows.length" class="mt-3 grid gap-2.5 sm:grid-cols-2">
                        <div v-for="row in memberTypeRows" :key="row.label" class="flex items-center gap-2.5 rounded-md bg-[#f7f9f8] px-3 py-2.5">
                            <div :class="['flex h-8 w-8 shrink-0 items-center justify-center rounded-md', row.bgColor]">
                                <svg viewBox="0 0 24 24" :class="['h-4 w-4', row.textColor]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path v-for="path in iconPath(row.icon)" :key="path" :d="path" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-[#14202c]">{{ row.label }}</p>
                                <p class="text-[0.68rem] text-[#596671]">{{ formatNumber(row.total) }} registered</p>
                            </div>
                            <span class="rounded-md bg-[#eef0ee] px-2 py-1 text-[0.62rem] font-semibold text-[#39433f]">{{ row.badge }}</span>
                        </div>
                    </div>
                    <div v-else class="mt-10 rounded-[1.5rem] border border-dashed border-[#d8dfdb] bg-[#f8faf9] px-5 py-10 text-center text-sm text-[#6c757d]">
                        No member type distribution available.
                    </div>
                </article>

                <article class="overflow-hidden rounded-lg border border-[#dfe6e2] bg-white p-3.5">
                    <div class="flex items-center justify-between gap-3 border-b border-[#e5ebe8] pb-2.5">
                        <h3 class="text-sm font-semibold text-[#14202c]">Recent Farmer Registry Entries</h3>
                        <Link :href="dashboard.actions.viewFarmersUrl" class="text-xs font-medium text-[#003e32] transition hover:underline">
                            View All Entries
                        </Link>
                    </div>

                    <div v-if="recentFarmers.length" class="mt-2 overflow-x-auto">
                        <table class="min-w-[680px] w-full table-fixed text-left">
                            <thead class="border-b border-[#e2e7e4] text-[0.62rem] font-semibold uppercase tracking-[0.06em] text-[#65736d]">
                                <tr>
                                    <th class="w-[46%] px-3 py-2">Farmer Name</th>
                                    <th class="w-[18%] px-3 py-2">Barangay</th>
                                    <th class="w-[20%] px-3 py-2">Status</th>
                                    <th class="w-[16%] px-3 py-2 text-right">Date Applied</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#edf1ee]">
                                <tr v-for="farmer in recentFarmers" :key="farmer.id" class="transition hover:bg-[#f9fbfa]">
                                    <td class="px-3 py-2.5">
                                        <div class="flex min-w-0 items-center gap-2.5">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#e8ebea] text-[0.68rem] font-semibold text-[#003e32]">
                                                {{ initials(farmer.fullName) }}
                                            </span>
                                            <span class="truncate text-xs font-medium text-[#12202b]" :title="farmer.fullName">{{ farmer.fullName }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-xs text-[#52615b]">{{ farmer.barangay || '-' }}</td>
                                    <td class="px-3 py-2.5">
                                        <span
                                            class="inline-flex whitespace-nowrap rounded-md px-2 py-1 text-[0.65rem] font-medium"
                                            :class="{
                                                'bg-[#c9f6dd] text-[#006c57]': farmer.farmerStatus === 'active',
                                                'bg-[#ffeaa8] text-[#c56d00]': farmer.membershipStatus === 'pending_application' || farmer.membershipStatus === 'pending_documents',
                                                'bg-[#d8ecff] text-[#0b75ba]': farmer.membershipStatus === 'pending_verification' || farmer.membershipStatus === 'pending_payment',
                                                'bg-[#ffd8dd] text-[#d12249]': farmer.farmerStatus === 'inactive' || farmer.farmerStatus === 'deceased',
                                            }"
                                        >
                                            {{ farmer.membershipStatusLabel || 'Not set' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right text-xs text-[#52615b]">{{ farmer.shortDate }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="mt-8 rounded-[1.5rem] border border-dashed border-[#d8dfdb] bg-[#f8faf9] px-5 py-10 text-center text-sm text-[#6c757d]">
                        No recent farmer records found for this filter.
                    </div>
                </article>

            </div>

            <div class="space-y-4 xl:col-span-4">
                <article v-if="isAdmin" class="rounded-lg border border-[#dfe6e2] bg-white p-3.5">
                    <h3 class="text-sm font-semibold text-[#14202c]">Top Barangays</h3>
                    <div v-if="topBarangays.length" class="mt-3 space-y-3">
                        <div v-for="row in topBarangays" :key="row.label">
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-xs font-medium text-[#14202c]">{{ row.label }}</span>
                                <span class="text-xs font-semibold text-[#003e32]">{{ formatNumber(row.total) }}</span>
                            </div>
                            <div class="relative h-6 overflow-hidden rounded-md bg-[#edf0ee]">
                                <div class="h-full bg-[#becbc6]" :style="{ width: row.width }"></div>
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[0.6rem] font-semibold uppercase tracking-wide text-[#66736e]">Rank #{{ row.rank }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="mt-8 rounded-[1.5rem] border border-dashed border-[#d8dfdb] bg-[#f8faf9] px-5 py-10 text-center text-sm text-[#6c757d]">
                        No barangay totals available.
                    </div>
                </article>

                <article v-if="isAdmin" class="rounded-lg border border-[#dfe6e2] bg-white p-3.5">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold text-[#14202c]">Fee Schedules</h3>
                        <span class="rounded-md bg-[#1d5f4f] px-2.5 py-1 text-[0.68rem] font-semibold text-white">{{ selectedYearLabel }}</span>
                    </div>

                    <div v-if="feeScheduleRows.length" class="mt-3 space-y-2">
                        <div v-for="row in feeScheduleRows" :key="row.name" class="rounded-md bg-[#f3f5f4] px-3 py-2.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-semibold text-[#14202c]">{{ row.name }}</p>
                                    <p class="mt-0.5 text-[0.68rem] leading-4 text-[#55616b]">{{ row.description }}</p>
                                    <p class="text-[0.65rem] text-[#79847d]">Mortuary {{ row.secondaryAmount }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-xs font-semibold text-[#507d1f]">{{ row.amount }}</p>
                                    <p class="mt-0.5 text-[0.58rem] font-semibold uppercase tracking-wide text-[#59645f]">{{ row.frequency }}</p>
                                </div>
                            </div>
                        </div>
                        <Link :href="dashboard.actions.manageFeeSchedulesUrl" class="mt-2 block w-full rounded-md bg-[#e4e8e6] px-3 py-2 text-center text-xs font-semibold text-[#003e32] transition hover:bg-[#d7ddda]">
                            Manage Schedules
                        </Link>
                    </div>
                    <div v-else class="mt-8 rounded-[1.5rem] border border-dashed border-[#d8dfdb] bg-[#f8faf9] px-5 py-10 text-center text-sm text-[#6c757d]">
                        No fee schedules exist for this year.
                    </div>
                </article>

            </div>
        </section>

        <Link
            :href="dashboard.actions.createMembershipApplicationUrl"
            class="fixed bottom-6 right-6 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-[#003e32] text-white shadow-[0_12px_28px_rgba(0,62,50,0.24)] transition hover:scale-105 hover:bg-[#0b5645] focus:outline-none focus:ring-4 focus:ring-[#b8d9cf]"
        >
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14" />
                <path d="M5 12h14" />
            </svg>
        </Link>

        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/45 px-4 py-6" @click.self="closeExportModal">
            <section class="w-full max-w-3xl rounded-[28px] border border-[#dbe2de] bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.22)]">
                <div class="flex items-start justify-between gap-4 border-b border-[#e4ebe7] pb-4">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Dashboard Export</p>
                        <h2 class="mt-1 text-xl font-bold text-[#1a2420]">Generate filtered report</h2>
                        <p class="mt-2 text-sm text-[#697772]">Choose which dashboard sections to export for {{ selectedYearLabel }} and {{ selectedBarangayName }}.</p>
                    </div>
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-[#d7e0db] text-[#66756f] transition hover:bg-[#f5f8f6]" @click="closeExportModal">
                        <span class="text-lg leading-none">&times;</span>
                    </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <label v-for="(enabled, key) in exportSections" :key="key" class="flex items-center gap-3 rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420]">
                        <input v-model="exportSections[key]" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#003629] focus:ring-[#003629]">
                        <span class="font-medium">
                            {{
                                key === 'summary' ? 'Summary cards'
                                    : key === 'collections' ? 'Collections totals'
                                    : key === 'payment_breakdown' ? 'Payment breakdown'
                                    : key === 'membership_status' ? 'Membership status'
                                    : key === 'member_types' ? 'Member types'
                                    : key === 'top_barangays' ? 'Top barangays'
                                    : key === 'top_associations' ? 'Top associations'
                                    : key === 'recent_farmers' ? 'Recent farmers'
                                    : key === 'application_records' ? 'Application records list'
                                    : key === 'renewal_records' ? 'Renewal records list'
                                    : 'Mortuary records list'
                            }}
                        </span>
                    </label>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-5 py-3 text-sm font-bold text-[#697772] transition hover:bg-[#f4f7f5]" @click="closeExportModal">
                        Cancel
                    </button>
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl bg-[#003629] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]" @click="exportDashboardReport">
                        Export CSV
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>
