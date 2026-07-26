<script setup>
defineProps({
    summary: { type: Object, required: true },
});

const cards = [
    {
        key: 'total',
        label: 'Registered Farmers',
        icon: 'group',
        trend: 'Registry total',
        iconClass: 'bg-[#003629]/6 text-[#003629]',
        valueClass: 'text-[#003629]',
        trendClass: 'text-[#416918]',
    },
    {
        key: 'active',
        label: 'Active Members',
        icon: 'check_circle',
        trend: 'Membership ready',
        iconClass: 'bg-[#416918]/8 text-[#416918]',
        valueClass: 'text-[#416918]',
        trendClass: 'text-[#416918]',
    },
    {
        key: 'inactive',
        label: 'Inactive Farmers',
        icon: 'block',
        trend: 'All inactive',
        iconClass: 'bg-[#d04d4d]/10 text-[#b53a3a]',
        valueClass: 'text-[#b53a3a]',
        trendClass: 'text-[#b53a3a]',
    },
    {
        key: 'deceased',
        label: 'Deceased Farmers',
        icon: 'block',
        trend: 'Recorded deceased',
        iconClass: 'bg-[#7f1d1d]/10 text-[#8f2d2d]',
        valueClass: 'text-[#8f2d2d]',
        trendClass: 'text-[#8f2d2d]',
    },
];
</script>

<template>
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article
            v-for="card in cards"
            :key="card.key"
            class="group rounded-[20px] border border-[#dbe2de] bg-white px-5 py-4 shadow-[0_8px_24px_rgba(15,23,42,0.045)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_30px_rgba(15,23,42,0.07)]"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="rounded-[1.05rem] p-2.5 transition group-hover:scale-105" :class="card.iconClass">
                    <svg v-if="card.icon === 'group'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                        <circle cx="9.5" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <svg v-else-if="card.icon === 'check_circle'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" />
                        <path d="m8.5 12.5 2.3 2.3 4.7-5.1" />
                    </svg>
                    <svg v-else-if="card.icon === 'schedule'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" />
                    </svg>
                    <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M8.5 8.5l7 7" />
                        <path d="M15.5 8.5l-7 7" />
                    </svg>
                </div>
                <span class="text-[0.62rem] font-black uppercase tracking-[0.2em]" :class="card.trendClass">{{ card.trend }}</span>
            </div>

            <p class="mt-4 text-[0.68rem] font-black uppercase tracking-[0.18em] text-[#76847d]">{{ card.label }}</p>
            <p class="mt-1.5 text-[2.2rem] font-black leading-none tracking-[-0.03em]" :class="card.valueClass">{{ summary[card.key] }}</p>
        </article>
    </section>
</template>
