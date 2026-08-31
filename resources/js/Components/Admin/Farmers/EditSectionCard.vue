<script setup>
defineProps({
    eyebrow: { type: String, required: true },
    title: { type: String, required: true },
});
</script>

<template>
    <section class="overflow-hidden rounded-[12px] border border-[#cbd4cf] bg-white">
        <div class="border-b border-[#e4ebe7] px-4 py-2.5">
            <p class="text-[0.68rem] font-black uppercase tracking-[0.08em] text-[#7a8781]">{{ eyebrow }}</p>
        </div>
        <div class="px-4 py-3">
            <slot />
        </div>
    </section>
</template>
