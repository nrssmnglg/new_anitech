<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    farmers: { type: Object, required: true },
    totalPages: { type: Number, required: true },
    selectedIds: { type: Array, required: true },
    allCurrentPageSelected: { type: Boolean, required: true },
    selectedCount: { type: Number, required: true },
    filteredTargetCount: { type: Number, required: true },
    bulkScope: { type: String, required: true },
    bulkPermissions: { type: Object, required: true },
});

defineEmits(['toggle-selection', 'toggle-select-all', 'open-bulk-modal', 'update-bulk-scope', 'clear-selection']);

function initials(name) {
    return String(name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || '?';
}

function qualityIssueClass(issue) {
    if (issue.includes('Duplicate')) return 'border-[#fde68a] bg-[#fefce8] text-[#a16207]';
    if (issue.includes('Invalid')) return 'border-[#fecdd3] bg-[#fff1f2] text-[#be123c]';
    if (issue.includes('Mismatch')) return 'border-[#fed7aa] bg-[#fff7ed] text-[#c2410c]';
    return 'border-[#e2e8f0] bg-[#f8fafc] text-[#475569]';
}

function visibleQualityIssues(issues) {
    return (issues || []).filter((issue) => issue !== 'Incomplete profile');
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
        <!-- Top bar: Record count & Bulk actions toggle -->
        <div class="flex flex-col gap-2 border-b border-[#f1f5f9] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-[#15803d]"></span>
                <span class="text-xs font-bold text-[#0f172a]">Farmer Directory</span>
            </div>
            <p class="text-xs text-[#64748b]">
                Showing <strong class="text-[#0f172a]">{{ farmers.from || 0 }}</strong> to <strong class="text-[#0f172a]">{{ farmers.to || 0 }}</strong> of <strong class="text-[#0f172a]">{{ farmers.total }}</strong> records
            </p>
        </div>

        <!-- Bulk Action Strip (active when records selected or filtered scope selected) -->
        <div v-if="selectedCount > 0 || bulkScope === 'filtered'" class="border-b border-[#bbf7d0] bg-gradient-to-r from-[#f0fdf4] to-[#f8fafc] px-4 py-2.5 sm:px-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#014d3c] px-2.5 py-1 text-xs font-bold text-white shadow-sm">
                        <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="currentColor"><path d="M12.736 3.97a.75.75 0 0 1 1.048 1.06l-7 7a.75.75 0 0 1-1.048 0l-3.5-3.5a.75.75 0 1 1 1.048-1.06L6 10.19l6.736-6.22Z"/></svg>
                        {{ bulkScope === 'selected' ? `${selectedCount} selected` : `${filteredTargetCount} filtered` }}
                    </span>
                    <button
                        v-if="selectedCount > 0"
                        type="button"
                        class="inline-flex items-center rounded-lg border border-[#dde4de] bg-white px-2.5 py-1 text-xs font-medium text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                        @click="$emit('clear-selection')"
                    >
                        Clear
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <label class="flex items-center gap-1.5 rounded-lg border border-[#dbe3dd] bg-white px-2.5 py-1 text-xs text-[#475569]">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Scope:</span>
                        <select
                            :value="bulkScope"
                            class="bg-transparent text-xs font-semibold text-[#0f172a] outline-none"
                            @change="$emit('update-bulk-scope', $event.target.value)"
                        >
                            <option value="selected">Selected records</option>
                            <option value="filtered">All filtered results</option>
                        </select>
                    </label>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            v-if="bulkPermissions.canNotify"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-[#dde4de] bg-white px-2.5 py-1 text-xs font-bold text-[#014d3c] shadow-sm transition hover:bg-[#f0fdf4] hover:border-[#86efac]"
                            @click="$emit('open-bulk-modal', 'notify')"
                        >
                            <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor"><path d="M1.75 3h12.5c.966 0 1.75.784 1.75 1.75v6.5A1.75 1.75 0 0 1 14.25 13H1.75A1.75 1.75 0 0 1 0 11.25v-6.5C0 3.784.784 3 1.75 3ZM1.5 5.09v6.16c0 .138.112.25.25.25h12.5a.25.25 0 0 0 .25-.25V5.09L8.435 8.914a.75.75 0 0 1-.87 0L1.5 5.09Zm12.39-1.09H2.11L8 7.625l5.89-3.625Z"/></svg>
                            Notify
                        </button>
                        <button
                            v-if="bulkPermissions.canAssign"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-[#dde4de] bg-white px-2.5 py-1 text-xs font-bold text-[#014d3c] shadow-sm transition hover:bg-[#f0fdf4] hover:border-[#86efac]"
                            @click="$emit('open-bulk-modal', 'assign')"
                        >
                            <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor"><path d="M7.75 2a.75.75 0 0 1 .75.75V7h4.25a.75.75 0 0 1 0 1.5H8.5v4.25a.75.75 0 0 1-1.5 0V8.5H2.75a.75.75 0 0 1 0-1.5H7V2.75A.75.75 0 0 1 7.75 2Z"/></svg>
                            Assign
                        </button>
                        <button
                            v-if="bulkPermissions.canFollowUp"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-[#dde4de] bg-white px-2.5 py-1 text-xs font-bold text-[#014d3c] shadow-sm transition hover:bg-[#f0fdf4] hover:border-[#86efac]"
                            @click="$emit('open-bulk-modal', 'follow_up')"
                        >
                            <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor"><path d="M0 2.75C0 1.784.784 1 1.75 1h12.5c.966 0 1.75.784 1.75 1.75v10.5A1.75 1.75 0 0 1 14.25 15H1.75A1.75 1.75 0 0 1 0 13.25V2.75Zm1.75-.25a.25.25 0 0 0-.25.25v10.5c0 .138.112.25.25.25h12.5a.25.25 0 0 0 .25-.25V2.75a.25.25 0 0 0-.25-.25H1.75ZM4 5.5a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 4 5.5Zm0 3a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 4 8.5Z"/></svg>
                            Follow-up
                        </button>
                        <button
                            v-if="bulkPermissions.canStatusReview"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-[#dde4de] bg-white px-2.5 py-1 text-xs font-bold text-[#b45309] shadow-sm transition hover:bg-[#fffbeb] hover:border-[#fcd34d]"
                            @click="$emit('open-bulk-modal', 'status')"
                        >
                            <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor"><path fill-rule="evenodd" d="M8.5.75a.75.75 0 0 0-1 0L1.75 5.5v5.75c0 3.25 4.5 4.5 5.75 4.75 1.25-.25 5.75-1.5 5.75-4.75V5.5L8.5.75Zm2.75 5.67-4 4.5a.75.75 0 0 1-1.08.04l-2-2a.75.75 0 0 1 1.06-1.06l1.43 1.43 3.47-3.9a.75.75 0 0 1 1.12.99Z" clip-rule="evenodd"/></svg>
                            Review
                        </button>
                        <button
                            v-if="bulkPermissions.canArchive"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-[#dde4de] bg-white px-2.5 py-1 text-xs font-bold text-[#dc2626] shadow-sm transition hover:bg-[#fef2f2] hover:border-[#fca5a5]"
                            @click="$emit('open-bulk-modal', 'archive')"
                        >
                            <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor"><path d="M0 2.25A2.25 2.25 0 0 1 2.25 0h11.5A2.25 2.25 0 0 1 16 2.25v1.5A2.25 2.25 0 0 1 13.75 6H13v7.25A2.75 2.75 0 0 1 10.25 16h-4.5A2.75 2.75 0 0 1 3 13.25V6h-.75A2.25 2.25 0 0 1 0 3.75v-1.5ZM2.25 1.5a.75.75 0 0 0-.75.75v1.5c0 .414.336.75.75.75h11.5a.75.75 0 0 0 .75-.75v-1.5a.75.75 0 0 0-.75-.75H2.25Zm2.25 4.5v7.25c0 .69.56 1.25 1.25 1.25h4.5c.69 0 1.25-.56 1.25-1.25V6h-7Z"/></svg>
                            Archive
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Data Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#f1f5f9] text-left text-xs">
                <thead>
                    <tr class="border-b border-[#e2e8f0] bg-gradient-to-b from-[#f8fafc] to-[#f1f5f9] text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                        <th class="w-12 px-4 py-3 text-center">
                            <input
                                :checked="allCurrentPageSelected"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-[#014d3c] focus:ring-[#014d3c]"
                                @change="$emit('toggle-select-all')"
                            >
                        </th>
                        <th class="px-5 py-3">Farmer</th>
                        <th class="px-5 py-3">Location &amp; Contact</th>
                        <th class="px-5 py-3">Member Type</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#f1f5f9] bg-white">
                    <tr
                        v-for="farmer in farmers.data"
                        :key="farmer.id"
                        class="transition-colors hover:bg-[#f8fafc]"
                        :class="{ 'bg-[#f0fdf4]/50': selectedIds.includes(farmer.id) }"
                    >
                        <!-- Checkbox column -->
                        <td class="px-4 py-3.5 text-center align-top">
                            <input
                                :checked="selectedIds.includes(farmer.id)"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-[#014d3c] focus:ring-[#014d3c]"
                                @change="$emit('toggle-selection', farmer.id)"
                            >
                        </td>

                        <!-- Farmer column -->
                        <td class="px-5 py-3.5 align-top">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-xs font-bold text-[#0f6b45] shadow-sm">
                                    {{ initials(farmer.fullName) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <Link :href="farmer.actions.showUrl" class="font-bold text-[#0f172a] hover:text-[#014d3c] hover:underline">
                                            {{ farmer.fullName }}
                                        </Link>
                                        <span class="inline-flex rounded-md bg-[#f1f5f9] px-1.5 py-0.5 font-mono text-[0.62rem] font-semibold text-[#475569]">
                                            {{ farmer.farmerCode }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-1 text-[0.65rem] text-[#94a3b8]">
                                        <svg viewBox="0 0 16 16" class="h-3 w-3 text-[#94a3b8]" fill="currentColor"><path d="M5.75 7.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5ZM5 3.75a.75.75 0 0 0 1.5 0V2.75a.75.75 0 0 0-1.5 0v1ZM5.75 12a.75.75 0 0 0-.75.75v1.5a.75.75 0 0 0 1.5 0v-1.5a.75.75 0 0 0-.75-.75ZM10.25 7.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5ZM9.5 3.75a.75.75 0 0 0 1.5 0V2.75a.75.75 0 0 0-1.5 0v1ZM10.25 12a.75.75 0 0 0-.75.75v1.5a.75.75 0 0 0 1.5 0v-1.5a.75.75 0 0 0-.75-.75Z"/></svg>
                                        Registered: {{ farmer.registeredAt || '-' }}
                                    </div>
                                    <p v-if="farmer.accountability?.lastUpdatedBy" class="mt-0.5 text-[0.62rem] text-[#94a3b8]">
                                        Edited by {{ farmer.accountability.lastUpdatedBy }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Location & Contact column -->
                        <td class="px-5 py-3.5 align-top">
                            <div class="space-y-1">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-[#0f172a]">
                                    <svg viewBox="0 0 16 16" class="h-3.5 w-3.5 text-[#014d3c]" fill="currentColor"><path fill-rule="evenodd" d="m8 1.75 4.5 4.5V13a1 1 0 0 1-1 1H4.5a1 1 0 0 1-1-1V6.25L8 1.75Zm0 1.768L4.75 6.75V12.5h6.5V6.75L8 3.518Z" clip-rule="evenodd"/></svg>
                                    {{ farmer.barangay || 'No barangay' }}
                                </div>
                                <div v-if="farmer.association" class="flex items-center gap-1.5 text-[0.68rem] text-[#64748b]">
                                    <svg viewBox="0 0 16 16" class="h-3 w-3 text-[#94a3b8]" fill="currentColor"><path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-4.5 6a4.5 4.5 0 0 1 9 0h-9Zm10-6a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Zm-1.5 6a3.5 3.5 0 0 1 3.5-3.5h.5a.5.5 0 0 1 .5.5v3h-4.5Z"/></svg>
                                    {{ farmer.association }}
                                </div>
                                <div v-if="farmer.contact.mobileNumber" class="flex items-center gap-1.5 text-[0.68rem] text-[#64748b]">
                                    <svg viewBox="0 0 16 16" class="h-3 w-3 text-[#94a3b8]" fill="currentColor"><path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511Z" clip-rule="evenodd"/></svg>
                                    {{ farmer.contact.mobileNumber }}
                                </div>
                                <div v-if="visibleQualityIssues(farmer.qualityIssues).length" class="flex flex-wrap gap-1 pt-1">
                                    <span
                                        v-for="issue in visibleQualityIssues(farmer.qualityIssues)"
                                        :key="`${farmer.id}-${issue}`"
                                        class="inline-flex rounded-md border px-1.5 py-0.5 text-[0.58rem] font-bold uppercase tracking-wider"
                                        :class="qualityIssueClass(issue)"
                                    >
                                        {{ issue }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Member Type column -->
                        <td class="px-5 py-3.5 align-top">
                            <span v-if="farmer.memberType" class="inline-flex rounded-lg bg-[#f1f5f9] px-2 py-0.5 text-[0.62rem] font-bold text-[#334155]">
                                {{ farmer.memberType.code }}
                            </span>
                            <p class="mt-1 text-xs text-[#64748b]">{{ farmer.memberType?.name || 'Unassigned' }}</p>
                        </td>

                        <!-- Status column -->
                        <td class="px-5 py-3.5 text-center align-top">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[0.62rem] font-bold"
                                :class="{
                                    'bg-[#dcfce7] text-[#15803d]': farmer.status.value === 'active',
                                    'bg-[#fef2f2] text-[#dc2626]': farmer.status.value === 'inactive',
                                    'bg-[#f1f5f9] text-[#64748b]': farmer.status.value === 'deceased',
                                }"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="{
                                        'bg-[#22c55e]': farmer.status.value === 'active',
                                        'bg-[#ef4444]': farmer.status.value === 'inactive',
                                        'bg-[#94a3b8]': farmer.status.value === 'deceased',
                                    }"
                                ></span>
                                {{ farmer.status.label }}
                            </span>
                            <p v-if="farmer.inactiveReason" class="mt-1 text-[0.62rem] text-[#ef4444]">
                                {{ farmer.inactiveReason }}
                            </p>
                        </td>

                        <!-- Actions column -->
                        <td class="px-5 py-3.5 align-top">
                            <div class="flex items-center justify-end gap-1.5">
                                <Link
                                    :href="farmer.actions.showUrl"
                                    title="View Profile"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dde4de] text-[#64748b] transition-all duration-200 hover:border-[#014d3c] hover:bg-[#f0fdf4] hover:text-[#014d3c]"
                                >
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                        <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                                    </svg>
                                </Link>
                                <Link
                                    v-if="farmer.actions.editUrl"
                                    :href="farmer.actions.editUrl"
                                    title="Edit Farmer"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dde4de] text-[#64748b] transition-all duration-200 hover:border-[#2563eb] hover:bg-[#eff6ff] hover:text-[#2563eb]"
                                >
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                        <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                        <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                                    </svg>
                                </Link>
                                <Link
                                    v-if="farmer.actions.renewalUrl"
                                    :href="farmer.actions.renewalUrl"
                                    :title="`Process renewal for ${farmer.fullName}`"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dde4de] text-[#15803d] transition-all duration-200 hover:border-[#15803d] hover:bg-[#f0fdf4]"
                                >
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.451a.75.75 0 0 0 0-1.5H4.5a.75.75 0 0 0-.75.75v3.75a.75.75 0 0 0 1.5 0v-2.199l.312.311a7 7 0 0 0 11.75-3.418.75.75 0 0 0-1.5-.048ZM4.688 8.576a5.5 5.5 0 0 1 9.201-2.466l.312.311H11.75a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 .75-.75V3.421a.75.75 0 0 0-1.5 0v2.199l-.312-.311A7 7 0 0 0 2.688 8.727a.75.75 0 0 0 1.5.048l.5-.199Z" clip-rule="evenodd" />
                                    </svg>
                                </Link>
                                <span
                                    v-else
                                    :title="farmer.renewal?.disabledReason || 'Renewal unavailable'"
                                    class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-[#f1f5f9] bg-[#f8fafc] text-[#cbd5e1]"
                                >
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.451a.75.75 0 0 0 0-1.5H4.5a.75.75 0 0 0-.75.75v3.75a.75.75 0 0 0 1.5 0v-2.199l.312.311a7 7 0 0 0 11.75-3.418.75.75 0 0 0-1.5-.048ZM4.688 8.576a5.5 5.5 0 0 1 9.201-2.466l.312.311H11.75a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 .75-.75V3.421a.75.75 0 0 0-1.5 0v2.199l-.312-.311A7 7 0 0 0 2.688 8.727a.75.75 0 0 0 1.5.048l.5-.199Z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="farmers.data.length === 0">
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0fdf4] text-[#15803d]">
                                    <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="10" cy="7" r="4" />
                                        <path d="m21 21-4.35-4.35" />
                                    </svg>
                                </div>
                                <h3 class="mt-3 text-sm font-bold text-[#0f172a]">No farmers found</h3>
                                <p class="mt-1 text-xs text-[#94a3b8]">
                                    Try adjusting your search or filters to locate farmer profiles.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="flex flex-col gap-3 border-t border-[#f1f5f9] bg-[#f8fafc] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-xs text-[#64748b]">
                Page <strong class="text-[#0f172a]">{{ farmers.current_page }}</strong> of <strong class="text-[#0f172a]">{{ totalPages }}</strong>
            </span>

            <div class="flex flex-wrap items-center gap-1.5">
                <template v-for="(link, index) in farmers.links" :key="index">
                    <span
                        v-if="!link.url"
                        class="inline-flex h-8 items-center justify-center rounded-lg border border-[#e2e8f0] bg-white px-3 text-xs text-[#94a3b8]"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        :only="['farmers']"
                        class="inline-flex h-8 items-center justify-center rounded-lg border px-3 text-xs font-semibold transition-all duration-200"
                        :class="link.active ? 'border-[#014d3c] bg-[#014d3c] text-white shadow-sm' : 'border-[#e2e8f0] bg-white text-[#475569] hover:bg-[#f1f5f9] hover:border-[#cbd5e1]'"
                        preserve-scroll
                        preserve-state
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </section>
</template>
