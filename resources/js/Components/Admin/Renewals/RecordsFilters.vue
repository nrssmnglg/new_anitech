<script setup>
defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    urls: { type: Object, required: true },
});

defineEmits(['apply', 'reset', 'export']);
</script>

<template>
    <section class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-xs">
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-[1.35fr_0.7fr_1fr_0.8fr_0.8fr_auto]" @submit.prevent="$emit('apply')">
            <div class="space-y-1">
                <label for="records-farmer-search" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Farmer</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                    </svg>
                    <input
                        id="records-farmer-search"
                        v-model="form.record_search"
                        type="text"
                        placeholder="Search name or code..."
                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                    />
                </div>
            </div>

            <div class="space-y-1">
                <label for="records-year" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Year</label>
                <select
                    id="records-year"
                    v-model="form.record_year"
                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                >
                    <option value="">All Years</option>
                    <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">{{ year.label }}</option>
                </select>
            </div>

            <div class="space-y-1">
                <label for="records-barangay" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Barangay</label>
                <select
                    id="records-barangay"
                    v-model="form.record_barangay_id"
                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                >
                    <option value="">All Barangays</option>
                    <option v-for="item in filterOptions.barangays" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                </select>
            </div>

            <div class="space-y-1">
                <label for="records-source" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Type</label>
                <select
                    id="records-source"
                    v-model="form.record_source"
                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                >
                    <option value="">All Types</option>
                    <option v-for="item in filterOptions.sources" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </div>

            <div class="space-y-1">
                <label for="records-status" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Status</label>
                <select
                    id="records-status"
                    v-model="form.record_status"
                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                >
                    <option value="">All Statuses</option>
                    <option v-for="item in filterOptions.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </div>

            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3 xl:col-span-1">
                <button
                    type="button"
                    class="inline-flex h-9 flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 xl:flex-initial"
                    @click="$emit('reset')"
                >
                    Reset
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 flex-1 items-center justify-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-bold text-emerald-800 shadow-xs transition hover:bg-emerald-100 xl:flex-initial"
                    @click="$emit('export')"
                >
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-emerald-600" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm4.75 6.75a.75.75 0 0 1 1.5 0v3.44l1.22-1.22a.75.75 0 1 1 1.06 1.06l-2.5 2.5a.75.75 0 0 1-1.06 0l-2.5-2.5a.75.75 0 1 1 1.06-1.06l1.22 1.22V8.75Z" clip-rule="evenodd" />
                    </svg>
                    <span>PDF</span>
                </button>
                <button
                    type="submit"
                    class="inline-flex h-9 flex-1 items-center justify-center rounded-lg bg-[#003629] px-4 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a] active:scale-95 xl:flex-initial"
                >
                    Apply
                </button>
            </div>
        </form>
    </section>
</template>
