<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    activeFilterCount: { type: Number, required: true },
    totalPages: { type: Number, required: true },
    filteredTargetCount: { type: Number, required: true },
});

const associations = computed(() => {
    if (!props.form.barangay_id) {
        return props.filterOptions.associations || [];
    }

    return (props.filterOptions.associations || []).filter(
        (association) => String(association.barangay_key || association.barangay_id) === String(props.form.barangay_id),
    );
});

watch(() => props.form.barangay_id, () => {
    if (props.form.association_id && !associations.value.some(
        (association) => String(association.key || association.id) === String(props.form.association_id),
    )) {
        props.form.association_id = '';
    }
});

defineEmits(['apply', 'reset', 'open-export']);
</script>

<template>
    <section class="bg-[#fbfcfc] px-4 py-3 sm:px-5">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#263d35]" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                <p class="text-[0.76rem] font-black uppercase tracking-[0.08em] text-[#263d35]">Registry Filters</p>
            </div>
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <span class="inline-flex items-center rounded-full bg-[#f0f4f2] px-2.5 py-1 font-semibold text-[#5e6d66]">
                    {{ activeFilterCount }} active filter{{ activeFilterCount === 1 ? '' : 's' }}
                </span>
                <span class="inline-flex items-center rounded-full border border-[#91d6b9] bg-[#f1fff9] px-2.5 py-1 font-semibold text-[#003629]">
                    {{ filteredTargetCount }} result{{ filteredTargetCount === 1 ? '' : 's' }}
                </span>
            </div>
        </div>

        <form class="mt-3 space-y-2" @submit.prevent="$emit('apply')">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.5fr_1fr_1fr_1fr_1fr]">
                <label class="space-y-1.5">
                    <span class="text-[0.65rem] font-black uppercase tracking-[0.08em] text-[#263d35]">Search Records</span>
                    <div class="relative">
                        <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#95a39c]" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-3.5-3.5"></path>
                        </svg>
                        <input v-model="form.search" type="text" placeholder="Search by code, name, mobile..." class="w-full rounded-md border border-[#c8d0cc] bg-white py-2 pl-9 pr-3 text-sm text-[#1a2420] outline-none transition focus:border-[#376757]">
                    </div>
                </label>

                <label class="space-y-1.5">
                    <span class="ml-1 text-[0.6rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Association</span>
                    <select v-model="form.association_id" class="w-full rounded-md border border-[#c8d0cc] bg-white px-3 py-2 text-sm text-[#1a2420] outline-none transition focus:border-[#376757]">
                        <option value="">All associations</option>
                        <option v-for="association in associations" :key="association.key || association.id" :value="String(association.key || association.id)">{{ association.name }}</option>
                    </select>
                </label>

                <label class="space-y-1.5">
                    <span class="ml-1 text-[0.6rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Status</span>
                    <select v-model="form.status" class="w-full rounded-md border border-[#c8d0cc] bg-white px-3 py-2 text-sm text-[#1a2420] outline-none transition focus:border-[#376757]" @change="$emit('apply')">
                        <option value="">All statuses</option>
                        <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </label>

                <label class="space-y-1.5">
                    <span class="ml-1 text-[0.6rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Barangay</span>
                    <select v-model="form.barangay_id" class="w-full rounded-md border border-[#c8d0cc] bg-white px-3 py-2 text-sm text-[#1a2420] outline-none transition focus:border-[#376757]">
                        <option value="">All barangays</option>
                        <option v-for="barangay in filterOptions.barangays" :key="barangay.key || barangay.id" :value="String(barangay.key || barangay.id)">{{ barangay.name }}</option>
                    </select>
                </label>

                <label class="space-y-1.5">
                    <span class="ml-1 text-[0.6rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Member Type</span>
                    <select v-model="form.member_type_id" class="w-full rounded-md border border-[#c8d0cc] bg-white px-3 py-2 text-sm text-[#1a2420] outline-none transition focus:border-[#376757]">
                        <option value="">All member types</option>
                        <option v-for="memberType in filterOptions.memberTypes" :key="memberType.key || memberType.id" :value="String(memberType.key || memberType.id)">
                            {{ memberType.code }} - {{ memberType.name }}
                        </option>
                    </select>
                </label>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                    <button type="button" class="inline-flex h-8 items-center justify-center px-2 text-xs font-bold text-[#263d35] transition hover:text-[#003629]" @click="$emit('reset')">
                        Clear All
                    </button>
                    <button type="submit" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md bg-[#003629] px-4 text-xs font-extrabold text-white transition hover:bg-[#0d4637]">
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16l-6 7v6l-4 2v-8z"/></svg>
                        Apply Filters
                    </button>
            </div>
        </form>
    </section>
</template>
