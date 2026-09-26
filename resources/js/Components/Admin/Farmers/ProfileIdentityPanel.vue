<script setup>
defineProps({
    farmer: { type: Object, required: true },
    summary: { type: Object, required: true },
});

const currencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
});
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-[#014d3c]"></span>
                <h2 class="text-xs font-bold text-[#0f172a]">Personal &amp; Registry Identity</h2>
            </div>
            <span class="rounded-md bg-[#f1f5f9] px-2 py-0.5 font-mono text-[0.62rem] font-semibold text-[#475569]">
                {{ farmer.farmerCode }}
            </span>
        </div>

        <div class="p-5 space-y-5">
            <!-- Section 1: Demographics -->
            <div>
                <p class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Demographics</p>
                <div class="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Birth Date</span>
                        <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ farmer.profile?.birthDateLabel || 'Not set' }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Sex / Gender</span>
                        <p class="mt-1 text-xs font-bold capitalize text-[#0f172a]">{{ farmer.profile?.sexLabel || 'Not set' }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Civil Status</span>
                        <p class="mt-1 text-xs font-bold capitalize text-[#0f172a]">{{ farmer.profile?.civilStatusLabel || 'Not set' }}</p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Contact & Location -->
            <div>
                <p class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Contact &amp; Location</p>
                <div class="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Mobile Phone</span>
                        <p class="mt-1 text-xs font-bold text-[#014d3c]">
                            <a v-if="farmer.profile?.mobileNumber" :href="`tel:${farmer.profile.mobileNumber}`" class="hover:underline">
                                {{ farmer.profile.mobileNumber }}
                            </a>
                            <span v-else class="text-[#94a3b8]">Not recorded</span>
                        </p>
                    </div>
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Barangay</span>
                        <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ farmer.barangay?.name || 'No barangay' }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Association</span>
                        <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ farmer.association?.name || 'No association' }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3 sm:col-span-2 lg:col-span-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Complete Address</span>
                        <p class="mt-1 text-xs font-medium text-[#334155]">{{ farmer.profile?.address || 'No street address specified' }}</p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Registry & Financial Information -->
            <div>
                <p class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Registry &amp; Ledger Standing</p>
                <div class="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Registration Date</span>
                        <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ farmer.registeredAt || 'Not recorded' }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Total Collections Paid</span>
                        <p class="mt-1 text-xs font-bold text-[#15803d]">
                            {{ currencyFormatter.format(Number(summary.totalPaid || 0)) }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Current Year Ledger</span>
                        <p class="mt-1 text-xs font-bold text-[#0f172a]">
                            {{ summary.currentYearLedger?.paymentStatus || 'No ledger' }}
                            <span v-if="summary.currentYearLedger?.year" class="text-[0.65rem] font-normal text-[#64748b]">({{ summary.currentYearLedger.year }})</span>
                        </p>
                    </div>
                    <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Record Source</span>
                        <p class="mt-1 text-xs font-bold text-[#0f172a] capitalize">{{ farmer.recordOrigin || 'Office record' }}</p>
                    </div>
                </div>
            </div>

            <!-- Accountability audit bar -->
            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] px-4 py-2.5 text-xs text-[#64748b] flex flex-wrap items-center justify-between gap-2">
                <span>
                    Last edited by: <strong class="text-[#0f172a]">{{ farmer.accountability?.lastUpdatedBy || 'System / Initial intake' }}</strong>
                </span>
                <span v-if="farmer.accountability?.lastUpdatedAt" class="text-[0.68rem] text-[#94a3b8]">
                    {{ farmer.accountability.lastUpdatedAt }}
                </span>
            </div>
        </div>
    </section>
</template>
