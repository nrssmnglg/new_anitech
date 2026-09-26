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
    <section class="rounded-2xl border border-[#dde4de] bg-white p-3.5 shadow-xs">
        <form class="flex flex-col gap-2.5 xl:flex-row xl:items-end xl:justify-between" @submit.prevent="emit('apply')">
            <div class="grid flex-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Search Input -->
                <label class="space-y-1">
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Search</span>
                    <div class="relative">
                        <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#94a3b8]" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                        </svg>
                        <input
                            v-model="form.search"
                            type="text"
                            :placeholder="activeSection === 'records' ? 'Reference, code, or name' : 'Farmer code or name'"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-8.5 pr-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                    </div>
                </label>

                <!-- Status Filter (Records only) -->
                <label v-if="activeSection === 'records'" class="space-y-1">
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Status</span>
                    <select
                        v-model="form.status"
                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                    >
                        <option value="">All Statuses</option>
                        <option v-for="item in filterOptions.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                    </select>
                </label>

                <!-- Year Filter -->
                <label class="space-y-1">
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Year</span>
                    <select
                        v-model="form.year"
                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                    >
                        <option value="">All Years</option>
                        <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">{{ year.label }}</option>
                    </select>
                </label>

                <!-- Barangay Filter -->
                <label class="space-y-1">
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Barangay</span>
                    <select
                        v-model="form.barangay_id"
                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                    >
                        <option value="">All Barangays</option>
                        <option v-for="item in filterOptions.barangays" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                    </select>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-3 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                    @click="emit('reset')"
                >
                    Reset
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-[#bbf7d0] bg-[#f0fdf4] px-3 text-xs font-bold text-[#15803d] transition hover:bg-[#dcfce7]"
                    @click="emit('export')"
                >
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm4.75 6.75a.75.75 0 0 1 1.5 0v3.44l1.22-1.22a.75.75 0 1 1 1.06 1.06l-2.5 2.5a.75.75 0 0 1-1.06 0l-2.5-2.5a.75.75 0 1 1 1.06-1.06l1.22 1.22V8.75Z" clip-rule="evenodd" />
                    </svg>
                    <span>Export</span>
                </button>
                <button
                    type="submit"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97]"
                >
                    Apply Filters
                </button>
            </div>
        </form>
    </section>
</template>
