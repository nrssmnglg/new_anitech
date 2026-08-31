<script setup>
defineProps({
    farmer: { type: Object, required: true },
    summary: { type: Object, required: true },
});
</script>

<template>
    <section class="overflow-hidden rounded-[12px] border border-[#cbd4cf] bg-white">
        <div class="border-b border-[#dbe2de] px-4 py-3">
            <p class="text-[0.7rem] font-black uppercase tracking-[0.08em] text-[#263d35]">Identity</p>
        </div>

        <div class="grid gap-2 px-4 py-3 md:grid-cols-2">
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Birth Date</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.profile.birthDateLabel || 'Not set' }}</p>
            </div>
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Sex</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.profile.sexLabel || 'Not set' }}</p>
            </div>
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Civil Status</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.profile.civilStatusLabel || 'Not set' }}</p>
            </div>
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Mobile Number</p>
                <p class="mt-1.5 text-xs font-semibold text-[#003629]">{{ farmer.profile.mobileNumber || 'Not set' }}</p>
            </div>
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[#7a8781]">Registered</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.registeredAt || 'Not recorded' }}</p>
            </div>
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[#7a8781]">Collections</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">PHP {{ Number(summary.totalPaid || 0).toFixed(2) }}</p>
            </div>
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[#7a8781]">Current Ledger</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ summary.currentYearLedger?.paymentStatus || 'No record' }} <span class="font-normal">{{ summary.currentYearLedger?.year ? `(Ledger year ${summary.currentYearLedger.year})` : '' }}</span></p>
            </div>
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[#7a8781]">Record Source</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.recordOrigin || 'Not set' }} <span class="font-normal">({{ farmer.barangay?.name || 'No barangay' }})</span></p>
            </div>
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5 md:col-span-2">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[#7a8781]">Association</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.association?.name || 'No association' }}</p>
            </div>
            <div class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5 md:col-span-2">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[#7a8781]">Last Record Edited By</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.accountability?.lastUpdatedBy || 'No staff edit recorded' }} <span v-if="farmer.accountability?.lastUpdatedAt" class="ml-3 font-normal">{{ farmer.accountability.lastUpdatedAt }}</span></p>
            </div>
        </div>
    </section>
</template>
