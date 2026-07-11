<script setup>
defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    activeFilterCount: { type: Number, required: true },
    totalPages: { type: Number, required: true },
    filteredTargetCount: { type: Number, required: true },
    qualityCards: { type: Array, required: true },
});

defineEmits(['apply', 'reset', 'open-export', 'set-quality-filter']);
</script>

<template>
    <section class="rounded-[20px] border border-[#dbe2de] bg-white p-3.5 shadow-[0_8px_24px_rgba(15,23,42,0.045)]">
        <div class="flex flex-col gap-3 border-b border-[#e4ebe7] px-1.5 pb-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Filters</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-[0.92rem]">
                <span class="inline-flex items-center rounded-full bg-[#f0f4f2] px-3 py-1 font-semibold text-[#5e6d66]">
                    {{ activeFilterCount }} active filter{{ activeFilterCount === 1 ? '' : 's' }}
                </span>
                <span class="inline-flex items-center rounded-full bg-[#eef7f2] px-3 py-1 font-semibold text-[#245342]">
                    {{ totalPages }} page{{ totalPages === 1 ? '' : 's' }}
                </span>
                <span class="inline-flex items-center rounded-full bg-[#f6f8f7] px-3 py-1 font-semibold text-[#5e6d66]">
                    {{ filteredTargetCount }} result{{ filteredTargetCount === 1 ? '' : 's' }}
                </span>
            </div>
        </div>

        <form class="space-y-3 px-1.5 pt-3" @submit.prevent="$emit('apply')">
            <div class="grid gap-3 xl:grid-cols-[1.5fr_1fr_1fr_1fr]">
                <label class="space-y-1.5">
                    <span class="ml-1 text-[0.6rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Search</span>
                    <div class="relative">
                        <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#95a39c]" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-3.5-3.5"></path>
                        </svg>
                        <input v-model="form.search" type="text" placeholder="Code, name, mobile, address" class="w-full rounded-[1.05rem] border border-[#d7e0db] bg-[#f8faf9] py-2.5 pl-10 pr-4 text-[0.95rem] text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                    </div>
                </label>

                <label class="space-y-1.5">
                    <span class="ml-1 text-[0.6rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Status</span>
                    <select v-model="form.status" class="w-full rounded-[1.05rem] border border-[#d7e0db] bg-[#f8faf9] px-4 py-2.5 text-[0.95rem] text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                        <option value="">All statuses</option>
                        <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </label>

                <label class="space-y-1.5">
                    <span class="ml-1 text-[0.6rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Barangay</span>
                    <select v-model="form.barangay_id" class="w-full rounded-[1.05rem] border border-[#d7e0db] bg-[#f8faf9] px-4 py-2.5 text-[0.95rem] text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                        <option value="">All barangays</option>
                        <option v-for="barangay in filterOptions.barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                    </select>
                </label>

                <label class="space-y-1.5">
                    <span class="ml-1 text-[0.6rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Member Type</span>
                    <select v-model="form.member_type_id" class="w-full rounded-[1.05rem] border border-[#d7e0db] bg-[#f8faf9] px-4 py-2.5 text-[0.95rem] text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                        <option value="">All member types</option>
                        <option v-for="memberType in filterOptions.memberTypes" :key="memberType.id" :value="String(memberType.id)">
                            {{ memberType.code }} - {{ memberType.name }}
                        </option>
                    </select>
                </label>
            </div>

            <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                <label class="space-y-1.5">
                    <span class="ml-1 text-[0.6rem] font-black uppercase tracking-[0.2em] text-[#78857f]">Data Quality</span>
                    <select v-model="form.quality" class="w-full rounded-[1.05rem] border border-[#d7e0db] bg-[#f8faf9] px-4 py-2.5 text-[0.95rem] text-[#1a2420] outline-none transition focus:border-[#376757] focus:bg-white">
                        <option value="">All records</option>
                        <option value="duplicate">Possible duplicates</option>
                        <option value="incomplete_profile">Incomplete profiles</option>
                        <option value="invalid_mobile">Invalid mobile numbers</option>
                        <option value="barangay_association_mismatch">Barangay and association mismatches</option>
                        <option value="inactive_review">Inactive records needing review</option>
                    </select>
                </label>

                <div class="flex flex-wrap items-end justify-start gap-2 xl:justify-end">
                    <button type="button" class="inline-flex h-[42px] items-center justify-center rounded-[1.05rem] border border-[#d6dfda] px-4 text-[0.95rem] font-bold text-[#5c6b65] transition hover:bg-[#f3f6f4]" @click="$emit('open-export', 'pdf')">
                        Export PDF
                    </button>
                    <button type="button" class="inline-flex h-[42px] items-center justify-center rounded-[1.05rem] border border-[#d6dfda] px-4 text-[0.95rem] font-bold text-[#5c6b65] transition hover:bg-[#f3f6f4]" @click="$emit('open-export', 'xlsx')">
                        Export Excel
                    </button>
                    <button type="button" class="inline-flex h-[42px] items-center justify-center rounded-[1.05rem] border border-[#d6dfda] px-4 text-[0.95rem] font-bold text-[#5c6b65] transition hover:bg-[#f3f6f4]" @click="$emit('reset')">
                        Reset
                    </button>
                    <button type="submit" class="inline-flex h-[42px] items-center justify-center rounded-[1.05rem] bg-[#003629] px-5 text-[0.95rem] font-extrabold text-white transition hover:bg-[#0d4637]">
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </section>
</template>
