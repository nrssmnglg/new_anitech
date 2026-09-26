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
    membership_status: false,
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
    if (!value) return '-';
    const normalized = String(value).replace(' ', 'T');
    const date = new Date(normalized);
    return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
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
    };
    return paths[icon] || paths.agriculture;
}

const summaryCards = computed(() => {
    return [
        {
            key: 'totalFarmers',
            label: 'Registered Farmers',
            value: props.dashboard.summary?.totalFarmers ?? 0,
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
            value: props.dashboard.summary?.activeFarmers ?? 0,
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
            value: props.dashboard.summary?.pendingApplications ?? 0,
            icon: 'pending',
            iconBg: 'bg-gradient-to-br from-[#ffefb8] to-[#ffe08a]',
            iconColor: 'text-[#c76900]',
            progress: 'bg-[#f59e0b]',
            meta: `Queued reviews for ${selectedYearLabel.value.toLowerCase()}`,
            href: props.dashboard.summaryCardLinks?.pendingApplications ?? null,
        },
        {
            key: 'inactiveFarmers',
            label: 'Inactive Farmers',
            value: props.dashboard.summary?.inactiveFarmers ?? 0,
            icon: 'inactive',
            iconBg: 'bg-gradient-to-br from-[#ffd9dd] to-[#ffc0c7]',
            iconColor: 'text-[#d81f46]',
            progress: 'bg-[#ef4444]',
            meta: 'Registry records marked inactive',
            href: props.dashboard.summaryCardLinks?.inactiveFarmers ?? null,
        },
    ];
});

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

const collections = computed(() => props.dashboard.collections ?? {
    totals: { overall: 0, applications: 0, renewals: 0, mortuary: 0 },
    counts: { applicationPayments: 0, renewalPayments: 0, mortuaryClaims: 0 },
    links: {},
});
const registrationTrend = computed(() => props.dashboard.breakdowns?.registrationTrend ?? { series: [], currentYearTotal: 0 });
const maximumMonthlyRegistrations = computed(() => Math.max(
    ...(registrationTrend.value.series ?? []).map((row) => Number(row.total || 0)),
    1,
));
const renewalStatistics = computed(() => props.dashboard.renewalStatistics ?? {
    year: new Date().getFullYear(),
    eligibleFarmers: 0,
    renewedFarmers: 0,
    unrenewedFarmers: 0,
    complianceRate: 0,
    pendingRequests: 0,
});
const renewalRate = computed(() => Math.min(Math.max(Number(renewalStatistics.value.complianceRate || 0), 0), 100));
const renewalDonutStyle = computed(() => ({
    background: `conic-gradient(#15803d 0 ${renewalRate.value}%, #f1f5f9 ${renewalRate.value}% 100%)`,
}));

const staffWorkspace = computed(() => props.dashboard.staffWorkspace ?? null);
const staffAssignedTasks = computed(() => staffWorkspace.value?.cards ?? []);
const staffMobileSupport = computed(() => staffWorkspace.value?.mobileSupport ?? []);

function workspaceTone(accent) {
    const tones = {
        emerald: {
            badge: 'bg-[#dcfce7] text-[#15803d]',
            value: 'text-[#166534]',
            border: 'border-[#bbf7d0]',
            hover: 'hover:border-[#86efac] hover:bg-[#f0fdf4]',
        },
        lime: {
            badge: 'bg-[#ecfccb] text-[#4d7c0f]',
            value: 'text-[#3f6212]',
            border: 'border-[#d9f99d]',
            hover: 'hover:border-[#bef264] hover:bg-[#f7fee7]',
        },
        sky: {
            badge: 'bg-[#e0f2fe] text-[#0369a1]',
            value: 'text-[#075985]',
            border: 'border-[#bae6fd]',
            hover: 'hover:border-[#7dd3fc] hover:bg-[#f0f9ff]',
        },
        amber: {
            badge: 'bg-[#fef3c7] text-[#b45309]',
            value: 'text-[#92400e]',
            border: 'border-[#fde68a]',
            hover: 'hover:border-[#fcd34d] hover:bg-[#fffbeb]',
        },
        rose: {
            badge: 'bg-[#ffe4e6] text-[#be123c]',
            value: 'text-[#9f1239]',
            border: 'border-[#fecdd3]',
            hover: 'hover:border-[#fda4af] hover:bg-[#fff1f2]',
        },
    };
    return tones[accent] ?? tones.emerald;
}

const recentFarmers = computed(() => (props.dashboard.recentFarmers ?? []).slice(0, 5).map((row) => ({
    ...row,
    shortDate: formatDate(row.registeredAt),
})));
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
                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Staff Workspace</p>
                        <h2 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Overview for {{ selectedYearLabel }}</h2>
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
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Showing:</span>
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
                Reset
            </button>
        </div>

        <!-- Summary cards -->
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
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

        <!-- Quick actions -->
        <section>
            <div class="grid gap-3 sm:grid-cols-3">
                <Link :href="dashboard.actions.createMembershipApplicationUrl" class="group flex items-center gap-3.5 rounded-xl border border-[#e2e8f0] bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#bbf7d0] hover:shadow-md">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#dcfce7] to-[#bbf7d0] shadow-sm">
                        <svg viewBox="0 0 20 20" class="h-5 w-5 text-[#15803d] transition-transform duration-200 group-hover:scale-110" fill="currentColor"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-[#0f172a]">New Application</h3>
                        <p class="mt-0.5 text-[0.65rem] text-[#64748b]">Register a new membership application</p>
                    </div>
                </Link>
                <Link :href="dashboard.actions.viewFarmersUrl" class="group flex items-center gap-3.5 rounded-xl border border-[#e2e8f0] bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#bae6fd] hover:shadow-md">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#dbeafe] to-[#bfdbfe] shadow-sm">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#2563eb] transition-transform duration-200 group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-[#0f172a]">Farmer Records</h3>
                        <p class="mt-0.5 text-[0.65rem] text-[#64748b]">Search and manage farmer profiles</p>
                    </div>
                </Link>
                <Link :href="dashboard.actions.viewRenewalsUrl" class="group flex items-center gap-3.5 rounded-xl border border-[#e2e8f0] bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#d9f99d] hover:shadow-md">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#ecfccb] to-[#d9f99d] shadow-sm">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#4d7c0f] transition-transform duration-200 group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 1-15.5 6.36L3 16"/><path d="M3 12A9 9 0 0 1 18.5 5.64L21 8"/><path d="M8 16H3v5"/><path d="M16 8h5V3"/></svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-[#0f172a]">Membership Renewals</h3>
                        <p class="mt-0.5 text-[0.65rem] text-[#64748b]">Review and process renewal records</p>
                    </div>
                </Link>
            </div>
        </section>

        <!-- Staff Assigned Work & Workspace Tasks (if available) -->
        <section v-if="staffAssignedTasks.length" class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-[#15803d]"></span>
                    <h3 class="text-[0.8rem] font-bold text-[#0f172a]">My Assigned Workflow & Tasks</h3>
                </div>
                <Link v-if="dashboard.actions.tasksUrl" :href="dashboard.actions.tasksUrl" class="text-[0.68rem] font-semibold text-[#014d3c] transition-colors hover:underline">
                    View All Tasks
                </Link>
            </div>
            <div class="grid grid-cols-2 gap-px bg-[#f1f5f9] sm:grid-cols-4">
                <Link
                    v-for="task in staffAssignedTasks"
                    :key="task.key"
                    :href="task.href"
                    class="bg-white p-4 transition-colors"
                    :class="workspaceTone(task.accent).hover"
                >
                    <span class="inline-flex rounded-lg px-2 py-0.5 text-[0.58rem] font-bold uppercase tracking-[0.05em]" :class="workspaceTone(task.accent).badge">
                        {{ task.label }}
                    </span>
                    <p class="mt-2 text-2xl font-bold tracking-tight" :class="workspaceTone(task.accent).value">
                        {{ formatNumber(task.count) }}
                    </p>
                    <p class="mt-1 line-clamp-1 text-[0.65rem] text-[#94a3b8]">{{ task.description }}</p>
                </Link>
            </div>
        </section>

        <!-- Mobile Support Signals (if any) -->
        <section v-if="staffMobileSupport.length" class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
            <div class="border-b border-[#f1f5f9] px-5 py-3.5">
                <h3 class="text-[0.8rem] font-bold text-[#0f172a]">Mobile & Online Support Signals</h3>
            </div>
            <div class="grid grid-cols-1 gap-px bg-[#f1f5f9] sm:grid-cols-3">
                <Link
                    v-for="item in staffMobileSupport"
                    :key="item.key"
                    :href="item.href"
                    class="bg-white p-4 transition-colors"
                    :class="workspaceTone(item.accent).hover"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#0f172a]">{{ item.label }}</span>
                        <span class="rounded-lg px-2 py-0.5 text-xs font-bold" :class="workspaceTone(item.accent).badge">
                            {{ formatNumber(item.count) }}
                        </span>
                    </div>
                    <p class="mt-1.5 text-[0.68rem] leading-4 text-[#64748b]">{{ item.description }}</p>
                </Link>
            </div>
        </section>

        <!-- Collections summary -->
        <section class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-4">
                <div>
                    <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Filtered Collections</p>
                    <h3 class="mt-1 text-lg font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(collections.totals.overall || 0)) }}</h3>
                </div>
                <Link :href="collections.links.renewals || dashboard.actions.viewRenewalsUrl" class="text-[0.68rem] font-bold text-[#014d3c] transition-colors hover:underline">
                    Open Records
                </Link>
            </div>
            <div class="grid gap-px bg-[#f1f5f9] md:grid-cols-3">
                <Link :href="collections.links.applications || '#'" class="bg-white px-5 py-4 transition-colors hover:bg-[#f8fafc]">
                    <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Applications</p>
                    <p class="mt-2 text-lg font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(collections.totals.applications || 0)) }}</p>
                    <p class="mt-1 text-[0.68rem] text-[#94a3b8]">{{ formatNumber(collections.counts.applicationPayments) }} payments</p>
                </Link>
                <Link :href="collections.links.renewals || '#'" class="bg-white px-5 py-4 transition-colors hover:bg-[#f8fafc]">
                    <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Renewals</p>
                    <p class="mt-2 text-lg font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(collections.totals.renewals || 0)) }}</p>
                    <p class="mt-1 text-[0.68rem] text-[#94a3b8]">{{ formatNumber(collections.counts.renewalPayments) }} payments</p>
                </Link>
                <Link :href="collections.links.mortuary || '#'" class="bg-white px-5 py-4 transition-colors hover:bg-[#f8fafc]">
                    <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Mortuary</p>
                    <p class="mt-2 text-lg font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(collections.totals.mortuary || 0)) }}</p>
                    <p class="mt-1 text-[0.68rem] text-[#94a3b8]">{{ formatNumber(collections.counts.mortuaryClaims) }} claims</p>
                </Link>
            </div>
        </section>

        <!-- Charts row -->
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <!-- Registration bar chart -->
            <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                <div class="flex items-start justify-between border-b border-[#f1f5f9] px-5 py-4">
                    <div>
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Farmer Registry</p>
                        <h3 class="mt-1 text-sm font-bold text-[#0f172a]">Monthly Registrations</h3>
                    </div>
                    <span class="inline-flex items-center rounded-lg bg-[#f0fdf4] px-2.5 py-1 text-[0.62rem] font-bold text-[#15803d]">
                        {{ formatNumber(registrationTrend.currentYearTotal) }} total
                    </span>
                </div>

                <div class="flex h-40 items-end gap-1.5 px-5 pb-2 pt-4 sm:gap-2.5">
                    <div v-for="row in registrationTrend.series" :key="row.month" class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1.5">
                        <span class="text-[0.58rem] font-bold text-[#475569]">{{ formatNumber(row.total) }}</span>
                        <div
                            class="w-full max-w-7 rounded-t-lg bg-gradient-to-t from-[#014d3c] to-[#22c55e] transition-all duration-300"
                            :style="{ height: `${Math.max((Number(row.total || 0) / maximumMonthlyRegistrations) * 75, 3)}%` }"
                        ></div>
                        <span class="text-[0.5rem] font-bold uppercase tracking-wider text-[#94a3b8]">{{ row.label }}</span>
                    </div>
                </div>
            </article>

            <!-- Renewal donut -->
            <article class="overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-sm">
                <div class="flex items-start justify-between border-b border-[#f1f5f9] px-5 py-4">
                    <div>
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Renewal Statistics</p>
                        <h3 class="mt-1 text-sm font-bold text-[#0f172a]">Completion for {{ renewalStatistics.year }}</h3>
                    </div>
                    <Link :href="dashboard.actions.viewRenewalsUrl" class="text-[0.68rem] font-bold text-[#014d3c] hover:underline">View</Link>
                </div>

                <div class="flex flex-col items-center gap-5 px-5 py-5 sm:flex-row sm:justify-center">
                    <div class="relative h-28 w-28 shrink-0 rounded-full p-2.5" :style="renewalDonutStyle">
                        <div class="flex h-full w-full flex-col items-center justify-center rounded-full bg-white">
                            <span class="text-2xl font-bold text-[#0f172a]">{{ renewalRate }}%</span>
                            <span class="text-[0.55rem] font-bold uppercase tracking-wider text-[#94a3b8]">Completed</span>
                        </div>
                    </div>
                    <div class="w-full max-w-xs space-y-2">
                        <div class="flex items-center justify-between rounded-xl border border-[#f1f5f9] px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-[#22c55e]"></span>
                                <span class="text-xs font-semibold text-[#475569]">Renewed</span>
                            </div>
                            <strong class="text-xs font-bold text-[#15803d]">{{ formatNumber(renewalStatistics.renewedFarmers) }}</strong>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-[#f1f5f9] px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-[#f59e0b]"></span>
                                <span class="text-xs font-semibold text-[#475569]">Still due</span>
                            </div>
                            <strong class="text-xs font-bold text-[#d97706]">{{ formatNumber(renewalStatistics.unrenewedFarmers) }}</strong>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-[#f1f5f9] px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-[#3b82f6]"></span>
                                <span class="text-xs font-semibold text-[#475569]">Pending review</span>
                            </div>
                            <strong class="text-xs font-bold text-[#2563eb]">{{ formatNumber(renewalStatistics.pendingRequests) }}</strong>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <!-- Recent Farmers -->
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

        <!-- Export modal -->
        <div v-if="exportModalOpen" role="dialog" aria-modal="true" aria-label="Generate dashboard report" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f172a]/50 px-4 py-6 backdrop-blur-sm" @click.self="closeExportModal">
            <section class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-[#e2e8f0] bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-[#f1f5f9] px-6 py-4">
                    <div>
                        <h2 class="text-sm font-bold text-[#0f172a]">Generate Report</h2>
                        <p class="mt-1 text-xs text-[#94a3b8]">Choose sections to export for {{ selectedYearLabel }} and {{ selectedBarangayName }}.</p>
                    </div>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#94a3b8] transition-colors hover:bg-[#f1f5f9] hover:text-[#64748b]" @click="closeExportModal">
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    </button>
                </div>

                <div class="grid gap-2 px-6 py-4 sm:grid-cols-2">
                    <label v-for="(enabled, key) in exportSections" :key="key" class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-[#e2e8f0] bg-[#f8fafc] px-3.5 py-3 text-xs text-[#0f172a] transition-all hover:border-[#cbd5e1] hover:bg-[#f1f5f9]">
                        <input v-model="exportSections[key]" type="checkbox" class="h-3.5 w-3.5 rounded border-slate-300 text-[#014d3c] focus:ring-[#014d3c]">
                        <span class="font-semibold">
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
