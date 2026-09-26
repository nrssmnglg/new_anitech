<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
    activeFilterCount: { type: Number, required: true },
    moduleOptions: { type: Array, required: true },
    eventOptions: { type: Array, required: true },
    actorOptions: { type: Array, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const form = reactive({
    search: props.filters.search ?? '',
    actor_user_id: props.filters.actor_user_id ?? '',
    module: props.filters.module ?? '',
    event: props.filters.event ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const applying = ref(false);
const datesOpen = ref(Boolean(props.filters.date_from || props.filters.date_to));
const selectedId = ref(null);

const selected = computed(() => {
    if (selectedId.value !== null) {
        return props.logs.data.find((log) => log.id === selectedId.value) || null;
    }
    return props.logs.data[0] || null;
});

watch(() => props.filters, (newFilters) => {
    Object.assign(form, {
        search: newFilters.search ?? '',
        actor_user_id: newFilters.actor_user_id ?? '',
        module: newFilters.module ?? '',
        event: newFilters.event ?? '',
        date_from: newFilters.date_from ?? '',
        date_to: newFilters.date_to ?? '',
    });
    if (newFilters.date_from || newFilters.date_to) {
        datesOpen.value = true;
    }
});

function applyFilters() {
    if (applying.value) return;
    applying.value = true;

    const payload = Object.fromEntries(
        Object.entries(form).filter(([, value]) => value !== '' && value != null),
    );

    router.get(props.urls.index, payload, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            applying.value = false;
        },
    });
}

function resetFilters() {
    form.search = '';
    form.actor_user_id = '';
    form.module = '';
    form.event = '';
    form.date_from = '';
    form.date_to = '';
    datesOpen.value = false;
    applyFilters();
}

function initials(name) {
    return String(name || 'System')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase() || 'SY';
}

function roleLabel(role) {
    if (role === 'Admin' || role === 'Administrator') return 'Admin';
    if (role === 'Staff') return 'Office Staff';
    return role || 'System';
}

function roleTone(role) {
    if (role === 'Administrator' || role === 'Admin') {
        return 'bg-[#eff6ff] text-[#2563eb] border-[#bfdbfe]';
    }
    if (role === 'Staff') {
        return 'bg-[#ecfdf5] text-[#059669] border-[#a7f3d0]';
    }
    return 'bg-[#f1f5f9] text-[#64748b] border-[#cbd5e1]';
}

function eventTone(event) {
    const ev = String(event || '').toLowerCase();
    if (ev.includes('delete') || ev.includes('reject') || ev.includes('fail') || ev.includes('archive')) {
        return 'bg-rose-50 text-rose-700 border-rose-200/70';
    }
    if (ev.includes('create') || ev.includes('approve') || ev.includes('success') || ev.includes('verify') || ev.includes('add') || ev.includes('release')) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200/70';
    }
    if (ev.includes('update') || ev.includes('edit') || ev.includes('change')) {
        return 'bg-blue-50 text-blue-700 border-blue-200/70';
    }
    return 'bg-slate-100 text-slate-700 border-slate-200/70';
}

function eventDotTone(event) {
    const ev = String(event || '').toLowerCase();
    if (ev.includes('delete') || ev.includes('reject') || ev.includes('fail') || ev.includes('archive')) {
        return 'bg-rose-500';
    }
    if (ev.includes('create') || ev.includes('approve') || ev.includes('success') || ev.includes('verify') || ev.includes('add') || ev.includes('release')) {
        return 'bg-emerald-500';
    }
    if (ev.includes('update') || ev.includes('edit') || ev.includes('change')) {
        return 'bg-blue-500';
    }
    return 'bg-slate-400';
}
</script>

<template>
    <Head title="Activity Logs" />

    <AdminLayout title="Activity Logs">
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-24 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Audit Trail</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Activity Logs</h1>
                        </div>
                    </div>

                    <!-- Summary Pills -->
                    <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                        <article class="px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Today</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-[#7ddfb8]">{{ summary.today ?? 0 }}</p>
                        </article>
                        <article class="border-x border-white/[0.08] px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Total</p>
                            <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.total ?? 0 }}</p>
                        </article>
                        <article class="border-r border-white/[0.08] px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Auth</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-[#fbbf24]">{{ summary.auth ?? 0 }}</p>
                        </article>
                        <article class="px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Admin</p>
                            <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.admin ?? 0 }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Filters Bar -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                <form class="space-y-3" @submit.prevent="applyFilters">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 xl:grid-cols-[1.5fr_1fr_1fr_1fr_auto]">
                        <!-- Search input -->
                        <div class="relative">
                            <span class="sr-only">Search activity</span>
                            <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#94a3b8]" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd"/>
                            </svg>
                            <input
                                v-model="form.search"
                                type="search"
                                placeholder="Search action, user, or record..."
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </div>

                        <!-- User / Actor -->
                        <select
                            v-model="form.actor_user_id"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            @change="applyFilters"
                        >
                            <option value="">All Users</option>
                            <option v-for="option in actorOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>

                        <!-- Module -->
                        <select
                            v-model="form.module"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            @change="applyFilters"
                        >
                            <option value="">All Modules</option>
                            <option v-for="option in moduleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>

                        <!-- Action / Event -->
                        <select
                            v-model="form.event"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            @change="applyFilters"
                        >
                            <option value="">All Actions</option>
                            <option v-for="option in eventOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>

                        <!-- Controls -->
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition-all duration-200"
                                :class="datesOpen || form.date_from || form.date_to ? 'border-[#014d3c] bg-[#e6f5ec] text-[#014d3c]' : 'border-[#dbe3dd] bg-white text-[#64748b] hover:border-[#c2ccc5] hover:bg-[#f4f7f5]'"
                                @click="datesOpen = !datesOpen"
                            >
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd"/>
                                </svg>
                                <span>Dates</span>
                            </button>

                            <button
                                type="submit"
                                class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97] disabled:opacity-60"
                                :disabled="applying"
                            >
                                {{ applying ? 'Searching...' : 'Apply' }}
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Date Range -->
                    <transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                        <div v-if="datesOpen" class="flex flex-wrap items-center gap-3 rounded-lg border border-[#e2eae4] bg-[#f8faf9] p-3">
                            <label class="flex items-center gap-2 text-xs font-semibold text-[#334155]">
                                <span>From:</span>
                                <input
                                    v-model="form.date_from"
                                    type="date"
                                    :max="form.date_to || undefined"
                                    class="h-8 rounded-lg border border-[#dbe3dd] bg-white px-2.5 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]"
                                    @change="applyFilters"
                                >
                            </label>
                            <label class="flex items-center gap-2 text-xs font-semibold text-[#334155]">
                                <span>To:</span>
                                <input
                                    v-model="form.date_to"
                                    type="date"
                                    :min="form.date_from || undefined"
                                    class="h-8 rounded-lg border border-[#dbe3dd] bg-white px-2.5 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]"
                                    @change="applyFilters"
                                >
                            </label>
                            <button
                                v-if="form.date_from || form.date_to"
                                type="button"
                                class="text-xs font-medium text-rose-600 hover:underline"
                                @click="form.date_from = ''; form.date_to = ''; applyFilters();"
                            >
                                Clear dates
                            </button>
                        </div>
                    </transition>

                    <!-- Filter summary footer -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-[#edf2ee] pt-2.5 text-xs text-[#64748b]">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md bg-[#e6f5ec] px-2 py-0.5 text-[0.65rem] font-bold text-[#0f6b45]">
                                {{ logs.total }} record{{ logs.total === 1 ? '' : 's' }}
                            </span>
                            <span v-if="activeFilterCount > 0" class="text-[0.68rem] text-[#64748b]">
                                · {{ activeFilterCount }} active filter{{ activeFilterCount === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <button
                            v-if="activeFilterCount > 0"
                            type="button"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-[#0f6b45] transition hover:text-[#01362a] hover:underline"
                            :disabled="applying"
                            @click="resetFilters"
                        >
                            <svg viewBox="0 0 20 20" class="h-3 w-3" fill="currentColor"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.451a.75.75 0 0 0 0-1.5H4.5a.75.75 0 0 0-.75.75v3.75a.75.75 0 0 0 1.5 0v-2.133l.53.53a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.43-.367ZM4.688 8.576a5.5 5.5 0 0 1 9.201-2.466l.312.311h-2.451a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 .75-.75V3.421a.75.75 0 0 0-1.5 0v2.133l-.53-.53a7 7 0 0 0-11.712 3.138.75.75 0 0 0 1.43.367Z" clip-rule="evenodd"/></svg>
                            Reset all filters
                        </button>
                    </div>
                </form>
            </section>

            <!-- Master-Detail Layout -->
            <div class="grid gap-4 lg:grid-cols-12 items-start">
                <!-- Activity Table Panel -->
                <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm lg:col-span-7 xl:col-span-8">
                    <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                        <div>
                            <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Activity Stream</h2>
                            <p class="mt-0.5 text-[0.68rem] text-[#64748b]">Select any row to view complete activity details</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-[#e8eeea] bg-gradient-to-b from-[#f8faf9] to-[#f3f6f4] text-left text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                                    <th class="px-4 py-3">Timestamp</th>
                                    <th class="px-4 py-3">User</th>
                                    <th class="px-4 py-3">Module</th>
                                    <th class="px-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id"
                                    class="group cursor-pointer border-b border-[#edf2ee] transition-all duration-150 hover:bg-[#f6faf8]"
                                    :class="selected?.id === log.id ? 'bg-[#f0faf5] font-medium' : ''"
                                    @click="selectedId = log.id"
                                >
                                    <td class="whitespace-nowrap px-4 py-3.5 align-middle text-[#334155]">
                                        <div class="flex items-center gap-1.5">
                                            <span v-if="selected?.id === log.id" class="h-1.5 w-1.5 rounded-full bg-[#0f6b45]"></span>
                                            <time class="text-[0.68rem]">{{ log.createdAt }}</time>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5 align-middle">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[0.62rem] font-bold text-[#0f6b45]">
                                                {{ initials(log.actorName) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-[#0f172a] leading-tight">{{ log.actorName }}</p>
                                                <span class="inline-flex items-center rounded px-1.5 py-0.2 text-[0.55rem] font-semibold" :class="roleTone(log.actorRole)">
                                                    {{ roleLabel(log.actorRole) }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5 align-middle">
                                        <span class="inline-flex rounded-md bg-[#f1f5f9] px-2 py-0.5 text-[0.62rem] font-semibold text-[#475569]">
                                            {{ log.module.label }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3.5 align-middle">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border px-2 py-0.5 text-[0.62rem] font-bold" :class="eventTone(log.event.value)">
                                            <span class="h-1.5 w-1.5 rounded-full" :class="eventDotTone(log.event.value)"></span>
                                            {{ log.event.label }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Empty state -->
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="4" class="px-5 py-16 text-center">
                                        <div class="mx-auto flex max-w-xs flex-col items-center">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0faf5]">
                                                <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <p class="mt-3 text-sm font-semibold text-[#334155]">No activities found</p>
                                            <p class="mt-1 text-xs text-[#94a3b8]">Try modifying your filter parameters.</p>
                                            <button
                                                v-if="activeFilterCount > 0"
                                                type="button"
                                                class="mt-3 inline-flex h-8 items-center rounded-lg border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#0f6b45] transition hover:bg-[#f4f7f5]"
                                                @click="resetFilters"
                                            >
                                                Clear filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Numbered Pill Pagination -->
                    <div v-if="logs.last_page > 1" class="flex flex-col gap-2 border-t border-[#edf2ee] bg-gradient-to-b from-[#fbfcfb] to-[#f8faf9] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[0.68rem] text-[#64748b]">Showing <span class="font-semibold text-[#334155]">{{ logs.from || 0 }}</span>–<span class="font-semibold text-[#334155]">{{ logs.to || 0 }}</span> of <span class="font-semibold text-[#334155]">{{ logs.total }}</span></p>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <template v-for="link in logs.links" :key="link.label">
                                <span v-if="!link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] text-[#cbd5e1]" v-html="link.label" />
                                <Link v-else :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] font-semibold transition-all duration-200" :class="link.active ? 'bg-[#014d3c] text-white shadow-sm' : 'text-[#64748b] hover:bg-[#f1f5f9]'" preserve-scroll preserve-state v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </section>

                <!-- Activity Details Panel -->
                <aside class="sticky top-6 overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm lg:col-span-5 xl:col-span-4">
                    <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[#0f6b45]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Activity Details</h2>
                        </div>
                        <span v-if="selected" class="inline-flex items-center gap-1 rounded-lg border px-2 py-0.5 text-[0.62rem] font-bold" :class="eventTone(selected.event.value)">
                            <span class="h-1.5 w-1.5 rounded-full" :class="eventDotTone(selected.event.value)"></span>
                            {{ selected.event.label }}
                        </span>
                    </div>

                    <div v-if="selected" class="space-y-4 p-5">
                        <!-- Key Metadata Card -->
                        <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-4 space-y-2.5 text-xs">
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Timestamp</span>
                                <span class="font-semibold text-[#0f172a] text-right">{{ selected.createdAt }}</span>
                            </div>
                            <div class="flex justify-between items-start gap-2 border-t border-[#edf2ee] pt-2">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Responsible User</span>
                                <span class="font-bold text-[#0f172a] text-right">{{ selected.actorName }}</span>
                            </div>
                            <div class="flex justify-between items-start gap-2 border-t border-[#edf2ee] pt-2">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Role</span>
                                <span class="inline-flex rounded px-2 py-0.5 text-[0.6rem] font-bold" :class="roleTone(selected.actorRole)">
                                    {{ roleLabel(selected.actorRole) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-start gap-2 border-t border-[#edf2ee] pt-2">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Module</span>
                                <span class="font-semibold text-[#0f172a]">{{ selected.module.label }}</span>
                            </div>
                            <div v-if="selected.subjectLabel" class="border-t border-[#edf2ee] pt-2">
                                <p class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Subject Record</p>
                                <p class="mt-0.5 font-bold text-[#0f6b45]">{{ selected.subjectLabel }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="rounded-xl border border-[#e8eeea] bg-white p-3.5">
                            <p class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Summary</p>
                            <p class="mt-1 text-xs leading-relaxed text-[#334155]">{{ selected.description }}</p>
                        </div>

                        <!-- Changes Diff -->
                        <div v-if="selected.changeSummary.length > 0" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h3 class="text-[0.65rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Recorded Changes</h3>
                                <span class="rounded-full bg-[#f1f5f9] px-2 py-0.5 text-[0.6rem] font-bold text-[#475569]">
                                    {{ selected.changeSummary.length }} updated
                                </span>
                            </div>

                            <div
                                v-for="(change, index) in selected.changeSummary"
                                :key="index"
                                class="rounded-xl border border-[#e8eeea] bg-[#fbfcfb] p-3 text-xs"
                            >
                                <p class="font-bold text-[#0f172a]">{{ change.field }}</p>
                                <div class="mt-1.5 flex items-center gap-2 text-[0.68rem]">
                                    <span class="rounded bg-rose-50 px-2 py-0.5 text-rose-700 line-through">{{ change.from || 'None' }}</span>
                                    <svg viewBox="0 0 20 20" class="h-3 w-3 text-[#94a3b8]" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="rounded bg-emerald-50 px-2 py-0.5 font-bold text-emerald-800">{{ change.to || 'None' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Metadata -->
                        <div v-if="selected.metadata.length > 0" class="space-y-2 border-t border-[#edf2ee] pt-3">
                            <h3 class="text-[0.65rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Context Attributes</h3>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div
                                    v-for="item in selected.metadata"
                                    :key="item.key"
                                    class="rounded-lg border border-[#e8eeea] bg-[#f9fbfa] px-3 py-2 text-xs"
                                >
                                    <p class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-[#94a3b8]">{{ item.key }}</p>
                                    <p class="mt-0.5 font-semibold text-[#0f172a] truncate" :title="item.value">{{ item.value }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Link to Record -->
                        <Link
                            v-if="selected.recordUrl"
                            :href="selected.recordUrl"
                            class="inline-flex w-full h-9 items-center justify-center gap-1.5 rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.98]"
                        >
                            <span>Open Related Record</span>
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 0 0-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 0 0 .75-.75v-4a.75.75 0 0 1 1.5 0v4A2.25 2.25 0 0 1 12.75 17h-8.5A2.25 2.25 0 0 1 2 14.75v-8.5A2.25 2.25 0 0 1 4.25 4h4a.75.75 0 0 1 0 1.5h-4Z" clip-rule="evenodd"/>
                                <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 0 0 1.06 1.06L17.5 3.56v3.69a.75.75 0 0 0 1.5 0v-5.5a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0 0 1.5h3.69l-10.246 10.253Z" clip-rule="evenodd"/>
                            </svg>
                        </Link>
                    </div>

                    <div v-else class="p-8 text-center text-xs text-[#94a3b8]">
                        <svg viewBox="0 0 24 24" class="mx-auto h-8 w-8 text-[#cbd5e1]" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                        </svg>
                        <p class="mt-2 font-medium">Select an activity to view details</p>
                    </div>
                </aside>
            </div>
        </div>
    </AdminLayout>
</template>
