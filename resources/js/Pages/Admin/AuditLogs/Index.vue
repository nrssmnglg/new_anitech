<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
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
    module: props.filters.module ?? '',
    event: props.filters.event ?? '',
    actor_user_id: props.filters.actor_user_id ? String(props.filters.actor_user_id) : '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    search: props.filters.search ?? '',
});

const applying = ref(false);
const filtersOpen = ref(props.activeFilterCount > 0);
const expandedIds = ref([]);

const summaryCards = computed(() => [
    {
        label: 'All recorded actions',
        value: props.summary.total ?? 0,
        helper: 'Complete audit history',
        tone: 'bg-[#e8f3ed] text-[#145c47]',
        icon: '≡',
    },
    {
        label: 'Actions today',
        value: props.summary.today ?? 0,
        helper: 'Since midnight',
        tone: 'bg-[#e9f1ff] text-[#2457a7]',
        icon: '↻',
    },
    {
        label: 'Login activity',
        value: props.summary.auth ?? 0,
        helper: 'Sign-ins and access events',
        tone: 'bg-[#fff2d9] text-[#9a5b00]',
        icon: '↪',
    },
    {
        label: 'Admin actions',
        value: props.summary.admin ?? 0,
        helper: 'Performed by administrators',
        tone: 'bg-[#f0eaff] text-[#6844a5]',
        icon: '◆',
    },
]);

function applyFilters() {
    if (applying.value) return;

    applying.value = true;
    router.get(props.urls.index, {
        module: form.module || undefined,
        event: form.event || undefined,
        actor_user_id: form.actor_user_id || undefined,
        date_from: form.date_from || undefined,
        date_to: form.date_to || undefined,
        search: form.search || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            applying.value = false;
        },
    });
}

function resetFilters() {
    if (applying.value) return;

    Object.assign(form, {
        module: '',
        event: '',
        actor_user_id: '',
        date_from: '',
        date_to: '',
        search: '',
    });
    applyFilters();
}

function toggleDetails(id) {
    expandedIds.value = expandedIds.value.includes(id)
        ? expandedIds.value.filter((item) => item !== id)
        : [...expandedIds.value, id];
}

function isExpanded(id) {
    return expandedIds.value.includes(id);
}

function eventStyle(event) {
    const value = String(event || '').toLowerCase();

    if (value.includes('delete') || value.includes('reject') || value.includes('fail')) {
        return {
            icon: '!',
            badge: 'bg-[#fff0f1] text-[#ad3443]',
            dot: 'bg-[#d54a58] ring-[#ffe0e3]',
        };
    }

    if (value.includes('create') || value.includes('approve') || value.includes('success')) {
        return {
            icon: '+',
            badge: 'bg-[#e7f6ed] text-[#167049]',
            dot: 'bg-[#239263] ring-[#d8f2e4]',
        };
    }

    if (value.includes('login') || value.includes('logout') || value.includes('auth')) {
        return {
            icon: '↪',
            badge: 'bg-[#fff3df] text-[#956000]',
            dot: 'bg-[#d99517] ring-[#ffedc9]',
        };
    }

    if (value.includes('update') || value.includes('edit') || value.includes('change')) {
        return {
            icon: '↻',
            badge: 'bg-[#e9f1ff] text-[#285faa]',
            dot: 'bg-[#4c7fc4] ring-[#dce9ff]',
        };
    }

    return {
        icon: '•',
        badge: 'bg-[#eef2f0] text-[#52635b]',
        dot: 'bg-[#718079] ring-[#e5ebe8]',
    };
}

function actorInitials(name) {
    return String(name || 'System')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}
</script>

<template>
    <Head title="Activity Logs" />

    <AdminLayout title="Activity Logs">
        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">System audit</p>
                <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">Activity Logs</h1>
            </section>

            <section class="grid grid-cols-2 gap-2 xl:grid-cols-4">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-lg border border-[#dce5df] bg-white px-3 py-2.5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#708078]">{{ card.label }}</p>
                            <p class="mt-1 text-xl font-semibold leading-none text-[#102f26]">{{ card.value }}</p>
                        </div>
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md text-xs font-semibold" :class="card.tone">{{ card.icon }}</span>
                    </div>
                </article>
            </section>

            <section class="rounded-lg border border-[#dce5df] bg-white">
                <div class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <form class="relative min-w-0 flex-1" @submit.prevent="applyFilters">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-[#708078]">⌕</span>
                        <input
                            v-model="form.search"
                            type="search"
                            placeholder="Search an action, person, module, or record..."
                            class="h-9 w-full rounded-md border border-[#d8e1db] bg-[#f9fbfa] pl-9 pr-3 text-xs text-[#17382e] outline-none transition focus:border-[#17634d] focus:bg-white"
                        >
                    </form>
                    <button type="button" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md border px-3 text-xs font-semibold transition" :class="filtersOpen || activeFilterCount ? 'border-[#17634d] bg-[#edf6f1] text-[#145642]' : 'border-[#d8e1db] text-[#52645b] hover:bg-[#f5f8f6]'" @click="filtersOpen = !filtersOpen">
                        Filters
                        <span v-if="activeFilterCount" class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-[#17634d] px-1.5 text-xs text-white">{{ activeFilterCount }}</span>
                    </button>
                    <button type="button" class="h-9 rounded-md bg-[#0d4d3b] px-4 text-xs font-semibold text-white transition hover:bg-[#083c2e] disabled:opacity-60" :disabled="applying" @click="applyFilters">
                        {{ applying ? 'Loading…' : 'Search logs' }}
                    </button>
                </div>

                <div v-if="filtersOpen" class="border-t border-[#e4ebe7] bg-[#f8faf9] p-3">
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-5">
                        <label class="space-y-1 text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#40534a]">
                            <span>Area of the system</span>
                            <select v-model="form.module" class="h-9 w-full rounded-md border border-[#d6e0da] bg-white px-3 text-xs normal-case tracking-normal outline-none focus:border-[#17634d]">
                                <option value="">Every module</option>
                                <option v-for="option in moduleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                        <label class="space-y-1 text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#40534a]">
                            <span>Type of action</span>
                            <select v-model="form.event" class="h-9 w-full rounded-md border border-[#d6e0da] bg-white px-3 text-xs normal-case tracking-normal outline-none focus:border-[#17634d]">
                                <option value="">Every action</option>
                                <option v-for="option in eventOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                        <label class="space-y-1 text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#40534a]">
                            <span>Performed by</span>
                            <select v-model="form.actor_user_id" class="h-9 w-full rounded-md border border-[#d6e0da] bg-white px-3 text-xs normal-case tracking-normal outline-none focus:border-[#17634d]">
                                <option value="">Everyone</option>
                                <option v-for="option in actorOptions" :key="option.value" :value="String(option.value)">{{ option.label }}</option>
                            </select>
                        </label>
                        <label class="space-y-1 text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#40534a]">
                            <span>From date</span>
                            <input v-model="form.date_from" type="date" :max="form.date_to || undefined" class="h-9 w-full rounded-md border border-[#d6e0da] bg-white px-3 text-xs normal-case tracking-normal outline-none focus:border-[#17634d]">
                        </label>
                        <label class="space-y-1 text-[0.6rem] font-semibold uppercase tracking-[0.06em] text-[#40534a]">
                            <span>To date</span>
                            <input v-model="form.date_to" type="date" :min="form.date_from || undefined" class="h-9 w-full rounded-md border border-[#d6e0da] bg-white px-3 text-xs normal-case tracking-normal outline-none focus:border-[#17634d]">
                        </label>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button type="button" class="h-8 rounded-md bg-[#17634d] px-3 text-[0.68rem] font-semibold text-white disabled:opacity-60" :disabled="applying" @click="applyFilters">Apply</button>
                        <button type="button" class="h-8 rounded-md border border-[#d6e0da] px-3 text-[0.68rem] font-semibold text-[#596a62] hover:bg-[#edf2ef]" :disabled="applying" @click="resetFilters">Clear</button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-[#dce5df] bg-white">
                <div class="flex flex-col gap-1 border-b border-[#e2e9e5] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-[#12372c]">Activity timeline</h2>
                        <p class="mt-0.5 text-[0.65rem] text-[#738078]">Newest actions first</p>
                    </div>
                    <p class="text-[0.65rem] font-semibold text-[#5f7067]">{{ logs.total }} {{ logs.total === 1 ? 'entry' : 'entries' }}</p>
                </div>

                <div v-if="logs.data.length" class="px-3 py-1 sm:px-4">
                    <article v-for="(log, index) in logs.data" :key="log.id" class="relative grid grid-cols-[30px_minmax(0,1fr)] gap-2.5 py-2.5">
                        <div v-if="index < logs.data.length - 1" class="absolute bottom-0 left-[14px] top-[34px] w-px bg-[#dce5df]"></div>
                        <div class="relative z-10 flex h-7 w-7 items-center justify-center rounded-full text-[0.65rem] font-semibold text-white ring-4" :class="eventStyle(log.event.value).dot">
                            {{ eventStyle(log.event.value).icon }}
                        </div>

                        <div class="min-w-0 rounded-md border border-[#e1e8e4] bg-[#fbfcfb] p-3 transition hover:border-[#cbd8d1] hover:bg-white">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2 py-0.5 text-[0.58rem] font-semibold uppercase tracking-[0.08em]" :class="eventStyle(log.event.value).badge">{{ log.event.label }}</span>
                                        <span class="rounded-full bg-[#edf2ef] px-2 py-0.5 text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#5b6d64]">{{ log.module.label }}</span>
                                    </div>
                                    <h3 class="mt-1.5 text-xs font-semibold leading-5 text-[#142f27]">{{ log.description }}</h3>

                                    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#dcece4] text-[0.55rem] font-semibold text-[#155a45]">{{ actorInitials(log.actorName) }}</span>
                                            <div class="leading-tight">
                                                <p class="text-[0.68rem] font-semibold text-[#28463c]">{{ log.actorName }}</p>
                                                <p class="text-[0.58rem] text-[#7b8982]">{{ log.actorRole || 'Automated system' }}</p>
                                            </div>
                                        </div>
                                        <span class="hidden h-1 w-1 rounded-full bg-[#aab6b0] sm:block"></span>
                                        <time class="text-[0.65rem] text-[#66766e]">{{ log.createdAt }}</time>
                                    </div>
                                </div>

                                <Link v-if="log.recordUrl" :href="log.recordUrl" class="inline-flex h-8 w-8 shrink-0 items-center justify-center self-start rounded-md border border-[#cbd9d2] bg-white text-[#145c47] transition hover:border-[#145c47] hover:bg-[#f0f7f3]" title="Open record" aria-label="Open record">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5h5v5M19 5l-8 8M18 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" />
                                    </svg>
                                </Link>
                            </div>

                            <div v-if="log.subjectLabel" class="mt-2 rounded-md border border-[#dfe7e2] bg-white px-3 py-2">
                                <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-[#819087]">Affected record</p>
                                <p class="mt-0.5 text-[0.68rem] font-semibold text-[#29483d]">{{ log.subjectLabel }}</p>
                            </div>

                            <div v-if="log.changeSummary.length || log.metadata.length" class="mt-2 border-t border-[#e1e8e4] pt-2">
                                <button type="button" class="flex w-full items-center justify-between gap-3 text-left text-[0.68rem] font-semibold text-[#476158]" :aria-expanded="isExpanded(log.id)" @click="toggleDetails(log.id)">
                                    <span>{{ isExpanded(log.id) ? 'Hide technical details' : `View details${log.changeSummary.length ? ` and ${log.changeSummary.length} recorded change${log.changeSummary.length === 1 ? '' : 's'}` : ''}` }}</span>
                                    <span class="text-lg transition" :class="isExpanded(log.id) ? 'rotate-180' : ''">⌄</span>
                                </button>

                                <div v-if="isExpanded(log.id)" class="mt-2 space-y-2">
                                    <div v-if="log.changeSummary.length" class="grid gap-2 lg:grid-cols-3">
                                        <div v-for="change in log.changeSummary" :key="`${log.id}-${change.field}`" class="rounded-md border border-[#dce5df] bg-white p-2.5">
                                            <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-[#74847b]">{{ change.field }}</p>
                                            <div class="mt-1.5 grid grid-cols-[1fr_auto_1fr] items-center gap-1.5 text-[0.65rem]">
                                                <span class="break-words rounded bg-[#f3f5f4] px-2 py-1.5 text-[#68766f]">{{ change.from }}</span>
                                                <span class="text-[#92a098]">→</span>
                                                <span class="break-words rounded bg-[#eaf5ef] px-2 py-1.5 font-semibold text-[#17634d]">{{ change.to }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="log.metadata.length" class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                        <div v-for="item in log.metadata" :key="`${log.id}-${item.key}`" class="rounded-md bg-[#f2f5f3] px-3 py-2">
                                            <p class="text-[0.55rem] font-semibold uppercase tracking-[0.08em] text-[#7b8982]">{{ item.key }}</p>
                                            <p class="mt-0.5 break-words text-[0.65rem] font-semibold text-[#334e44]">{{ item.value }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="px-6 py-16 text-center">
                    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#edf3ef] text-2xl text-[#557168]">⌕</span>
                    <h3 class="mt-4 text-lg font-black text-[#17382e]">No activity matches these filters</h3>
                    <p class="mt-2 text-sm text-[#74827b]">Clear one or more filters to see a broader history.</p>
                    <button type="button" class="mt-5 rounded-xl bg-[#17634d] px-5 py-2.5 text-sm font-bold text-white" @click="resetFilters">Clear all filters</button>
                </div>

                <div class="flex flex-col gap-4 border-t border-[#e2e9e5] bg-[#f8faf9] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[#68776f]">Showing {{ logs.from || 0 }}–{{ logs.to || 0 }} of {{ logs.total }} entries</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in logs.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-[#dde5e0] px-3 text-sm text-[#9aa6a1]" v-html="link.label" />
                            <Link v-else :href="link.url" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-bold transition" :class="link.active ? 'border-[#145c47] bg-[#145c47] text-white' : 'border-[#d8e1db] bg-white text-[#5d6c65] hover:border-[#9fb5aa]'" preserve-scroll preserve-state v-html="link.label" />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
