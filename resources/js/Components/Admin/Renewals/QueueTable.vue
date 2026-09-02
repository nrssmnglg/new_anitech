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
    if (value === 'needs_renewal' || value === 'pending') return 'bg-[#fff3dc] text-[#a86100]';
    if (value === 'approved' || value === 'completed') return 'bg-[#eef7e3] text-[#416918]';
    if (value === 'rejected' || value === 'cancelled') return 'bg-[#ffdad6] text-[#93000a]';
    return 'bg-[#eef3f1] text-[#47625a]';
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
    <section class="overflow-hidden rounded-lg border border-[#dfe5e1] bg-white">
        <div class="flex flex-col gap-2 border-b border-[#e4ebe7] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-[#191c1c]">Farmers due for renewal</h2>
                <p class="mt-0.5 text-[0.68rem] text-[#697772]">{{ renewals.total }} farmer{{ renewals.total === 1 ? '' : 's' }} awaiting renewal</p>
            </div>
            <div class="flex items-center gap-2 text-[0.68rem] text-[#697772]">
                <span>Showing {{ renewals.data.length }}</span>
                <button type="button" class="inline-flex h-8 items-center rounded-md border border-[#d9e2dc] px-2.5 text-[0.65rem] font-semibold text-[#334155] transition hover:bg-[#f4f7f5]" @click="$emit('toggle-compact')">
                    {{ compactMode ? 'Comfortable' : 'Compact' }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#e5ece8] text-xs">
                <thead class="bg-[#f2f4f3]">
                    <tr class="text-left text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#6d7873]">
                        <th class="px-4 py-2.5"><button type="button" @click="toggleSort('farmer')">Farmer</button></th>
                        <th class="px-4 py-2.5">Member Type</th>
                        <th class="px-4 py-2.5"><button type="button" @click="toggleSort('year')">Year</button></th>
                        <th class="px-4 py-2.5"><button type="button" @click="toggleSort('status')">Status</button></th>
                        <th class="px-4 py-2.5">Reminder</th>
                        <th class="px-4 py-2.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf2ef]">
                    <tr v-for="renewal in sortedRenewals" :key="renewal.id" class="transition hover:bg-[#fbfdfc]">
                        <td class="px-4" :class="compactMode ? 'py-2' : 'py-2.5'">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#e4f0e9] text-[0.62rem] font-semibold text-[#245444]">
                                    {{ initials(renewal.farmer.fullName) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-[#191c1c]">{{ renewal.farmer.fullName }}</p>
                                    <p class="mt-0.5 text-[0.62rem] text-[#7b8882]">{{ renewal.farmer.farmerCode || 'Not assigned' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 text-[#34423d]" :class="compactMode ? 'py-2' : 'py-2.5'">{{ memberTypeLabel(renewal.farmer.memberType) }}</td>
                        <td class="px-4 font-semibold text-[#191c1c]" :class="compactMode ? 'py-2' : 'py-2.5'">{{ renewal.year }}</td>
                        <td class="px-4" :class="compactMode ? 'py-2' : 'py-2.5'">
                            <span class="inline-flex rounded-md px-2 py-1 text-[0.58rem] font-semibold" :class="statusBadge(renewal.status.value)">
                                {{ renewal.status.label }}
                            </span>
                        </td>
                        <td class="px-4" :class="compactMode ? 'py-2' : 'py-2.5'">
                            <div v-if="renewal.reminder?.hasSent" class="space-y-1">
                                <span class="inline-flex rounded-md bg-[#edf7f2] px-2 py-1 text-[0.58rem] font-semibold text-[#1f6a49]">
                                    Email sent
                                </span>
                                <p class="text-[0.6rem] text-[#6c7b75]">{{ renewal.reminder.sentAt }}</p>
                            </div>
                            <button
                                v-else-if="renewal.reminder?.emailAvailable"
                                type="button"
                                class="inline-flex h-8 items-center rounded-md border border-[#cfe0d6] bg-white px-2.5 text-[0.62rem] font-semibold text-[#0f5b46] transition hover:bg-[#edf7f2] disabled:cursor-wait disabled:opacity-60"
                                :disabled="sendingReminderId !== null"
                                @click="sendReminderEmail(renewal)"
                            >
                                {{ sendingReminderId === renewal.id ? 'Sending...' : 'Send Email' }}
                            </button>
                            <span v-else class="text-[0.65rem] text-[#9a6b23]">No email</span>
                        </td>
                        <td class="px-4 text-right" :class="compactMode ? 'py-2' : 'py-2.5'">
                            <Link :href="renewal.actions.createUrl" class="inline-flex h-8 items-center rounded-md bg-[#003629] px-3 text-[0.65rem] font-semibold text-white transition hover:bg-[#0d4637]">
                                Start Renewal
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="renewals.data.length === 0">
                        <td colspan="6" class="px-6 py-16 text-center text-sm text-[#6a7872]">All active farmers already have a recorded renewal for the current year.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-[#e4ebe7] bg-[#f8faf9] px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-[0.68rem] text-[#65736d]">Page {{ renewals.current_page }} of {{ renewals.last_page || 1 }}</span>
            <div class="flex flex-wrap items-center gap-2">
                <template v-for="link in renewals.links" :key="link.label">
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
    </section>
</template>
