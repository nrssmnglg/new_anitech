<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { readStoredValue, persistValue } from '../../../Composables/usePersistentUiState';

defineEmits(['toggle-compact']);
const props = defineProps({
    renewals: { type: Object, required: true },
    compactMode: { type: Boolean, default: false },
});

const sortKey = ref(readStoredValue('staff.renewals.queue.sort-key', 'farmer'));
const sortDirection = ref(readStoredValue('staff.renewals.queue.sort-direction', 'asc'));
const sendingReminderId = ref(null);

persistValue('staff.renewals.queue.sort-key', sortKey);
persistValue('staff.renewals.queue.sort-direction', sortDirection);

function sendReminderEmail(renewal) {
    if (!renewal.actions?.sendReminderEmailUrl || sendingReminderId.value) return;

    sendingReminderId.value = renewal.id;
    router.post(renewal.actions.sendReminderEmailUrl, { year: renewal.year }, {
        preserveScroll: true,
        onFinish: () => {
            sendingReminderId.value = null;
        },
    });
}

function initials(name) {
    return String(name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part[0])
        .join('')
        .toUpperCase() || '--';
}

function statusBadge(value) {
    if (value === 'needs_renewal' || value === 'pending') return 'border-amber-200 bg-amber-50 text-amber-800';
    if (value === 'approved' || value === 'completed') return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    if (value === 'rejected' || value === 'cancelled') return 'border-rose-200 bg-rose-50 text-rose-800';
    return 'border-slate-200 bg-slate-100 text-slate-700';
}

function memberTypeLabel(memberType) {
    if (!memberType) return 'Unassigned';
    if (memberType.code && memberType.name && memberType.code !== memberType.name) {
        return `${memberType.code} - ${memberType.name}`;
    }

    return memberType.name || memberType.code || 'Unassigned';
}

function toggleSort(key) {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortKey.value = key;
    sortDirection.value = key === 'year' ? 'desc' : 'asc';
}

const sortedRenewals = computed(() => {
    const items = [...props.renewals.data];
    const direction = sortDirection.value === 'asc' ? 1 : -1;

    items.sort((left, right) => {
        if (sortKey.value === 'year') {
            return (Number(left.year || 0) - Number(right.year || 0)) * direction;
        }

        if (sortKey.value === 'status') {
            return String(left.status.label || '').localeCompare(String(right.status.label || '')) * direction;
        }

        return String(left.farmer.fullName || '').localeCompare(String(right.farmer.fullName || '')) * direction;
    });

    return items;
});
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
        <!-- Table Header Bar -->
        <div class="flex flex-col gap-2 border-b border-[#f1f5f9] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-bold text-[#0f172a]">Farmers Due for Renewal</h2>
                <p class="text-[0.68rem] text-slate-500">{{ renewals.total }} farmer{{ renewals.total === 1 ? '' : 's' }} awaiting renewal</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="text-[0.68rem]">Showing {{ renewals.data.length }} of {{ renewals.total }}</span>
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
                        <th class="px-4 py-3">Member Type</th>
                        <th class="px-4 py-3">
                            <button type="button" class="inline-flex items-center gap-1 font-bold hover:text-slate-800" @click="toggleSort('year')">
                                <span>Year</span>
                                <span v-if="sortKey === 'year'" class="text-[#003629]">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button type="button" class="inline-flex items-center gap-1 font-bold hover:text-slate-800" @click="toggleSort('status')">
                                <span>Status</span>
                                <span v-if="sortKey === 'status'" class="text-[#003629]">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            </button>
                        </th>
                        <th class="px-4 py-3">Reminder</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f1f5f9]">
                    <tr v-for="renewal in sortedRenewals" :key="renewal.id" class="transition hover:bg-[#fbfcfb]">
                        <!-- Farmer Identity -->
                        <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                            <div class="flex items-center gap-3">
                                <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-emerald-100 font-bold text-xs text-[#003629]">
                                    {{ initials(renewal.farmer.fullName) }}
                                </div>
                                <div>
                                    <p class="font-bold text-[#0f172a]">{{ renewal.farmer.fullName }}</p>
                                    <p class="text-[0.65rem] font-mono text-slate-500">{{ renewal.farmer.farmerCode || 'Not assigned' }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Member Type -->
                        <td class="px-4 text-slate-700" :class="compactMode ? 'py-2' : 'py-3'">
                            <span class="inline-flex items-center rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-[0.68rem] font-medium text-slate-700">
                                {{ memberTypeLabel(renewal.farmer.memberType) }}
                            </span>
                        </td>

                        <!-- Year -->
                        <td class="px-4 font-mono font-bold text-slate-900" :class="compactMode ? 'py-2' : 'py-3'">
                            {{ renewal.year }}
                        </td>

                        <!-- Status -->
                        <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[0.62rem] font-bold uppercase tracking-wider" :class="statusBadge(renewal.status.value)">
                                <span class="h-1.5 w-1.5 rounded-full" :class="renewal.status.value === 'approved' || renewal.status.value === 'completed' ? 'bg-emerald-500' : renewal.status.value === 'rejected' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                                {{ renewal.status.label }}
                            </span>
                        </td>

                        <!-- Reminder -->
                        <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                            <div v-if="renewal.reminder?.hasSent" class="space-y-0.5">
                                <span class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[0.65rem] font-bold text-emerald-800">
                                    <svg viewBox="0 0 20 20" class="h-3 w-3 text-emerald-600" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                    Email sent
                                </span>
                                <p class="text-[0.6rem] text-slate-400 font-mono">{{ renewal.reminder.sentAt }}</p>
                            </div>
                            <button
                                v-else-if="renewal.reminder?.emailAvailable"
                                type="button"
                                class="inline-flex h-7 items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50/70 px-2.5 text-[0.65rem] font-bold text-emerald-800 transition hover:bg-emerald-100 disabled:cursor-wait disabled:opacity-60"
                                :disabled="sendingReminderId !== null"
                                @click="sendReminderEmail(renewal)"
                            >
                                <svg v-if="sendingReminderId === renewal.id" class="h-3 w-3 animate-spin text-emerald-700" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                <svg v-else viewBox="0 0 20 20" class="h-3 w-3 text-emerald-700" fill="currentColor">
                                    <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
                                    <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                                </svg>
                                <span>{{ sendingReminderId === renewal.id ? 'Sending…' : 'Send Email' }}</span>
                            </button>
                            <span v-else class="text-[0.65rem] text-slate-400 italic">No email</span>
                        </td>

                        <!-- Action -->
                        <td class="px-4 text-right" :class="compactMode ? 'py-2' : 'py-3'">
                            <Link
                                :href="renewal.actions.createUrl"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#003629] text-white shadow-xs transition hover:bg-[#00483a] active:scale-95"
                                title="Start Renewal"
                                aria-label="Start Renewal"
                            >
                                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                    <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.31V15a.75.75 0 0 1-1.5 0v-3.75A.75.75 0 0 1 5 10.5h3.75a.75.75 0 0 1 0 1.5H6.862l.248.248a4 4 0 0 0 6.69-1.785.75.75 0 0 1 1.512.461Zm-10.624-2.848a5.5 5.5 0 0 1 9.201-2.466l.312.31V5a.75.75 0 0 1 1.5 0v3.75A.75.75 0 0 1 15 9.5h-3.75a.75.75 0 0 1 0-1.5h1.888l-.248-.248a4 4 0 0 0-6.69 1.785.75.75 0 0 1-1.512-.461Z" clip-rule="evenodd" />
                                </svg>
                            </Link>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="renewals.data.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="mt-2 text-xs font-bold text-slate-800">All caught up!</p>
                            <p class="mt-0.5 text-[0.7rem] text-slate-500">All active farmers already have a recorded renewal for the selected year.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col gap-2 border-t border-[#f1f5f9] bg-[#f8faf9] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-[0.68rem] text-slate-500">Page {{ renewals.current_page }} of {{ renewals.last_page || 1 }}</span>
            <div class="flex flex-wrap items-center gap-1.5">
                <template v-for="link in renewals.links" :key="link.label">
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
    </section>
</template>
