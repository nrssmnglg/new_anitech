<script setup>
defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

defineEmits(['apply', 'reset']);
</script>

<template>
    <section class="rounded-[20px] border border-[#dbe2de] bg-white/75 p-4 shadow-[0_10px_24px_rgba(15,23,42,0.045)] backdrop-blur-sm">
        <div class="mb-4 flex items-center gap-3">
            <div class="rounded-[1rem] bg-[#e8f2ea] p-2 text-[#1b4d3e]">
                <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16" />
                    <path d="M7 12h10" />
                    <path d="M10 18h4" />
                </svg>
            </div>
            <div>
                <p class="text-[0.66rem] font-black uppercase tracking-[0.18em] text-[#7b8782]">Filters</p>
            </div>
        </div>

        <form class="grid gap-3 lg:grid-cols-2 xl:grid-cols-4" @submit.prevent="$emit('apply')">
            <label class="space-y-1.5">
                <span class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Registration Source</span>
                <select v-model="form.source" class="w-full rounded-[1.05rem] border border-[#d6dfda] bg-[#f5f8f6] px-4 py-2.5 text-[0.95rem] text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                    <option value="">All sources</option>
                    <option v-for="source in filterOptions.sources" :key="source.value" :value="source.value">{{ source.label }}</option>
                </select>
            </label>

            <label class="space-y-1.5">
                <span class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Application Status</span>
                <select v-model="form.status" class="w-full rounded-[1.05rem] border border-[#d6dfda] bg-[#f5f8f6] px-4 py-2.5 text-[0.95rem] text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                    <option value="">Any status</option>
                    <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
            </label>

            <label class="space-y-1.5">
                <span class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Fiscal Year</span>
                <select v-model="form.year" class="w-full rounded-[1.05rem] border border-[#d6dfda] bg-[#f5f8f6] px-4 py-2.5 text-[0.95rem] text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                    <option value="">All years</option>
                    <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">{{ year.label }}</option>
                </select>
            </label>

            <label class="space-y-1.5">
                <span class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Barangay Location</span>
                <select v-model="form.barangay_id" class="w-full rounded-[1.05rem] border border-[#d6dfda] bg-[#f5f8f6] px-4 py-2.5 text-[0.95rem] text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                    <option value="">All barangays</option>
                    <option v-for="barangay in filterOptions.barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                </select>
            </label>

            <div class="col-span-full mt-1 flex justify-end gap-2.5 border-t border-[#e4ebe7] pt-4">
                <button type="button" class="rounded-[1.05rem] px-4 py-2.5 text-[0.95rem] font-bold text-[#697772] transition hover:bg-[#f2f5f3]" @click="$emit('reset')">
                    Reset
                </button>
                <button type="submit" class="rounded-[1.05rem] bg-[#003629] px-5 py-2.5 text-[0.95rem] font-extrabold text-white shadow-sm transition hover:bg-[#0d4637]">
                    Apply Filters
                </button>
            </div>
        </form>
    </section>
</template>
