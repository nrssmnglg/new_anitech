<script setup>
defineProps({
    eyebrow: { type: String, required: true },
    title: { type: String, required: true },
});
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-[#dbe2de] bg-white">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-[#e4ebe7] bg-[#f6f8f7] px-4 py-2.5">
            <p v-if="eyebrow" class="text-[0.62rem] font-semibold uppercase tracking-[0.08em] text-[#65736d]">{{ eyebrow }}</p>
            <h2 v-if="title" class="text-xs font-medium text-[#415049]">{{ title }}</h2>
        </div>
        <div class="p-4">
            <slot />
        </div>
    </section>
</template>
