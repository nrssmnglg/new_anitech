<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { readStoredValue, persistValue } from '../../../Composables/usePersistentUiState';
import QuickActionDialog from '../QuickActionDialog.vue';

const props = defineProps({
    renewalRecords: { type: Object, required: true },
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
const sortKey = ref(readStoredValue('staff.renewals.records.sort-key', 'submittedAt'));
const sortDirection = ref(readStoredValue('staff.renewals.records.sort-direction', 'desc'));

persistValue('staff.renewals.records.sort-key', sortKey);
persistValue('staff.renewals.records.sort-direction', sortDirection);

function submitQuickAction(record, action) {
    quickActionForm.transform(() => ({
        module: 'renewals',
        record: record.recordKey,
        action,
        note: '',
    })).post(props.quickActionUrl, {
        preserveScroll: true,
        onSuccess: closeQuickActionDialog,
    });
}

function openQuickActionDialog(record, action) {
    quickActionForm.module = 'renewals';
    quickActionForm.record = record.recordKey;
    quickActionForm.action = action;
    quickActionForm.note = '';
    quickActionDialog.value = action === 'request_correction'
        ? {
            open: true,
            action,
            title: 'Request correction',
            noteLabel: 'Correction notes',
            submitLabel: 'Send correction request',
        }
        : {
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
    sortDirection.value = key === 'farmer' || key === 'source' ? 'asc' : 'desc';
}

const sortedRecords = computed(() => {
    const items = [...props.renewalRecords.data];
    const direction = sortDirection.value === 'asc' ? 1 : -1;

    items.sort((left, right) => {
        if (sortKey.value === 'farmer') {
            return String(left.farmer.fullName || '').localeCompare(String(right.farmer.fullName || '')) * direction;
        }

        if (sortKey.value === 'source') {
            return String(left.sourceLabel || '').localeCompare(String(right.sourceLabel || '')) * direction;
        }

        if (sortKey.value === 'years') {
            return String(left.yearRangeLabel || '').localeCompare(String(right.yearRangeLabel || '')) * direction;
        }

        if (sortKey.value === 'amount') {
            return (Number(left.amountPaid || 0) - Number(right.amountPaid || 0)) * direction;
        }

        return String(left.submittedAt || '').localeCompare(String(right.submittedAt || '')) * direction;
    });

    return items;
});

function densityToggleLabel() {
    return props.compactMode ? 'Switch to comfortable rows' : 'Switch to compact rows';
}
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-[#dfe5e1] bg-white">
        <div class="flex items-center justify-between border-b border-[#e4ebe7] px-4 py-3">
            <div>
                <h2 class="text-sm font-semibold text-[#191c1c]">Renewal records</h2>
                <p class="mt-0.5 text-[0.68rem] text-[#697772]">{{ renewalRecords.total }} settled record{{ renewalRecords.total === 1 ? '' : 's' }}</p>
            </div>
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d9e2dc] bg-white text-[#334155] transition hover:bg-[#f4f7f5]"
                :title="densityToggleLabel()"
                @click="$emit('toggle-compact')"
            >
                <svg v-if="compactMode" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 7h16" />
                    <path d="M4 12h16" />
                    <path d="M4 17h16" />
                </svg>
                <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 6h16" />
                    <path d="M4 12h16" />
                    <path d="M4 18h16" />
                    <path d="M9 4v4" />
                    <path d="M15 10v4" />
                    <path d="M9 16v4" />
                </svg>
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#e5ece8] text-xs">
                <thead class="bg-[#f2f4f3]">
                    <tr class="text-left text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#6d7873]">
                        <th class="px-4 py-2.5"><button type="button" @click="toggleSort('farmer')">Farmer</button></th>
                        <th class="px-4 py-2.5"><button type="button" @click="toggleSort('years')">Years</button></th>
                        <th class="px-4 py-2.5"><button type="button" @click="toggleSort('source')">Type</button></th>
                        <th class="px-4 py-2.5"><button type="button" @click="toggleSort('submittedAt')">Latest Settled</button></th>
                        <th class="px-4 py-2.5"><button type="button" @click="toggleSort('amount')">Total Paid</button></th>
                        <th class="px-4 py-2.5">Reference</th>
                        <th class="px-4 py-2.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf2ef]">
                    <tr v-for="record in sortedRecords" :key="record.id" class="transition hover:bg-[#fbfdfc]">
                        <td class="px-4" :class="compactMode ? 'py-2' : 'py-2.5'">
                            <p class="font-semibold text-[#191c1c]">{{ record.farmer.fullName }}</p>
                            <p class="mt-0.5 text-[0.62rem] text-[#7b8882]">{{ record.farmer.farmerCode || 'No code assigned' }}</p>
                            <div class="mt-1 text-[0.58rem] leading-4 text-[#6c7772]">
                                <p>Edited: {{ record.accountability?.lastUpdatedBy || 'Not recorded' }}</p>
                                <p>Reviewed: {{ record.accountability?.reviewedBy || 'Pending' }}</p>
                            </div>
                        </td>
                        <td class="px-4 text-[#475651]" :class="compactMode ? 'py-2' : 'py-2.5'">
                            <p class="font-semibold text-[#191c1c]">{{ record.yearRangeLabel || 'No settled years' }}</p>
                            <p class="mt-0.5 text-[0.62rem] text-[#7b8882]">{{ record.record?.renewalCount || 0 }} record{{ Number(record.record?.renewalCount || 0) === 1 ? '' : 's' }}</p>
                        </td>
                        <td class="px-4 text-[#475651]" :class="compactMode ? 'py-2' : 'py-2.5'">{{ record.sourceLabel }}</td>
                        <td class="px-4 text-[#475651]" :class="compactMode ? 'py-2' : 'py-2.5'">{{ record.submittedAt || 'Not settled' }}</td>
                        <td class="px-4 font-semibold text-[#191c1c]" :class="compactMode ? 'py-2' : 'py-2.5'">PHP {{ Number(record.amountPaid || 0).toFixed(2) }}</td>
                        <td class="px-4 font-mono text-[0.65rem] text-[#5f6c67]" :class="compactMode ? 'py-2' : 'py-2.5'">{{ record.paymentReference || record.referenceNo || 'N/A' }}</td>
                        <td class="px-4 text-right" :class="compactMode ? 'py-2' : 'py-2.5'">
                            <div class="flex flex-wrap justify-end gap-2">
                                <button
                                    v-if="record.quickActions?.canMarkComplete"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#cfe0d6] bg-[#eff7e8] text-[#486814] transition hover:bg-[#e5f1da]"
                                    title="Mark complete"
                                    @click="submitQuickAction(record, 'mark_complete')"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m5 12 4 4L19 6" />
                                    </svg>
                                </button>
                                <button
                                    v-if="record.quickActions?.canForwardToAdmin"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d6dfda] bg-white text-[#36554a] transition hover:bg-[#f7faf8]"
                                    title="Forward to admin"
                                    @click="openQuickActionDialog(record, 'forward_to_admin')"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 12h11" />
                                        <path d="m11 5 7 7-7 7" />
                                    </svg>
                                </button>
                                <button
                                    v-if="record.quickActions?.canRequestCorrection"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#f2d4c8] bg-[#fff6f1] text-[#b85b34] transition hover:bg-[#fff0e7]"
                                    title="Request correction"
                                    @click="openQuickActionDialog(record, 'request_correction')"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                    </svg>
                                </button>
                                <Link
                                    v-if="record.actions?.showUrl"
                                    :href="record.actions.showUrl"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d6dfda] bg-white text-[#003629] transition hover:bg-[#f7faf8]"
                                    title="View latest renewal record"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </Link>
                                <span
                                    v-else
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#e3e8e5] bg-[#f4f7f5] text-[#9aa6a1]"
                                    title="No renewal record available"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="renewalRecords.data.length === 0">
                        <td colspan="7" class="px-6 py-16 text-center text-sm text-[#6a7872]">No renewal records matched the current filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-[#e4ebe7] bg-[#f8faf9] px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-[0.68rem] text-[#65736d]">Page {{ renewalRecords.current_page }} of {{ renewalRecords.last_page || 1 }}</span>
            <div class="flex flex-wrap items-center gap-2">
                <template v-for="link in renewalRecords.links" :key="link.label">
                    <span v-if="!link.url" class="inline-flex h-8 items-center rounded-md border border-[#dbe2de] px-2.5 text-[0.68rem] text-[#9aa6a1]" v-html="link.label" />
                    <Link
                        v-else
                        :href="link.url"
                        class="inline-flex h-8 items-center rounded-md border px-2.5 text-[0.68rem] font-semibold transition"
                        :class="link.active ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#dbe2de] text-[#5f6b66] hover:bg-white'"
                        preserve-scroll
                        preserve-state
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

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
