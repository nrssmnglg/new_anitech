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
    <section class="rounded-[10px] bg-[#00513f] px-5 py-4 text-white sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-[#b9dccc]">Membership Application</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ application.applicationNo }}</h1>
            </div>

            <div class="flex items-center gap-2">
                <div class="inline-flex rounded-md bg-white/10 px-2.5 py-1 text-[0.65rem] font-medium text-[#d8ebe3]">
                    Step {{ flow.step }} of 4
                </div>
                <div class="inline-flex rounded-md px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.05em]" :class="statusBadge(application.status.value)">
                    {{ application.status.label }}
                </div>
            </div>
        </div>
    </section>
</template>
