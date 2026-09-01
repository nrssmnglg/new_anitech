<script setup>
defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

defineEmits(['apply', 'reset']);
</script>

<template>
    <section class="rounded-lg border border-[#dbe2de] bg-white p-3.5">
        <div class="mb-3 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-md bg-[#e8f2ea] text-[#1b4d3e]">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16" />
                    <path d="M7 12h10" />
                    <path d="M10 18h4" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[#52615b]">Application Filters</p>
            </div>
        </div>

        <form class="grid items-end gap-2.5 md:grid-cols-2 xl:grid-cols-[1fr_1fr_0.8fr_1fr_auto]" @submit.prevent="$emit('apply')">
            <label class="space-y-1">
                <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#6b7772]">Registration Source</span>
                <select v-model="form.source" class="h-9 w-full rounded-md border border-[#d6dfda] bg-[#f7f9f8] px-3 text-xs text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                    <option value="">All sources</option>
                    <option v-for="source in filterOptions.sources" :key="source.value" :value="source.value">{{ source.label }}</option>
                </select>
            </label>

            <label class="space-y-1">
                <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#6b7772]">Application Status</span>
                <select v-model="form.status" class="h-9 w-full rounded-md border border-[#d6dfda] bg-[#f7f9f8] px-3 text-xs text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                    <option value="">Any status</option>
                    <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
            </label>

            <label class="space-y-1">
                <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#6b7772]">Fiscal Year</span>
                <select v-model="form.year" class="h-9 w-full rounded-md border border-[#d6dfda] bg-[#f7f9f8] px-3 text-xs text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                    <option value="">All years</option>
                    <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">{{ year.label }}</option>
                </select>
            </label>

            <label class="space-y-1">
                <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#6b7772]">Barangay Location</span>
                <select v-model="form.barangay_id" class="h-9 w-full rounded-md border border-[#d6dfda] bg-[#f7f9f8] px-3 text-xs text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                    <option value="">All barangays</option>
                    <option v-for="barangay in filterOptions.barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                </select>
            </label>

            <div class="flex items-center justify-end gap-2">
                <button type="button" class="h-9 rounded-md px-3 text-xs font-medium text-[#697772] transition hover:bg-[#f2f5f3]" @click="$emit('reset')">
                    Reset
                </button>
                <button type="submit" class="h-9 whitespace-nowrap rounded-md bg-[#003629] px-3.5 text-xs font-semibold text-white transition hover:bg-[#0d4637]">
                    Apply Filters
                </button>
            </div>
        </form>
    </section>
</template>
