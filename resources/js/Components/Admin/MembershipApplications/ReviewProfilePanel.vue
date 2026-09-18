<script setup>
defineProps({
    application: { type: Object, required: true },
    farmer: { type: Object, required: true },
});
</script>

<template>
    <section class="rounded-lg border border-[#dbe2de] bg-white">
        <div class="border-b border-[#e4ebe7] bg-[#f6f8f7] px-4 py-2.5">
            <p class="text-[0.62rem] font-semibold uppercase tracking-[0.08em] text-[#65736d]">Linked Farmer</p>
        </div>

        <div class="grid gap-2.5 p-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Membership Status</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ farmer.membershipStatusLabel || 'Not set' }}</p>
            </div>
            <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Submitted</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ application.submittedAt || (application.status?.value === 'approved' ? 'Submission date not recorded' : 'Not submitted') }}</p>
            </div>
            <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Source</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ application.sourceLabel }}</p>
            </div>
            <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Birth Date</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ farmer.birthDate || 'Not set' }}</p>
            </div>
            <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Mobile Number</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ farmer.mobileNumber || 'Not set' }}</p>
            </div>
            <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Location</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ farmer.barangay || 'No barangay' }}</p>
                <p class="mt-1 text-xs text-[#78857f]">{{ farmer.association || 'No association' }}</p>
            </div>
            <div class="rounded-md border border-[#e3eae6] bg-[#f7faf8] p-3 md:col-span-2 xl:col-span-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Address</p>
                <p class="mt-2 text-sm leading-6 text-[#5f6c67]">{{ farmer.address || 'No address recorded.' }}</p>
            </div>
        </div>
    </section>
</template>
