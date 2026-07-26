<script setup>
defineProps({
    application: { type: Object, required: true },
    flow: { type: Object, required: true },
});

function statusBadge(value) {
    if (value === 'approved') return 'bg-[#eef7e3] text-[#416918]';
    if (value === 'rejected') return 'bg-[#ffdad6] text-[#93000a]';
    return 'bg-[#fff3dc] text-[#a86100]';
}
</script>

<template>
    <section class="relative overflow-hidden rounded-[24px] bg-[#1b4d3e] px-5 py-6 text-white shadow-[0_16px_38px_rgba(15,23,42,0.08)] lg:px-7">
        <div class="pointer-events-none absolute inset-0 opacity-10">
            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
                <path d="M0 100 C 20 0 55 0 100 100 Z" fill="currentColor"></path>
            </svg>
        </div>

        <div class="relative z-10 grid gap-5 lg:grid-cols-[minmax(0,1.2fr)_auto] lg:items-end">
            <div>
                <p class="text-[0.64rem] font-black uppercase tracking-[0.24em] text-[#b9dccc]">Step Processing</p>
                <h1 class="mt-2.5 text-3xl font-black tracking-[-0.04em] sm:text-[3.2rem]">{{ application.applicationNo }}</h1>
            </div>

            <div class="flex flex-col items-start gap-3 lg:items-end">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/12 px-3.5 py-1.5 text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#d8ebe3]">
                    Step {{ flow.step }} of 4
                </div>
                <div class="inline-flex rounded-full px-3.5 py-1.5 text-[0.68rem] font-black uppercase tracking-[0.14em]" :class="statusBadge(application.status.value)">
                    {{ application.status.label }}
                </div>
            </div>
        </div>
    </section>
</template>
