<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    timeline: { type: Array, required: true },
});

const activeFilter = ref('all');

const filters = [
    { value: 'all', label: 'All' },
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
        registration: 'bg-[#dff6ea] text-[#0c7a58]',
        application: 'bg-[#e9f3ff] text-[#0b74ba]',
        renewal: 'bg-[#eef7d6] text-[#5f8418]',
        payment: 'bg-[#fff0d8] text-[#c46e00]',
        inquiry: 'bg-[#ece8ff] text-[#6c41db]',
        notification: 'bg-[#f1f3f4] text-[#50606a]',
        document: 'bg-[#e7fbf8] text-[#007567]',
        mortuary: 'bg-[#ffe4e7] text-[#cf3657]',
    };

    return tones[type] ?? 'bg-[#eef1ef] text-[#334155]';
}

function typeLabel(type) {
    return String(type || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}
</script>

<template>
    <section class="overflow-hidden rounded-[12px] border border-[#cbd4cf] bg-white">
        <div class="flex items-center justify-between border-b border-[#e4ebe7] px-4 py-2.5">
            <div>
                <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Farmer History</p>
            </div>
            <span class="inline-flex rounded-full bg-[#003629]/10 px-2.5 py-0.5 text-[0.68rem] font-semibold text-[#003629]">
                {{ filteredTimeline.length }} entries
            </span>
        </div>

        <div v-if="timeline.length" class="px-4 py-3">
            <div class="mb-3 flex flex-wrap gap-1.5">
                <button
                    v-for="filter in visibleFilters"
                    :key="filter.value"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-[0.7rem] font-normal transition"
                    :class="activeFilter === filter.value ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#d7e0db] bg-white text-[#5f6c67] hover:bg-[#f6f9f7]'"
                    @click="activeFilter = filter.value"
                >
                    <span>{{ filter.label }}</span>
                    <span
                        class="inline-flex min-w-5 items-center justify-center rounded-full px-1 py-0.5 text-[0.62rem] font-semibold"
                        :class="activeFilter === filter.value ? 'bg-white/20 text-white' : 'bg-[#eef1ef] text-[#31443b]'"
                    >
                        {{ filterCount(filter.value) }}
                    </span>
                </button>
            </div>

            <div class="space-y-2">
                <article v-for="item in filteredTimeline" :key="item.key" class="rounded-md border border-[#d8e0dc] bg-white px-3 py-2.5">
                    <div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-1.5 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span :class="['inline-flex rounded px-2 py-0.5 text-[0.58rem] font-semibold uppercase tracking-[0.08em]', typeBadge(item.type)]">
                                            {{ typeLabel(item.type) }}
                                        </span>
                                        <span v-if="item.status" class="inline-flex rounded border border-[#d7e0db] bg-white px-2 py-0.5 text-[0.58rem] font-normal uppercase tracking-[0.08em] text-[#63706b]">
                                            {{ item.status }}
                                        </span>
                                    </div>
                                    <h3 class="mt-1.5 text-sm font-semibold text-[#1a2420]">{{ item.title }}</h3>
                                    <p class="mt-0.5 text-[0.7rem] text-[#57646e]">{{ item.subtitle }}</p>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <p class="text-[0.68rem] font-normal text-[#52626b]">{{ item.occurredAt }}</p>
                                    <Link v-if="item.href" :href="item.href" class="rounded border border-[#d7e0db] bg-white px-2 py-1 text-[0.65rem] font-normal text-[#5f6c67] transition hover:bg-[#f7f9f8]">
                                        Open
                                    </Link>
                                </div>
                            </div>

                            <div v-if="item.meta && Object.keys(item.meta).length" class="mt-2 flex flex-wrap gap-x-5 gap-y-1 border-t border-[#edf1ef] pt-2">
                                <div v-for="(value, label) in item.meta" :key="label" class="flex items-baseline gap-1.5 text-[0.68rem]">
                                    <span class="font-semibold uppercase tracking-[0.06em] text-[#7a8781]">{{ label }}:</span>
                                    <span class="text-[#1a2420]">{{ value || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="filteredTimeline.length === 0" class="rounded-[18px] border border-dashed border-[#d7e0db] bg-[#f8faf9] px-5 py-10 text-center text-xs text-[#6a7872]">
                No records found for the selected timeline filter.
            </div>
        </div>
        <div v-else class="px-5 py-12 text-center text-xs text-[#6a7872]">
            No historical records have been captured for this farmer yet.
        </div>
    </section>
</template>
