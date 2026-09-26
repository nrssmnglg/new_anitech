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

function initials(name) {
    return String(name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part[0])
        .join('')
        .toUpperCase() || '--';
}

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
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
        <!-- Table Header Bar -->
        <div class="flex flex-col gap-2 border-b border-[#f1f5f9] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-bold text-[#0f172a]">Renewal Records</h2>
                <p class="text-[0.68rem] text-slate-500">{{ renewalRecords.total }} settled record{{ renewalRecords.total === 1 ? '' : 's' }}</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="text-[0.68rem]">Showing {{ renewalRecords.data.length }} of {{ renewalRecords.total }}</span>
                <button
                    type="button"
                    class="inline-flex h-7 items-center gap-1 rounded-md border border-[#dde4de] bg-white px-2 text-[0.65rem] font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95"
                    @click="$emit('toggle-compact')"
                >
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-slate-400" fill="currentColor">
                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ compactMode ? 'Comfortable' : 'Compact' }}</span>
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#f1f5f9] text-xs">
                <thead class="bg-[#f8faf9]">
                    <tr class="text-left text-[0.62rem] font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-3">
                            <button type="button" class="inline-flex items-center gap-1 font-bold hover:text-slate-800" @click="toggleSort('farmer')">
                                <span>Farmer</span>
                                <span v-if="sortKey === 'farmer'" class="text-[#003629]">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button type="button" class="inline-flex items-center gap-1 font-bold hover:text-slate-800" @click="toggleSort('years')">
                                <span>Years</span>
                                <span v-if="sortKey === 'years'" class="text-[#003629]">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button type="button" class="inline-flex items-center gap-1 font-bold hover:text-slate-800" @click="toggleSort('source')">
                                <span>Type</span>
                                <span v-if="sortKey === 'source'" class="text-[#003629]">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button type="button" class="inline-flex items-center gap-1 font-bold hover:text-slate-800" @click="toggleSort('submittedAt')">
                                <span>Latest Settled</span>
                                <span v-if="sortKey === 'submittedAt'" class="text-[#003629]">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button type="button" class="inline-flex items-center gap-1 font-bold hover:text-slate-800" @click="toggleSort('amount')">
                                <span>Total Paid</span>
                                <span v-if="sortKey === 'amount'" class="text-[#003629]">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            </button>
                        </th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f1f5f9]">
                    <tr v-for="record in sortedRecords" :key="record.id" class="transition hover:bg-[#fbfcfb]">
                        <!-- Farmer Identity -->
                        <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                            <div class="flex items-center gap-3">
                                <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-emerald-100 font-bold text-xs text-[#003629]">
                                    {{ initials(record.farmer.fullName) }}
                                </div>
                                <div>
                                    <p class="font-bold text-[#0f172a]">{{ record.farmer.fullName }}</p>
                                    <p class="text-[0.65rem] font-mono text-slate-500">{{ record.farmer.farmerCode || 'No code assigned' }}</p>
                                    <div class="mt-0.5 text-[0.6rem] leading-3 text-slate-400">
                                        <span>Edited: {{ record.accountability?.lastUpdatedBy || 'Not recorded' }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Years -->
                        <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                            <span class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-xs font-bold text-slate-800">
                                {{ record.yearRangeLabel || 'No settled years' }}
                            </span>
                            <p class="mt-0.5 text-[0.62rem] text-slate-400 font-medium">
                                {{ record.record?.renewalCount || 0 }} record{{ Number(record.record?.renewalCount || 0) === 1 ? '' : 's' }}
                            </p>
                        </td>

                        <!-- Source Type -->
                        <td class="px-4 text-slate-600 font-medium" :class="compactMode ? 'py-2' : 'py-3'">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[0.65rem] font-bold" :class="record.sourceLabel === 'Mobile' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700 border border-slate-200'">
                                {{ record.sourceLabel }}
                            </span>
                        </td>

                        <!-- Latest Settled -->
                        <td class="px-4 font-mono text-slate-600" :class="compactMode ? 'py-2' : 'py-3'">
                            {{ record.submittedAt || 'Not settled' }}
                        </td>

                        <!-- Amount Paid -->
                        <td class="px-4 font-mono font-bold text-slate-900" :class="compactMode ? 'py-2' : 'py-3'">
                            PHP {{ Number(record.amountPaid || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </td>

                        <!-- Reference -->
                        <td class="px-4 font-mono text-[0.65rem] text-slate-500" :class="compactMode ? 'py-2' : 'py-3'">
                            {{ record.paymentReference || record.referenceNo || '—' }}
                        </td>

                        <!-- Actions -->
                        <td class="px-4 text-right" :class="compactMode ? 'py-2' : 'py-3'">
                            <div class="flex flex-wrap items-center justify-end gap-1.5">
                                <button
                                    v-if="record.quickActions?.canForwardToAdmin"
                                    type="button"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                    title="Forward to admin"
                                    @click="openQuickActionDialog(record, 'forward_to_admin')"
                                >
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <Link
                                    v-if="record.actions?.showUrl"
                                    :href="record.actions.showUrl"
                                    class="inline-flex h-7 items-center gap-1 rounded-lg border border-[#003629] bg-white px-2.5 text-xs font-bold text-[#003629] shadow-xs transition hover:bg-[#003629] hover:text-white"
                                    title="View latest renewal record"
                                >
                                    <span>View</span>
                                </Link>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="renewalRecords.data.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="mt-2 text-xs font-bold text-slate-800">No renewal records found</p>
                            <p class="mt-0.5 text-[0.7rem] text-slate-500">No settled records matched the current filter criteria.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col gap-2 border-t border-[#f1f5f9] bg-[#f8faf9] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-[0.68rem] text-slate-500">Page {{ renewalRecords.current_page }} of {{ renewalRecords.last_page || 1 }}</span>
            <div class="flex flex-wrap items-center gap-1.5">
                <template v-for="link in renewalRecords.links" :key="link.label">
                    <span v-if="!link.url" class="inline-flex h-7 items-center rounded-md border border-slate-200 px-2 text-[0.68rem] text-slate-400" v-html="link.label" />
                    <Link
                        v-else
                        :href="link.url"
                        class="inline-flex h-7 items-center rounded-md border px-2.5 text-[0.68rem] font-bold transition"
                        :class="link.active ? 'border-[#003629] bg-[#003629] text-white shadow-xs' : 'border-slate-200 text-slate-600 hover:bg-white'"
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
