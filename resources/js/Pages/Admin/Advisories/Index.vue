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
        return 'bg-[#d4f4a6] text-[#4e7c11]';
    }

    if (status === 'Draft') {
        return 'bg-[#fff1cb] text-[#c27a00]';
    }

    return 'bg-[#e8eeef] text-[#56666f]';
}

function advisoryTone(status) {
    if (status === 'Published') {
        return 'bg-[#ebf7dd] text-[#5d8f1a]';
    }

    if (status === 'Draft') {
        return 'bg-[#fff3de] text-[#d48308]';
    }

    return 'bg-[#eef1f2] text-[#60717a]';
}

function statusMeta(status) {
    if (status === 'Published') {
        return 'Active broadcasts';
    }

    if (status === 'Draft') {
        return 'Pending review';
    }

    return 'Past events';
}
</script>

<template>
    <Head title="Advisories" />

    <AdminLayout title="Advisories">
        <div class="space-y-5">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.2rem] bg-[linear-gradient(135deg,#134c45,#194f4f)] p-5 text-white shadow-[0_14px_28px_rgba(15,91,70,0.14)]">
                    <div class="flex items-start justify-between gap-4">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/12 text-white">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <path d="M4 12h5l2-4 3 8 2-4h4" />
                            </svg>
                        </span>
                        <span class="text-xs text-white/70">Total</span>
                    </div>
                    <p class="mt-4 text-[2.2rem] font-black leading-none">{{ summary.total }}</p>
                    <p class="mt-2 text-sm text-white/75">+{{ publishedRate }}% this month</p>
                </article>

                <article class="rounded-[1.2rem] bg-[linear-gradient(135deg,#d7f6a8,#b9f07a)] p-5 text-[#214321] shadow-[0_14px_28px_rgba(153,192,78,0.18)]">
                    <div class="flex items-start justify-between gap-4">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/45 text-[#496b12]">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <circle cx="12" cy="12" r="7" />
                                <path d="m9.5 12 1.8 1.8 3.2-3.6" />
                            </svg>
                        </span>
                        <span class="text-xs text-[#496b12]">Published</span>
                    </div>
                    <p class="mt-4 text-[2.2rem] font-black leading-none">{{ summary.published }}</p>
                    <p class="mt-2 text-sm text-[#587a20]">{{ statusMeta('Published') }}</p>
                </article>

                <article class="rounded-[1.2rem] bg-[linear-gradient(135deg,#fff6d5,#ffe69f)] p-5 text-[#5d4210] shadow-[0_14px_28px_rgba(210,166,61,0.14)]">
                    <div class="flex items-start justify-between gap-4">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/45 text-[#d2870a]">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <path d="M6 8.5 12 4l6 4.5v8L12 20l-6-3.5z" />
                            </svg>
                        </span>
                        <span class="text-xs text-[#9b6a12]">Drafts</span>
                    </div>
                    <p class="mt-4 text-[2.2rem] font-black leading-none">{{ summary.drafts }}</p>
                    <p class="mt-2 text-sm text-[#b0780f]">{{ statusMeta('Draft') }}</p>
                </article>

                <article class="rounded-[1.2rem] border border-[#dce2e5] bg-[#f7f8f8] p-5 text-[#1f2937] shadow-[0_14px_28px_rgba(15,91,70,0.06)]">
                    <div class="flex items-start justify-between gap-4">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-[#6b7280]">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
                                <path d="M8 8h8v8H8z" />
                                <path d="m10 12 2 2 2-2" />
                            </svg>
                        </span>
                        <span class="text-xs text-[#66727c]">Archived</span>
                    </div>
                    <p class="mt-4 text-[2.2rem] font-black leading-none">{{ summary.archived }}</p>
                    <p class="mt-2 text-sm text-[#7b8790]">{{ statusMeta('Archived') }}</p>
                </article>

            </section>

            <div class="flex justify-end">
                <Link :href="urls.create" class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-4 py-2.5 text-sm font-black text-white transition hover:bg-[#01362a]">
                    Create Advisory
                </Link>
            </div>

            <section class="rounded-[1.35rem] border border-[#dde4de] bg-white p-4 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <div class="grid gap-4 xl:grid-cols-[1fr_1fr_auto] xl:items-end">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Filter by Status</span>
                            <select v-model="filterForm.status" class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Target Audience</span>
                            <select v-model="filterForm.audience_type" class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                                <option v-for="option in audienceOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex items-center gap-3 xl:justify-end">
                        <button type="button" class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-6 py-3 text-sm font-black text-white transition hover:bg-[#01362a]" @click="applyFilters">
                            Apply Filters
                        </button>
                        <button type="button" class="inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]" @click="resetFilters">
                            Reset
                        </button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[1.35rem] border border-[#dde4de] bg-white shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-[#f9fbfa]">
                            <tr class="border-b border-[#e8eeea] text-left text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">
                                <th class="px-6 py-4">Advisory</th>
                                <th class="px-6 py-4">Audience</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Attachments</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="advisory in advisories.data" :key="advisory.id" class="border-b border-[#edf2ee] transition hover:bg-[#fcfefd]">
                                <td class="px-6 py-5 align-top">
                                    <div class="flex items-start gap-4">
                                        <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-sm font-black" :class="advisoryTone(advisory.status)">
                                            <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2">
                                                <path v-if="advisory.status === 'Published'" d="M6 12h12M12 6v12" />
                                                <path v-else-if="advisory.status === 'Draft'" d="M6 8.5 12 4l6 4.5v8L12 20l-6-3.5z" />
                                                <path v-else d="M8 8h8v8H8z" />
                                            </svg>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="font-bold leading-5 text-[#0f172a]">{{ advisory.title }}</p>
                                            <p class="text-xs text-[#64748b]">{{ advisory.publishedAt ? `Published ${advisory.publishedAt}` : `Created ${advisory.createdAt}` }}</p>
                                            <p v-if="advisory.publisher" class="text-xs text-[#64748b]">{{ advisory.status === 'Draft' ? `Drafted by ${advisory.publisher}` : `Published by ${advisory.publisher}` }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <span class="inline-flex rounded-md bg-[#f3f5f4] px-2.5 py-1 text-xs font-medium text-[#425466]">
                                        {{ advisory.audienceLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold" :class="statusTone(advisory.status)">
                                        {{ advisory.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 align-top font-medium text-[#334155]">
                                    {{ advisory.attachmentsCount ? advisory.attachmentsCount : '0' }}
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div class="flex items-center justify-end gap-4 text-sm">
                                        <Link :href="advisory.actions.show" class="font-medium text-[#014d3c] transition hover:underline">View</Link>
                                        <Link :href="advisory.actions.edit" class="font-medium text-[#1f2937] transition hover:underline">Edit</Link>
                                        <button v-if="advisory.actions.publish" type="button" class="font-medium text-[#6c9f1b] transition hover:underline disabled:cursor-not-allowed disabled:opacity-50" :disabled="busyAction !== null" @click="publishAdvisory(advisory)">
                                            {{ busyAction === `publish-${advisory.id}` ? 'Publishing...' : 'Publish' }}
                                        </button>
                                        <button v-if="advisory.actions.unpublish" type="button" class="font-medium text-[#c05c3c] transition hover:underline disabled:cursor-not-allowed disabled:opacity-50" :disabled="busyAction !== null" @click="unpublishAdvisory(advisory)">
                                            {{ busyAction === `unpublish-${advisory.id}` ? 'Updating...' : 'Unpublish' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="advisories.data.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center text-sm text-[#64748b]">No advisories found for the selected filter.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-[#edf2ee] bg-[#fbfcfb] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[#64748b]">Showing {{ advisories.from || 0 }} to {{ advisories.to || 0 }} of {{ advisories.total }} advisories</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in advisories.links" :key="link.label">
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
