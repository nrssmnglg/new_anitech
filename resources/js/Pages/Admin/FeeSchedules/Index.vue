<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
</script>

<template>
    <Head title="Fee Schedules" />

    <AdminLayout title="Fee Schedules">
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-20 top-4 h-20 w-20 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 7h20M2 12h20M2 17h20"/><circle cx="6" cy="7" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="18" cy="17" r="1" fill="currentColor"/></svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Fee Configuration</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Fee Schedules</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Summary pills -->
                        <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Total</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.total }}</p>
                            </article>
                            <article class="border-x border-white/[0.08] px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Active</p>
                                <p class="mt-0.5 text-lg font-bold leading-none text-[#7ddfb8]">{{ summary.active }}</p>
                            </article>
                            <article class="border-r border-white/[0.08] px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Types</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.activeMemberTypes }}</p>
                            </article>
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Latest</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.latest_year }}</p>
                            </article>
                        </div>

                        <Link :href="urls.create" class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-90" fill="currentColor"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                            Add Schedule
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Active schedules -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex items-center gap-2.5 border-b border-[#edf2ee] px-5 py-3.5">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-[#dcfce7] to-[#bbf7d0]">
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 text-[#15803d]" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M12 3l2.5 1.5L17 4l.5 2.5L20 9l-2.5 2.5L17 14l-2.5-.5L12 15l-2.5-1.5L7 14l-.5-2.5L4 9l2.5-2.5L7 4l2.5.5L12 3z"/></svg>
                    </div>
                    <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Active Schedules by Member Type</h2>
                </div>

                <div v-if="activeSchedules.length" class="grid gap-3 p-4 lg:grid-cols-2">
                    <article
                        v-for="schedule in activeSchedules"
                        :key="schedule.id"
                        class="group rounded-xl border border-[#e4ece6] bg-gradient-to-br from-[#f8faf9] to-[#f4f7f5] px-4 py-3.5 transition-all duration-200 hover:border-[#c8d8cc] hover:shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[0.65rem] font-bold text-[#0f6b45]">
                                    {{ schedule.memberType?.code?.charAt(0) || 'F' }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-[#0f172a]">{{ schedule.memberType?.code || 'N/A' }} · {{ schedule.memberType?.name || 'No member type' }}</p>
                                    <p class="mt-0.5 text-[0.62rem] text-[#64748b]">CY {{ schedule.year }} · Deadline {{ schedule.renewalDeadline }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#dcfce7] px-2.5 py-1 text-[0.62rem] font-bold text-[#15803d]">
                                <span class="h-1.5 w-1.5 rounded-full bg-[#22c55e]"></span>
                                Active
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <div class="rounded-lg bg-white px-3 py-2 shadow-sm shadow-[#0f172a]/[0.02]">
                                <p class="text-[0.55rem] font-bold uppercase tracking-[0.08em] text-[#94a3b8]">Membership</p>
                                <p class="mt-1 text-xs font-bold text-[#0f172a]">₱{{ schedule.membershipFee.toFixed(2) }}</p>
                            </div>
                            <div class="rounded-lg bg-white px-3 py-2 shadow-sm shadow-[#0f172a]/[0.02]">
                                <p class="text-[0.55rem] font-bold uppercase tracking-[0.08em] text-[#94a3b8]">Annual</p>
                                <p class="mt-1 text-xs font-bold text-[#0f172a]">₱{{ schedule.annualDue.toFixed(2) }}</p>
                            </div>
                            <div class="rounded-lg bg-white px-3 py-2 shadow-sm shadow-[#0f172a]/[0.02]">
                                <p class="text-[0.55rem] font-bold uppercase tracking-[0.08em] text-[#94a3b8]">Mortuary</p>
                                <p class="mt-1 text-xs font-bold text-[#0f172a]">₱{{ schedule.mortuaryFee.toFixed(2) }}</p>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <div class="mx-auto flex max-w-xs flex-col items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f0faf5]">
                            <svg viewBox="0 0 24 24" class="h-6 w-6 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-[#334155]">No active schedules</p>
                        <p class="mt-1 text-xs text-[#94a3b8]">Create and activate a fee schedule to get started.</p>
                    </div>
                </div>
            </section>

            <!-- Data table -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-[#edf2ee] px-5 py-3.5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Fee Schedule Records</h2>
                        <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ schedules.total }} schedule{{ schedules.total === 1 ? '' : 's' }} found</p>
                    </div>

                    <form class="flex w-full max-w-md items-center gap-2" @submit.prevent="applySearch">
                        <label class="relative flex-1">
                            <span class="sr-only">Search fee schedules</span>
                            <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#94a3b8]" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Year, member type, or status"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </label>
                        <button v-if="filters.search" type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]" @click="clearSearch">Reset</button>
                        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97]">Apply</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#e8eeea] bg-gradient-to-b from-[#f8faf9] to-[#f3f6f4] text-left text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                                <th class="px-5 py-3">Year</th>
                                <th class="px-5 py-3">Member Type</th>
                                <th class="px-5 py-3">Fees</th>
                                <th class="px-5 py-3">Deadline</th>
                                <th class="px-5 py-3">Effective Dates</th>
                                <th class="px-5 py-3 text-center">Assessments</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="schedule in schedules.data" :key="schedule.id" class="group border-b border-[#edf2ee] transition-all duration-150 hover:bg-[#f6faf8]">
                                <td class="px-5 py-3 align-top">
                                    <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] px-2 text-[0.65rem] font-bold text-[#0f6b45]">{{ schedule.year }}</span>
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <p class="font-semibold text-[#0f172a]">{{ schedule.memberType?.code || 'N/A' }}</p>
                                    <p class="mt-0.5 text-[0.6rem] text-[#94a3b8]">{{ schedule.memberType?.name || 'No member type' }}</p>
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <p class="font-semibold text-[#0f172a]">₱{{ schedule.fees.membership.toFixed(2) }}</p>
                                    <p class="mt-0.5 text-[0.6rem] text-[#94a3b8]">Annual ₱{{ schedule.fees.annual.toFixed(2) }} · Mortuary ₱{{ schedule.fees.mortuary.toFixed(2) }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 align-top text-[#334155]">{{ schedule.renewalDeadline || 'Not set' }}</td>
                                <td class="whitespace-nowrap px-5 py-3 align-top text-[#334155]">{{ schedule.effectiveRange.from }} – {{ schedule.effectiveRange.to }}</td>
                                <td class="px-5 py-3 align-top text-center">
                                    <span
                                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg px-2 text-[0.62rem] font-bold transition-colors"
                                        :class="schedule.assessmentsCount > 0 ? 'bg-[#fef3c7] text-[#b45309]' : 'bg-[#f1f5f9] text-[#94a3b8]'"
                                    >{{ schedule.assessmentsCount }}</span>
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[0.62rem] font-bold"
                                        :class="schedule.status.value === 'active' ? 'bg-[#dcfce7] text-[#15803d]' : 'bg-[#f1f5f9] text-[#64748b]'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="schedule.status.value === 'active' ? 'bg-[#22c55e]' : 'bg-[#94a3b8]'"></span>
                                        {{ schedule.status.label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 align-top text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-60 transition-opacity duration-200 group-hover:opacity-100">
                                        <Link
                                            :href="schedule.actions.editUrl"
                                            aria-label="Edit fee schedule" title="Edit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#475569] transition-all duration-150 hover:bg-[#f1f5f9]"
                                            :class="{ 'pointer-events-none opacity-50': activatingId !== null || deletingId !== null }"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </Link>
                                        <button
                                            v-if="schedule.actions.activateUrl"
                                            type="button"
                                            aria-label="Activate fee schedule" :title="activatingId === schedule.id ? 'Activating' : 'Activate'"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#15803d] transition-all duration-150 hover:bg-[#dcfce7] disabled:opacity-50"
                                            :disabled="activatingId !== null || deletingId !== null"
                                            @click="activate(schedule)"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v10"/><path d="M18.4 6.6a9 9 0 1 1-12.8 0"/></svg>
                                        </button>
                                        <button
                                            type="button"
                                            aria-label="Delete fee schedule"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#dc2626] transition-all duration-150 hover:bg-[#fef2f2] disabled:opacity-50"
                                            :disabled="activatingId !== null || deletingId !== null"
                                            :title="schedule.actions.deleteDisabledReason || 'Delete'"
                                            @click="destroySchedule(schedule)"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 10v6M14 10v6"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="schedules.data.length === 0">
                                <td colspan="8" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-xs flex-col items-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0faf5]">
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 7h20M2 12h20M2 17h20"/></svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-[#334155]">{{ filters.search ? 'No matching schedules' : 'No fee schedules' }}</p>
                                        <p class="mt-1 text-xs text-[#94a3b8]">{{ filters.search ? `No results for "${filters.search}".` : 'Add a fee schedule to get started.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col gap-2 border-t border-[#edf2ee] bg-gradient-to-b from-[#fbfcfb] to-[#f8faf9] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.68rem] text-[#64748b]">Showing <span class="font-semibold text-[#334155]">{{ schedules.from || 0 }}</span>–<span class="font-semibold text-[#334155]">{{ schedules.to || 0 }}</span> of <span class="font-semibold text-[#334155]">{{ schedules.total }}</span></p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template v-for="link in schedules.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] text-[#cbd5e1]" v-html="link.label" />
                            <Link v-else :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] font-semibold transition-all duration-200" :class="link.active ? 'bg-[#014d3c] text-white shadow-sm' : 'text-[#64748b] hover:bg-[#f1f5f9]'" preserve-scroll preserve-state v-html="link.label" />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
