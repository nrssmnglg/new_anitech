<script setup>
defineProps({
    reapplyApplication: { type: Object, required: true },
});
</script>

<template>
    <section class="rounded-[24px] border border-[#ffd7b8] bg-[linear-gradient(135deg,_#fff8ec_0%,_#fff2d8_100%)] p-6 shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-[0.72rem] font-black uppercase tracking-[0.24em] text-[#a86100]">Re-application Source</p>
                <h2 class="mt-2 text-xl font-black text-[#191c1c]">Replacement membership record</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-[#5f6c67]">
                    The rejected application stays in history. This form creates a new application for the same farmer record.
                </p>
            </div>
            <div class="rounded-2xl border border-white/70 bg-white/70 px-4 py-3 text-sm text-[#5f6c67]">
                <p class="font-bold text-[#191c1c]">{{ reapplyApplication.applicationNo }}</p>
                <p class="mt-1">{{ reapplyApplication.farmer?.fullName || 'Unknown farmer' }}</p>
            </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-white/80 bg-white/75 p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Rejected Reason</p>
                <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ reapplyApplication.rejectionReasonLabel || 'Not classified' }}</p>
            </div>
            <div class="rounded-2xl border border-white/80 bg-white/75 p-4 md:col-span-2">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#7a8781]">Recorded Details</p>
                <p class="mt-2 text-sm leading-6 text-[#5f6c67]">{{ reapplyApplication.rejectionDetails || 'No details recorded.' }}</p>
            </div>
        </div>
    </section>
</template>
