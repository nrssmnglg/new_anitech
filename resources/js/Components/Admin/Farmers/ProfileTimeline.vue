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
    <section class="overflow-hidden rounded-[26px] border border-[#dbe2de] bg-white shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
        <div class="flex items-center justify-between border-b border-[#e4ebe7] bg-[#f4f7f5] px-6 py-5">
            <div>
                <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Farmer History</p>
            </div>
            <span class="inline-flex rounded-full bg-[#003629]/10 px-3 py-1 text-xs font-black text-[#003629]">
                {{ filteredTimeline.length }} entries
            </span>
        </div>

        <div v-if="timeline.length" class="px-6 py-6">
            <div class="mb-6 flex flex-wrap gap-3">
                <button
                    v-for="filter in filters"
                    :key="filter.value"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-bold transition"
                    :class="activeFilter === filter.value ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#d7e0db] bg-white text-[#5f6c67] hover:bg-[#f6f9f7]'"
                    @click="activeFilter = filter.value"
                >
                    <span>{{ filter.label }}</span>
                    <span
                        class="inline-flex min-w-6 items-center justify-center rounded-full px-1.5 py-0.5 text-[0.7rem] font-black"
                        :class="activeFilter === filter.value ? 'bg-white/20 text-white' : 'bg-[#eef1ef] text-[#31443b]'"
                    >
                        {{ filterCount(filter.value) }}
                    </span>
                </button>
            </div>

            <div class="space-y-5">
                <article v-for="item in filteredTimeline" :key="item.key" class="relative rounded-[22px] border border-[#e3eae6] bg-[#f8faf9] p-5">
                    <div class="absolute bottom-0 left-8 top-0 hidden w-px bg-[#dbe3df] sm:block"></div>
                    <div class="relative flex gap-4">
                        <div class="hidden sm:flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[0.65rem] font-black uppercase tracking-[0.08em] text-[#003629] shadow-[inset_0_0_0_1px_rgba(219,226,222,0.95)]">
                            {{ typeLabel(item.type).slice(0, 2) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span :class="['inline-flex rounded-full px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em]', typeBadge(item.type)]">
                                            {{ typeLabel(item.type) }}
                                        </span>
                                        <span v-if="item.status" class="inline-flex rounded-full border border-[#d7e0db] bg-white px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] text-[#63706b]">
                                            {{ item.status }}
                                        </span>
                                    </div>
                                    <h3 class="mt-3 text-[1.08rem] font-bold text-[#1a2420]">{{ item.title }}</h3>
                                    <p class="mt-1 text-sm text-[#57646e]">{{ item.subtitle }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <p class="text-sm font-semibold text-[#52626b]">{{ item.occurredAt }}</p>
                                    <Link v-if="item.href" :href="item.href" class="rounded-xl border border-[#d7e0db] bg-white px-3 py-2 text-xs font-bold text-[#5f6c67] transition hover:bg-[#ffffff]">
                                        Open
                                    </Link>
                                </div>
                            </div>

                            <div v-if="item.meta && Object.keys(item.meta).length" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                <div v-for="(value, label) in item.meta" :key="label" class="rounded-2xl bg-white px-4 py-3 shadow-[inset_0_0_0_1px_rgba(228,235,231,0.95)]">
                                    <p class="text-[0.65rem] font-black uppercase tracking-[0.14em] text-[#7a8781]">{{ label }}</p>
                                    <p class="mt-1 text-sm font-semibold text-[#1a2420]">{{ value || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="filteredTimeline.length === 0" class="rounded-[22px] border border-dashed border-[#d7e0db] bg-[#f8faf9] px-6 py-12 text-center text-sm text-[#6a7872]">
                No records found for the selected timeline filter.
            </div>
        </div>
        <div v-else class="px-6 py-14 text-center text-sm text-[#6a7872]">
            No historical records have been captured for this farmer yet.
        </div>
    </section>
</template>
