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

const staffWorkspace = computed(() => props.dashboard.staffWorkspace ?? {
    headline: { assignedWorkCount: 0 },
    cards: [],
    mobileSupport: [],
    priority: null,
    recentActions: [],
});
const collections = computed(() => props.dashboard.collections ?? {
    totals: { overall: 0, applications: 0, renewals: 0, mortuary: 0 },
    counts: { applicationPayments: 0, renewalPayments: 0, mortuaryClaims: 0 },
    links: {},
});

const operationAccentClasses = {
    emerald: {
        badge: 'bg-[#dff6ea] text-[#0c7a58]',
        number: 'text-[#0b5c46]',
        ring: 'border-[#d7ebe1]',
    },
    lime: {
        badge: 'bg-[#eef7d6] text-[#5f8418]',
        number: 'text-[#486814]',
        ring: 'border-[#e0e9ca]',
    },
    amber: {
        badge: 'bg-[#fff1d6] text-[#c57a00]',
        number: 'text-[#a55f00]',
        ring: 'border-[#efe0c3]',
    },
    sky: {
        badge: 'bg-[#e3f2ff] text-[#0c7cc2]',
        number: 'text-[#0b649e]',
        ring: 'border-[#d6e6f0]',
    },
    rose: {
        badge: 'bg-[#ffe4e7] text-[#cf3657]',
        number: 'text-[#ae1d44]',
        ring: 'border-[#edd8dc]',
    },
};

function operationTone(accent) {
    return operationAccentClasses[accent] ?? operationAccentClasses.emerald;
}

const workspaceCards = computed(() => staffWorkspace.value.cards ?? []);
const mobileSupportCards = computed(() => staffWorkspace.value.mobileSupport ?? []);
const topOperation = computed(() => staffWorkspace.value.priority ?? null);

function iconForKey(key) {
    const value = String(key || '').toLowerCase();

    if (value.includes('application')) {
        return 'document';
    }

    if (value.includes('renewal')) {
        return 'refresh';
    }

    if (value.includes('quer') || value.includes('inquir')) {
        return 'chat';
    }

    if (value.includes('today') || value.includes('action')) {
        return 'spark';
    }

    if (value.includes('record')) {
        return 'folder';
    }

    if (value.includes('payment')) {
        return 'wallet';
    }

    return 'grid';
}
</script>

<template>
    <div class="mx-auto w-full max-w-[1536px] space-y-8 pb-10">
        <section class="relative overflow-hidden rounded-[2.2rem] bg-[linear-gradient(135deg,#003e32,#0f5b46_58%,#b7e29a)] px-6 py-7 text-white shadow-[0_18px_60px_rgba(0,54,41,0.18)] sm:px-8 sm:py-8 lg:px-10 lg:py-9">
            <div class="absolute inset-0 opacity-15" style="background-image: linear-gradient(rgba(255,255,255,0.07) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 30px 30px;"></div>

            <div class="relative z-10 flex flex-col gap-8 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold text-[#e6f5da]">
                        Staff Workspace
                    </div>
                    <h2 class="mt-5 text-3xl font-semibold tracking-tight text-white sm:text-[2.2rem]">Assigned work dashboard for {{ selectedYearLabel }}</h2>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            :href="dashboard.actions.tasksUrl"
                            class="inline-flex items-center justify-center rounded-[1.2rem] bg-white px-5 py-3 text-sm font-extrabold text-[#003e32] transition hover:bg-[#f3f7f5]"
                        >
                            Open My Tasks
                        </Link>
                        <button type="button" class="inline-flex items-center justify-center rounded-[1.2rem] border border-white/25 bg-white/10 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-white/15" @click="openExportModal">
                            Generate Report
                        </button>
                    </div>
                </div>

                <form class="grid w-full max-w-[470px] gap-3 rounded-[2rem] border border-white/20 bg-white/12 p-5 backdrop-blur-xl md:grid-cols-[1fr_1fr_auto]" @submit.prevent="apply">
                    <label class="space-y-2">
                        <span class="ml-1 block text-sm font-bold text-white/70">Select Year</span>
                        <select v-model="state.year" class="w-full rounded-2xl border-0 bg-white/14 px-4 py-3 text-base font-semibold text-white outline-none ring-1 ring-white/10">
                            <option value="" class="text-stone-900">All years</option>
                            <option v-for="year in dashboard.filters.availableYears" :key="year" :value="String(year)" class="text-stone-900">{{ year }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="ml-1 block text-sm font-bold text-white/70">Barangay</span>
                        <select v-model="state.barangayId" class="w-full rounded-2xl border-0 bg-white/14 px-4 py-3 text-base font-semibold text-white outline-none ring-1 ring-white/10">
                            <option value="" class="text-stone-900">All Barangays</option>
                            <option v-for="barangay in dashboard.filters.barangays" :key="barangay.id" :value="String(barangay.id)" class="text-stone-900">
                                {{ barangay.name }}
                            </option>
                        </select>
                    </label>
                    <button type="submit" class="flex h-[54px] w-[54px] items-center justify-center self-end rounded-[1.4rem] bg-[#6a8f12] text-white transition hover:bg-[#5f820f]">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 6h16" />
                            <path d="M7 12h10" />
                            <path d="M10 18h4" />
                        </svg>
                    </button>
                </form>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <span class="text-[1.05rem] font-bold uppercase tracking-wide text-[#344654]">Active Filters:</span>
            <span class="inline-flex items-center rounded-full border border-[#d9dfdc] bg-[#eef1ef] px-4 py-2 text-lg font-semibold text-[#102533]">
                {{ selectedYearLabel }}
            </span>
            <span class="inline-flex items-center rounded-full border border-[#d9dfdc] bg-[#eef1ef] px-4 py-2 text-lg font-semibold text-[#102533]">
                {{ selectedBarangayName }}
            </span>
            <button type="button" class="ml-auto text-base font-bold text-[#6c8900] hover:underline" @click="resetFilters">
                Clear All Filters
            </button>
        </div>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <component
                :is="card.href ? Link : 'article'"
                v-for="card in workspaceCards"
                :key="card.key"
                :href="card.href || undefined"
                class="rounded-[1.75rem] border border-[#e4e9e6] bg-white p-5 shadow-[0_10px_30px_rgba(0,54,41,0.05)] transition"
                :class="card.href ? 'group block hover:-translate-y-0.5 hover:border-[#cdd8d3] hover:shadow-[0_18px_48px_rgba(0,54,41,0.1)] focus:outline-none focus:ring-2 focus:ring-[#b8d9cf]' : ''"
            >
                <div class="flex items-start justify-between gap-3">
                    <div :class="['inline-flex rounded-[1rem] px-3 py-2 text-[0.82rem] font-bold', card.tone]">
                        {{ card.label }}
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-[1rem] bg-[#f4f7f5] text-[#003e32]">
                        <svg v-if="iconForKey(card.key) === 'document'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                            <path d="M14 3v5h5" />
                        </svg>
                        <svg v-else-if="iconForKey(card.key) === 'refresh'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a9 9 0 0 1-15.5 6.36L3 16" />
                            <path d="M3 12A9 9 0 0 1 18.5 5.64L21 8" />
                            <path d="M8 16H3v5" />
                            <path d="M16 8h5V3" />
                        </svg>
                        <svg v-else-if="iconForKey(card.key) === 'chat'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        <svg v-else-if="iconForKey(card.key) === 'spark'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9Z" />
                        </svg>
                        <svg v-else-if="iconForKey(card.key) === 'folder'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        </svg>
                        <svg v-else-if="iconForKey(card.key) === 'wallet'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 7H4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z" />
                            <path d="M16 13h.01" />
                            <path d="M6 7V5a2 2 0 0 1 2-2h10" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h7v7H4z" />
                            <path d="M13 4h7v7h-7z" />
                            <path d="M4 13h7v7H4z" />
                            <path d="M13 13h7v7h-7z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-[1.9rem] font-semibold tracking-tight text-[#14202c]">{{ formatNumber(card.value) }}</p>
            </component>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <article class="rounded-[2rem] border border-[#e4e9e6] bg-white p-6 shadow-[0_10px_36px_rgba(0,54,41,0.05)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Filtered Collections</p>
                        <h3 class="mt-2 text-[1.2rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.overall || 0)) }}</h3>
                    </div>
                    <Link :href="collections.links.renewals || dashboard.actions.tasksUrl" class="text-sm font-bold text-[#003e32] transition hover:text-[#0f5b46]">
                        Open Records
                    </Link>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <Link :href="collections.links.applications || '#'" class="rounded-[1.35rem] border border-[#dce5df] bg-[#f7faf8] px-4 py-4 transition hover:bg-[#f1f7f3]">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Applications</p>
                        <p class="mt-2 text-[1.1rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.applications || 0)) }}</p>
                        <p class="mt-1 text-xs text-[#5d6973]">{{ formatNumber(collections.counts.applicationPayments) }} payments</p>
                    </Link>
                    <Link :href="collections.links.renewals || '#'" class="rounded-[1.35rem] border border-[#dce5df] bg-[#f7faf8] px-4 py-4 transition hover:bg-[#f1f7f3]">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Renewals</p>
                        <p class="mt-2 text-[1.1rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.renewals || 0)) }}</p>
                        <p class="mt-1 text-xs text-[#5d6973]">{{ formatNumber(collections.counts.renewalPayments) }} payments</p>
                    </Link>
                    <Link :href="collections.links.mortuary || '#'" class="rounded-[1.35rem] border border-[#dce5df] bg-[#f7faf8] px-4 py-4 transition hover:bg-[#f1f7f3]">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Mortuary</p>
                        <p class="mt-2 text-[1.1rem] font-semibold text-[#14202c]">{{ currencyFormatter.format(Number(collections.totals.mortuary || 0)) }}</p>
                        <p class="mt-1 text-xs text-[#5d6973]">{{ formatNumber(collections.counts.mortuaryClaims) }} claims</p>
                    </Link>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <article class="rounded-[2rem] border border-[#e4e9e6] bg-white p-7 shadow-[0_10px_36px_rgba(0,54,41,0.05)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[0.78rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Staff Processing</p>
                        <h3 class="mt-2 text-[1.35rem] font-semibold text-[#14202c]">Assigned work overview</h3>
                    </div>
                    <span class="rounded-full bg-[#eef5ef] px-4 py-2 text-sm font-bold text-[#335043]">
                        {{ staffWorkspace.headline.assignedWorkCount }} assigned item{{ staffWorkspace.headline.assignedWorkCount === 1 ? '' : 's' }}
                    </span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="item in workspaceCards"
                        :key="item.key"
                        :href="item.href || '#'"
                        :class="['rounded-[1.7rem] border bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-[0_16px_36px_rgba(0,54,41,0.09)]', operationTone(item.accent).ring]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <span :class="['inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]', operationTone(item.accent).badge]">
                                {{ item.label }}
                            </span>
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#f5f8f6] text-[#003e32]">
                                <svg v-if="iconForKey(item.key) === 'document'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                    <path d="M14 3v5h5" />
                                </svg>
                                <svg v-else-if="iconForKey(item.key) === 'refresh'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12a9 9 0 0 1-15.5 6.36L3 16" />
                                    <path d="M3 12A9 9 0 0 1 18.5 5.64L21 8" />
                                    <path d="M8 16H3v5" />
                                    <path d="M16 8h5V3" />
                                </svg>
                                <svg v-else-if="iconForKey(item.key) === 'chat'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                </svg>
                                <svg v-else-if="iconForKey(item.key) === 'spark'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9Z" />
                                </svg>
                                <svg v-else-if="iconForKey(item.key) === 'folder'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                </svg>
                                <svg v-else-if="iconForKey(item.key) === 'wallet'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 7H4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z" />
                                    <path d="M16 13h.01" />
                                    <path d="M6 7V5a2 2 0 0 1 2-2h10" />
                                </svg>
                                <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h7v7H4z" />
                                    <path d="M13 4h7v7h-7z" />
                                    <path d="M4 13h7v7H4z" />
                                    <path d="M13 13h7v7h-7z" />
                                </svg>
                            </span>
                        </div>
                        <p :class="['mt-4 text-[2.2rem] font-semibold leading-none', operationTone(item.accent).number]">
                            {{ formatNumber(item.count) }}
                        </p>
                        <span class="mt-5 inline-flex items-center text-sm font-bold text-[#003e32]">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14" />
                                <path d="m12 5 7 7-7 7" />
                            </svg>
                        </span>
                    </Link>
                </div>
            </article>

            <article class="rounded-[2rem] border border-[#dfe7e2] bg-[linear-gradient(180deg,#f8fbf8,#eef5ef)] p-7 shadow-[0_10px_36px_rgba(0,54,41,0.05)]">
                <p class="text-[0.78rem] font-black uppercase tracking-[0.14em] text-[#6c7a74]">Priority Focus</p>
                <div v-if="topOperation" class="mt-4">
                    <h3 class="text-[1.5rem] font-semibold text-[#14202c]">{{ topOperation.label }}</h3>
                    <div class="mt-6 rounded-[1.7rem] bg-white px-5 py-5 shadow-[inset_0_0_0_1px_rgba(217,226,220,0.9)]">
                        <p class="text-sm font-bold uppercase tracking-[0.08em] text-[#60706b]">Current count</p>
                        <p class="mt-2 text-[2.8rem] font-semibold leading-none text-[#003e32]">{{ formatNumber(topOperation.count) }}</p>
                    </div>
                    <div class="mt-6 space-y-3">
                        <Link v-if="topOperation.href" :href="topOperation.href" class="inline-flex w-full items-center justify-center gap-3 rounded-[1.4rem] bg-[#003e32] px-5 py-3.5 text-sm font-extrabold text-white transition hover:bg-[#0b5645]">
                            <span>{{ topOperation.label }}</span>
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14" />
                                <path d="m12 5 7 7-7 7" />
                            </svg>
                        </Link>
                        <Link :href="dashboard.actions.createMembershipApplicationUrl" class="inline-flex w-full items-center justify-center rounded-[1.4rem] border border-[#cfd9d3] bg-white px-5 py-3.5 text-sm font-bold text-[#26413a] transition hover:bg-[#f5f8f6]">
                            New Membership Application
                        </Link>
                    </div>
                </div>
                <div v-else class="mt-4 rounded-[1.5rem] border border-dashed border-[#d8dfdb] bg-white px-5 py-10 text-center text-sm text-[#6c757d]">
                    No staff operations available.
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-[2rem] border border-[#e4e9e6] bg-white p-7 shadow-[0_10px_36px_rgba(0,54,41,0.05)]">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-[1.15rem] font-semibold text-[#14202c]">Today's Actions</h3>
                    <span class="text-[1rem] font-bold text-[#57736a]">
                        {{ formatNumber(workspaceCards.find((card) => card.key === 'today_actions')?.count || 0) }} total
                    </span>
                </div>

                <div v-if="staffWorkspace.recentActions.length" class="mt-8 overflow-x-auto">
                    <table class="min-w-full text-left">
                        <thead class="border-b border-[#e2e7e4] text-[0.82rem] font-bold uppercase tracking-[0.08em] text-[#344654]">
                            <tr>
                                <th class="px-5 py-4">Module</th>
                                <th class="px-5 py-4">Activity</th>
                                <th class="px-5 py-4">Record</th>
                                <th class="px-5 py-4 text-right">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf1ee]">
                            <tr v-for="action in staffWorkspace.recentActions" :key="action.id" class="transition hover:bg-[#f9fbfa]">
                                <td class="px-5 py-5">
                                    <div class="flex items-center gap-4">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e8ebea] text-sm font-bold text-[#003e32]">
                                            {{ initials(action.module) }}
                                        </span>
                                        <span class="text-[1.05rem] font-semibold text-[#12202b]">{{ action.module }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-5 text-[1.05rem] text-[#364754]">{{ action.description }}</td>
                                <td class="px-5 py-5 text-[1.05rem] text-[#364754]">{{ action.subjectLabel || '-' }}</td>
                                <td class="px-5 py-5 text-right text-[1.05rem] text-[#364754]">{{ action.createdAt }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="mt-8 rounded-[1.5rem] border border-dashed border-[#d8dfdb] bg-[#f8faf9] px-5 py-10 text-center text-sm text-[#6c757d]">
                    No staff actions recorded yet for today.
                </div>
            </article>

            <div class="space-y-6">
                <article class="rounded-[2rem] border border-[#e4e9e6] bg-white p-7 shadow-[0_10px_36px_rgba(0,54,41,0.05)]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-[1.15rem] font-semibold text-[#14202c]">Mobile Support Queue</h3>
                        </div>
                        <span class="rounded-full bg-[#eef5ef] px-4 py-2 text-sm font-bold text-[#335043]">
                            {{ formatNumber(mobileSupportCards.reduce((sum, card) => sum + Number(card.count || 0), 0)) }} items
                        </span>
                    </div>

                    <div class="mt-5 space-y-4">
                        <Link
                            v-for="item in mobileSupportCards"
                            :key="item.key"
                            :href="item.href || '#'"
                            class="block rounded-[1.4rem] border border-[#e1e7e3] bg-white px-5 py-4 transition hover:-translate-y-0.5 hover:shadow-[0_16px_36px_rgba(0,54,41,0.09)]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#f5f8f6] text-[#003e32]">
                                        <svg v-if="iconForKey(item.key) === 'document'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                            <path d="M14 3v5h5" />
                                        </svg>
                                        <svg v-else-if="iconForKey(item.key) === 'refresh'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 12a9 9 0 0 1-15.5 6.36L3 16" />
                                            <path d="M3 12A9 9 0 0 1 18.5 5.64L21 8" />
                                            <path d="M8 16H3v5" />
                                            <path d="M16 8h5V3" />
                                        </svg>
                                        <svg v-else-if="iconForKey(item.key) === 'chat'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                        </svg>
                                        <svg v-else-if="iconForKey(item.key) === 'spark'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9Z" />
                                        </svg>
                                        <svg v-else-if="iconForKey(item.key) === 'folder'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                        </svg>
                                        <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h7v7H4z" />
                                            <path d="M13 4h7v7h-7z" />
                                            <path d="M4 13h7v7H4z" />
                                            <path d="M13 13h7v7h-7z" />
                                        </svg>
                                    </span>
                                    <p class="text-[1.02rem] font-semibold text-[#14202c]">{{ item.label }}</p>
                                </div>
                                <span :class="['rounded-full px-3 py-1 text-sm font-black', operationTone(item.accent).badge]">
                                    {{ formatNumber(item.count) }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-[#e4e9e6] bg-white p-7 shadow-[0_10px_36px_rgba(0,54,41,0.05)]">
                    <h3 class="text-[1.15rem] font-semibold text-[#14202c]">Assigned Work Focus</h3>

                    <div class="mt-8 rounded-[1.7rem] bg-[#f4f6f5] px-5 py-5">
                        <p class="text-sm font-bold uppercase tracking-[0.08em] text-[#60706b]">Assigned work total</p>
                        <p class="mt-2 text-[2.6rem] font-semibold leading-none text-[#003e32]">{{ formatNumber(staffWorkspace.headline.assignedWorkCount) }}</p>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div
                            v-for="item in workspaceCards.filter((card) => card.key !== 'today_actions')"
                            :key="item.key"
                            class="rounded-[1.4rem] border border-[#e1e7e3] bg-white px-5 py-4"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#f5f8f6] text-[#003e32]">
                                        <svg v-if="iconForKey(item.key) === 'document'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                            <path d="M14 3v5h5" />
                                        </svg>
                                        <svg v-else-if="iconForKey(item.key) === 'refresh'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 12a9 9 0 0 1-15.5 6.36L3 16" />
                                            <path d="M3 12A9 9 0 0 1 18.5 5.64L21 8" />
                                            <path d="M8 16H3v5" />
                                            <path d="M16 8h5V3" />
                                        </svg>
                                        <svg v-else-if="iconForKey(item.key) === 'chat'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                        </svg>
                                        <svg v-else-if="iconForKey(item.key) === 'folder'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                        </svg>
                                        <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h7v7H4z" />
                                            <path d="M13 4h7v7h-7z" />
                                            <path d="M4 13h7v7H4z" />
                                            <path d="M13 13h7v7h-7z" />
                                        </svg>
                                    </span>
                                    <p class="text-[1.02rem] font-semibold text-[#14202c]">{{ item.label }}</p>
                                </div>
                                <span class="text-[1.2rem] font-bold text-[#003e32]">{{ formatNumber(item.count) }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <Link
            :href="dashboard.actions.createMembershipApplicationUrl"
            class="fixed bottom-8 right-8 z-50 flex h-[76px] w-[76px] items-center justify-center rounded-full bg-[#003e32] text-white shadow-[0_18px_40px_rgba(0,62,50,0.28)] transition hover:scale-105 hover:bg-[#0b5645] focus:outline-none focus:ring-4 focus:ring-[#b8d9cf]"
        >
            <svg viewBox="0 0 24 24" class="h-9 w-9" fill="none" stroke="currentColor" stroke-width="2">
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
                                    : 'Recent farmers'
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
