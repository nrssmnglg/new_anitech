<script setup>
defineProps({
    eyebrow: { type: String, required: true },
    title: { type: String, required: true },
});
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm transition-all duration-200">
        <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-[#014d3c]"></span>
                <span class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">{{ eyebrow }}</span>
                <span class="text-[#cbd5e1]">·</span>
                <h2 class="text-xs font-bold text-[#0f172a]">{{ title }}</h2>
            </div>
        </div>
        <div class="p-5">
            <slot />
        </div>
    </section>
</template>
