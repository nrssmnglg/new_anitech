<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    application: { type: Object, required: true },
    farmer: { type: Object, required: true },
});

function farmerInitials(name) {
    return String(name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || '?';
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
        <div class="flex items-center justify-between border-b border-[#dde4de] bg-[#f9fbfa] px-4 py-3">
            <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#003629]/10 text-[#003629]">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                        <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" />
                    </svg>
                </div>
                <span class="text-xs font-bold text-[#0f172a]">Applicant Profile & Information</span>
            </div>

            <Link
                v-if="farmer.showUrl"
                :href="farmer.showUrl"
                class="inline-flex items-center gap-1 rounded-lg border border-[#dde4de] bg-white px-2.5 py-1 text-[0.68rem] font-semibold text-[#003629] shadow-2xs transition hover:bg-[#f1f5f3]"
            >
                <span>View in Registry</span>
                <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor">
                    <path fill-rule="evenodd" d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </Link>
        </div>

        <div class="p-4 sm:p-5">
            <!-- Farmer Profile summary row -->
            <div class="mb-4 flex items-center gap-3.5 rounded-xl border border-emerald-100 bg-emerald-50/40 p-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#003629] to-[#005a45] text-sm font-bold text-white shadow-xs">
                    {{ farmerInitials(farmer.fullName) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-bold text-[#0f172a]">{{ farmer.fullName }}</h2>
                        <span class="font-mono text-xs font-semibold text-[#003629]">{{ farmer.farmerCode }}</span>
                    </div>
                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span>{{ farmer.barangay || 'No barangay' }}</span>
                        <span>•</span>
                        <span>{{ farmer.association || 'No association' }}</span>
                        <span v-if="farmer.memberType">•</span>
                        <span v-if="farmer.memberType" class="font-medium text-[#003629]">{{ farmer.memberType.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Detail Grid -->
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Status -->
                <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-3">
                    <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Membership Status</span>
                    <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ farmer.membershipStatusLabel || 'Pending' }}</p>
                </div>

                <!-- Registration Source -->
                <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-3">
                    <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Intake Source</span>
                    <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ application.sourceLabel }}</p>
                </div>

                <!-- Created / Submitted -->
                <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-3">
                    <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Submission Date</span>
                    <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ application.submittedAt || application.createdAt || 'N/A' }}</p>
                </div>

                <!-- Birth Date & Age -->
                <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-3">
                    <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Birth Date</span>
                    <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ farmer.birthDate || 'Not specified' }}</p>
                </div>

                <!-- Sex & Civil Status -->
                <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-3">
                    <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Personal Demographics</span>
                    <p class="mt-1 text-xs font-bold text-[#0f172a]">
                        {{ [farmer.sex, farmer.civilStatus].filter(Boolean).join(' • ') || 'Not specified' }}
                    </p>
                </div>

                <!-- Mobile -->
                <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-3">
                    <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Contact Mobile</span>
                    <p class="mt-1 font-mono text-xs font-bold text-[#0f172a]">{{ farmer.mobileNumber || 'No mobile recorded' }}</p>
                </div>

                <!-- Address -->
                <div class="rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-3 sm:col-span-2 lg:col-span-3">
                    <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Residential Address</span>
                    <p class="mt-1 text-xs leading-5 text-[#334155]">{{ farmer.address || 'No residential address on file.' }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
