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
    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <article
            v-for="card in cards"
            :key="card.key"
            class="rounded-[18px] border border-[#dbe2de] bg-white/80 px-4 py-3 shadow-[0_8px_20px_rgba(15,23,42,0.04)] backdrop-blur-sm"
        >
            <div class="mb-2.5 flex items-start justify-between gap-3">
                <div class="rounded-[0.9rem] p-2" :class="card.iconWrap">
                    <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle v-if="card.key !== 'total'" cx="12" cy="12" r="9" />
                        <path :d="iconPath(card.key)" />
                    </svg>
                </div>
            </div>

            <p class="text-[0.64rem] font-black uppercase tracking-[0.16em] text-[#7a8781]">{{ card.label }}</p>
            <p class="mt-1 text-[1.95rem] font-black leading-none tracking-[-0.04em]" :class="card.valueClass">{{ summary[card.key] }}</p>
        </article>
    </section>
</template>
