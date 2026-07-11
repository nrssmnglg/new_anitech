<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { readStoredValue, persistValue } from '../../../Composables/usePersistentUiState';

defineEmits(['toggle-compact']);
const props = defineProps({
    renewals: { type: Object, required: true },
    compactMode: { type: Boolean, default: false },
});

const sortKey = ref(readStoredValue('staff.renewals.queue.sort-key', 'farmer'));
const sortDirection = ref(readStoredValue('staff.renewals.queue.sort-direction', 'asc'));

persistValue('staff.renewals.queue.sort-key', sortKey);
persistValue('staff.renewals.queue.sort-direction', sortDirection);

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
    <section class="overflow-hidden rounded-[24px] border border-[#dfe5e1] bg-white shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
        <div class="flex flex-col gap-3 border-b border-[#e4ebe7] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Renewal Queue</p>
                <h2 class="mt-1 text-xl font-bold text-[#191c1c]">Farmers Due This Year</h2>
            </div>
            <div class="flex items-center gap-3 text-sm text-[#697772]">
                <span>Showing {{ renewals.data.length }} of {{ renewals.total }}</span>
                <button type="button" class="inline-flex items-center rounded-2xl border border-[#d9e2dc] bg-white px-4 py-2.5 text-sm font-bold text-[#334155] transition hover:bg-[#f4f7f5]" @click="$emit('toggle-compact')">
                    {{ compactMode ? 'Comfortable Rows' : 'Compact Rows' }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#e5ece8] text-sm">
                <thead class="bg-[#f2f4f3]">
                    <tr class="text-left text-[0.72rem] font-black uppercase tracking-[0.16em] text-[#6d7873]">
                        <th class="px-6 py-4"><button type="button" @click="toggleSort('farmer')">Farmer</button></th>
                        <th class="px-6 py-4">Membership Type</th>
                        <th class="px-6 py-4"><button type="button" @click="toggleSort('year')">Year</button></th>
                        <th class="px-6 py-4"><button type="button" @click="toggleSort('status')">Renewal Status</button></th>
                        <th class="px-6 py-4">Reminder</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf2ef]">
                    <tr v-for="renewal in sortedRenewals" :key="renewal.id" class="transition hover:bg-[#fbfdfc]">
                        <td class="px-6" :class="compactMode ? 'py-3' : 'py-4'">
                            <div class="flex items-center gap-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[#d9ecd7] text-sm font-black text-[#2a5000]">
                                    {{ initials(renewal.farmer.fullName) }}
                                </div>
                                <div>
                                    <p class="font-bold text-[#191c1c]">{{ renewal.farmer.fullName }}</p>
                                    <p class="mt-1 text-[0.78rem] text-[#7b8882]">ID: {{ renewal.farmer.farmerCode || 'Not assigned' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 text-[#34423d]" :class="compactMode ? 'py-3' : 'py-4'">{{ memberTypeLabel(renewal.farmer.memberType) }}</td>
                        <td class="px-6 font-semibold text-[#191c1c]" :class="compactMode ? 'py-3' : 'py-4'">{{ renewal.year }}</td>
                        <td class="px-6" :class="compactMode ? 'py-3' : 'py-4'">
                            <span class="inline-flex rounded-full border px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em]" :class="statusBadge(renewal.status.value)">
                                {{ renewal.status.label }}
                            </span>
                        </td>
                        <td class="px-6" :class="compactMode ? 'py-3' : 'py-4'">
                            <div v-if="renewal.reminder?.hasSent" class="space-y-1">
                                <span class="inline-flex rounded-full border border-[#cfe4d7] bg-[#edf7f2] px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em] text-[#1f6a49]">
                                    Reminder Sent
                                </span>
                                <p class="text-xs text-[#6c7b75]">{{ renewal.reminder.sentAt }}</p>
                            </div>
                            <span v-else class="text-sm text-[#7b8882]">Not yet notified</span>
                        </td>
                        <td class="px-6 text-right" :class="compactMode ? 'py-3' : 'py-4'">
                            <Link :href="renewal.actions.createUrl" class="inline-flex items-center rounded-xl text-sm font-bold text-[#003629] transition hover:underline">
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

        <div class="flex flex-col gap-4 border-t border-[#e4ebe7] bg-[#f8faf9] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-sm text-[#65736d]">Page {{ renewals.current_page }} of {{ renewals.last_page || 1 }}</span>
            <div class="flex flex-wrap items-center gap-2">
                <template v-for="link in renewals.links" :key="link.label">
                    <span v-if="!link.url" class="inline-flex items-center rounded-xl border border-[#dbe2de] px-3 py-2 text-sm text-[#9aa6a1]" v-html="link.label" />
                    <Link
                        v-else
                        :href="link.url"
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
