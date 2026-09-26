<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    timeline: { type: Array, required: true },
});

const activeFilter = ref('all');
const activeDetailItem = ref(null);

const filters = [
    { value: 'all', label: 'All History' },
    { value: 'payment', label: 'Payments' },
    { value: 'renewal', label: 'Renewals' },
    { value: 'inquiry', label: 'Inquiries' },
    { value: 'notification', label: 'Notifications' },
    { value: 'application', label: 'Applications' },
    { value: 'document', label: 'Documents' },
    { value: 'mortuary', label: 'Mortuary' },
    { value: 'registration', label: 'Registration' },
];

const filteredTimeline = computed(() => {
    if (activeFilter.value === 'all') {
        return props.timeline;
    }

    return props.timeline.filter((item) => item.type === activeFilter.value);
});

const visibleFilters = computed(() => filters.filter((filter) => ['all', 'registration', 'application', 'renewal', 'payment'].includes(filter.value) || filterCount(filter.value) > 0));

function filterCount(value) {
    if (value === 'all') {
        return props.timeline.length;
    }

    return props.timeline.filter((item) => item.type === value).length;
}

function typeBadge(type) {
    const tones = {
        registration: 'bg-[#dcfce7] text-[#15803d]',
        application: 'bg-[#dbeafe] text-[#1d4ed8]',
        renewal: 'bg-[#ecfccb] text-[#4d7c0f]',
        payment: 'bg-[#fef3c7] text-[#b45309]',
        inquiry: 'bg-[#f3e8ff] text-[#7e22ce]',
        notification: 'bg-[#f1f5f9] text-[#475569]',
        document: 'bg-[#ccfbf1] text-[#0f766e]',
        mortuary: 'bg-[#ffe4e6] text-[#be123c]',
    };

    return tones[type] ?? 'bg-[#f1f5f9] text-[#475569]';
}

function openDetail(item) {
    activeDetailItem.value = item;
}

function closeDetail() {
    activeDetailItem.value = null;
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-[#f1f5f9] px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-[#014d3c]"></span>
                <h2 class="text-xs font-bold text-[#0f172a]">Farmer History &amp; Timeline</h2>
            </div>
            <span class="inline-flex rounded-lg bg-[#f0fdf4] px-2.5 py-0.5 text-xs font-bold text-[#15803d]">
                {{ filteredTimeline.length }} entries
            </span>
        </div>

        <div v-if="timeline.length" class="p-5">
            <!-- Filter Pills -->
            <div class="mb-5 flex flex-wrap gap-1.5">
                <button
                    v-for="filter in visibleFilters"
                    :key="filter.value"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all duration-200"
                    :class="activeFilter === filter.value ? 'border-[#014d3c] bg-[#014d3c] text-white shadow-sm' : 'border-[#e2e8f0] bg-white text-[#64748b] hover:bg-[#f8fafc] hover:border-[#cbd5e1]'"
                    @click="activeFilter = filter.value"
                >
                    <span>{{ filter.label }}</span>
                    <span
                        class="inline-flex min-w-4 items-center justify-center rounded-full px-1.5 py-0.2 text-[0.62rem] font-bold"
                        :class="activeFilter === filter.value ? 'bg-white/20 text-white' : 'bg-[#f1f5f9] text-[#475569]'"
                    >
                        {{ filterCount(filter.value) }}
                    </span>
                </button>
            </div>

            <!-- Timeline Entries Rail -->
            <div class="relative pl-6 space-y-3.5 before:absolute before:left-2 before:top-3 before:bottom-3 before:w-0.5 before:bg-[#e2e8f0]">
                <!-- Clickable Timeline Card -->
                <component
                    :is="item.href ? Link : 'div'"
                    v-for="item in filteredTimeline"
                    :key="item.key"
                    :href="item.href || undefined"
                    class="group relative block cursor-pointer rounded-xl border border-[#e2e8f0] bg-white p-4 shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-[#86efac] hover:bg-[#f0fdf4]/40 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#014d3c]/20"
                    :role="item.href ? undefined : 'button'"
                    :title="item.href ? `Open ${item.title}` : `View details for ${item.title}`"
                    @click="!item.href ? openDetail(item) : null"
                >
                    <!-- Timeline Node Marker -->
                    <span class="absolute -left-[1.85rem] top-4.5 h-3 w-3 rounded-full border-2 border-white bg-[#014d3c] shadow-xs transition-transform duration-200 group-hover:scale-125 group-hover:bg-[#15803d]"></span>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-wider" :class="typeBadge(item.type)">
                                    {{ item.type }}
                                </span>
                                <h3 class="text-xs font-bold text-[#0f172a] transition-colors group-hover:text-[#014d3c]">
                                    {{ item.title }}
                                </h3>
                                <span v-if="item.status" class="rounded-md bg-[#f1f5f9] px-2 py-0.5 text-[0.6rem] font-semibold text-[#475569]">
                                    {{ item.status }}
                                </span>
                            </div>

                            <p v-if="item.subtitle" class="mt-1.5 text-xs text-[#64748b] leading-relaxed">
                                {{ item.subtitle }}
                            </p>

                            <!-- Structured metadata badges -->
                            <div v-if="item.meta && typeof item.meta === 'object' && Object.keys(item.meta).length" class="mt-2.5 flex flex-wrap gap-1.5">
                                <span
                                    v-for="(val, key) in item.meta"
                                    :key="key"
                                    class="inline-flex items-center gap-1 rounded-md border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.62rem] text-[#475569]"
                                >
                                    <strong class="font-semibold text-[#334155]">{{ key }}:</strong>
                                    <span>{{ val }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Right meta: Date & Click prompt -->
                        <div class="shrink-0 text-left sm:text-right">
                            <span class="text-[0.68rem] font-medium text-[#94a3b8] block">{{ item.occurredAt || item.date }}</span>
                            <div class="mt-1.5 inline-flex items-center gap-1 text-xs font-bold text-[#014d3c] group-hover:text-[#003629]">
                                <span>{{ item.href ? 'Open Record' : 'View Details' }}</span>
                                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-1" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </component>
            </div>
        </div>

        <div v-else class="p-10 text-center text-xs text-[#94a3b8]">
            No timeline events recorded for this farmer.
        </div>

        <!-- Detail Modal for items without external link (e.g. notifications) -->
        <div
            v-if="activeDetailItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f172a]/50 px-4 backdrop-blur-sm"
            @click.self="closeDetail"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-[#e2e8f0] bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-[#f1f5f9] pb-4">
                    <div>
                        <span class="rounded-md px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-wider" :class="typeBadge(activeDetailItem.type)">
                            {{ activeDetailItem.type }}
                        </span>
                        <h3 class="mt-1.5 text-base font-bold text-[#0f172a]">{{ activeDetailItem.title }}</h3>
                        <p class="mt-0.5 text-xs text-[#94a3b8]">{{ activeDetailItem.occurredAt || activeDetailItem.date }}</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#94a3b8] transition hover:bg-[#f1f5f9] hover:text-[#64748b]"
                        @click="closeDetail"
                    >
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <div v-if="activeDetailItem.subtitle" class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3 text-xs text-[#334155] leading-relaxed">
                        {{ activeDetailItem.subtitle }}
                    </div>

                    <div v-if="activeDetailItem.meta && typeof activeDetailItem.meta === 'object'" class="space-y-2">
                        <p class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Details</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div
                                v-for="(val, key) in activeDetailItem.meta"
                                :key="key"
                                class="rounded-lg border border-[#f1f5f9] bg-[#f8fafc] p-2.5"
                            >
                                <span class="text-[0.58rem] font-bold uppercase tracking-wider text-[#94a3b8] block">{{ key }}</span>
                                <span class="text-xs font-semibold text-[#0f172a] mt-0.5 block">{{ val }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t border-[#f1f5f9] pt-4">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-xl bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a]"
                        @click="closeDetail"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
