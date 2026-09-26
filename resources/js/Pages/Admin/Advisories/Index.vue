<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    advisories: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
    audienceOptions: { type: Array, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const filterForm = reactive({
    status: props.filters.status ?? 'All',
    audience_type: props.filters.audience_type ?? 'All',
});

const busyAction = ref(null);

const publishedRate = computed(() => {
    if (!props.summary.total) {
        return 0;
    }

    return Math.round((props.summary.published / props.summary.total) * 100);
});

function applyFilters() {
    router.get(props.urls.index, {
        status: filterForm.status,
        audience_type: filterForm.audience_type,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.status = 'All';
    filterForm.audience_type = 'All';
    applyFilters();
}

function publishAdvisory(advisory) {
    if (!advisory.actions.publish || busyAction.value !== null) {
        return;
    }

    if (!window.confirm(`Publish "${advisory.title}" now?`)) {
        return;
    }

    busyAction.value = `publish-${advisory.id}`;

    router.post(advisory.actions.publish, {}, {
        preserveScroll: true,
        onFinish: () => {
            busyAction.value = null;
        },
    });
}

function unpublishAdvisory(advisory) {
    if (!advisory.actions.unpublish || busyAction.value !== null) {
        return;
    }

    if (!window.confirm(`Move "${advisory.title}" back to draft?`)) {
        return;
    }

    busyAction.value = `unpublish-${advisory.id}`;

    router.post(advisory.actions.unpublish, {}, {
        preserveScroll: true,
        onFinish: () => {
            busyAction.value = null;
        },
    });
}

function statusTone(status) {
    if (status === 'Published') {
        return 'bg-[#dcfce7] text-[#15803d] border-[#bbf7d0]';
    }

    if (status === 'Draft') {
        return 'bg-[#fffbeb] text-[#d97706] border-[#fde68a]';
    }

    return 'bg-[#f1f5f9] text-[#64748b] border-[#cbd5e1]';
}

function statusDotTone(status) {
    if (status === 'Published') return 'bg-[#22c55e]';
    if (status === 'Draft') return 'bg-[#f59e0b]';
    return 'bg-[#94a3b8]';
}

function advisoryIconTone(status) {
    if (status === 'Published') {
        return 'bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[#0f6b45]';
    }

    if (status === 'Draft') {
        return 'bg-gradient-to-br from-[#fef3c7] to-[#fde68a] text-[#b45309]';
    }

    return 'bg-[#f1f5f9] text-[#64748b]';
}
</script>

<template>
    <Head title="Advisories" />

    <AdminLayout title="Advisories">
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
                                <path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 10a6 6 0 00-6-6v12a6 6 0 006-6v0z" />
                                <path d="M18 10l3 2v-4l-3 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Communication</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Advisories</h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Summary Pills -->
                        <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Total</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.total }}</p>
                            </article>
                            <article class="border-x border-white/[0.08] px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Published</p>
                                <p class="mt-0.5 text-lg font-bold leading-none text-[#7ddfb8]">{{ summary.published }}</p>
                            </article>
                            <article class="border-r border-white/[0.08] px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Drafts</p>
                                <p class="mt-0.5 text-lg font-bold leading-none text-[#fbbf24]">{{ summary.drafts }}</p>
                            </article>
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Archived</p>
                                <p class="mt-0.5 text-lg font-bold leading-none text-white/70">{{ summary.archived }}</p>
                            </article>
                        </div>

                        <Link :href="urls.create" class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-90" fill="currentColor">
                                <path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/>
                            </svg>
                            Create Advisory
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Filters Bar -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_auto] xl:items-end">
                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select
                            v-model="filterForm.status"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Target Audience</span>
                        <select
                            v-model="filterForm.audience_type"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option v-for="option in audienceOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <div class="flex items-center gap-2 xl:justify-end">
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97]"
                            @click="applyFilters"
                        >
                            Apply
                        </button>
                        <button
                            v-if="filterForm.status !== 'All' || filterForm.audience_type !== 'All'"
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-3.5 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]"
                            @click="resetFilters"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </section>

            <!-- Advisories Table Card -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                    <div>
                        <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Broadcast Advisories</h2>
                        <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ advisories.total }} broadcast record{{ advisories.total === 1 ? '' : 's' }} found</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#e8eeea] bg-gradient-to-b from-[#f8faf9] to-[#f3f6f4] text-left text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                                <th class="px-5 py-3">Advisory</th>
                                <th class="px-5 py-3">Audience</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-center">Files</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="advisory in advisories.data" :key="advisory.id" class="group border-b border-[#edf2ee] transition-all duration-150 hover:bg-[#f6faf8]">
                                <td class="px-5 py-3.5 align-middle">
                                    <div class="flex items-start gap-3">
                                        <div class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold" :class="advisoryIconTone(advisory.status)">
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                                <path v-if="advisory.status === 'Published'" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 10a6 6 0 00-6-6v12a6 6 0 006-6v0z" />
                                                <path v-else-if="advisory.status === 'Draft'" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                <path v-else d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-[#0f172a] leading-tight">{{ advisory.title }}</p>
                                            <p class="mt-1 text-[0.62rem] text-[#64748b]">
                                                {{ advisory.publishedAt ? `Published ${advisory.publishedAt}` : `Created ${advisory.createdAt}` }}
                                                <span v-if="advisory.publisher"> · {{ advisory.status === 'Draft' ? `Drafted by ${advisory.publisher}` : `By ${advisory.publisher}` }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-3.5 align-middle">
                                    <span class="inline-flex rounded-lg bg-[#f1f5f9] px-2.5 py-1 text-[0.62rem] font-bold text-[#475569]">
                                        {{ advisory.audienceLabel }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 align-middle">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[0.62rem] font-bold" :class="statusTone(advisory.status)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusDotTone(advisory.status)"></span>
                                        {{ advisory.status }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 align-middle text-center">
                                    <span
                                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg px-2 text-[0.65rem] font-bold"
                                        :class="advisory.attachmentsCount > 0 ? 'bg-[#e6f5ec] text-[#0f6b45]' : 'bg-[#f1f5f9] text-[#94a3b8]'"
                                    >
                                        {{ advisory.attachmentsCount || 0 }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 align-middle text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-70 transition-opacity duration-200 group-hover:opacity-100">
                                        <Link
                                            :href="advisory.actions.show"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#014d3c] transition hover:bg-[#e6f5ec]"
                                            title="View advisory"
                                            aria-label="View advisory"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6Z" />
                                                <circle cx="12" cy="12" r="2.5" />
                                            </svg>
                                        </Link>

                                        <Link
                                            :href="advisory.actions.edit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#475569] transition hover:bg-[#f1f5f9]"
                                            title="Edit advisory"
                                            aria-label="Edit advisory"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                            </svg>
                                        </Link>

                                        <button
                                            v-if="advisory.actions.publish"
                                            type="button"
                                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-[#bbf7d0] bg-[#f0fdf4] px-2.5 text-[0.65rem] font-bold text-[#15803d] transition hover:bg-[#dcfce7] disabled:opacity-50"
                                            :disabled="busyAction !== null"
                                            title="Publish advisory"
                                            @click="publishAdvisory(advisory)"
                                        >
                                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                                            <span>{{ busyAction === `publish-${advisory.id}` ? '...' : 'Publish' }}</span>
                                        </button>

                                        <button
                                            v-if="advisory.actions.unpublish"
                                            type="button"
                                            class="inline-flex h-8 items-center gap-1 rounded-lg border border-[#fed7aa] bg-[#fffaf5] px-2.5 text-[0.65rem] font-bold text-[#c2410c] transition hover:bg-[#ffedd5] disabled:opacity-50"
                                            :disabled="busyAction !== null"
                                            title="Unpublish advisory"
                                            @click="unpublishAdvisory(advisory)"
                                        >
                                            <span>{{ busyAction === `unpublish-${advisory.id}` ? '...' : 'Unpublish' }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="advisories.data.length === 0">
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-xs flex-col items-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0faf5]">
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 10a6 6 0 00-6-6v12a6 6 0 006-6v0z" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-[#334155]">No advisories found</p>
                                        <p class="mt-1 text-xs text-[#94a3b8]">No broadcast records match your filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Numbered Pill Pagination -->
                <div v-if="advisories.last_page > 1" class="flex flex-col gap-2 border-t border-[#edf2ee] bg-gradient-to-b from-[#fbfcfb] to-[#f8faf9] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.68rem] text-[#64748b]">Showing <span class="font-semibold text-[#334155]">{{ advisories.from || 0 }}</span>–<span class="font-semibold text-[#334155]">{{ advisories.to || 0 }}</span> of <span class="font-semibold text-[#334155]">{{ advisories.total }}</span></p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template v-for="link in advisories.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] text-[#cbd5e1]" v-html="link.label" />
                            <Link v-else :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] font-semibold transition-all duration-200" :class="link.active ? 'bg-[#014d3c] text-white shadow-sm' : 'text-[#64748b] hover:bg-[#f1f5f9]'" preserve-scroll preserve-state v-html="link.label" />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
