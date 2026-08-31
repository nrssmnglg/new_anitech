<script setup>
defineProps({
    columns: {
        type: Array,
        required: true,
    },
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
        default: 'No data available.',
    },
});
</script>

<template>
    <section class="rounded-lg border border-stone-200/80 bg-white p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <p class="text-[0.68rem] font-bold uppercase tracking-[0.28em] text-stone-400">Records</p>
                <h3 class="mt-1 text-sm font-semibold text-stone-950">{{ title }}</h3>
            </div>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-500">{{ rows.length }} rows</span>
        </div>

        <div v-if="rows.length" class="overflow-x-auto rounded-md border border-stone-200/80">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead>
                    <tr class="bg-stone-50 text-left text-[0.68rem] uppercase tracking-[0.24em] text-stone-500">
                        <th v-for="column in columns" :key="column.key" class="px-3 py-2">{{ column.label }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 bg-white">
                    <tr v-for="(row, index) in rows" :key="row.id ?? `${title}-${index}`" class="align-top transition hover:bg-stone-50/70">
                        <td v-for="column in columns" :key="column.key" class="px-3 py-2 text-stone-700">
                            {{ row[column.key] ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="rounded-2xl border border-dashed border-stone-200 bg-stone-50 px-4 py-8 text-center text-sm text-stone-500">
            {{ emptyLabel }}
        </div>
    </section>
</template>
