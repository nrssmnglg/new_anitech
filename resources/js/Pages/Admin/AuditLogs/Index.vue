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
        <div class="space-y-6">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-[24px] border border-[#dce5df] bg-white p-5 shadow-[0_14px_34px_rgba(15,23,42,0.05)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.13em] text-[#708078]">{{ card.label }}</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.04em] text-[#102f26]">{{ card.value }}</p>
                            <p class="mt-1 text-xs text-[#7a8981]">{{ card.helper }}</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl text-xl font-black" :class="card.tone">{{ card.icon }}</span>
                    </div>
                </article>
            </section>

            <section class="rounded-[26px] border border-[#dce5df] bg-white shadow-[0_16px_38px_rgba(15,23,42,0.05)]">
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <form class="relative min-w-0 flex-1" @submit.prevent="applyFilters">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-[#708078]">⌕</span>
                        <input
                            v-model="form.search"
                            type="search"
                            placeholder="Search an action, person, module, or record..."
                            class="w-full rounded-2xl border border-[#d8e1db] bg-[#f9fbfa] py-3 pl-11 pr-4 text-sm text-[#17382e] outline-none transition focus:border-[#17634d] focus:bg-white focus:ring-4 focus:ring-[#17634d]/10"
                        >
                    </form>
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl border px-5 py-3 text-sm font-bold transition" :class="filtersOpen || activeFilterCount ? 'border-[#17634d] bg-[#edf6f1] text-[#145642]' : 'border-[#d8e1db] text-[#52645b] hover:bg-[#f5f8f6]'" @click="filtersOpen = !filtersOpen">
                        Filters
                        <span v-if="activeFilterCount" class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-[#17634d] px-1.5 text-xs text-white">{{ activeFilterCount }}</span>
                    </button>
                    <button type="button" class="rounded-2xl bg-[#0d4d3b] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#083c2e] disabled:opacity-60" :disabled="applying" @click="applyFilters">
                        {{ applying ? 'Loading…' : 'Search logs' }}
                    </button>
                </div>

                <div v-if="filtersOpen" class="border-t border-[#e4ebe7] bg-[#f8faf9] px-5 py-5 sm:px-6">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <label class="space-y-2 text-sm font-semibold text-[#40534a]">
                            <span>Area of the system</span>
                            <select v-model="form.module" class="w-full rounded-xl border border-[#d6e0da] bg-white px-4 py-3 text-sm outline-none focus:border-[#17634d]">
                                <option value="">Every module</option>
                                <option v-for="option in moduleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                        <label class="space-y-2 text-sm font-semibold text-[#40534a]">
                            <span>Type of action</span>
                            <select v-model="form.event" class="w-full rounded-xl border border-[#d6e0da] bg-white px-4 py-3 text-sm outline-none focus:border-[#17634d]">
                                <option value="">Every action</option>
                                <option v-for="option in eventOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                        <label class="space-y-2 text-sm font-semibold text-[#40534a]">
                            <span>Performed by</span>
                            <select v-model="form.actor_user_id" class="w-full rounded-xl border border-[#d6e0da] bg-white px-4 py-3 text-sm outline-none focus:border-[#17634d]">
                                <option value="">Everyone</option>
                                <option v-for="option in actorOptions" :key="option.value" :value="String(option.value)">{{ option.label }}</option>
                            </select>
                        </label>
                        <label class="space-y-2 text-sm font-semibold text-[#40534a]">
                            <span>From date</span>
                            <input v-model="form.date_from" type="date" :max="form.date_to || undefined" class="w-full rounded-xl border border-[#d6e0da] bg-white px-4 py-3 text-sm outline-none focus:border-[#17634d]">
                        </label>
                        <label class="space-y-2 text-sm font-semibold text-[#40534a]">
                            <span>To date</span>
                            <input v-model="form.date_to" type="date" :min="form.date_from || undefined" class="w-full rounded-xl border border-[#d6e0da] bg-white px-4 py-3 text-sm outline-none focus:border-[#17634d]">
                        </label>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button" class="rounded-xl bg-[#17634d] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60" :disabled="applying" @click="applyFilters">Apply filters</button>
                        <button type="button" class="rounded-xl px-4 py-2.5 text-sm font-bold text-[#596a62] hover:bg-[#edf2ef]" :disabled="applying" @click="resetFilters">Clear all</button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[28px] border border-[#dce5df] bg-white shadow-[0_18px_42px_rgba(15,23,42,0.06)]">
                <div class="flex flex-col gap-2 border-b border-[#e2e9e5] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-black tracking-[-0.025em] text-[#12372c]">Activity timeline</h2>
                        <p class="mt-1 text-sm text-[#738078]">Newest actions appear first.</p>
                    </div>
                    <p class="text-sm font-semibold text-[#5f7067]">{{ logs.total }} matching {{ logs.total === 1 ? 'entry' : 'entries' }}</p>
                </div>

                <div v-if="logs.data.length" class="px-5 py-2 sm:px-7">
                    <article v-for="(log, index) in logs.data" :key="log.id" class="relative grid grid-cols-[42px_minmax(0,1fr)] gap-4 py-5 sm:grid-cols-[54px_minmax(0,1fr)] sm:gap-5">
                        <div v-if="index < logs.data.length - 1" class="absolute bottom-0 left-[25px] top-[54px] w-px bg-[#dce5df] sm:left-[33px]"></div>
                        <div class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full text-sm font-black text-white ring-8 sm:h-12 sm:w-12" :class="eventStyle(log.event.value).dot">
                            {{ eventStyle(log.event.value).icon }}
                        </div>

                        <div class="min-w-0 rounded-[22px] border border-[#e1e8e4] bg-[#fbfcfb] p-5 transition hover:border-[#cbd8d1] hover:bg-white hover:shadow-[0_12px_28px_rgba(15,23,42,0.05)]">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em]" :class="eventStyle(log.event.value).badge">{{ log.event.label }}</span>
                                        <span class="rounded-full bg-[#edf2ef] px-3 py-1 text-[0.68rem] font-bold uppercase tracking-[0.12em] text-[#5b6d64]">{{ log.module.label }}</span>
                                    </div>
                                    <h3 class="mt-3 text-base font-black leading-6 text-[#142f27] sm:text-lg">{{ log.description }}</h3>

                                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                                        <div class="flex items-center gap-2.5">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#dcece4] text-[0.68rem] font-black text-[#155a45]">{{ actorInitials(log.actorName) }}</span>
                                            <div class="leading-tight">
                                                <p class="text-sm font-bold text-[#28463c]">{{ log.actorName }}</p>
                                                <p class="text-xs text-[#7b8982]">{{ log.actorRole || 'Automated system' }}</p>
                                            </div>
                                        </div>
                                        <span class="hidden h-1 w-1 rounded-full bg-[#aab6b0] sm:block"></span>
                                        <time class="text-sm text-[#66766e]">{{ log.createdAt }}</time>
                                    </div>
                                </div>

                                <Link v-if="log.recordUrl" :href="log.recordUrl" class="inline-flex shrink-0 items-center gap-2 self-start rounded-xl border border-[#cbd9d2] bg-white px-4 py-2.5 text-sm font-bold text-[#145c47] transition hover:border-[#145c47] hover:bg-[#f0f7f3]">
                                    Open record <span aria-hidden="true">→</span>
                                </Link>
                            </div>

                            <div v-if="log.subjectLabel" class="mt-4 rounded-xl border border-[#dfe7e2] bg-white px-4 py-3">
                                <p class="text-[0.67rem] font-black uppercase tracking-[0.14em] text-[#819087]">Affected record</p>
                                <p class="mt-1 text-sm font-semibold text-[#29483d]">{{ log.subjectLabel }}</p>
                            </div>

                            <div v-if="log.changeSummary.length || log.metadata.length" class="mt-4 border-t border-[#e1e8e4] pt-4">
                                <button type="button" class="flex w-full items-center justify-between gap-3 text-left text-sm font-bold text-[#476158]" :aria-expanded="isExpanded(log.id)" @click="toggleDetails(log.id)">
                                    <span>{{ isExpanded(log.id) ? 'Hide technical details' : `View details${log.changeSummary.length ? ` and ${log.changeSummary.length} recorded change${log.changeSummary.length === 1 ? '' : 's'}` : ''}` }}</span>
                                    <span class="text-lg transition" :class="isExpanded(log.id) ? 'rotate-180' : ''">⌄</span>
                                </button>

                                <div v-if="isExpanded(log.id)" class="mt-4 space-y-4">
                                    <div v-if="log.changeSummary.length" class="grid gap-3 lg:grid-cols-3">
                                        <div v-for="change in log.changeSummary" :key="`${log.id}-${change.field}`" class="rounded-xl border border-[#dce5df] bg-white p-4">
                                            <p class="text-xs font-black uppercase tracking-[0.12em] text-[#74847b]">{{ change.field }}</p>
                                            <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-sm">
                                                <span class="break-words rounded-lg bg-[#f3f5f4] px-2.5 py-2 text-[#68766f]">{{ change.from }}</span>
                                                <span class="text-[#92a098]">→</span>
                                                <span class="break-words rounded-lg bg-[#eaf5ef] px-2.5 py-2 font-bold text-[#17634d]">{{ change.to }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="log.metadata.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                        <div v-for="item in log.metadata" :key="`${log.id}-${item.key}`" class="rounded-xl bg-[#f2f5f3] px-4 py-3">
                                            <p class="text-[0.67rem] font-black uppercase tracking-[0.12em] text-[#7b8982]">{{ item.key }}</p>
                                            <p class="mt-1.5 break-words text-sm font-semibold text-[#334e44]">{{ item.value }}</p>
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
