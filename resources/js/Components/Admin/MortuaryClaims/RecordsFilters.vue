<script setup>
defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    activeSection: { type: String, required: true },
    urls: { type: Object, required: true },
});

const emit = defineEmits(['apply', 'reset', 'export']);
</script>

<template>
    <section class="rounded-lg border border-[#dfe5e1] bg-[#f7f9f8] p-3">
        <form class="grid gap-2.5 xl:grid-cols-[1.35fr_0.75fr_0.85fr_1fr_auto]" @submit.prevent="emit('apply')">
            <label class="space-y-1">
                <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Search</span>
                <div class="relative">
                    <input v-model="form.search" type="text" :placeholder="activeSection === 'records' ? 'Reference, code, or name' : 'Farmer code or name'" class="h-9 w-full rounded-md border border-[#cfd7d3] bg-white px-3 text-xs text-[#191c1c] outline-none transition focus:border-[#376757]">
                </div>
            </label>
            <label v-if="activeSection === 'records'" class="space-y-1">
                <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Status</span>
                <select v-model="form.status" class="h-9 w-full rounded-md border border-[#cfd7d3] bg-white px-3 text-xs text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All statuses</option>
                    <option v-for="item in filterOptions.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </label>
            <label class="space-y-1">
                <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Year</span>
                <select v-model="form.year" class="h-9 w-full rounded-md border border-[#cfd7d3] bg-white px-3 text-xs text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All years</option>
                    <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">{{ year.label }}</option>
                </select>
            </label>
            <label class="space-y-1">
                <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Barangay</span>
                <select v-model="form.barangay_id" class="h-9 w-full rounded-md border border-[#cfd7d3] bg-white px-3 text-xs text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All barangays</option>
                    <option v-for="item in filterOptions.barangays" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                </select>
            </label>
            <div class="flex items-end gap-2">
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-[#cfd7d3] bg-white px-3 text-xs font-semibold text-[#697772] transition hover:bg-[#f9fbfa]" @click="emit('reset')">
                    Reset
                </button>
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-[#cfe0d6] bg-white px-3 text-xs font-semibold text-[#047857] transition hover:bg-[#eef8f2]" @click="emit('export')">
                    Export
                </button>
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#003629] px-3.5 text-xs font-semibold text-white transition hover:bg-[#0d4637]">
                    Apply
                </button>
            </div>
        </form>
    </section>
</template>
