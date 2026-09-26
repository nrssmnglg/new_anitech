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
const exportPreviewUrl = computed(() => {
    const url = new URL(buildExportUrl(exportFormat.value));
    url.searchParams.set('preview', '1');
    return url.toString();
});
const selectedExportColumnLabels = computed(() => availableExportColumns.filter((column) => selectedExportColumns.includes(column.value)).map((column) => column.label));

const activeCount = computed(() => props.summary.new + props.summary.inProgress + props.summary.escalated);

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
        return 'bg-[#ecfdf5] text-[#059669] border-[#a7f3d0]';
    }

    if (status === 'Resolved') {
        return 'bg-[#f1f5f9] text-[#475569] border-[#cbd5e1]';
    }

    if (status === 'Escalated') {
        return 'bg-[#fff1f2] text-[#e11d48] border-[#fecdd3]';
    }

    return 'bg-[#fffbeb] text-[#d97706] border-[#fde68a]';
}

function statusDotTone(status) {
    if (status === 'In Progress') return 'bg-[#10b981]';
    if (status === 'Resolved') return 'bg-[#64748b]';
    if (status === 'Escalated') return 'bg-[#f43f5e]';
    return 'bg-[#f59e0b]';
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
    const diffMinutes = Math.max(0, Math.floor((now.getTime() - date.getTime()) / (1000 * 60)));
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);
    const primary = date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });

    let secondary = 'Just now';

    if (diffMinutes >= 1 && diffMinutes < 60) {
        secondary = `${diffMinutes}m ago`;
    } else if (diffHours >= 1 && diffHours < 24) {
        secondary = `${diffHours}h ago`;
    } else if (diffDays >= 1) {
        secondary = `${diffDays}d ago`;
    }

    return {
        primary,
        secondary,
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
                                <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Communication</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Farmer Inquiries</h1>
                        </div>
                    </div>

                    <!-- Summary Stat Pills -->
                    <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                        <article class="px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Total</p>
                            <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.total }}</p>
                        </article>
                        <article class="border-x border-white/[0.08] px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">New</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-[#fbbf24]">{{ summary.new }}</p>
                        </article>
                        <article class="border-r border-white/[0.08] px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">In Progress</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-[#7ddfb8]">{{ summary.inProgress }}</p>
                        </article>
                        <article class="border-r border-white/[0.08] px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Resolved</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-white/90">{{ summary.resolved }}</p>
                        </article>
                        <article class="px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Escalated</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-[#f87171]">{{ summary.escalated }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Filters Bar -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-[1fr_1fr_1.5fr_auto] xl:items-end">
                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select
                            v-model="form.status"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option v-for="option in filterOptions.statuses" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Year</span>
                        <select
                            v-model="form.year"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option value="">All years</option>
                            <option v-for="option in filterOptions.years" :key="option.value" :value="String(option.value)">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Barangay</span>
                        <select
                            v-model="form.barangay_id"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option value="">All Locations</option>
                            <option v-for="option in filterOptions.barangays" :key="option.value" :value="String(option.value)">{{ option.label }}</option>
                        </select>
                    </label>

                    <div class="flex items-center gap-2 xl:justify-end">
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97] disabled:opacity-60"
                            :disabled="applying"
                            @click="applyFilters"
                        >
                            {{ applying ? 'Applying...' : 'Apply' }}
                        </button>
                        <button
                            v-if="form.status || form.year || form.barangay_id"
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-3.5 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5] disabled:opacity-60"
                            :disabled="applying"
                            @click="resetFilters"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </section>

            <!-- Inquiry Queue Card -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#edf2ee] px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Inquiry Queue</h2>
                        <span class="inline-flex items-center gap-1 rounded-full bg-[#dcfce7] px-2.5 py-0.5 text-[0.62rem] font-bold text-[#15803d]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#22c55e]"></span>
                            {{ activeCount }} Active
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white text-[#475569] transition hover:bg-[#f4f7f5]"
                            :title="compactMode ? 'Comfortable view' : 'Compact view'"
                            :aria-label="compactMode ? 'Comfortable view' : 'Compact view'"
                            @click="compactMode = !compactMode"
                        >
                            <svg v-if="compactMode" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                            <svg v-else viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 6h16M4 12h16M4 18h16M7 8.5h10M7 14.5h10" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-[#dbe3dd] bg-white px-3 text-[0.68rem] font-semibold text-[#0f6b45] transition hover:bg-[#f4f7f5]"
                            @click="openExportModal('pdf')"
                        >
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v8m-3.5-3.5L12 13l3.5-3.5M5 15.5v1A2.5 2.5 0 007.5 19h9a2.5 2.5 0 002.5-2.5v-1" />
                            </svg>
                            <span>Export</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#e8eeea] bg-gradient-to-b from-[#f8faf9] to-[#f3f6f4] text-left text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                                <th class="px-5 py-3"><button type="button" class="font-bold hover:text-[#0f172a]" @click="toggleSort('farmer')">Farmer Details</button></th>
                                <th class="px-5 py-3">Inquiry &amp; Subject</th>
                                <th class="px-5 py-3"><button type="button" class="font-bold hover:text-[#0f172a]" @click="toggleSort('status')">Status</button></th>
                                <th class="px-5 py-3 text-center"><button type="button" class="font-bold hover:text-[#0f172a]" @click="toggleSort('responses')">Replies</button></th>
                                <th class="px-5 py-3"><button type="button" class="font-bold hover:text-[#0f172a]" @click="toggleSort('submittedAt')">Submitted</button></th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="query in sortedQueries"
                                :key="query.id"
                                class="border-b border-[#edf2ee] transition-all duration-150 hover:bg-[#f6faf8]"
                            >
                                <td class="px-5 align-middle" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[0.65rem] font-bold text-[#0f6b45]">
                                            {{ farmerInitials(query.farmer.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-[#0f172a]">{{ query.farmer.name }}</p>
                                            <p class="mt-0.5 text-[0.62rem] font-medium text-[#64748b]">{{ query.farmer.code }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 align-middle" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                                    <p class="font-bold text-[#0f172a]">{{ query.subject }}</p>
                                    <p class="mt-0.5 max-w-sm truncate text-[0.65rem] text-[#64748b]">{{ query.messagePreview }}</p>
                                </td>

                                <td class="px-5 align-middle" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[0.62rem] font-bold" :class="statusTone(query.status)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusDotTone(query.status)"></span>
                                        {{ query.status }}
                                    </span>
                                </td>

                                <td class="px-5 align-middle text-center" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                                    <span
                                        class="inline-flex h-7 min-w-7 items-center justify-center gap-1 rounded-lg px-2 text-[0.65rem] font-bold"
                                        :class="query.responsesCount > 0 ? 'bg-[#e6f5ec] text-[#0f6b45]' : 'bg-[#f1f5f9] text-[#94a3b8]'"
                                    >
                                        <svg viewBox="0 0 20 20" class="h-3 w-3" fill="currentColor"><path fill-rule="evenodd" d="M10 2c-4.418 0-8 3.134-8 7 0 1.76.743 3.37 1.97 4.6-.097 1.016-.417 2.13-.771 2.966-.079.186.074.394.276.353 1.258-.255 2.55-.83 3.407-1.309.957.25 1.993.39 3.118.39 4.418 0 8-3.134 8-7s-3.582-7-8-7Z" clip-rule="evenodd"/></svg>
                                        <span>{{ query.responsesCount }}</span>
                                    </span>
                                </td>

                                <td class="px-5 align-middle" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                                    <p class="font-semibold text-[#0f172a]">{{ formatSubmitted(query.submittedAt).primary }}</p>
                                    <p class="text-[0.62rem] text-[#94a3b8]">{{ formatSubmitted(query.submittedAt).secondary }}</p>
                                </td>

                                <td class="px-5 align-middle text-right" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="query.quickActions?.canMarkComplete"
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#15803d] transition-all duration-150 hover:bg-[#dcfce7]"
                                            title="Mark complete"
                                            aria-label="Mark complete"
                                            @click="submitQuickAction(query, 'mark_complete')"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2">
                                                <path d="m5 12 4.2 4.2L19 6.5" />
                                            </svg>
                                        </button>

                                        <button
                                            v-if="query.quickActions?.canForwardToAdmin"
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#d97706] transition-all duration-150 hover:bg-[#fef3c7]"
                                            title="Forward to admin"
                                            aria-label="Forward to admin"
                                            @click="openQuickActionDialog(query, 'forward_to_admin')"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 5h5v5m-5-5L5 19m14-6v5h-5" />
                                            </svg>
                                        </button>

                                        <Link
                                            :href="query.actions.show"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#014d3c] transition-all duration-150 hover:bg-[#e6f5ec]"
                                            title="Review inquiry thread"
                                            aria-label="Review inquiry thread"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6Z" />
                                                <circle cx="12" cy="12" r="2.5" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="queries.data.length === 0">
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-xs flex-col items-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0faf5]">
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-[#334155]">No farmer inquiries found</p>
                                        <p class="mt-1 text-xs text-[#94a3b8]">No inquiries match your current filter settings.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Numbered Pill Pagination -->
                <div v-if="queries.last_page > 1" class="flex flex-col gap-2 border-t border-[#edf2ee] bg-gradient-to-b from-[#fbfcfb] to-[#f8faf9] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.68rem] text-[#64748b]">Showing <span class="font-semibold text-[#334155]">{{ queries.from || 0 }}</span>–<span class="font-semibold text-[#334155]">{{ queries.to || 0 }}</span> of <span class="font-semibold text-[#334155]">{{ queries.total }}</span></p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template v-for="link in queries.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] text-[#cbd5e1]" v-html="link.label" />
                            <Link v-else :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] font-semibold transition-all duration-200" :class="link.active ? 'bg-[#014d3c] text-white shadow-sm' : 'text-[#64748b] hover:bg-[#f1f5f9]'" preserve-scroll preserve-state v-html="link.label" />
                        </template>
                    </div>
                </div>
            </section>
        </div>

        <!-- Export Modal -->
        <div v-if="exportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/50 p-4 backdrop-blur-sm" @click.self="closeExportModal">
            <section class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl border border-[#dde4de] bg-white p-6 shadow-2xl">
                <div class="flex flex-col gap-3 border-b border-[#edf2ee] pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[0.6rem] font-bold uppercase tracking-[0.14em] text-[#0f6b45]">Export Inquiries</p>
                        <h2 class="mt-0.5 text-lg font-bold text-[#0f172a]">Download Inquiries Report ({{ exportFormat.toUpperCase() }})</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-150"
                            :class="exportFormat === 'pdf' ? 'bg-[#014d3c] text-white shadow-sm' : 'border border-[#dbe3dd] bg-white text-[#64748b] hover:bg-[#f4f7f5]'"
                            @click="exportFormat = 'pdf'"
                        >
                            PDF
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all duration-150"
                            :class="exportFormat === 'xlsx' ? 'bg-[#014d3c] text-white shadow-sm' : 'border border-[#dbe3dd] bg-white text-[#64748b] hover:bg-[#f4f7f5]'"
                            @click="exportFormat = 'xlsx'"
                        >
                            Excel
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dbe3dd] text-[#64748b] transition hover:bg-[#f4f7f5]"
                            @click="closeExportModal"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <p class="text-xs text-[#64748b]">{{ selectedExportColumns.length }} column{{ selectedExportColumns.length === 1 ? '' : 's' }} selected</p>
                    <button type="button" class="text-xs font-bold text-[#0f6b45] hover:underline" @click="refreshQueue">
                        Refresh
                    </button>
                </div>

                <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                    <label
                        v-for="column in availableExportColumns"
                        :key="column.value"
                        class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-[#e2eae4] bg-[#f9fbfa] px-3.5 py-2.5 text-xs font-semibold text-[#0f172a] transition hover:border-[#cbd8cf] hover:bg-[#f4f7f5]"
                    >
                        <input
                            :checked="selectedExportColumns.includes(column.value)"
                            type="checkbox"
                            class="h-4 w-4 rounded border-[#0f5b46]/30 text-[#0f5b46] focus:ring-[#0f5b46]/20"
                            @change="toggleExportColumn(column.value)"
                        >
                        <span>{{ column.label }}</span>
                    </label>
                </div>

                <div class="mt-4 rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-xs font-bold text-[#0f172a]">Report Preview</p>
                        <a :href="exportPreviewUrl" target="_blank" rel="noopener" class="text-xs font-bold text-[#0f6b45] hover:underline">Open in new tab ↗</a>
                    </div>
                    <iframe v-if="exportFormat === 'pdf'" :src="exportPreviewUrl" title="Report preview" class="h-60 w-full rounded-lg border border-[#dbe3dd] bg-white"></iframe>
                    <div v-else class="rounded-lg border border-[#dbe3dd] bg-white p-4 text-xs text-[#64748b]">
                        <p class="font-bold text-[#0f172a]">Excel Export</p>
                        <p class="mt-1">Generated spreadsheet will include columns: {{ selectedExportColumnLabels.join(', ') }}</p>
                    </div>
                </div>

                <div class="mt-5 flex gap-2 sm:justify-end">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-4 text-xs font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]"
                        @click="closeExportModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a]"
                        @click="startExport"
                    >
                        Download {{ exportFormat.toUpperCase() }}
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
