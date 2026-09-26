<script setup>
defineProps({
    form: { type: Object, required: true },
    memberTypeOptions: { type: Array, required: true },
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <label class="space-y-1.5">
            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Member Type</span>
            <select
                v-model="form.member_type_id"
                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
            >
                <option value="">Select member type</option>
                <option v-for="option in memberTypeOptions" :key="option.id" :value="String(option.id)">{{ option.label }}</option>
            </select>
            <p v-if="form.errors.member_type_id" class="text-xs font-medium text-rose-600">{{ form.errors.member_type_id }}</p>
        </label>

        <label class="space-y-1.5">
            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Year</span>
            <input
                v-model="form.year"
                type="number"
                min="2000"
                max="2100"
                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
            >
            <p v-if="form.errors.year" class="text-xs font-medium text-rose-600">{{ form.errors.year }}</p>
        </label>

        <label class="space-y-1.5">
            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Renewal Deadline</span>
            <input
                v-model="form.renewal_deadline"
                type="date"
                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
            >
            <p v-if="form.errors.renewal_deadline" class="text-xs font-medium text-rose-600">{{ form.errors.renewal_deadline }}</p>
        </label>

        <label class="space-y-1.5">
            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Membership Fee (₱)</span>
            <input
                v-model="form.membership_fee"
                type="number"
                min="0"
                step="0.01"
                placeholder="0.00"
                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
            >
            <p v-if="form.errors.membership_fee" class="text-xs font-medium text-rose-600">{{ form.errors.membership_fee }}</p>
        </label>

        <label class="space-y-1.5">
            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Annual Due (₱)</span>
            <input
                v-model="form.annual_due"
                type="number"
                min="0"
                step="0.01"
                placeholder="0.00"
                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
            >
            <p v-if="form.errors.annual_due" class="text-xs font-medium text-rose-600">{{ form.errors.annual_due }}</p>
        </label>

        <label class="space-y-1.5">
            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Mortuary Fee (₱)</span>
            <input
                v-model="form.mortuary_fee"
                type="number"
                min="0"
                step="0.01"
                placeholder="0.00"
                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
            >
            <p v-if="form.errors.mortuary_fee" class="text-xs font-medium text-rose-600">{{ form.errors.mortuary_fee }}</p>
        </label>

        <label class="space-y-1.5">
            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Effective From</span>
            <input
                v-model="form.effective_from"
                type="date"
                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
            >
            <p v-if="form.errors.effective_from" class="text-xs font-medium text-rose-600">{{ form.errors.effective_from }}</p>
        </label>

        <label class="space-y-1.5">
            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Effective To</span>
            <input
                v-model="form.effective_to"
                type="date"
                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
            >
            <p v-if="form.errors.effective_to" class="text-xs font-medium text-rose-600">{{ form.errors.effective_to }}</p>
        </label>

        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#e2eae4] bg-gradient-to-r from-[#fbfcfb] to-[#f8faf9] p-3.5 transition-all duration-150 hover:border-[#cbd8cf] hover:bg-[#f4f7f5] md:col-span-2">
            <input
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 rounded border-[#0f5b46]/30 text-[#0f5b46] focus:ring-[#0f5b46]/20"
            >
            <div>
                <p class="text-xs font-bold text-[#0f172a]">Set as active schedule</p>
                <p class="text-[0.68rem] text-[#64748b]">Used immediately for assessments and renewals across all eligible members.</p>
            </div>
        </label>
    </div>
</template>
