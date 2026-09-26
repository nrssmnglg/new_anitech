<script setup>
defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const emit = defineEmits(['apply', 'reset']);
</script>

<template>
    <section class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-xs">
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-[1.4fr_0.7fr_1fr_1fr_auto]" @submit.prevent="emit('apply')">
            <div class="space-y-1">
                <label for="queue-farmer-search" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Farmer</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                    </svg>
                    <input
                        id="queue-farmer-search"
                        v-model="form.queue_search"
                        type="text"
                        placeholder="Search by name or code..."
                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                    />
                </div>
            </div>

            <div class="space-y-1">
                <label for="queue-year" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Year</label>
                <select
                    id="queue-year"
                    v-model="form.queue_year"
                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                >
                    <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">{{ year.label }}</option>
                </select>
            </div>

            <div class="space-y-1">
                <label for="queue-barangay" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Barangay</label>
                <select
                    id="queue-barangay"
                    v-model="form.queue_barangay_id"
                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                >
                    <option value="">All Barangays</option>
                    <option v-for="barangay in filterOptions.barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                </select>
            </div>

            <div class="space-y-1">
                <label for="queue-member-type" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Member Type</label>
                <select
                    id="queue-member-type"
                    v-model="form.queue_member_type_id"
                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                >
                    <option value="">All Member Types</option>
                    <option v-for="memberType in filterOptions.memberTypes" :key="memberType.id" :value="String(memberType.id)">{{ memberType.label }}</option>
                </select>
            </div>

            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4 xl:col-span-1">
                <button
                    type="button"
                    class="inline-flex h-9 flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 xl:flex-initial"
                    @click="emit('reset')"
                >
                    Reset
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
