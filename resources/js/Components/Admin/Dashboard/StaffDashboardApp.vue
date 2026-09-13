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
function formatNumber(value) {
    return numberFormatter.format(Number(value || 0));
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

const collections = computed(() => props.dashboard.collections ?? {
    totals: { overall: 0, applications: 0, renewals: 0, mortuary: 0 },
    counts: { applicationPayments: 0, renewalPayments: 0, mortuaryClaims: 0 },
    links: {},
});
const registrationTrend = computed(() => props.dashboard.breakdowns.registrationTrend ?? { series: [], currentYearTotal: 0 });
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
    background: `conic-gradient(#5f8418 0 ${renewalRate.value}%, #e6ece8 ${renewalRate.value}% 100%)`,
}));
</script>

<template>
    <div class="dashboard-compact mx-auto w-full max-w-[1280px] space-y-3 pb-4">
        <section class="relative overflow-hidden rounded-[14px] bg-[#0f5b46] px-4 py-3 text-white">

            <div class="relative z-10 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="inline-flex items-center gap-2 rounded-md border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-medium text-[#e6f5da]">
                        Staff Dashboard
                    </div>
                    <button type="button" class="inline-flex h-8 items-center justify-center rounded-md border border-white/25 bg-white/10 px-3 text-xs font-medium text-white transition hover:bg-white/15" @click="openExportModal">
                        Generate Report
                    </button>
                </div>

                <form class="grid w-full max-w-[520px] gap-2 rounded-lg border border-white/20 bg-white/10 p-3 sm:grid-cols-[1fr_1fr_auto]" @submit.prevent="apply">
                    <label class="space-y-1.5">
                        <span class="ml-1 block text-sm font-bold text-white/70">Year</span>
                        <select v-model="state.year" class="w-full rounded-md border-0 bg-white/14 px-3 py-2 text-xs font-medium text-white outline-none ring-1 ring-white/10">
                            <option value="" class="text-stone-900">All years</option>
                            <option v-for="year in dashboard.filters.availableYears" :key="year" :value="String(year)" class="text-stone-900">{{ year }}</option>
                        </select>
                    </label>
                    <label class="space-y-1.5">
                        <span class="ml-1 block text-sm font-bold text-white/70">Barangay</span>
                        <select v-model="state.barangayId" class="w-full rounded-md border-0 bg-white/14 px-3 py-2 text-xs font-medium text-white outline-none ring-1 ring-white/10">
                            <option value="" class="text-stone-900">All Barangays</option>
                            <option v-for="barangay in dashboard.filters.barangays" :key="barangay.id" :value="String(barangay.id)" class="text-stone-900">
                                {{ barangay.name }}
                            </option>
                        </select>
                    </label>
                    <button type="submit" class="h-9 self-end rounded-md bg-white px-3 text-xs font-semibold text-[#0f5b46] transition hover:bg-[#eef5ef]">Apply</button>
                </form>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-[#344654]">Showing:</span>
            <span class="inline-flex items-center rounded-md border border-[#d9dfdc] bg-[#eef1ef] px-2.5 py-1 text-xs font-normal text-[#102533]">
                {{ selectedYearLabel }}
            </span>
            <span class="inline-flex items-center rounded-md border border-[#d9dfdc] bg-[#eef1ef] px-2.5 py-1 text-xs font-normal text-[#102533]">
                {{ selectedBarangayName }}
            </span>
            <button type="button" class="ml-auto text-xs font-medium text-[#6c8900] hover:underline" @click="resetFilters">
                Reset
            </button>
        </div>

        <section>
            <h2 class="mb-2 text-sm font-semibold text-[#344654]">Quick actions</h2>
            <div class="grid gap-2 sm:grid-cols-3">
                <Link :href="dashboard.actions.createMembershipApplicationUrl" class="group rounded-lg border border-[#dce5df] bg-white p-3 shadow-sm transition hover:border-[#9eb9aa] hover:shadow-sm">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#e8f2eb] text-2xl font-light text-[#003e32]">+</span>
                    <h3 class="mt-2 text-sm font-semibold text-[#14202c]">New Application</h3>
                    <p class="mt-1 text-xs text-[#66756f]">Register a new membership application.</p>
                </Link>
                <Link :href="dashboard.actions.viewFarmersUrl" class="group rounded-lg border border-[#dce5df] bg-white p-3 shadow-sm transition hover:border-[#9eb9aa] hover:shadow-sm">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#e8f2eb] text-[#003e32]">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                    </span>
                    <h3 class="mt-2 text-sm font-semibold text-[#14202c]">Farmer Records</h3>
                    <p class="mt-1 text-xs text-[#66756f]">Search and manage farmer profiles.</p>
                </Link>
                <Link :href="dashboard.actions.viewRenewalsUrl" class="group rounded-lg border border-[#dce5df] bg-white p-3 shadow-sm transition hover:border-[#9eb9aa] hover:shadow-sm">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#eef3df] text-[#5f8418]">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 1-15.5 6.36L3 16"/><path d="M3 12A9 9 0 0 1 18.5 5.64L21 8"/><path d="M8 16H3v5"/><path d="M16 8h5V3"/></svg>
                    </span>
                    <h3 class="mt-2 text-sm font-semibold text-[#14202c]">Membership Renewals</h3>
                    <p class="mt-1 text-xs text-[#66756f]">Review and process renewal records.</p>
                </Link>
            </div>
        </section>

        <section>
            <article class="rounded-lg border border-[#e4e9e6] bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[0.72rem] font-semibold uppercase tracking-wide text-[#6c7a74]">Filtered Collections</p>
                        <h3 class="mt-2 text-base font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.overall || 0)) }}</h3>
                    </div>
                    <Link :href="collections.links.renewals || dashboard.actions.viewRenewalsUrl" class="text-sm font-bold text-[#003e32] transition hover:text-[#0f5b46]">
                        Open Records
                    </Link>
                </div>

                <div class="mt-3 grid gap-2 md:grid-cols-3">
                    <Link :href="collections.links.applications || '#'" class="rounded-lg border border-[#dce5df] bg-[#f7faf8] px-3 py-3 transition hover:bg-[#f1f7f3]">
                        <p class="text-[0.72rem] font-semibold uppercase tracking-wide text-[#6c7a74]">Applications</p>
                        <p class="mt-2 text-[1.1rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.applications || 0)) }}</p>
                        <p class="mt-1 text-xs text-[#5d6973]">{{ formatNumber(collections.counts.applicationPayments) }} payments</p>
                    </Link>
                    <Link :href="collections.links.renewals || '#'" class="rounded-lg border border-[#dce5df] bg-[#f7faf8] px-3 py-3 transition hover:bg-[#f1f7f3]">
                        <p class="text-[0.72rem] font-semibold uppercase tracking-wide text-[#6c7a74]">Renewals</p>
                        <p class="mt-2 text-[1.1rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.renewals || 0)) }}</p>
                        <p class="mt-1 text-xs text-[#5d6973]">{{ formatNumber(collections.counts.renewalPayments) }} payments</p>
                    </Link>
                    <Link :href="collections.links.mortuary || '#'" class="rounded-lg border border-[#dce5df] bg-[#f7faf8] px-3 py-3 transition hover:bg-[#f1f7f3]">
                        <p class="text-[0.72rem] font-semibold uppercase tracking-wide text-[#6c7a74]">Mortuary</p>
                        <p class="mt-2 text-[1.1rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.mortuary || 0)) }}</p>
                        <p class="mt-1 text-xs text-[#5d6973]">{{ formatNumber(collections.counts.mortuaryClaims) }} claims</p>
                    </Link>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <article class="rounded-lg border border-[#e4e9e6] bg-white p-3 shadow-sm sm:p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[0.72rem] font-semibold uppercase tracking-wide text-[#6c7a74]">Farmer Registry</p>
                        <h3 class="mt-1.5 text-base font-semibold text-[#14202c]">Monthly registrations</h3>
                    </div>
                    <span class="rounded-full bg-[#eef5ef] px-3 py-1.5 text-sm font-bold text-[#335043]">
                        {{ formatNumber(registrationTrend.currentYearTotal) }} total
                    </span>
                </div>

                <div class="mt-4 flex h-36 items-end gap-2 border-b border-[#dfe6e2] px-1 pb-1 sm:gap-3">
                    <div v-for="row in registrationTrend.series" :key="row.month" class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-xs font-bold text-[#53645d]">{{ formatNumber(row.total) }}</span>
                        <div class="w-full max-w-8 rounded-t-lg bg-[#0f5b46] transition-all" :style="{ height: `${Math.max((Number(row.total || 0) / maximumMonthlyRegistrations) * 75, 3)}%` }"></div>
                        <span class="text-[0.62rem] font-bold uppercase text-[#7b8882]">{{ row.label }}</span>
                    </div>
                </div>
            </article>

            <article class="rounded-lg border border-[#e4e9e6] bg-white p-3 shadow-sm sm:p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[0.72rem] font-semibold uppercase tracking-wide text-[#6c7a74]">Renewal Statistics</p>
                        <h3 class="mt-1.5 text-base font-semibold text-[#14202c]">Renewal completion for {{ renewalStatistics.year }}</h3>
                    </div>
                    <Link :href="dashboard.actions.viewRenewalsUrl" class="text-sm font-bold text-[#0f5b46] hover:underline">View renewals</Link>
                </div>

                <div class="mt-4 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                    <div class="relative h-32 w-32 shrink-0 rounded-full p-3" :style="renewalDonutStyle">
                        <div class="flex h-full w-full flex-col items-center justify-center rounded-full bg-white">
                            <span class="text-2xl font-semibold text-[#14202c]">{{ renewalRate }}%</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#708078]">Completed</span>
                        </div>
                    </div>
                    <div class="w-full max-w-xs space-y-3">
                        <div class="flex items-center justify-between rounded-xl bg-[#f5f8f6] px-4 py-3">
                            <span class="text-sm font-semibold text-[#52615b]">Renewed farmers</span>
                            <strong class="text-[#0f5b46]">{{ formatNumber(renewalStatistics.renewedFarmers) }}</strong>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-[#f5f8f6] px-4 py-3">
                            <span class="text-sm font-semibold text-[#52615b]">Still due</span>
                            <strong class="text-[#a55f00]">{{ formatNumber(renewalStatistics.unrenewedFarmers) }}</strong>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-[#f5f8f6] px-4 py-3">
                            <span class="text-sm font-semibold text-[#52615b]">Pending review</span>
                            <strong class="text-[#0b649e]">{{ formatNumber(renewalStatistics.pendingRequests) }}</strong>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <div v-if="exportModalOpen" role="dialog" aria-modal="true" aria-label="Generate dashboard report" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/45 px-4 py-6" @click.self="closeExportModal">
            <section class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-lg border border-[#dbe2de] bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-[#e4ebe7] pb-4">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Dashboard Export</p>
                        <h2 class="mt-1 text-xl font-bold text-[#1a2420]">Generate filtered report</h2>
                        <p class="mt-2 text-sm text-[#697772]">Choose which dashboard sections to export for {{ selectedYearLabel }} and {{ selectedBarangayName }}.</p>
                    </div>
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-[#d7e0db] text-[#66756f] transition hover:bg-[#f5f8f6]" @click="closeExportModal">
                        <span class="sr-only">Close report dialog</span><span aria-hidden="true" class="text-lg leading-none">&times;</span>
                    </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <label v-for="(enabled, key) in exportSections" :key="key" class="flex items-center gap-3 rounded-lg border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420]">
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
                    <button type="button" class="inline-flex items-center justify-center rounded-lg border border-[#d7e0db] px-5 py-3 text-sm font-bold text-[#697772] transition hover:bg-[#f4f7f5]" @click="closeExportModal">
                        Cancel
                    </button>
                    <button type="button" class="inline-flex items-center justify-center rounded-lg bg-[#003629] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]" @click="exportDashboardReport">
                        Export CSV
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>
