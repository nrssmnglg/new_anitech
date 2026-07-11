<script setup>
defineProps({
    application: { type: Object, required: true },
    farmer: { type: Object, required: true },
});
</script>

<template>
    <section class="rounded-[24px] border border-[#dbe2de] bg-white shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
        <div class="border-b border-[#e4ebe7] px-5 py-4">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Linked Farmer</p>
            <h2 class="mt-1 text-lg font-bold text-[#1a2420]">Applicant profile</h2>
        </div>

        <div class="grid gap-4 px-5 py-5 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Membership Status</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ farmer.membershipStatusLabel || 'Not set' }}</p>
            </div>
            <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Submitted</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ application.submittedAt || 'Not submitted' }}</p>
            </div>
            <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Source</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ application.sourceLabel }}</p>
            </div>
            <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Birth Date</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ farmer.birthDate || 'Not set' }}</p>
            </div>
            <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Mobile Number</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ farmer.mobileNumber || 'Not set' }}</p>
            </div>
            <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Location</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ farmer.barangay || 'No barangay' }}</p>
                <p class="mt-1 text-xs text-[#78857f]">{{ farmer.association || 'No association' }}</p>
            </div>
            <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] p-4 md:col-span-2 xl:col-span-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Address</p>
                <p class="mt-2 text-sm leading-6 text-[#5f6c67]">{{ farmer.address || 'No address recorded.' }}</p>
            </div>
        </div>
    </section>
</template>
