<script setup>
const props = defineProps({
    rows: {
        type: Array,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    emptyLabel: {
        type: String,
        default: 'No records found.',
    },
});

const maxValue = Math.max(...props.rows.map((row) => Number(row.total || 0)), 0);

function widthFor(value) {
    if (maxValue === 0) {
        return '0%';
    }

    return `${Math.max((Number(value || 0) / maxValue) * 100, 6)}%`;
}
</script>

<template>
    <section class="rounded-[1.9rem] border border-stone-200/80 bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <p class="text-[0.68rem] font-bold uppercase tracking-[0.28em] text-stone-400">Breakdown</p>
                <h3 class="mt-2 text-xl font-bold tracking-tight text-stone-950">{{ title }}</h3>
            </div>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-500">{{ rows.length }} items</span>
        </div>

        <div v-if="rows.length" class="space-y-4">
            <div v-for="row in rows" :key="row.label" class="rounded-2xl border border-stone-100 bg-stone-50/70 p-4">
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="font-semibold text-stone-700">{{ row.label }}</span>
                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-stone-900 shadow-sm">{{ row.total }}</span>
                </div>
                <div class="mt-3 h-2.5 rounded-full bg-stone-200/80">
                    <div
                        class="h-2.5 rounded-full bg-gradient-to-r from-[#1B4D3E] via-[#4F7F62] to-[#9CC77A]"
                        :style="{ width: widthFor(row.total) }"
                    ></div>
                </div>
            </div>
        </div>

        <div v-else class="rounded-2xl border border-dashed border-stone-200 bg-stone-50 px-4 py-8 text-center text-sm text-stone-500">
            {{ emptyLabel }}
        </div>
    </section>
</template>
