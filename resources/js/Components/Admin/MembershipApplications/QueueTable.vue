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
    if (value === 'approved') return 'bg-[#eef7e3] text-[#416918]';
    if (value === 'rejected') return 'bg-[#ffdad6] text-[#93000a]';
    return 'bg-[#fff3dc] text-[#a86100]';
}

function checklistTone(application) {
    if (application.payment.isSettled) return 'bg-[#416918]';
    if (application.documents.totalCount > 0) {
        const percent = Math.round((application.documents.verifiedCount / application.documents.totalCount) * 100);
        return percent >= 100 ? 'bg-[#416918]' : (percent >= 50 ? 'bg-[#d99d2b]' : 'bg-[#ba1a1a]');
    }

    return 'bg-[#c0c9c3]';
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

function submitQuickAction(application, action, note = '') {
    quickActionForm.transform(() => ({
        module: 'applications',
        record: application.recordKey,
        action,
        note,
    })).post(props.quickActionUrl, {
        preserveScroll: true,
        onSuccess: closeQuickActionDialog,
    });
}

function openQuickActionDialog(action) {
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

        return String(left.submittedAt || '').localeCompare(String(right.submittedAt || '')) * direction;
    });

    return items;
});

function densityToggleLabel() {
    return props.compactMode ? 'Switch to comfortable rows' : 'Switch to compact rows';
}
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-[#dbe2de] bg-white">
        <div class="flex items-center justify-between border-b border-[#dbe2de] px-3.5 py-2.5">
            <p class="text-xs font-semibold text-[#34463f]">Application Records</p>
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d9e2dc] bg-white text-[#53615c] transition hover:bg-[#f4f7f5]"
                :title="densityToggleLabel()"
                @click="$emit('toggle-compact')"
            >
                <svg v-if="compactMode" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 7h16" />
                    <path d="M4 12h16" />
                    <path d="M4 17h16" />
                </svg>
                <svg v-else viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
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
            <table class="min-w-[1050px] w-full border-collapse text-left text-xs">
                <thead class="border-b border-[#dbe2de] bg-[#f3f6f4]">
                    <tr class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#6b7772]">
                        <th class="px-3 py-2.5"><button type="button" @click="toggleSort('submittedAt')">Application</button></th>
                        <th class="px-3 py-2.5"><button type="button" @click="toggleSort('farmer')">Farmer</button></th>
                        <th class="px-3 py-2.5">Assignment</th>
                        <th class="px-3 py-2.5"><button type="button" @click="toggleSort('checklist')">Checklist</button></th>
                        <th class="px-3 py-2.5"><button type="button" @click="toggleSort('status')">Status</button></th>
                        <th class="px-3 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#ebf0ed]">
                    <tr v-for="application in sortedApplications" :key="application.id" class="transition hover:bg-[#f9fbfa]">
                        <td class="px-3 align-top" :class="compactMode ? 'py-2' : 'py-3'">
                            <div class="font-semibold text-[#003629]">{{ application.applicationNo }}</div>
                            <div class="text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#7a8781]">{{ application.sourceLabel }}</div>
                            <div class="mt-0.5 text-[0.68rem] text-[#86918c]">{{ application.submittedAt || (application.status?.value === 'approved' ? 'Submission date not recorded' : 'Not submitted yet') }}</div>
                        </td>
                        <td class="px-3 align-top" :class="compactMode ? 'py-2' : 'py-3'">
                            <div class="flex items-start gap-2">
                                <div class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#dff0e7] text-[0.65rem] font-semibold text-[#003629]">
                                    {{ farmerInitials(application.farmer.fullName) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-[#191c1c]">{{ application.farmer.fullName }}</div>
                                    <div class="text-[0.68rem] text-[#86918c]">{{ application.farmer.farmerCode }}</div>
                                    <span class="mt-0.5 inline-flex rounded bg-[#edf1ef] px-1.5 py-0.5 text-[0.58rem] font-semibold uppercase tracking-[0.05em] text-[#5f6b66]">
                                        {{ application.farmer.memberType?.name || 'Pending member type' }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 align-top" :class="compactMode ? 'py-2' : 'py-3'">
                            <div><span class="font-semibold">Brgy:</span> {{ application.farmer.barangay || 'No barangay' }}</div>
                            <div class="mt-0.5 truncate" :title="application.farmer.association"><span class="font-semibold">Assoc:</span> {{ application.farmer.association || 'No association' }}</div>
                            <div class="mt-0.5 text-[0.68rem] font-semibold text-[#6d8a7d]">
                                {{ application.farmer.memberType ? `${application.farmer.memberType.code} - ${application.farmer.memberType.name}` : 'Member type not set' }}
                            </div>
                        </td>
                        <td class="px-3 align-top" :class="compactMode ? 'py-2' : 'py-3'">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#e6ebe8]">
                                        <div class="h-full" :class="checklistTone(application)" :style="{ width: `${checklistPercent(application)}%` }"></div>
                                    </div>
                                    <span class="text-[0.65rem] font-medium text-[#2e3131]">{{ checklistPercent(application) }}%</span>
                                </div>
                                <div class="text-[0.68rem]" :class="application.payment.isSettled ? 'text-[#416918]' : 'text-[#697772]'">
                                    {{ application.payment.statusLabel }}
                                    <span v-if="application.payment.amountDue > 0"> (PHP {{ Number(application.payment.amountDue).toFixed(2) }})</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 align-top" :class="compactMode ? 'py-2' : 'py-3'">
                            <span class="inline-flex w-fit rounded-md px-2 py-1 text-[0.6rem] font-semibold uppercase tracking-[0.05em]" :class="statusBadge(application.status.value)">
                                {{ application.status.label }}
                            </span>
                        </td>
                        <td class="px-3 align-top text-right" :class="compactMode ? 'py-2' : 'py-3'">
                            <div class="flex flex-wrap justify-end gap-1.5">
                                <button
                                    v-if="application.quickActions?.canMarkComplete"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#cfe0d6] bg-[#eff7e8] text-[#486814] transition hover:bg-[#e5f1da]"
                                    title="Mark complete"
                                    @click="submitQuickAction(application, 'mark_complete')"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m5 12 4 4L19 6" />
                                    </svg>
                                </button>
                                <button
                                    v-if="application.quickActions?.canForwardToAdmin"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d6dfda] bg-white text-[#36554a] transition hover:bg-[#f7faf8]"
                                    title="Forward to admin"
                                    @click="quickActionForm.record = application.recordKey; quickActionForm.module = 'applications'; quickActionForm.action = 'forward_to_admin'; openQuickActionDialog('forward_to_admin')"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 12h11" />
                                        <path d="m11 5 7 7-7 7" />
                                    </svg>
                                </button>
                                <button
                                    v-if="application.quickActions?.canRequestCorrection"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#f2d4c8] bg-[#fff6f1] text-[#b85b34] transition hover:bg-[#fff0e7]"
                                    title="Request correction"
                                    @click="quickActionForm.record = application.recordKey; quickActionForm.module = 'applications'; quickActionForm.action = 'request_correction'; openQuickActionDialog('request_correction')"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                    </svg>
                                </button>
                                <Link
                                    :href="application.actions.showUrl"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d6dfda] bg-white text-[#003629] transition hover:bg-[#f7faf8]"
                                    title="Review"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </Link>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="applications.data.length === 0">
                        <td colspan="6" class="px-4 py-10 text-center">
                            <div class="mx-auto max-w-md rounded-md border border-dashed border-[#dbe2de] bg-[#f8faf9] px-4 py-5">
                                <p class="text-sm font-semibold text-[#1a2420]">No queued applications found.</p>
                                <p class="mt-1 text-xs text-[#6a7872]">New membership transactions will appear here once they are created.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col items-start justify-between gap-3 border-t border-[#dbe2de] bg-[#fbfcfb] px-3.5 py-2.5 sm:flex-row sm:items-center">
            <p class="text-xs text-[#697772]">
                Showing <span class="font-bold">{{ applications.from || 0 }}-{{ applications.to || 0 }}</span> of <span class="font-bold">{{ applications.total }}</span> applications
            </p>

            <div class="flex flex-wrap items-center gap-2">
                <template v-for="link in applications.links" :key="link.label">
                    <span
                        v-if="!link.url"
                        class="inline-flex min-h-8 items-center rounded-md border border-[#dbe2de] px-2.5 py-1.5 text-xs text-[#9aa6a1]"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        class="inline-flex min-h-8 items-center rounded-md border px-2.5 py-1.5 text-xs font-semibold transition"
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
