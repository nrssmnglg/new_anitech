<script setup>
defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

defineEmits(['apply', 'reset']);
</script>

<template>
    <section class="rounded-[24px] border border-[#dfe5e1] bg-[#f2f4f3] p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
        <form class="grid gap-4 xl:grid-cols-[1.4fr_0.7fr_1fr_1fr_auto]" @submit.prevent="$emit('apply')">
            <label class="space-y-2">
                <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Farmer</span>
                <input v-model="form.queue_search" type="text" placeholder="Name or farmer code" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
            </label>
            <label class="space-y-2">
                <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Year</span>
                <select v-model="form.queue_year" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">{{ year.label }}</option>
                </select>
            </label>
            <label class="space-y-2">
                <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Barangay</span>
                <select v-model="form.queue_barangay_id" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All barangays</option>
                    <option v-for="barangay in filterOptions.barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                </select>
            </label>
            <label class="space-y-2">
                <span class="text-[0.66rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Member Type</span>
                <select v-model="form.queue_member_type_id" class="w-full rounded-xl border border-[#cfd7d3] bg-white px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All member types</option>
                    <option v-for="memberType in filterOptions.memberTypes" :key="memberType.id" :value="String(memberType.id)">{{ memberType.label }}</option>
                </select>
            </label>
            <div class="flex items-end gap-2">
                <button type="button" class="inline-flex h-[48px] items-center justify-center rounded-xl border border-[#cfd7d3] bg-white px-4 text-sm font-bold text-[#697772] transition hover:bg-[#f9fbfa]" @click="$emit('reset')">
                    Reset
                </button>
                <button type="submit" class="inline-flex h-[48px] items-center justify-center rounded-xl border border-[#003629] bg-white px-5 text-sm font-extrabold text-[#003629] transition hover:bg-[#edf5f2]">
                    Apply Filters
                </button>
            </div>
        </form>
    </section>
</template>
