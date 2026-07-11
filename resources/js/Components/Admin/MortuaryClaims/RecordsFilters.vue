<script setup>
defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    activeSection: { type: String, required: true },
    urls: { type: Object, required: true },
});

defineEmits(['apply', 'reset', 'export']);
</script>

<template>
    <section class="rounded-[24px] border border-[#dfe5e1] bg-[#f2f4f3] p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
        <form class="grid gap-4 xl:grid-cols-[1.35fr_0.75fr_0.85fr_1fr_auto]" @submit.prevent="$emit('apply')">
            <label class="space-y-2">
                <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Search</span>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-[#7a8781]">Search</span>
                    <input v-model="form.search" type="text" :placeholder="activeSection === 'records' ? 'Ref #, code, or name' : 'Farmer code or name'" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 pl-20 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                </div>
            </label>
            <label v-if="activeSection === 'records'" class="space-y-2">
                <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Status</span>
                <select v-model="form.status" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All statuses</option>
                    <option v-for="item in filterOptions.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </label>
            <label class="space-y-2">
                <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Year</span>
                <select v-model="form.year" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All years</option>
                    <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">{{ year.label }}</option>
                </select>
            </label>
            <label class="space-y-2">
                <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Barangay</span>
                <select v-model="form.barangay_id" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All barangays</option>
                    <option v-for="item in filterOptions.barangays" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                </select>
            </label>
            <div class="flex items-end gap-2">
                <button type="button" class="inline-flex h-[48px] items-center justify-center rounded-xl border border-[#cfd7d3] bg-white px-4 text-sm font-bold text-[#697772] transition hover:bg-[#f9fbfa]" @click="$emit('reset')">
                    Reset
                </button>
                <button type="button" class="inline-flex h-[48px] items-center justify-center rounded-xl border border-[#d6eadf] bg-[#eef8f2] px-4 text-sm font-extrabold text-[#047857] transition hover:bg-[#dff2e6]" @click="$emit('export')">
                    Export
                </button>
                <button type="submit" class="inline-flex h-[48px] items-center justify-center rounded-xl border border-[#003629] bg-white px-5 text-sm font-extrabold text-[#003629] transition hover:bg-[#edf5f2]">
                    Apply Filters
                </button>
            </div>
        </form>
    </section>
</template>
