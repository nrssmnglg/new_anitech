<script setup>
defineProps({
    application: { type: Object, required: true },
    farmer: { type: Object, required: true },
    flow: { type: Object, required: true },
    assessment: { type: Object, required: true },
    paymentStatusLabel: { type: String, required: true },
});
</script>

<template>
    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <!-- Applicant -->
        <article class="flex items-center gap-3 rounded-2xl border border-[#dde4de] bg-white p-3.5 shadow-xs transition hover:border-[#b5c7bd]">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-[#003629]">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Applicant</p>
                <p class="truncate text-sm font-bold text-[#0f172a]">{{ farmer.fullName }}</p>
                <p class="font-mono text-[0.68rem] text-slate-500">{{ farmer.farmerCode }}</p>
            </div>
        </article>

        <!-- Document Checklist -->
        <article class="flex items-center gap-3 rounded-2xl border border-[#dde4de] bg-white p-3.5 shadow-xs transition hover:border-[#b5c7bd]">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                    <rect x="9" y="3" width="6" height="4" rx="2" />
                    <path d="m9 14 2 2 4-4" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Checklist</p>
                <p class="text-sm font-bold text-[#0f172a]">
                    {{ flow.isHistorical ? 'Historical' : `${flow.verifiedCount} of ${flow.requiredCount} Cleared` }}
                </p>
                <p class="text-[0.68rem] text-slate-500">
                    {{ flow.isHistorical ? 'Legacy record' : flow.verifiedCount >= flow.requiredCount ? 'All required verified' : 'Pending verification' }}
                </p>
            </div>
        </article>

        <!-- Payment Assessment -->
        <article class="flex items-center gap-3 rounded-2xl border border-[#dde4de] bg-white p-3.5 shadow-xs transition hover:border-[#b5c7bd]">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2" />
                    <line x1="2" y1="10" x2="22" y2="10" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Assessment</p>
                <p class="text-sm font-bold text-[#0f172a]">
                    ₱{{ Number(assessment.totalAmountDue || 0).toFixed(2) }}
                </p>
                <p class="text-[0.68rem] font-semibold" :class="flow.paymentSettled ? 'text-emerald-700' : 'text-amber-700'">
                    {{ paymentStatusLabel }}
                </p>
            </div>
        </article>

        <!-- Member Type -->
        <article class="flex items-center gap-3 rounded-2xl border border-[#dde4de] bg-white p-3.5 shadow-xs transition hover:border-[#b5c7bd]">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-700">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Member Type</p>
                <p class="truncate text-sm font-bold text-[#0f172a]">
                    {{ farmer.memberType?.code || 'N/A' }}
                </p>
                <p class="truncate text-[0.68rem] text-slate-500">
                    {{ farmer.memberType?.name || 'Category not set' }}
                </p>
            </div>
        </article>
    </section>
</template>
