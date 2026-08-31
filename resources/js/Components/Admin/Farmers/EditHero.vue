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
    <section class="rounded-[12px] border border-[#cbd4cf] bg-white p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#eef3f0] text-base font-black text-[#0f4a3d]">
                    {{ initials(farmer.fullName) }}
                </div>
                <div>
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.1em] text-[#7a8781]">Record Editing</p>
                    <h1 class="mt-1 text-xl font-black tracking-[-0.03em] text-[#173e34] sm:text-2xl">{{ farmer.fullName }}</h1>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row lg:min-w-[320px]">
                <div class="rounded-md border border-[#e5ece8] bg-[#f8faf9] px-3 py-2">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Farmer Code</p>
                    <p class="mt-1 text-sm font-semibold text-[#173e34]">{{ farmer.farmerCode }}</p>
                </div>
                <Link
                    :href="showUrl"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-[#d7e0db] bg-white text-[#173e34] transition hover:bg-[#f4f7f5]"
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
