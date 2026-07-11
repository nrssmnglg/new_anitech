<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    farmer: { type: Object, required: true },
    showUrl: { type: String, required: true },
});

function initials(name) {
    return String(name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || '?';
}
</script>

<template>
    <section class="rounded-[24px] border border-[#dfe7e2] bg-white p-6 shadow-[0_14px_40px_rgba(15,23,42,0.06)]">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-20 w-20 items-center justify-center rounded-[20px] bg-[#eef3f0] text-2xl font-black text-[#0f4a3d]">
                    {{ initials(farmer.fullName) }}
                </div>
                <div>
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.28em] text-[#7a8781]">Record Editing</p>
                    <h1 class="mt-2 text-3xl font-black tracking-[-0.03em] text-[#173e34] sm:text-4xl">{{ farmer.fullName }}</h1>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row lg:min-w-[320px]">
                <div class="rounded-2xl border border-[#e5ece8] bg-[#f8faf9] px-4 py-4">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Farmer Code</p>
                    <p class="mt-2 text-lg font-black text-[#173e34]">{{ farmer.farmerCode }}</p>
                </div>
                <Link
                    :href="showUrl"
                    class="inline-flex h-[72px] w-[72px] items-center justify-center rounded-2xl border border-[#d7e0db] bg-white text-[#173e34] transition hover:bg-[#f4f7f5]"
                    title="View Record"
                >
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </Link>
            </div>
        </div>
    </section>
</template>
