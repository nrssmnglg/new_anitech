<script setup>
defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const emit = defineEmits(['apply', 'reset']);
</script>

<template>
    <section class="rounded-lg border border-[#dfe5e1] bg-[#f7f9f8] p-3">
        <form class="grid gap-2.5 xl:grid-cols-[1.4fr_0.65fr_1fr_1fr_auto]" @submit.prevent="emit('apply')">
            <label class="space-y-1">
                <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Farmer</span>
                <input v-model="form.queue_search" type="text" placeholder="Name or farmer code" class="h-9 w-full rounded-md border border-[#cfd7d3] bg-white px-3 text-xs text-[#191c1c] outline-none transition focus:border-[#376757]">
            </label>
            <label class="space-y-1">
                <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Year</span>
                <select v-model="form.queue_year" class="h-9 w-full rounded-md border border-[#cfd7d3] bg-white px-3 text-xs text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">{{ year.label }}</option>
                </select>
            </label>
            <label class="space-y-1">
                <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Barangay</span>
                <select v-model="form.queue_barangay_id" class="h-9 w-full rounded-md border border-[#cfd7d3] bg-white px-3 text-xs text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All barangays</option>
                    <option v-for="barangay in filterOptions.barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                </select>
            </label>
            <label class="space-y-1">
                <span class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Member Type</span>
                <select v-model="form.queue_member_type_id" class="h-9 w-full rounded-md border border-[#cfd7d3] bg-white px-3 text-xs text-[#191c1c] outline-none transition focus:border-[#376757]">
                    <option value="">All member types</option>
                    <option v-for="memberType in filterOptions.memberTypes" :key="memberType.id" :value="String(memberType.id)">{{ memberType.label }}</option>
                </select>
            </label>
            <div class="flex items-end gap-2">
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-[#cfd7d3] bg-white px-3 text-xs font-semibold text-[#697772] transition hover:bg-[#f9fbfa]" @click="emit('reset')">
                    Reset
                </button>
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#003629] px-3.5 text-xs font-semibold text-white transition hover:bg-[#0d4637]">
                    Apply
                </button>
            </div>
        </form>
    </section>
</template>
