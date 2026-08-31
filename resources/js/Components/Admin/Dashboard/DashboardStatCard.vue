<script setup>
defineProps({
    accent: {
        type: String,
        default: 'bg-emerald-600',
    },
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [Number, String],
        required: true,
    },
    tone: {
        type: String,
        default: 'from-emerald-500/15 to-white',
    },
});
</script>

<template>
    <article class="relative overflow-hidden rounded-lg border border-stone-200/80 bg-gradient-to-br p-3.5" :class="tone">
        <div class="absolute inset-x-0 top-0 h-1" :class="accent"></div>
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[0.7rem] font-bold uppercase tracking-[0.3em] text-stone-500">{{ label }}</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-stone-950">{{ value }}</p>
                <div class="mt-2 h-1 w-12 rounded-full bg-stone-200/80">
                    <div class="h-1.5 w-8 rounded-full" :class="accent"></div>
                </div>
            </div>
            <span class="mt-1 h-3.5 w-3.5 rounded-full shadow-sm" :class="accent"></span>
        </div>
    </article>
</template>
