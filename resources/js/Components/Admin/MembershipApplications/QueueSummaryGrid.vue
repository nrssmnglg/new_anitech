<script setup>
defineProps({
    summary: { type: Object, required: true },
    currentStatus: { type: String, default: '' },
});

defineEmits(['filter-status']);

const cards = [
    {
        key: 'total',
        statusValue: '',
        label: 'Total',
        iconWrap: 'bg-emerald-50 text-[#003629]',
        countClass: 'text-[#003629]',
        activeClass: 'border-[#003629] ring-1.5 ring-[#003629]/20 bg-emerald-50/20',
    },
    {
        key: 'pending',
        statusValue: 'pending',
        label: 'Pending',
        iconWrap: 'bg-amber-50 text-amber-700',
        countClass: 'text-amber-800',
        activeClass: 'border-amber-500 ring-1.5 ring-amber-500/20 bg-amber-50/30',
    },
    {
        key: 'approved',
        statusValue: 'approved',
        label: 'Approved',
        iconWrap: 'bg-emerald-50 text-emerald-700',
        countClass: 'text-emerald-800',
        activeClass: 'border-emerald-500 ring-1.5 ring-emerald-500/20 bg-emerald-50/30',
    },
    {
        key: 'rejected',
        statusValue: 'rejected',
        label: 'Rejected',
        iconWrap: 'bg-rose-50 text-rose-700',
        countClass: 'text-rose-800',
        activeClass: 'border-rose-500 ring-1.5 ring-rose-500/20 bg-rose-50/30',
    },
];

function isCardActive(card, currentStatus) {
    if (card.key === 'total') {
        return !currentStatus || currentStatus === '';
    }
    return currentStatus === card.statusValue;
}
</script>

<template>
    <section class="grid grid-cols-2 gap-2 sm:gap-2.5 lg:grid-cols-4">
        <button
            v-for="card in cards"
            :key="card.key"
            type="button"
            class="flex items-center gap-2.5 rounded-lg border border-[#dde4de] bg-white px-3 py-2 text-left shadow-xs transition hover:border-[#b5c7bd] active:scale-[0.99]"
            :class="isCardActive(card, currentStatus) ? card.activeClass : ''"
            @click="$emit('filter-status', card.statusValue)"
        >
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md" :class="card.iconWrap">
                <svg v-if="card.key === 'total'" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg v-else-if="card.key === 'pending'" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v5l3 2" />
                </svg>
                <svg v-else-if="card.key === 'approved'" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <path d="m9 11 3 3L22 4" />
                </svg>
                <svg v-else viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" />
                    <path d="m15 9-6 6M9 9l6 6" />
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-[0.6rem] font-bold uppercase tracking-[0.06em] text-[#64748b]">
                    {{ card.label }}
                </p>
                <p class="mt-0.5 text-base font-bold leading-none sm:text-lg" :class="card.countClass">
                    {{ summary[card.key] ?? 0 }}
                </p>
            </div>
        </button>
    </section>
</template>
