<script setup>
defineProps({
    form: { type: Object, required: true },
    memberTypeOptions: { type: Array, required: true },
});
</script>

<template>
    <div class="grid gap-3 md:grid-cols-2">
        <label class="space-y-1">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Member Type</span>
            <select v-model="form.member_type_id" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                <option value="">Select member type</option>
                <option v-for="option in memberTypeOptions" :key="option.id" :value="String(option.id)">{{ option.label }}</option>
            </select>
            <p v-if="form.errors.member_type_id" class="text-sm font-medium text-error">{{ form.errors.member_type_id }}</p>
        </label>

        <label class="space-y-1">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Year</span>
            <input v-model="form.year" type="number" min="2000" max="2100" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
            <p v-if="form.errors.year" class="text-sm font-medium text-error">{{ form.errors.year }}</p>
        </label>

        <label class="space-y-1"><span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Renewal Deadline</span>
            <input v-model="form.renewal_deadline" type="date" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
            <p v-if="form.errors.renewal_deadline" class="text-sm font-medium text-error">{{ form.errors.renewal_deadline }}</p>
        </label>

        <label class="space-y-1"><span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Membership Fee</span>
            <input v-model="form.membership_fee" type="number" min="0" step="0.01" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
            <p v-if="form.errors.membership_fee" class="text-sm font-medium text-error">{{ form.errors.membership_fee }}</p>
        </label>

        <label class="space-y-1"><span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Annual Due</span>
            <input v-model="form.annual_due" type="number" min="0" step="0.01" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
            <p v-if="form.errors.annual_due" class="text-sm font-medium text-error">{{ form.errors.annual_due }}</p>
        </label>

        <label class="space-y-1"><span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Mortuary Fee</span>
            <input v-model="form.mortuary_fee" type="number" min="0" step="0.01" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
            <p v-if="form.errors.mortuary_fee" class="text-sm font-medium text-error">{{ form.errors.mortuary_fee }}</p>
        </label>

        <label class="space-y-1"><span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Effective From</span>
            <input v-model="form.effective_from" type="date" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
            <p v-if="form.errors.effective_from" class="text-sm font-medium text-error">{{ form.errors.effective_from }}</p>
        </label>

        <label class="space-y-1"><span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Effective To</span>
            <input v-model="form.effective_to" type="date" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
            <p v-if="form.errors.effective_to" class="text-sm font-medium text-error">{{ form.errors.effective_to }}</p>
        </label>

        <label class="flex items-center gap-2 rounded-md border border-[#e9efeb] bg-[#fbfcfb] px-3 py-2 md:col-span-2">
            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-[#0f5b46]/25 text-[#0f5b46] focus:ring-[#0f5b46]/20">
            <div>
                <p class="text-xs font-semibold text-[#0f172a]">Set as active schedule</p>
                <p class="text-[0.62rem] text-[#64748b]">Used immediately for assessments and renewals.</p>
            </div>
        </label>
    </div>
</template>
