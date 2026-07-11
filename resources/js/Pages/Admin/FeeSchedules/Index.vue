<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineComponent, h, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    schedules: { type: Object, required: true },
    activeSchedules: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const activatingId = ref(null);
const deletingId = ref(null);

function activate(schedule) {
    if (!schedule.actions.activateUrl || activatingId.value !== null) {
        return;
    }

    activatingId.value = schedule.id;

    router.post(schedule.actions.activateUrl, {}, {
        preserveScroll: true,
        onFinish: () => {
            activatingId.value = null;
        },
    });
}

function destroySchedule(schedule) {
    if (!schedule.actions.deleteUrl || deletingId.value !== null || activatingId.value !== null) {
        return;
    }

    if (schedule.actions.deleteDisabledReason) {
        window.alert(schedule.actions.deleteDisabledReason);
        return;
    }

    if (!window.confirm(`Delete fee schedule for ${schedule.memberType?.code || 'this member type'} (${schedule.year})?`)) {
        return;
    }

    deletingId.value = schedule.id;

    router.delete(schedule.actions.deleteUrl, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

const activeRate = computed(() => (props.summary.total ? Math.round((props.summary.active / props.summary.total) * 100) : 0));

const iconNodes = {
    receipt: [
        ['path', { d: 'M6 2h12v20l-3-2-3 2-3-2-3 2V2z' }],
        ['path', { d: 'M9 7h6' }],
        ['path', { d: 'M9 11h6' }],
        ['path', { d: 'M9 15h4' }],
    ],
    verified: [
        ['path', { d: 'M9 12l2 2 4-4' }],
        ['path', { d: 'M12 3l2.5 1.5L17 4l.5 2.5L20 9l-2.5 2.5L17 14l-2.5-.5L12 15l-2.5-1.5L7 14l-.5-2.5L4 9l2.5-2.5L7 4l2.5.5L12 3z' }],
    ],
    calendar: [
        ['rect', { x: '3', y: '4', width: '18', height: '18', rx: '2' }],
        ['path', { d: 'M16 2v4' }],
        ['path', { d: 'M8 2v4' }],
        ['path', { d: 'M3 10h18' }],
    ],
    add: [
        ['path', { d: 'M12 5v14' }],
        ['path', { d: 'M5 12h14' }],
    ],
};

const AppIcon = defineComponent({
    name: 'AppIcon',
    props: {
        name: { type: String, required: true },
    },
    setup(iconProps, { attrs }) {
        return () => h(
            'svg',
            {
                viewBox: '0 0 24 24',
                fill: 'none',
                stroke: 'currentColor',
                'stroke-width': '1.8',
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'aria-hidden': 'true',
                ...attrs,
            },
            (iconNodes[iconProps.name] || []).map(([tag, tagAttrs]) => h(tag, tagAttrs))
        );
    },
});
</script>

<template>
    <Head title="Fee Configuration" />

    <AdminLayout title="Fee Configuration">
        <div class="space-y-5">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Total Schedules</p>
                            <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#0f172a]">{{ summary.total }}</h2>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#edf4ef] text-[#0f5b46]">
                            <AppIcon name="receipt" class="h-5 w-5" />
                        </span>
                    </div>
                </article>

                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Active Member Types</p>
                            <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#0f5b46]">{{ summary.activeMemberTypes }}</h2>
                        </div>
                        <span class="text-xs font-bold text-[#50761b]">{{ summary.active }} active record{{ summary.active === 1 ? '' : 's' }}</span>
                    </div>
                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-[#e4eee8]">
                        <div class="h-full rounded-full bg-[#2f7d5e]" :style="{ width: `${activeRate}%` }"></div>
                    </div>
                </article>

                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Current Year</p>
                    <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#b46d00]">{{ summary.current_year }}</h2>
                </article>

                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Latest Year</p>
                    <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#0f172a]">{{ summary.latest_year }}</h2>
                </article>
            </section>

            <div class="flex justify-end">
                <Link :href="urls.create" class="inline-flex items-center gap-2 rounded-xl bg-[#014d3c] px-4 py-2.5 text-sm font-black text-white transition hover:bg-[#01362a]">
                    <AppIcon name="add" class="h-4 w-4" />
                    Add Fee Schedule
                </Link>
            </div>

            <section class="rounded-[1.35rem] border border-[#dde4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <div class="mb-5 flex items-center gap-3">
                    <AppIcon name="verified" class="h-5 w-5 text-[#0f5b46]" />
                    <h2 class="text-lg font-black text-[#0f172a]">Active Fee Schedules By Member Type</h2>
                </div>

                <div v-if="activeSchedules.length" class="grid gap-4 xl:grid-cols-2">
                    <article
                        v-for="schedule in activeSchedules"
                        :key="schedule.id"
                        class="rounded-[1.2rem] border border-[#e4ece6] bg-[#fbfcfb] p-5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Member Type</p>
                                <h3 class="mt-2 text-xl font-black text-[#0f172a]">
                                    {{ schedule.memberType?.code || 'N/A' }}
                                </h3>
                                <p class="mt-1 text-sm text-[#64748b]">{{ schedule.memberType?.name || 'No member type assigned' }}</p>
                            </div>
                            <span class="rounded-full bg-[#d9f4c2] px-3 py-1 text-xs font-bold text-[#50761b]">Active</span>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-[#e9efeb] bg-white p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Year</p>
                                <p class="mt-2 text-xl font-black text-[#0f172a]">{{ schedule.year }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#e9efeb] bg-white p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Renewal Deadline</p>
                                <p class="mt-2 text-sm font-semibold text-[#0f172a]">{{ schedule.renewalDeadline }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#e9efeb] bg-white p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Membership Fee</p>
                                <p class="mt-2 text-sm font-semibold text-[#0f172a]">PHP {{ schedule.membershipFee.toFixed(2) }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#e9efeb] bg-white p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Annual Due</p>
                                <p class="mt-2 text-sm font-semibold text-[#0f172a]">PHP {{ schedule.annualDue.toFixed(2) }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#e9efeb] bg-white p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Mortuary Fee</p>
                                <p class="mt-2 text-sm font-semibold text-[#0f172a]">PHP {{ schedule.mortuaryFee.toFixed(2) }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#e9efeb] bg-white p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Effective Dates</p>
                                <p class="mt-2 text-sm font-semibold text-[#0f172a]">{{ schedule.effectiveFrom }} to {{ schedule.effectiveTo }}</p>
                            </div>
                        </div>
                    </article>
                </div>
                <p v-else class="text-sm text-[#64748b]">No active fee schedules exist yet.</p>
            </section>

            <section class="overflow-hidden rounded-[1.35rem] border border-[#dde4de] bg-white shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <div class="border-b border-[#edf2ee] bg-[#fbfcfb] px-6 py-4">
                    <h2 class="text-lg font-black text-[#0f172a]">Fee Schedule Records</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-[#f9fbfa]">
                            <tr class="border-b border-[#e8eeea] text-left text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">
                                <th class="px-6 py-4">Year</th>
                                <th class="px-6 py-4">Member Type</th>
                                <th class="px-6 py-4">Fees</th>
                                <th class="px-6 py-4">Renewal Deadline</th>
                                <th class="px-6 py-4">Effective Dates</th>
                                <th class="px-6 py-4 text-center">Assessments</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="schedule in schedules.data" :key="schedule.id" class="border-b border-[#edf2ee] transition hover:bg-[#fcfefd]">
                                <td class="px-6 py-5 align-top">
                                    <span class="font-bold text-[#0f172a]">{{ schedule.year }}</span>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div class="space-y-1">
                                        <p class="font-semibold text-[#0f172a]">{{ schedule.memberType?.code || 'N/A' }}</p>
                                        <p class="text-xs text-[#64748b]">{{ schedule.memberType?.name || 'No member type' }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div class="space-y-1">
                                        <p class="text-[#0f172a]">Membership: PHP {{ schedule.fees.membership.toFixed(2) }}</p>
                                        <p class="text-xs text-[#64748b]">Annual: PHP {{ schedule.fees.annual.toFixed(2) }} | Mortuary: PHP {{ schedule.fees.mortuary.toFixed(2) }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-top text-[#0f172a]">{{ schedule.renewalDeadline || 'Not set' }}</td>
                                <td class="px-6 py-5 align-top text-[#0f172a]">{{ schedule.effectiveRange.from }} to {{ schedule.effectiveRange.to }}</td>
                                <td class="px-6 py-5 align-top text-center">
                                    <span class="rounded-full bg-[#f1f5f3] px-3 py-1 text-xs font-bold text-[#64748b]">{{ schedule.assessmentsCount }}</span>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="schedule.status.value === 'active' ? 'bg-[#d9f4c2] text-[#50761b]' : 'bg-[#eceff1] text-[#5e6c74]'">
                                        {{ schedule.status.label }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div class="flex items-center justify-end gap-4 text-sm">
                                        <Link
                                            :href="schedule.actions.editUrl"
                                            class="font-medium text-[#014d3c] transition hover:underline"
                                            :class="{ 'pointer-events-none opacity-50': activatingId !== null || deletingId !== null }"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="schedule.actions.activateUrl"
                                            type="button"
                                            class="font-medium text-[#50761b] transition hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                                            :disabled="activatingId !== null || deletingId !== null"
                                            @click="activate(schedule)"
                                        >
                                            {{ activatingId === schedule.id ? 'Activating...' : 'Activate' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="font-medium text-[#c05c3c] transition hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                                            :disabled="activatingId !== null || deletingId !== null"
                                            :title="schedule.actions.deleteDisabledReason || ''"
                                            @click="destroySchedule(schedule)"
                                        >
                                            {{ deletingId === schedule.id ? 'Deleting...' : 'Delete' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="schedules.data.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center text-sm text-[#64748b]">No fee schedules found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-[#edf2ee] bg-[#fbfcfb] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[#64748b]">Showing {{ schedules.from || 0 }}-{{ schedules.to || 0 }} of {{ schedules.total }} entries</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in schedules.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-[#dbe3dd] px-3 text-sm text-[#9aa6a1]" v-html="link.label" />
                            <Link
                                v-else
                                :href="link.url"
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border px-3 text-sm font-bold transition"
                                :class="link.active ? 'border-[#014d3c] bg-[#014d3c] text-white' : 'border-[#dbe3dd] bg-white text-[#64748b] hover:bg-[#f4f7f5]'"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
