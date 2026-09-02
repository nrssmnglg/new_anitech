<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { defineComponent, h, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    schedules: { type: Object, required: true },
    activeSchedules: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    urls: { type: Object, required: true },
});

const activatingId = ref(null);
const deletingId = ref(null);
const search = ref(props.filters.search || '');

function applySearch() {
    router.get(props.urls.index, {
        search: search.value.trim() || undefined,
    }, {
        preserveScroll: true,
        preserveState: false,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    applySearch();
}

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

const iconNodes = {
    verified: [
        ['path', { d: 'M9 12l2 2 4-4' }],
        ['path', { d: 'M12 3l2.5 1.5L17 4l.5 2.5L20 9l-2.5 2.5L17 14l-2.5-.5L12 15l-2.5-1.5L7 14l-.5-2.5L4 9l2.5-2.5L7 4l2.5.5L12 3z' }],
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
        <div class="space-y-3">
            <section class="flex flex-col gap-3 rounded-xl bg-[#003629] p-4 text-white lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-5">
                    <div><p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/65">Fee configuration</p><h1 class="mt-0.5 text-xl font-semibold">Fee Schedules</h1></div>
                    <div class="grid grid-cols-4 overflow-hidden rounded-lg border border-white/15 bg-white/[0.08]">
                        <div class="px-3 py-2 text-center"><p class="text-[0.52rem] font-semibold uppercase text-white/60">Total</p><p class="mt-0.5 text-base font-semibold">{{ summary.total }}</p></div>
                        <div class="border-l border-white/10 px-3 py-2 text-center"><p class="text-[0.52rem] font-semibold uppercase text-white/60">Active</p><p class="mt-0.5 text-base font-semibold text-[#c0f190]">{{ summary.active }}</p></div>
                        <div class="border-l border-white/10 px-3 py-2 text-center"><p class="text-[0.52rem] font-semibold uppercase text-white/60">Types</p><p class="mt-0.5 text-base font-semibold">{{ summary.activeMemberTypes }}</p></div>
                        <div class="border-l border-white/10 px-3 py-2 text-center"><p class="text-[0.52rem] font-semibold uppercase text-white/60">Latest</p><p class="mt-0.5 text-base font-semibold">{{ summary.latest_year }}</p></div>
                    </div>
                </div>
                <Link :href="urls.create" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md bg-white px-3 text-[0.68rem] font-semibold text-[#003629] transition hover:bg-[#f1f7f3]"><AppIcon name="add" class="h-3.5 w-3.5" />Add Schedule</Link>
            </section>

            <section class="rounded-lg border border-[#dde4de] bg-white p-3">
                <div class="mb-2 flex items-center gap-2">
                    <AppIcon name="verified" class="h-4 w-4 text-[#0f5b46]" />
                    <h2 class="text-sm font-semibold text-[#0f172a]">Active schedules by member type</h2>
                </div>

                <div v-if="activeSchedules.length" class="grid gap-2 lg:grid-cols-2">
                    <article
                        v-for="schedule in activeSchedules"
                        :key="schedule.id"
                        class="rounded-md border border-[#e4ece6] bg-[#f8faf9] px-3 py-2.5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold text-[#0f172a]">{{ schedule.memberType?.code || 'N/A' }} · {{ schedule.memberType?.name || 'No member type' }}</p>
                                <p class="mt-1 text-[0.62rem] text-[#64748b]">CY {{ schedule.year }} · Deadline {{ schedule.renewalDeadline }}</p>
                            </div>
                            <span class="rounded-md bg-[#d9f4c2] px-2 py-1 text-[0.58rem] font-semibold text-[#50761b]">Active</span>
                        </div>

                        <div class="mt-2 grid grid-cols-3 gap-1.5 text-[0.65rem]">
                            <div class="rounded-md bg-white px-2 py-1.5"><span class="text-[#64748b]">Membership</span><p class="font-semibold">PHP {{ schedule.membershipFee.toFixed(2) }}</p></div>
                            <div class="rounded-md bg-white px-2 py-1.5"><span class="text-[#64748b]">Annual</span><p class="font-semibold">PHP {{ schedule.annualDue.toFixed(2) }}</p></div>
                            <div class="rounded-md bg-white px-2 py-1.5"><span class="text-[#64748b]">Mortuary</span><p class="font-semibold">PHP {{ schedule.mortuaryFee.toFixed(2) }}</p></div>
                        </div>
                    </article>
                </div>
                <p v-else class="text-sm text-[#64748b]">No active fee schedules exist yet.</p>
            </section>

            <section class="overflow-hidden rounded-lg border border-[#dde4de] bg-white">
                <div class="flex flex-col gap-2 border-b border-[#edf2ee] px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                    <div><h2 class="text-sm font-semibold text-[#0f172a]">Fee schedule records</h2><p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ schedules.total }} schedule{{ schedules.total === 1 ? '' : 's' }}</p></div>

                    <form class="flex w-full max-w-xl flex-col gap-2 sm:flex-row" @submit.prevent="applySearch">
                        <label class="relative flex-1">
                            <span class="sr-only">Search fee schedules</span>
                            <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#8a9791]" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-3.5-3.5"></path>
                            </svg>
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search year, member type, or status"
                                class="h-9 w-full rounded-md border border-[#d7e0db] bg-white pl-9 pr-3 text-xs text-[#1a2420] outline-none transition placeholder:text-[#98a39e] focus:border-[#376757]"
                            >
                        </label>
                        <button v-if="filters.search" type="button" class="h-9 rounded-md border border-[#d7e0db] bg-white px-3 text-xs font-semibold text-[#64748b] transition hover:bg-[#f3f6f4]" @click="clearSearch">
                            Clear
                        </button>
                        <button type="submit" class="h-9 rounded-md bg-[#014d3c] px-3.5 text-xs font-semibold text-white transition hover:bg-[#01362a]">
                            Search
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead class="bg-[#f9fbfa]">
                            <tr class="border-b border-[#e8eeea] text-left text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">
                                <th class="px-4 py-2.5">Year</th><th class="px-4 py-2.5">Member Type</th><th class="px-4 py-2.5">Fees</th><th class="px-4 py-2.5">Deadline</th><th class="px-4 py-2.5">Effective Dates</th><th class="px-4 py-2.5 text-center">Assessments</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="schedule in schedules.data" :key="schedule.id" class="border-b border-[#edf2ee] transition hover:bg-[#fcfefd]">
                                <td class="px-4 py-2.5 align-top">
                                    <span class="font-bold text-[#0f172a]">{{ schedule.year }}</span>
                                </td>
                                <td class="px-4 py-2.5 align-top">
                                    <div class="space-y-1">
                                        <p class="font-semibold text-[#0f172a]">{{ schedule.memberType?.code || 'N/A' }}</p>
                                        <p class="text-[0.6rem] text-[#64748b]">{{ schedule.memberType?.name || 'No member type' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 align-top">
                                    <div class="space-y-1">
                                        <p class="text-[#0f172a]">Membership: PHP {{ schedule.fees.membership.toFixed(2) }}</p>
                                        <p class="text-[0.6rem] text-[#64748b]">Annual: PHP {{ schedule.fees.annual.toFixed(2) }} · Mortuary: PHP {{ schedule.fees.mortuary.toFixed(2) }}</p>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top text-[#0f172a]">{{ schedule.renewalDeadline || 'Not set' }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top text-[#0f172a]">{{ schedule.effectiveRange.from }} to {{ schedule.effectiveRange.to }}</td>
                                <td class="px-4 py-2.5 align-top text-center">
                                    <span class="rounded-md bg-[#f1f5f3] px-2 py-1 text-[0.62rem] font-semibold text-[#64748b]">{{ schedule.assessmentsCount }}</span>
                                </td>
                                <td class="px-4 py-2.5 align-top">
                                    <span class="rounded-md px-2 py-1 text-[0.62rem] font-semibold" :class="schedule.status.value === 'active' ? 'bg-[#d9f4c2] text-[#50761b]' : 'bg-[#eceff1] text-[#5e6c74]'">
                                        {{ schedule.status.label }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 align-top">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link
                                            :href="schedule.actions.editUrl"
                                            aria-label="Edit fee schedule" title="Edit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d6dfda] text-[#014d3c] transition hover:bg-[#f3f8f5]"
                                            :class="{ 'pointer-events-none opacity-50': activatingId !== null || deletingId !== null }"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </Link>
                                        <button
                                            v-if="schedule.actions.activateUrl"
                                            type="button"
                                            aria-label="Activate fee schedule" :title="activatingId === schedule.id ? 'Activating' : 'Activate'" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#d5e5c4] bg-[#f5faef] text-[#50761b] transition hover:bg-[#ebf5df] disabled:opacity-50"
                                            :disabled="activatingId !== null || deletingId !== null"
                                            @click="activate(schedule)"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v10"/><path d="M18.4 6.6a9 9 0 1 1-12.8 0"/></svg>
                                        </button>
                                        <button
                                            type="button"
                                            aria-label="Delete fee schedule" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#f2d4c8] bg-[#fff7f3] text-[#c05c3c] transition hover:bg-[#ffede6] disabled:opacity-50"
                                            :disabled="activatingId !== null || deletingId !== null"
                                            :title="schedule.actions.deleteDisabledReason || ''"
                                            @click="destroySchedule(schedule)"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 10v6M14 10v6"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="schedules.data.length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-xs text-[#64748b]">
                                    {{ filters.search ? `No fee schedules match "${filters.search}".` : 'No fee schedules found.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-2 border-t border-[#edf2ee] bg-[#fbfcfb] px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.68rem] text-[#64748b]">Showing {{ schedules.from || 0 }}-{{ schedules.to || 0 }} of {{ schedules.total }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in schedules.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-[#dbe3dd] px-2.5 text-[0.68rem] text-[#9aa6a1]" v-html="link.label" />
                            <Link
                                v-else
                                :href="link.url"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2.5 text-[0.68rem] font-semibold transition"
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
