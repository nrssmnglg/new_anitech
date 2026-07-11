<script setup>
defineProps({
    displayFarmerCode: { type: String, required: true },
    effectiveStatusLabel: { type: String, required: true },
});
</script>

<template>
    <section class="relative overflow-hidden rounded-[28px] bg-[radial-gradient(circle_at_top_left,_#124b3c_0%,_#003629_55%,_#416918_100%)] p-8 text-white shadow-[0_22px_50px_rgba(15,23,42,0.12)]">
        <div class="absolute -bottom-24 -right-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl space-y-3">
                <p class="text-[0.72rem] font-black uppercase tracking-[0.28em] text-[#cde0d7]">Walk-In Intake</p>
                <h1 class="text-4xl font-black tracking-[-0.04em] sm:text-5xl">New Membership Application</h1>
                <p class="max-w-2xl text-[0.98rem] leading-8 text-white/80">
                    Create the farmer application record. Ensure all intake details are complete before the checklist, payment, and approval steps.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 backdrop-blur">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#b9dccc]">Farmer Code</p>
                    <p class="mt-2 text-xl font-black">{{ displayFarmerCode }}</p>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 backdrop-blur">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#b9dccc]">Farmer Status</p>
                    <span class="mt-2 inline-flex rounded-full bg-[#c0f190] px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#0e2000]">
                        {{ effectiveStatusLabel }}
                    </span>
                </div>
            </div>
        </div>
    </section>
</template>
