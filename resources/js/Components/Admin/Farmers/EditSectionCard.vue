<script setup>
defineProps({
    eyebrow: { type: String, required: true },
    title: { type: String, required: true },
});
</script>

<template>
    <section class="overflow-hidden rounded-[24px] border border-[#dbe2de] bg-white shadow-[0_14px_32px_rgba(15,23,42,0.05)]">
        <div class="border-b border-[#e4ebe7] bg-[#f4f7f5] px-5 py-4">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">{{ eyebrow }}</p>
            <h2 class="mt-1 text-lg font-bold text-[#1a2420]">{{ title }}</h2>
        </div>
        <div class="px-5 py-5">
            <slot />
        </div>
    </section>
</template>
