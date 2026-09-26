<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { readStoredValue, persistValue } from '../../../Composables/usePersistentUiState';
import QuickActionDialog from '../QuickActionDialog.vue';

const props = defineProps({
    applications: { type: Object, required: true },
    quickActionUrl: { type: String, required: true },
    compactMode: { type: Boolean, default: false },
});

defineEmits(['toggle-compact']);

const quickActionForm = useForm({
    module: '',
    record: '',
    action: '',
    note: '',
});

const quickActionDialog = ref({
    open: false,
    action: '',
    title: '',
    noteLabel: '',
    submitLabel: '',
});

const sortKey = ref(readStoredValue('staff.membership-applications.sort-key', 'submittedAt'));
const sortDirection = ref(readStoredValue('staff.membership-applications.sort-direction', 'desc'));

persistValue('staff.membership-applications.sort-key', sortKey);
persistValue('staff.membership-applications.sort-direction', sortDirection);

function statusBadge(value) {
    if (value === 'approved') return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    if (value === 'rejected') return 'bg-rose-50 text-rose-800 border-rose-200';
    return 'bg-amber-50 text-amber-800 border-amber-200';
}

function statusDot(value) {
    if (value === 'approved') return 'bg-emerald-500';
    if (value === 'rejected') return 'bg-rose-500';
    return 'bg-amber-500 animate-pulse';
}

function checklistTone(application) {
    if (application.payment.isSettled) return 'bg-emerald-600';
    if (application.documents.totalCount > 0) {
        const percent = Math.round((application.documents.verifiedCount / application.documents.totalCount) * 100);
        return percent >= 100 ? 'bg-emerald-600' : (percent >= 50 ? 'bg-amber-500' : 'bg-rose-500');
    }
    return 'bg-slate-300';
}

function checklistPercent(application) {
    if (!application.documents.totalCount) return 0;
    return Math.min(100, Math.round((application.documents.verifiedCount / application.documents.totalCount) * 100));
}

function farmerInitials(name) {
    return String(name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || '?';
}


function openQuickActionDialog(action) {
    quickActionDialog.value = action === 'request_correction'
        ? {
            open: true,
            action,
            title: 'Request Correction',
            noteLabel: 'Correction Notes',
            submitLabel: 'Send Correction Request',
        }
        : {
            open: true,
            action,
            title: 'Forward to Admin',
            noteLabel: 'Forwarding Note',
            submitLabel: 'Forward Now',
        };

    quickActionForm.note = '';
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

const sortedApplications = computed(() => {
    const items = [...props.applications.data];
    const direction = sortDirection.value === 'asc' ? 1 : -1;

    items.sort((left, right) => {
        if (sortKey.value === 'farmer') {
            return String(left.farmer.fullName || '').localeCompare(String(right.farmer.fullName || '')) * direction;
        }

        if (sortKey.value === 'status') {
            return String(left.status.label || '').localeCompare(String(right.status.label || '')) * direction;
        }

        if (sortKey.value === 'checklist') {
            return (checklistPercent(left) - checklistPercent(right)) * direction;
        }

        return String(left.submittedAt || left.createdAt || '').localeCompare(String(right.submittedAt || right.createdAt || '')) * direction;
    });

    return items;
});

function densityToggleLabel() {
    return props.compactMode ? 'Switch to comfortable view' : 'Switch to compact view';
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
        <!-- Top Toolbar -->
        <div class="flex items-center justify-between border-b border-[#dde4de] bg-[#f9fbfa] px-4 py-3">
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-bold text-[#0f172a]">Application Records</span>
                <span class="inline-flex items-center rounded-full bg-[#003629]/10 px-2 py-0.5 text-[0.65rem] font-bold text-[#003629]">
                    {{ applications.total ?? sortedApplications.length }} Total
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-[#dde4de] bg-white px-2.5 text-xs font-medium text-[#64748b] shadow-xs transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                    :title="densityToggleLabel()"
                    @click="$emit('toggle-compact')"
                >
                    <svg v-if="compactMode" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                    <svg v-else viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h16M4 18h16M9 4v4M15 10v4M9 16v4" />
                    </svg>
                    <span class="hidden sm:inline text-[0.7rem]">{{ compactMode ? 'Comfortable' : 'Compact' }}</span>
                </button>
            </div>
        </div>

        <!-- Table container -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px] border-collapse text-left text-xs">
                <thead>
                    <tr class="border-b border-[#dde4de] bg-[#f4f7f5] text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">
                        <th class="px-4 py-3">
                            <button type="button" class="group inline-flex items-center gap-1 font-bold text-[#64748b] hover:text-[#003629]" @click="toggleSort('submittedAt')">
                                Application
                                <svg viewBox="0 0 16 16" class="h-3 w-3 transition-opacity" :class="sortKey === 'submittedAt' ? 'opacity-100 text-[#003629]' : 'opacity-0 group-hover:opacity-60'" fill="currentColor">
                                    <path v-if="sortDirection === 'asc'" d="M8 3.5l4 4H4l4-4z" />
                                    <path v-else d="M8 12.5l4-4H4l4 4z" />
                                </svg>
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button type="button" class="group inline-flex items-center gap-1 font-bold text-[#64748b] hover:text-[#003629]" @click="toggleSort('farmer')">
                                Farmer Applicant
                                <svg viewBox="0 0 16 16" class="h-3 w-3 transition-opacity" :class="sortKey === 'farmer' ? 'opacity-100 text-[#003629]' : 'opacity-0 group-hover:opacity-60'" fill="currentColor">
                                    <path v-if="sortDirection === 'asc'" d="M8 3.5l4 4H4l4-4z" />
                                    <path v-else d="M8 12.5l4-4H4l4 4z" />
                                </svg>
                            </button>
                        </th>
                        <th class="px-4 py-3">Location & Association</th>
                        <th class="px-4 py-3">
                            <button type="button" class="group inline-flex items-center gap-1 font-bold text-[#64748b] hover:text-[#003629]" @click="toggleSort('checklist')">
                                Requirements & Payment
                                <svg viewBox="0 0 16 16" class="h-3 w-3 transition-opacity" :class="sortKey === 'checklist' ? 'opacity-100 text-[#003629]' : 'opacity-0 group-hover:opacity-60'" fill="currentColor">
                                    <path v-if="sortDirection === 'asc'" d="M8 3.5l4 4H4l4-4z" />
                                    <path v-else d="M8 12.5l4-4H4l4 4z" />
                                </svg>
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button type="button" class="group inline-flex items-center gap-1 font-bold text-[#64748b] hover:text-[#003629]" @click="toggleSort('status')">
                                Status
                                <svg viewBox="0 0 16 16" class="h-3 w-3 transition-opacity" :class="sortKey === 'status' ? 'opacity-100 text-[#003629]' : 'opacity-0 group-hover:opacity-60'" fill="currentColor">
                                    <path v-if="sortDirection === 'asc'" d="M8 3.5l4 4H4l4-4z" />
                                    <path v-else d="M8 12.5l4-4H4l4 4z" />
                                </svg>
                            </button>
                        </th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#edf2ee]">
                    <tr
                        v-for="application in sortedApplications"
                        :key="application.id"
                        class="group transition-colors duration-150 hover:bg-[#f6faf8]"
                    >
                        <!-- Application Info -->
                        <td class="px-4 align-top" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-[#003629]">
                                    {{ application.applicationNo }}
                                </span>
                            </div>
                            <div class="mt-1 flex items-center gap-1.5">
                                <span
                                    v-if="application.source === 'mobile'"
                                    class="inline-flex items-center gap-1 rounded-md border border-purple-200 bg-purple-50 px-1.5 py-0.5 text-[0.6rem] font-bold text-purple-700"
                                >
                                    <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2" />
                                        <line x1="12" y1="18" x2="12.01" y2="18" />
                                    </svg>
                                    Mobile
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[0.6rem] font-bold text-emerald-800"
                                >
                                    <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    Walk-in
                                </span>
                            </div>
                            <div class="mt-1 flex items-center gap-1 text-[0.68rem] text-[#94a3b8]">
                                <svg viewBox="0 0 20 20" class="h-3 w-3 shrink-0 text-[#94a3b8]" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ application.submittedAt || application.createdAt || 'Date not recorded' }}</span>
                            </div>
                        </td>

                        <!-- Farmer Applicant -->
                        <td class="px-4 align-top" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                            <div class="flex items-start gap-2.5">
                                <div class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#003629] to-[#005a45] text-[0.7rem] font-bold text-white shadow-xs">
                                    {{ farmerInitials(application.farmer.fullName) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-[#0f172a] group-hover:text-[#003629] transition-colors">
                                        {{ application.farmer.fullName }}
                                    </div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                        <span class="font-mono text-[0.68rem] text-[#64748b]">
                                            {{ application.farmer.farmerCode }}
                                        </span>
                                        <span
                                            v-if="application.farmer.memberType?.name"
                                            class="inline-flex rounded-md border border-[#dde4de] bg-[#f8fafc] px-1.5 py-0.5 text-[0.58rem] font-semibold text-[#475569]"
                                        >
                                            {{ application.farmer.memberType.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Location & Association -->
                        <td class="px-4 align-top" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                            <div class="flex items-center gap-1.5 text-xs text-[#0f172a]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 shrink-0 text-[#003629]/70" fill="currentColor">
                                    <path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.541l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium">{{ application.farmer.barangay || 'No barangay' }}</span>
                            </div>
                            <div class="mt-1 flex items-center gap-1.5 text-xs text-[#64748b]">
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 shrink-0 text-[#94a3b8]" fill="currentColor">
                                    <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.813.813 0 0 1-.63 1.052H14.5Z" />
                                </svg>
                                <span class="truncate max-w-[180px]" :title="application.farmer.association">
                                    {{ application.farmer.association || 'No association' }}
                                </span>
                            </div>
                        </td>

                        <!-- Requirements Checklist & Payment -->
                        <td class="px-4 align-top" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                            <div class="space-y-1.5 max-w-[200px]">
                                <div class="flex items-center justify-between text-[0.68rem]">
                                    <span class="font-medium text-[#475569]">
                                        {{ application.documents.completionLabel || `${checklistPercent(application)}% verified` }}
                                    </span>
                                    <span class="font-bold text-[#0f172a]">{{ checklistPercent(application) }}%</span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-[#e2e8f0]">
                                    <div
                                        class="h-full transition-all duration-300"
                                        :class="checklistTone(application)"
                                        :style="{ width: `${checklistPercent(application)}%` }"
                                    ></div>
                                </div>
                                <div class="flex items-center gap-1.5 pt-0.5">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[0.62rem] font-semibold"
                                        :class="application.payment.isSettled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                                    >
                                        <svg v-if="application.payment.isSettled" viewBox="0 0 16 16" class="h-2.5 w-2.5 text-emerald-600" fill="currentColor">
                                            <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                                        </svg>
                                        {{ application.payment.statusLabel }}
                                    </span>
                                    <span v-if="application.payment.amountDue > 0" class="text-[0.65rem] font-bold text-[#64748b]">
                                        ₱{{ Number(application.payment.amountDue).toFixed(2) }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="px-4 align-top" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wider"
                                :class="statusBadge(application.status.value)"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(application.status.value)"></span>
                                {{ application.status.label }}
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 align-top text-right" :class="compactMode ? 'py-2.5' : 'py-3.5'">
                            <div class="flex items-center justify-end gap-1.5">

                                <!-- Forward to Admin (if available) -->
                                <button
                                    v-if="application.quickActions?.canForwardToAdmin"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dde4de] bg-white text-[#334155] shadow-xs transition hover:bg-[#f1f5f3]"
                                    title="Forward to Admin"
                                    @click="quickActionForm.record = application.recordKey; quickActionForm.module = 'applications'; quickActionForm.action = 'forward_to_admin'; openQuickActionDialog('forward_to_admin')"
                                >
                                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <!-- Request Correction (if available) -->
                                <button
                                    v-if="application.quickActions?.canRequestCorrection"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 shadow-xs transition hover:bg-amber-100"
                                    title="Request correction"
                                    @click="quickActionForm.record = application.recordKey; quickActionForm.module = 'applications'; quickActionForm.action = 'request_correction'; openQuickActionDialog('request_correction')"
                                >
                                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                        <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                        <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                                    </svg>
                                </button>

                                <!-- Primary Review Link -->
                                <Link
                                    :href="application.actions.showUrl"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-[#003629] px-3 text-xs font-bold text-white shadow-xs transition-all duration-200 hover:bg-[#00483a] hover:shadow-sm active:scale-[0.97]"
                                    title="Review application"
                                >
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                        <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Review</span>
                                </Link>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="sortedApplications.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center justify-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                                        <rect x="9" y="3" width="6" height="4" rx="2" />
                                        <path d="M9 14h6M9 10h6M9 18h3" />
                                    </svg>
                                </div>
                                <h3 class="mt-3 text-sm font-bold text-[#0f172a]">No Queued Applications Found</h3>
                                <p class="mt-1 text-xs text-[#64748b]">
                                    There are currently no membership applications matching your filter criteria.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Footer -->
        <div class="flex flex-col items-start justify-between gap-3 border-t border-[#dde4de] bg-[#f9fbfa] px-4 py-3 sm:flex-row sm:items-center">
            <p class="text-xs text-[#64748b]">
                Showing <span class="font-bold text-[#0f172a]">{{ applications.from || 0 }}</span> to <span class="font-bold text-[#0f172a]">{{ applications.to || 0 }}</span> of <span class="font-bold text-[#0f172a]">{{ applications.total }}</span> applications
            </p>

            <div class="flex flex-wrap items-center gap-1.5">
                <template v-for="link in applications.links" :key="link.label">
                    <span
                        v-if="!link.url"
                        class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg border border-[#dde4de] bg-white/60 px-2.5 py-1 text-xs text-slate-300"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"
                        :class="link.active ? 'border-[#003629] bg-[#003629] text-white shadow-xs' : 'border-[#dde4de] bg-white text-[#475569] hover:bg-[#f1f5f3]'"
                        preserve-scroll
                        preserve-state
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Quick Action Dialog Modal -->
        <QuickActionDialog
            :open="quickActionDialog.open"
            :title="quickActionDialog.title"
            :note-label="quickActionDialog.noteLabel"
            :submit-label="quickActionDialog.submitLabel"
            :note="quickActionForm.note"
            :placeholder="quickActionDialog.action === 'request_correction' ? 'Explain what must be fixed before review can continue.' : 'Add context for the admin escalation.'"
            :processing="quickActionForm.processing"
            :error="quickActionForm.errors.note"
            @close="closeQuickActionDialog"
            @submit="quickActionForm.post(props.quickActionUrl, { preserveScroll: true, onSuccess: closeQuickActionDialog })"
            @update:note="quickActionForm.note = $event"
        />
    </section>
</template>
