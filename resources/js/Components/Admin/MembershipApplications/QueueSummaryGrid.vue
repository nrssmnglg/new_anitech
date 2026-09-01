<script setup>
defineProps({
    summary: { type: Object, required: true },
});

const cards = [
    {
        key: 'total',
        label: 'Total In Queue',
        iconWrap: 'bg-[#1b4d3e]/10 text-[#003629]',
        valueClass: 'text-[#003629]',
    },
    {
        key: 'pending',
        label: 'Pending Review',
        iconWrap: 'bg-[#fff3dc] text-[#c07b10]',
        valueClass: 'text-[#1f2724]',
    },
    {
        key: 'approved',
        label: 'Approved',
        iconWrap: 'bg-[#eef7e3] text-[#416918]',
        valueClass: 'text-[#416918]',
    },
    {
        key: 'rejected',
        label: 'Rejected',
        iconWrap: 'bg-[#fff1f1] text-[#ba1a1a]',
        valueClass: 'text-[#ba1a1a]',
    },
];

function iconPath(key) {
    if (key === 'total') return 'M4 6h16M4 12h16M4 18h16';
    if (key === 'pending') return 'M12 7v5l3 2';
    if (key === 'approved') return 'm8.5 12.5 2.3 2.3 4.7-5.1';
    return 'M8.5 8.5l7 7M15.5 8.5l-7 7';
}
</script>

<template>
    <section class="grid grid-cols-2 gap-2 lg:grid-cols-4">
        <article
            v-for="card in cards"
            :key="card.key"
            class="rounded-lg border border-[#dbe2de] bg-white p-3"
        >
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md" :class="card.iconWrap">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle v-if="card.key !== 'total'" cx="12" cy="12" r="9" />
                        <path :d="iconPath(card.key)" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-[0.62rem] font-semibold uppercase tracking-[0.06em] text-[#6c7873]">{{ card.label }}</p>
                    <p class="mt-0.5 text-lg font-semibold leading-none" :class="card.valueClass">{{ summary[card.key] }}</p>
                </div>
            </div>
        </article>
    </section>
</template>
