<script setup>
defineProps({
    farmer: { type: Object, required: true },
});
</script>

<template>
    <section class="overflow-hidden rounded-[20px] border border-[#dbe2de] bg-white shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
        <div class="border-b border-[#e4ebe7] bg-[#f4f7f5] px-4 py-3">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Identity</p>
            <h2 class="mt-1 text-base font-bold text-[#1a2420]">Farmer details</h2>
        </div>

        <div class="grid gap-3 px-4 py-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Birth Date</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.profile.birthDateLabel || 'Not set' }}</p>
            </div>
            <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Sex</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.profile.sexLabel || 'Not set' }}</p>
            </div>
            <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Civil Status</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.profile.civilStatusLabel || 'Not set' }}</p>
            </div>
            <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Mobile Number</p>
                <p class="mt-1.5 text-xs font-semibold text-[#003629]">{{ farmer.profile.mobileNumber || 'Not set' }}</p>
            </div>
            <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Barangay</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.barangay?.name || 'Not set' }}</p>
            </div>
            <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Association</p>
                <p class="mt-1.5 text-xs font-semibold text-[#191c1c]">{{ farmer.association?.name || 'No association' }}</p>
            </div>
            <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3 md:col-span-2 xl:col-span-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Permanent Address</p>
                <p class="mt-1.5 text-xs leading-5 text-[#5f6c67]">{{ farmer.profile.address || 'No address recorded.' }}</p>
            </div>
        </div>
    </section>
</template>
