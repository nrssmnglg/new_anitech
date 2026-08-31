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

function statusBadge(status) {
    if (status === 'active') return 'border-[#a9d578] bg-[#edf7e5] text-[#416918]';
    if (status === 'inactive' || status === 'deceased') return 'border-[#edc1c1] bg-[#fff2f2] text-[#b53a3a]';
    return 'border-[#edd4a5] bg-[#fff6e6] text-[#b7791f]';
}

function avatarTone(status) {
    if (status === 'active') return 'bg-[#e7f3ee] text-[#003629]';
    if (status === 'inactive' || status === 'deceased') return 'bg-[#fff1f1] text-[#b53a3a]';
    return 'bg-[#fff6e8] text-[#b7791f]';
}

function actionButtonClass(type, disabled = false) {
    if (disabled) {
        return 'cursor-not-allowed border-[#e1e6e3] bg-[#f6f8f7] text-[#a1ada8]';
    }

    if (type === 'view') return 'border-[#d8e2dc] text-[#5a6762] hover:border-[#003629] hover:text-[#003629] hover:bg-[#f7fbf9]';
    if (type === 'edit') return 'border-[#cfe0f4] text-[#2f6690] hover:border-[#2f6690] hover:bg-[#f3f8fd]';
    return 'border-[#d9ddb3] text-[#7a7f27] hover:border-[#7a7f27] hover:bg-[#fbfce9]';
}

function qualityIssueClass(issue) {
    if (issue.includes('Duplicate')) return 'border-[#ead7b2] bg-[#fff8ea] text-[#996515]';
    if (issue.includes('Invalid')) return 'border-[#edd5cf] bg-[#fff4f1] text-[#a44d3f]';
    if (issue.includes('Mismatch')) return 'border-[#d8daf5] bg-[#f4f5ff] text-[#4655a4]';

    return 'border-[#e2d4db] bg-[#fbf5f8] text-[#8d4663]';
}

function visibleQualityIssues(issues) {
    return (issues || []).filter((issue) => issue !== 'Incomplete profile');
}
</script>

<template>
    <section class="overflow-hidden border-t border-[#cbd4cf] bg-white">
        <div class="flex flex-col gap-2 border-b border-[#e4ebe7] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="flex items-center gap-3 text-sm font-medium text-[#263d35]"><span class="text-xl leading-none">⋮</span>Bulk Actions</p>
            </div>
            <p class="text-sm text-[#697772]">
                Showing {{ farmers.from || 0 }} to {{ farmers.to || 0 }} of {{ farmers.total }} records
            </p>
        </div>

        <div class="border-b border-[#e4ebe7] bg-[#f8faf9] px-5 py-2.5">
            <div v-if="selectedCount > 0 || bulkScope === 'filtered'" class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-[#245342]">
                        {{ bulkScope === 'selected' ? `${selectedCount} selected` : `${filteredTargetCount} filtered` }}
                    </span>
                    <button
                        v-if="selectedCount > 0"
                        type="button"
                        class="inline-flex items-center rounded-full border border-[#d6dfda] bg-white px-3 py-1.5 text-sm font-semibold text-[#5c6b65] transition hover:bg-[#f3f6f4]"
                        @click="$emit('clear-selection')"
                    >
                        Clear
                    </button>
                </div>

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <label class="flex items-center gap-3 rounded-2xl border border-[#dbe2de] bg-white px-4 py-2.5 text-sm font-semibold text-[#41514a]">
                        <span>Apply actions to</span>
                        <select
                            :value="bulkScope"
                            class="rounded-xl border border-[#d7e0db] bg-white px-3 py-2 text-sm text-[#1a2420] outline-none"
                            @change="$emit('update-bulk-scope', $event.target.value)"
                        >
                            <option value="selected">Selected records</option>
                            <option value="filtered">Current filtered results</option>
                        </select>
                    </label>

                    <div class="flex flex-wrap gap-2">
                        <button v-if="bulkPermissions.canNotify" type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white px-4 py-2.5 text-sm font-bold text-[#003629] transition hover:bg-[#f6fbf8]" @click="$emit('open-bulk-modal', 'notify')">Notify</button>
                        <button v-if="bulkPermissions.canAssign" type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white px-4 py-2.5 text-sm font-bold text-[#003629] transition hover:bg-[#f6fbf8]" @click="$emit('open-bulk-modal', 'assign')">Assign</button>
                        <button v-if="bulkPermissions.canFollowUp" type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white px-4 py-2.5 text-sm font-bold text-[#003629] transition hover:bg-[#f6fbf8]" @click="$emit('open-bulk-modal', 'follow_up')">Follow-up</button>
                        <button v-if="bulkPermissions.canStatusReview" type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white px-4 py-2.5 text-sm font-bold text-[#003629] transition hover:bg-[#f6fbf8]" @click="$emit('open-bulk-modal', 'status')">Review</button>
                        <button v-if="bulkPermissions.canArchive" type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white px-4 py-2.5 text-sm font-bold text-[#003629] transition hover:bg-[#f6fbf8]" @click="$emit('open-bulk-modal', 'archive')">Archive</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#e5ece8] text-sm">
                <thead class="bg-[#f4f7f5]">
                    <tr class="text-left text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">
                        <th class="px-5 py-3">
                            <input :checked="allCurrentPageSelected" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#003629] focus:ring-[#003629]" @change="$emit('toggle-select-all')">
                        </th>
                        <th class="px-5 py-3">Farmer</th>
                        <th class="px-5 py-3">Contact &amp; Location</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#edf2ef] bg-white">
                    <tr v-for="farmer in farmers.data" :key="farmer.id" class="transition hover:bg-[#fbfdfc]">
                        <td class="px-5 py-3 align-top">
                            <input :checked="selectedIds.includes(farmer.id)" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-[#003629] focus:ring-[#003629]" @change="$emit('toggle-selection', farmer.id)">
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-4">
                                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-xs font-black" :class="avatarTone(farmer.status.value)">
                                    {{ initials(farmer.fullName) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-[#1b2320]">{{ farmer.fullName }}</p>
                                    <p class="mt-1 text-[0.65rem] font-black uppercase tracking-[0.2em] text-[#8b9791]">{{ farmer.farmerCode }}</p>
                                    <p class="mt-1 text-[0.72rem] italic text-[#7b8782]">Registered {{ farmer.registeredAt || '-' }}</p>
                                    <p class="mt-1 text-[0.72rem] text-[#6c7772]"><span class="font-semibold">Last updated by:</span> {{ farmer.accountability?.lastUpdatedBy || 'System' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <p class="text-sm font-bold text-[#1b2320]">{{ farmer.barangay || 'No barangay' }}</p>
                            <p v-if="farmer.association" class="mt-1 text-xs font-medium text-[#61706a]">{{ farmer.association }}</p>
                            <p class="mt-2 text-sm text-[#6c7772]">{{ farmer.contact.mobileNumber || 'No mobile number' }}</p>
                            <div v-if="visibleQualityIssues(farmer.qualityIssues).length" class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-for="issue in visibleQualityIssues(farmer.qualityIssues)"
                                    :key="`${farmer.id}-${issue}`"
                                    class="inline-flex rounded-full border px-2.5 py-1 text-[0.62rem] font-black uppercase tracking-[0.14em]"
                                    :class="qualityIssueClass(issue)"
                                >
                                    {{ issue }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <span v-if="farmer.memberType" class="inline-flex rounded-full bg-[#eef1ef] px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] text-[#5f6d67]">
                                {{ farmer.memberType.code }}
                            </span>
                            <p class="mt-2 text-xs text-[#61706a]">{{ farmer.memberType?.name || 'Unassigned' }}</p>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex rounded-full border px-4 py-1.5 text-[0.64rem] font-black uppercase tracking-[0.22em]" :class="statusBadge(farmer.status.value)">
                                {{ farmer.status.label }}
                            </span>
                            <p v-if="farmer.inactiveReason" class="mt-2 text-[0.72rem] leading-5 text-[#7a5555]">
                                {{ farmer.inactiveReason }}
                            </p>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-2">
                                <Link :href="farmer.actions.showUrl" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition" :class="actionButtonClass('view')">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </Link>
                                <Link v-if="farmer.actions.editUrl" :href="farmer.actions.editUrl" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition" :class="actionButtonClass('edit')">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                    </svg>
                                </Link>
                                <Link
                                    v-if="farmer.actions.renewalUrl"
                                    :href="farmer.actions.renewalUrl"
                                    :aria-label="`Process renewal for ${farmer.fullName}`"
                                    :title="`Process renewal for ${farmer.fullName}`"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition"
                                    :class="actionButtonClass('renewal')"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 12a9 9 0 1 1-2.64-6.36" />
                                        <path d="M21 3v6h-6" />
                                    </svg>
                                </Link>
                                <span
                                    v-else
                                    :title="farmer.renewal?.disabledReason || ''"
                                    :aria-label="`Renewal unavailable: ${farmer.renewal?.disabledReason || 'not eligible'}`"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border"
                                    :class="actionButtonClass('renewal', true)"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 12a9 9 0 1 1-2.64-6.36" />
                                        <path d="M21 3v6h-6" />
                                    </svg>
                                </span>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="farmers.data.length === 0">
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="mx-auto max-w-md rounded-[24px] border border-dashed border-[#dbe2de] bg-[#f8faf9] px-6 py-8">
                                <p class="text-base font-semibold text-[#1a2420]">No farmer records found.</p>
                                <p class="mt-2 text-sm text-[#6a7872]">Records will appear here once they exist in the farmers registry.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-[#e4ebe7] bg-[#f4f7f5] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-sm text-[#65736d]">Page {{ farmers.current_page }} of {{ totalPages }}</span>

            <div class="flex flex-wrap items-center gap-2">
                <template v-for="link in farmers.links" :key="link.label">
                    <span
                        v-if="!link.url"
                        class="inline-flex items-center rounded-xl border border-[#dbe2de] px-3 py-2 text-sm text-[#9aa6a1]"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        :only="['farmers']"
                        class="inline-flex items-center rounded-xl border px-3 py-2 text-sm font-bold transition"
                        :class="link.active ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#dbe2de] text-[#5f6b66] hover:bg-white'"
                        preserve-scroll
                        preserve-state
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </section>
</template>
