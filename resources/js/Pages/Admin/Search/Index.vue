<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    search: {
        type: Object,
        required: true,
    },
});

const selectedGroup = ref('All');

const visibleGroups = computed(() => {
    if (selectedGroup.value === 'All') {
        return props.search.groups;
    }
    return props.search.groups.filter((g) => g.title === selectedGroup.value);
});

function clearSearch() {
    router.get('/admin/search', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function getGroupMeta(title) {
    switch (title) {
        case 'Users':
            return {
                bg: 'bg-indigo-50',
                text: 'text-indigo-600',
                border: 'border-indigo-100',
                icon: 'users',
            };
        case 'Farmers':
            return {
                bg: 'bg-[#e6f5ec]',
                text: 'text-[#014d3c]',
                border: 'border-[#bbf7d0]',
                icon: 'farmer',
            };
        case 'Membership Applications':
            return {
                bg: 'bg-amber-50',
                text: 'text-amber-700',
                border: 'border-amber-100',
                icon: 'file',
            };
        case 'Renewals':
            return {
                bg: 'bg-purple-50',
                text: 'text-purple-700',
                border: 'border-purple-100',
                icon: 'refresh',
            };
        case 'Inquiries':
            return {
                bg: 'bg-sky-50',
                text: 'text-sky-700',
                border: 'border-sky-100',
                icon: 'chat',
            };
        case 'Advisories':
            return {
                bg: 'bg-rose-50',
                text: 'text-rose-700',
                border: 'border-rose-100',
                icon: 'megaphone',
            };
        case 'Barangays':
            return {
                bg: 'bg-teal-50',
                text: 'text-teal-700',
                border: 'border-teal-100',
                icon: 'map',
            };
        case 'Associations':
            return {
                bg: 'bg-blue-50',
                text: 'text-blue-700',
                border: 'border-blue-100',
                icon: 'building',
            };
        default:
            return {
                bg: 'bg-stone-50',
                text: 'text-stone-700',
                border: 'border-stone-100',
                icon: 'folder',
            };
    }
}

function getInitials(name) {
    if (!name) return '•';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((n) => n[0].toUpperCase())
        .join('');
}
</script>

<template>
    <Head title="Search" />

    <AdminLayout title="Search">
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-24 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m20 20-3.5-3.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">System Directory</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">
                                <template v-if="search.hasQuery">
                                    Results for <span class="text-[#7ddfb8] underline decoration-[#7ddfb8]/40 underline-offset-4">"{{ search.term }}"</span>
                                </template>
                                <template v-else>Search Directory & Records</template>
                            </h1>
                        </div>
                    </div>

                    <!-- Summary Stats Pill -->
                    <div v-if="search.hasQuery" class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                        <article class="px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Total Matches</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-[#7ddfb8]">{{ search.totalResults }}</p>
                        </article>
                        <article class="border-l border-white/[0.08] px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Modules</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-white">{{ search.groups.length }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Module Filter Pills (when multiple modules returned) -->
            <section v-if="search.groups.length > 1" class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl px-3.5 py-1.5 text-xs font-semibold transition-all duration-200"
                    :class="selectedGroup === 'All' ? 'bg-[#014d3c] text-white shadow-sm' : 'border border-[#dde4de] bg-white text-[#475569] hover:bg-[#f8faf9]'"
                    @click="selectedGroup = 'All'"
                >
                    All Modules
                    <span class="ml-1 rounded-full px-1.5 py-0.2 text-[0.65rem] font-bold" :class="selectedGroup === 'All' ? 'bg-white/20 text-white' : 'bg-[#f1f5f9] text-[#64748b]'">
                        {{ search.totalResults }}
                    </span>
                </button>

                <button
                    v-for="group in search.groups"
                    :key="group.title"
                    type="button"
                    class="rounded-xl px-3.5 py-1.5 text-xs font-semibold transition-all duration-200"
                    :class="selectedGroup === group.title ? 'bg-[#014d3c] text-white shadow-sm' : 'border border-[#dde4de] bg-white text-[#475569] hover:bg-[#f8faf9]'"
                    @click="selectedGroup = group.title"
                >
                    {{ group.title }}
                    <span class="ml-1 rounded-full px-1.5 py-0.2 text-[0.65rem] font-bold" :class="selectedGroup === group.title ? 'bg-white/20 text-white' : 'bg-[#f1f5f9] text-[#64748b]'">
                        {{ group.count }}
                    </span>
                </button>
            </section>

            <!-- No Matches Empty State -->
            <section v-if="search.hasQuery && search.totalResults === 0" class="overflow-hidden rounded-2xl border border-dashed border-[#d5ded8] bg-white p-8 text-center shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#014d3c]/10 text-[#014d3c]">
                    <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20 20-3.5-3.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 11h4" />
                    </svg>
                </div>
                <h2 class="mt-4 text-base font-bold text-[#0f172a]">No matches found for "{{ search.term }}"</h2>
                <p class="mx-auto mt-1.5 max-w-md text-xs text-[#64748b]">
                    Try searching with different keywords such as a farmer's name, farmer code (e.g. FRM-...), email address, or application number.
                </p>
                <div class="mt-4">
                    <button
                        type="button"
                        class="inline-flex h-8.5 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-3.5 text-xs font-semibold text-[#475569] transition hover:bg-[#f8faf9]"
                        @click="clearSearch"
                    >
                        Clear search
                    </button>
                </div>
            </section>

            <!-- Search Initial State (No Query Entered) -->
            <section v-if="!search.hasQuery" class="space-y-4">
                <div class="rounded-xl border border-[#dde4de] bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-[#0f172a]">Available Search Modules</h2>
                    <p class="mt-1 text-xs text-[#64748b]">Quickly navigate to any operational registry or search records across these categories.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="group in search.groups"
                            :key="group.title"
                            :href="group.viewAllUrl"
                            class="group flex items-center justify-between rounded-xl border border-[#edf2ef] bg-[#fbfdfc] p-3.5 transition-all duration-200 hover:border-[#014d3c]/40 hover:bg-[#f0faf5] hover:shadow-xs"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl font-bold" :class="[getGroupMeta(group.title).bg, getGroupMeta(group.title).text]">
                                    <svg v-if="group.title === 'Users'" viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                    </svg>
                                    <svg v-else-if="group.title === 'Farmers'" viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else-if="group.title === 'Membership Applications'" viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                                    </svg>
                                    <svg v-else-if="group.title === 'Renewals'" viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.45a.75.75 0 000-1.5H4.5a.75.75 0 00-.75.75v3.75a.75.75 0 001.5 0v-2.128l.45.45a7 7 0 0011.838-3.177.75.75 0 00-1.226-.75zm-10.624-2.85a5.5 5.5 0 019.201-2.465l.312.311h-2.45a.75.75 0 000 1.5H15.5a.75.75 0 00.75-.75V3.42a.75.75 0 00-1.5 0v2.128l-.45-.45A7 7 0 002.462 8.275a.75.75 0 001.226.75z" clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                        <path fill-rule="evenodd" d="M2 4.75C2 3.784 2.784 3 3.75 3h4.836c.464 0 .909.184 1.237.513l1.414 1.414a1.75 1.75 0 001.237.513h4.776c.966 0 1.75.784 1.75 1.75v8.5A1.75 1.75 0 0117.25 17H3.75A1.75 1.75 0 012 15.25V4.75z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-[#0f172a] group-hover:text-[#014d3c]">{{ group.title }}</span>
                            </div>
                            <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#94a3b8] transition-transform group-hover:translate-x-0.5 group-hover:text-[#014d3c]" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Group Results List -->
            <div v-for="group in visibleGroups" :key="group.title" class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <!-- Group Card Header (Clean with NO redundant subtitle description) -->
                <div class="flex items-center justify-between border-b border-[#f1f5f3] px-5 py-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg" :class="[getGroupMeta(group.title).bg, getGroupMeta(group.title).text]">
                            <svg v-if="group.title === 'Users'" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                            </svg>
                            <svg v-else-if="group.title === 'Farmers'" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else-if="group.title === 'Membership Applications'" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                            </svg>
                            <svg v-else-if="group.title === 'Renewals'" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.45a.75.75 0 000-1.5H4.5a.75.75 0 00-.75.75v3.75a.75.75 0 001.5 0v-2.128l.45.45a7 7 0 0011.838-3.177.75.75 0 00-1.226-.75zm-10.624-2.85a5.5 5.5 0 019.201-2.465l.312.311h-2.45a.75.75 0 000 1.5H15.5a.75.75 0 00.75-.75V3.42a.75.75 0 00-1.5 0v2.128l-.45-.45A7 7 0 002.462 8.275a.75.75 0 001.226.75z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else-if="group.title === 'Inquiries'" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else-if="group.title === 'Advisories'" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 10a6 6 0 00-6-6v12a6 6 0 006-6v0z" />
                            </svg>
                            <svg v-else viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M2 4.75C2 3.784 2.784 3 3.75 3h4.836c.464 0 .909.184 1.237.513l1.414 1.414a1.75 1.75 0 001.237.513h4.776c.966 0 1.75.784 1.75 1.75v8.5A1.75 1.75 0 0117.25 17H3.75A1.75 1.75 0 012 15.25V4.75z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h2 class="text-sm font-bold text-[#0f172a]">{{ group.title }}</h2>
                        <span class="rounded-full bg-[#014d3c]/10 px-2 py-0.5 text-[0.68rem] font-bold text-[#014d3c]">
                            {{ group.count }}
                        </span>
                    </div>

                    <Link
                        :href="group.viewAllUrl"
                        class="group/link inline-flex items-center gap-1.5 rounded-lg border border-[#dde4de] bg-[#f8faf9] px-3 py-1.5 text-xs font-semibold text-[#014d3c] transition-all duration-200 hover:border-[#014d3c] hover:bg-[#f0faf5]"
                    >
                        View module
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 transition-transform duration-200 group-hover/link:translate-x-0.5" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                </div>

                <!-- Items List -->
                <div v-if="group.items.length" class="space-y-2 p-4">
                    <Link
                        v-for="item in group.items"
                        :key="`${group.title}-${item.id}`"
                        :href="item.url"
                        class="group flex flex-col gap-3 rounded-xl border border-[#edf2ef] bg-[#fbfdfc] p-3.5 transition-all duration-200 hover:border-[#014d3c]/30 hover:bg-white hover:shadow-sm sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Initials Avatar or Icon Box -->
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold"
                                :class="[getGroupMeta(group.title).bg, getGroupMeta(group.title).text]"
                            >
                                <span v-if="group.title === 'Users' || group.title === 'Farmers'">{{ getInitials(item.title) }}</span>
                                <svg v-else viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-[#0f172a] transition-colors group-hover:text-[#014d3c]">
                                    {{ item.title }}
                                </p>
                                <!-- Metadata Badges -->
                                <div v-if="item.subtitle" class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span
                                        v-for="(part, idx) in item.subtitle.split(' | ')"
                                        :key="idx"
                                        class="inline-flex items-center rounded-md bg-[#f1f5f9] px-2 py-0.5 text-[0.68rem] font-medium text-[#475569]"
                                    >
                                        {{ part }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Open Action Button -->
                        <div class="flex shrink-0 items-center justify-end">
                            <span class="inline-flex items-center gap-1 rounded-lg border border-[#dde4de] bg-white px-3 py-1.5 text-xs font-bold text-[#014d3c] shadow-2xs transition-all duration-200 group-hover:border-[#014d3c] group-hover:bg-[#014d3c] group-hover:text-white">
                                Open
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </div>
                    </Link>
                </div>

                <div v-else-if="search.hasQuery" class="px-5 py-6 text-center text-xs text-[#94a3b8]">
                    No {{ group.title.toLowerCase() }} matched this search.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
