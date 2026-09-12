<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import QuickActionDialog from '../../../Components/Admin/QuickActionDialog.vue';
import { usePersistentObject, readStoredValue, persistValue } from '../../../Composables/usePersistentUiState';

const availableExportColumns = [
    { value: 'farmer_code', label: 'Farmer Code' },
    { value: 'full_name', label: 'Farmer Name' },
    { value: 'barangay', label: 'Barangay' },
    { value: 'subject', label: 'Subject' },
    { value: 'message', label: 'Inquiry Message' },
    { value: 'status', label: 'Status' },
    { value: 'responses_count', label: 'Responses' },
    { value: 'submitted_at', label: 'Submitted At' },
];

const props = defineProps({
    queries: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const form = reactive({
    status: props.filters.status ?? '',
    year: props.filters.year ? String(props.filters.year) : '',
    barangay_id: props.filters.barangay_id ? String(props.filters.barangay_id) : '',
});
const compactMode = ref(Boolean(readStoredValue('staff.queries.compact-mode', false)));
const sortKey = ref(readStoredValue('staff.queries.sort-key', 'submittedAt'));
const sortDirection = ref(readStoredValue('staff.queries.sort-direction', 'desc'));

usePersistentObject('staff.queries.filters', form);
persistValue('staff.queries.compact-mode', compactMode);
persistValue('staff.queries.sort-key', sortKey);
persistValue('staff.queries.sort-direction', sortDirection);

const applying = ref(false);
const exportModalOpen = ref(false);
const quickActionDialog = ref({
    open: false,
    action: '',
    title: '',
    noteLabel: '',
    submitLabel: '',
});
const quickActionForm = useForm({
    module: '',
    record: '',
    action: '',
    note: '',
});
const exportFormat = ref('pdf');
const selectedExportColumns = reactive([
    'farmer_code',
    'full_name',
    'barangay',
    'subject',
    'message',
    'status',
    'responses_count',
    'submitted_at',
]);

const progressRate = computed(() => {
    const openTotal = props.summary.new + props.summary.inProgress + props.summary.resolved + props.summary.escalated;

    if (!openTotal) {
        return 0;
    }

    return Math.round((props.summary.resolved / openTotal) * 100);
});

const activeCount = computed(() => props.summary.new + props.summary.inProgress + props.summary.escalated);
const inProgressRate = computed(() => {
    const openTotal = props.summary.new + props.summary.inProgress + props.summary.resolved + props.summary.escalated;

    if (!openTotal) {
        return 0;
    }

    return Math.round((props.summary.inProgress / openTotal) * 100);
});
const escalatedRate = computed(() => {
    const openTotal = props.summary.new + props.summary.inProgress + props.summary.resolved + props.summary.escalated;

    if (!openTotal) {
        return 0;
    }

    return Math.round((props.summary.escalated / openTotal) * 100);
});
const sortedQueries = computed(() => {
    const items = [...props.queries.data];

    items.sort((left, right) => {
        const direction = sortDirection.value === 'asc' ? 1 : -1;

        if (sortKey.value === 'farmer') {
            return String(left.farmer.name || '').localeCompare(String(right.farmer.name || '')) * direction;
        }

        if (sortKey.value === 'status') {
            return String(left.status || '').localeCompare(String(right.status || '')) * direction;
        }

        if (sortKey.value === 'responses') {
            return ((Number(left.responsesCount || 0) - Number(right.responsesCount || 0)) * direction);
        }

        return String(left.submittedAt || '').localeCompare(String(right.submittedAt || '')) * direction;
    });

    return items;
});

function applyFilters() {
    if (applying.value) {
        return;
    }

    applying.value = true;

    router.get(props.urls.index, {
        status: form.status || undefined,
        year: form.year || undefined,
        barangay_id: form.barangay_id || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            applying.value = false;
        },
    });
}

function resetFilters() {
    if (applying.value) {
        return;
    }

    form.status = '';
    form.year = '';
    form.barangay_id = '';
    applyFilters();
}

function refreshQueue() {
    if (applying.value) {
        return;
    }

    applying.value = true;

    router.get(props.urls.index, {
        status: form.status || undefined,
        year: form.year || undefined,
        barangay_id: form.barangay_id || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            applying.value = false;
        },
    });
}

function buildExportUrl(format) {
    const url = new URL(props.urls.export, window.location.origin);

    const params = {
        status: form.status || '',
        year: form.year || '',
        barangay_id: form.barangay_id || '',
        format,
    };

    Object.entries(params).forEach(([key, value]) => {
        if (value) {
            url.searchParams.set(key, value);
        }
    });

    selectedExportColumns.forEach((column) => {
        url.searchParams.append('columns[]', column);
    });

    return url.toString();
}

function toggleExportColumn(column) {
    const index = selectedExportColumns.indexOf(column);

    if (index >= 0) {
        if (selectedExportColumns.length === 1) {
            return;
        }

        selectedExportColumns.splice(index, 1);
        return;
    }

    selectedExportColumns.push(column);
}

function openExportModal(format = 'pdf') {
    exportFormat.value = format;
    exportModalOpen.value = true;
}

function closeExportModal() {
    exportModalOpen.value = false;
}

function startExport() {
    window.location.href = buildExportUrl(exportFormat.value);
    closeExportModal();
}

function statusTone(status) {
    if (status === 'In Progress') {
        return 'bg-[#e4f4c8] text-[#4b7517]';
    }

    if (status === 'Resolved') {
        return 'bg-[#e9eeef] text-[#5f6d73]';
    }

    if (status === 'Escalated') {
        return 'bg-[#ffe4e7] text-[#cf3657]';
    }

    return 'bg-[#fff1cd] text-[#c07a00]';
}

function statTone(type) {
    if (type === 'new') {
        return {
            iconWrap: 'bg-[#fff3df] text-[#d47500]',
            value: 'text-[#bb5a00]',
            meta: 'bg-[#fff1cd] text-[#b56a00]',
        };
    }

    if (type === 'in_progress') {
        return {
            iconWrap: 'bg-[#eef8dd] text-[#50761b]',
            value: 'text-[#50761b]',
            meta: 'text-[#50761b]',
        };
    }

    if (type === 'resolved') {
        return {
            iconWrap: 'bg-[#eceff1] text-[#52626b]',
            value: 'text-[#22343b]',
            meta: 'text-[#52626b]',
        };
    }

    if (type === 'escalated') {
        return {
            iconWrap: 'bg-[#ffe4e7] text-[#cf3657]',
            value: 'text-[#cf3657]',
            meta: 'text-[#cf3657]',
        };
    }

    return {
        iconWrap: 'bg-[#f3f6f5] text-[#0f5b46]',
        value: 'text-[#111827]',
        meta: 'text-[#50761b]',
    };
}

function farmerInitials(name) {
    return String(name || 'Farmer')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('') || 'FM';
}

function formatSubmitted(value) {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return {
            primary: value,
            secondary: '',
        };
    }

    const now = new Date();
    const diffHours = Math.max(0, Math.round((now.getTime() - date.getTime()) / (1000 * 60 * 60)));
    const primary = date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });

    return {
        primary,
        secondary: diffHours < 24 ? `${Math.max(1, diffHours)} hour${diffHours === 1 ? '' : 's'} ago` : `${Math.round(diffHours / 24)} day${Math.round(diffHours / 24) === 1 ? '' : 's'} ago`,
    };
}

function submitQuickAction(query, action) {
    quickActionForm.transform(() => ({
        module: 'inquiries',
        record: query.recordKey,
        action,
        note: '',
    })).post(props.urls.quickAction, {
        preserveScroll: true,
        onSuccess: closeQuickActionDialog,
    });
}

function openQuickActionDialog(query, action) {
    quickActionForm.module = 'inquiries';
    quickActionForm.record = query.recordKey;
    quickActionForm.action = action;
    quickActionForm.note = '';
    quickActionDialog.value = {
        open: true,
        action,
        title: 'Forward to admin',
        noteLabel: 'Forwarding note',
        submitLabel: 'Forward now',
    };
}

function closeQuickActionDialog() {
    quickActionDialog.value.open = false;
    quickActionForm.reset();
}

function toggleSort(key) {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortKey.value = key;
    sortDirection.value = key === 'farmer' || key === 'status' ? 'asc' : 'desc';
}
</script>

<template>
    <Head title="Farmer Inquiries" />

    <AdminLayout title="Farmer Inquiries">
        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">Communication</p>
                <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">Farmer Inquiries</h1>
            </section>

            <section class="grid grid-cols-2 gap-2 lg:grid-cols-5">
                <article class="rounded-lg border border-[#d9e2dc] bg-white p-3">
                    <div class="flex items-start justify-between gap-4">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-md" :class="statTone('total').iconWrap">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                                <path d="M5 6.5h14v11H5z" />
                                <path d="M8 10.5h8M8 14.5h5" />
                            </svg>
                        </span>
                        <span class="text-xs font-bold text-[#50761b]">{{ progressRate }}% resolved</span>
                    </div>
                    <p class="mt-2 text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#334155]">Total Inquiries</p>
                    <div class="mt-2 flex items-end gap-2">
                        <h2 class="text-xl font-semibold leading-none text-[#111827]">{{ summary.total }}</h2>
                    </div>
                </article>

                <article class="rounded-lg border border-[#eadfc9] bg-white p-3">
                    <div class="flex items-start justify-between gap-4">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-md" :class="statTone('new').iconWrap">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                                <circle cx="12" cy="12" r="7" />
                                <path d="M9.5 12h5" />
                            </svg>
                        </span>
                        <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]" :class="statTone('new').meta">Incoming</span>
                    </div>
                    <p class="mt-2 text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#334155]">New Tickets</p>
                    <div class="mt-2 flex items-end gap-2">
                        <h2 class="text-xl font-semibold leading-none" :class="statTone('new').value">{{ summary.new }}</h2>
                    </div>
                </article>

                <article class="rounded-lg border border-[#dce7cf] bg-white p-3">
                    <div class="flex items-start justify-between gap-4">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-md" :class="statTone('in_progress').iconWrap">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                                <path d="M5 7.5h14v9H8l-3 3v-12z" />
                            </svg>
                        </span>
                        <span class="text-xs font-bold" :class="statTone('in_progress').meta">{{ inProgressRate }}% open</span>
                    </div>
                    <p class="mt-2 text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#334155]">In Progress</p>
                    <div class="mt-2 flex items-end gap-2">
                        <h2 class="text-xl font-semibold leading-none" :class="statTone('in_progress').value">{{ summary.inProgress }}</h2>
                    </div>
                </article>

                <article class="rounded-lg border border-[#d9e2dc] bg-white p-3">
                    <div class="flex items-start justify-between gap-4">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-md" :class="statTone('resolved').iconWrap">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                                <path d="M8 6.5h8v11H8z" />
                                <path d="M10 9.5h4M10 12.5h4" />
                            </svg>
                        </span>
                        <span class="text-xs font-bold text-[#52626b]">{{ progressRate }}% rate</span>
                    </div>
                    <p class="mt-2 text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#334155]">Resolved</p>
                    <div class="mt-2 flex items-end gap-2">
                        <h2 class="text-xl font-semibold leading-none" :class="statTone('resolved').value">{{ summary.resolved }}</h2>
                    </div>
                </article>

                <article class="rounded-lg border border-[#f0d4db] bg-white p-3">
                    <div class="flex items-start justify-between gap-4">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-md" :class="statTone('escalated').iconWrap">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                                <path d="M12 4v10" />
                                <path d="m8 10 4 4 4-4" />
                                <path d="M5 19h14" />
                            </svg>
                        </span>
                        <span class="text-xs font-bold" :class="statTone('escalated').meta">{{ escalatedRate }}% escalated</span>
                    </div>
                    <p class="mt-2 text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#334155]">Escalated</p>
                    <div class="mt-2 flex items-end gap-2">
                        <h2 class="text-xl font-semibold leading-none" :class="statTone('escalated').value">{{ summary.escalated }}</h2>
                    </div>
                </article>

            </section>

            <section class="rounded-lg border border-[#d9e2dc] bg-white p-3">
                <div class="grid gap-3 xl:grid-cols-[auto_1fr_auto] xl:items-end">
                    <div class="flex items-center gap-2 text-[#334155]">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                            <path d="M4 7h16M7 12h10M10 17h4" />
                        </svg>
                        <span class="text-xs font-semibold">Filters</span>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                        <label class="space-y-1">
                            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#52626b]">Status</span>
                            <select v-model="form.status" class="h-9 w-full rounded-md border border-[#d9e2dc] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#0f5b46]">
                                <option v-for="option in filterOptions.statuses" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>

                        <label class="space-y-1">
                            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#52626b]">Year</span>
                            <select v-model="form.year" class="h-9 w-full rounded-md border border-[#d9e2dc] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#0f5b46]">
                                <option value="">All years</option>
                                <option v-for="option in filterOptions.years" :key="option.value" :value="String(option.value)">{{ option.label }}</option>
                            </select>
                        </label>

                        <label class="space-y-1 md:col-span-2 xl:col-span-1">
                            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#52626b]">Barangay</span>
                            <select v-model="form.barangay_id" class="h-9 w-full rounded-md border border-[#d9e2dc] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#0f5b46]">
                                <option value="">All Locations</option>
                                <option v-for="option in filterOptions.barangays" :key="option.value" :value="String(option.value)">{{ option.label }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" class="inline-flex h-9 items-center justify-center rounded-md bg-[#014d3c] px-4 text-xs font-semibold text-white transition hover:bg-[#013628] disabled:opacity-60" :disabled="applying" @click="applyFilters">
                            {{ applying ? 'Applying...' : 'Apply' }}
                        </button>
                        <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-[#d9e2dc] px-3 text-xs font-semibold text-[#52626b] transition hover:bg-[#e3e8e4] disabled:opacity-60" :disabled="applying" @click="resetFilters">
                            Clear
                        </button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-[#d9e2dc] bg-white">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#e6ece8] px-4 py-3">
                    <div class="flex items-center gap-3">
                        <h2 class="text-sm font-semibold text-[#0f172a]">Inquiry queue</h2>
                        <span class="rounded-full bg-[#c9f7df] px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#0f7d5a]">{{ activeCount }} Active</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-[#d9e2dc] bg-white text-[#334155] transition hover:bg-[#f4f7f5]"
                            :title="compactMode ? 'Comfortable rows' : 'Compact rows'"
                            :aria-label="compactMode ? 'Comfortable rows' : 'Compact rows'"
                            @click="compactMode = !compactMode"
                        >
                            <svg v-if="compactMode" viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <path d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                            <svg v-else viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <path d="M4 6h16" />
                                <path d="M4 12h16" />
                                <path d="M4 18h16" />
                                <path d="M7 8.5h10" opacity=".45" />
                                <path d="M7 14.5h10" opacity=".45" />
                            </svg>
                        </button>
                        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl text-[#22343b] transition hover:bg-[#f4f7f5]" @click="openExportModal('pdf')" aria-label="Export farmer inquiries">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <path d="M12 5v8" />
                                <path d="M8.5 9.5 12 13l3.5-3.5" />
                                <path d="M5 15.5v1A2.5 2.5 0 0 0 7.5 19h9a2.5 2.5 0 0 0 2.5-2.5v-1" />
                            </svg>
                        </button>
                        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl text-[#22343b] transition hover:bg-[#f4f7f5]" @click="resetFilters" :disabled="applying" aria-label="Clear inquiry filters">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <circle cx="12" cy="5" r="1.5" fill="currentColor" stroke="none" />
                                <circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none" />
                                <circle cx="12" cy="19" r="1.5" fill="currentColor" stroke="none" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="border-b border-[#e6ece8] text-left text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#334155]">
                                <th class="px-5 py-4 sm:px-6"><button type="button" class="font-black uppercase tracking-[0.08em]" @click="toggleSort('farmer')">Farmer Details</button></th>
                                <th class="px-5 py-4 sm:px-6">Inquiry &amp; Subject</th>
                                <th class="px-5 py-4 sm:px-6"><button type="button" class="font-black uppercase tracking-[0.08em]" @click="toggleSort('status')">Status</button></th>
                                <th class="px-5 py-4 sm:px-6"><button type="button" class="font-black uppercase tracking-[0.08em]" @click="toggleSort('responses')">Responses</button></th>
                                <th class="px-5 py-4 sm:px-6"><button type="button" class="font-black uppercase tracking-[0.08em]" @click="toggleSort('submittedAt')">Submitted</button></th>
                                <th class="px-5 py-4 text-right sm:px-6">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="query in sortedQueries" :key="query.id" class="border-b border-[#edf2ee] align-top transition hover:bg-[#fbfdfc]">
                                <td class="px-5 sm:px-6" :class="compactMode ? 'py-3' : 'py-5'">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#dff7e9] text-[0.65rem] font-semibold text-[#0f5b46]">
                                            {{ farmerInitials(query.farmer.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold leading-4 text-[#0f172a]">{{ query.farmer.name }}</p>
                                            <p class="mt-0.5 text-[0.65rem] text-[#52626b]">{{ query.farmer.code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 sm:px-6" :class="compactMode ? 'py-3' : 'py-5'">
                                    <p class="text-xs font-semibold leading-4 text-[#0f172a]">{{ query.subject }}</p>
                                    <p class="mt-0.5 max-w-[22rem] truncate text-[0.65rem] text-[#52626b]">{{ query.messagePreview }}</p>
                                </td>
                                <td class="px-5 sm:px-6" :class="compactMode ? 'py-3' : 'py-5'">
                                    <div class="space-y-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]" :class="statusTone(query.status)">
                                            {{ query.status }}
                                        </span>
                                        <div v-if="query.status === 'New'" class="block">
                                            <span class="inline-flex rounded-full bg-[#fff1cd] px-3 py-1 text-xs font-black uppercase tracking-[0.08em] text-[#c07a00]">
                                                Thread
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 sm:px-6" :class="compactMode ? 'py-3' : 'py-5'">
                                    <div class="flex items-center gap-2 text-sm font-bold text-[#0f172a]">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current text-[#52626b]" stroke-width="2">
                                            <path d="M5 7.5h14v9H8l-3 3v-12z" />
                                        </svg>
                                        <span>{{ query.responsesCount }}</span>
                                    </div>
                                </td>
                                <td class="px-5 sm:px-6" :class="compactMode ? 'py-3' : 'py-5'">
                                    <div class="text-sm text-[#0f172a]">
                                        <p class="font-semibold">{{ formatSubmitted(query.submittedAt).primary }}</p>
                                        <p class="mt-1 text-xs text-[#71808b]">{{ formatSubmitted(query.submittedAt).secondary }}</p>
                                        <div class="mt-2 space-y-1 text-[0.72rem] text-[#6c7772]">
                                            <p><span class="font-semibold">Last updated by:</span> {{ query.accountability?.lastUpdatedBy || 'System' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 text-right sm:px-6" :class="compactMode ? 'py-3' : 'py-5'">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button
                                            v-if="query.quickActions?.canMarkComplete"
                                            type="button"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[#cfe0d6] bg-[#eff7e8] text-[#486814] transition hover:bg-[#e5f1da]"
                                            title="Mark complete"
                                            aria-label="Mark complete"
                                            @click="submitQuickAction(query, 'mark_complete')"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2.2">
                                                <path d="m5 12 4.2 4.2L19 6.5" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="query.quickActions?.canForwardToAdmin"
                                            type="button"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[#d6dfda] bg-white text-[#36554a] transition hover:bg-[#f7faf8]"
                                            title="Forward to admin"
                                            aria-label="Forward to admin"
                                            @click="openQuickActionDialog(query, 'forward_to_admin')"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2">
                                                <path d="M14 5h5v5" />
                                                <path d="M10 14 19 5" />
                                                <path d="M19 13v5h-5" />
                                                <path d="M5 10 14 19" />
                                            </svg>
                                        </button>
                                        <Link
                                            :href="query.actions.show"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[#d9e2dc] bg-white text-[#014d3c] transition hover:bg-[#f4f7f5] hover:text-[#022f25]"
                                            title="Review inquiry"
                                            aria-label="Review inquiry"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2">
                                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                                <circle cx="12" cy="12" r="2.5" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="queries.data.length === 0">
                                <td colspan="6" class="px-6 py-14 text-center text-sm text-[#71808b]">
                                    No farmer inquiries have been recorded yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="text-sm text-[#52626b]">Showing {{ queries.from || 0 }}-{{ queries.to || 0 }} of {{ queries.total }} entries</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in queries.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-10 min-w-10 items-center justify-center rounded-2xl border border-[#d9e2dc] px-3 text-sm text-[#9aa6a1]" v-html="link.label" />
                            <Link
                                v-else
                                :href="link.url"
                                class="inline-flex h-10 min-w-10 items-center justify-center rounded-2xl border px-3 text-sm font-black transition"
                                :class="link.active ? 'border-[#014d3c] bg-[#014d3c] text-white' : 'border-[#d9e2dc] bg-white text-[#334155] hover:bg-[#f4f7f5]'"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </div>

        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/45 px-4 py-6" @click.self="closeExportModal">
            <section class="w-full max-w-6xl rounded-[28px] border border-[#dbe2de] bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.22)] sm:p-6">
                <div class="flex flex-col gap-4 border-b border-[#e4ebe7] pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Export Inquiries</p>
                        <h2 class="mt-1 text-xl font-bold text-[#1a2420]">Choose fields to include in {{ exportFormat === 'pdf' ? 'PDF' : 'Excel' }}</h2>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-2xl border px-4 py-2 text-sm font-black transition"
                            :class="exportFormat === 'pdf' ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#d7e0db] text-[#66756f] hover:bg-[#f5f8f6]'"
                            @click="exportFormat = 'pdf'"
                        >
                            PDF
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-2xl border px-4 py-2 text-sm font-black transition"
                            :class="exportFormat === 'xlsx' ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#d7e0db] text-[#66756f] hover:bg-[#f5f8f6]'"
                            @click="exportFormat = 'xlsx'"
                        >
                            Excel
                        </button>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-[#d7e0db] text-[#66756f] transition hover:bg-[#f5f8f6]" @click="closeExportModal">
                            <span class="text-lg leading-none">&times;</span>
                        </button>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between gap-4">
                    <p class="text-sm text-[#697772]">{{ selectedExportColumns.length }} column{{ selectedExportColumns.length === 1 ? '' : 's' }} selected</p>
                    <button type="button" class="text-sm font-bold text-[#014d3c] transition hover:underline" @click="refreshQueue">
                        Refresh list
                    </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <label
                        v-for="column in availableExportColumns"
                        :key="column.value"
                        class="flex items-center gap-3 rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420]"
                    >
                        <input
                            :checked="selectedExportColumns.includes(column.value)"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-[#003629] focus:ring-[#003629]"
                            @change="toggleExportColumn(column.value)"
                        >
                        <span class="font-medium">{{ column.label }}</span>
                    </label>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-5 py-3 text-sm font-bold text-[#697772] transition hover:bg-[#f4f7f5]" @click="closeExportModal">
                        Cancel
                    </button>
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl bg-[#003629] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]" @click="startExport">
                        Export {{ exportFormat === 'pdf' ? 'PDF' : 'Excel' }}
                    </button>
                </div>
            </section>
        </div>

        <QuickActionDialog
            :open="quickActionDialog.open"
            :title="quickActionDialog.title"
            :note-label="quickActionDialog.noteLabel"
            :submit-label="quickActionDialog.submitLabel"
            :note="quickActionForm.note"
            placeholder="Add context for the admin escalation."
            :processing="quickActionForm.processing"
            :error="quickActionForm.errors.note"
            @close="closeQuickActionDialog"
            @submit="quickActionForm.post(props.urls.quickAction, { preserveScroll: true, onSuccess: closeQuickActionDialog })"
            @update:note="quickActionForm.note = $event"
        />
    </AdminLayout>
</template>
