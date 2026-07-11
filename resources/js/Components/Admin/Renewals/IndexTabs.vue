<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    activeSection: { type: String, required: true },
    urls: { type: Object, required: true },
});
</script>

<template>
    <section class="flex justify-center">
        <div class="inline-flex flex-wrap items-center gap-2 rounded-full border border-[#dfe5e1] bg-[#eceeed] p-1.5 shadow-[inset_0_1px_2px_rgba(15,23,42,0.05)]">
        <Link
            :href="urls.queue"
            class="inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-extrabold transition"
            :class="activeSection === 'queue' ? 'bg-white text-[#003629] shadow-sm' : 'text-[#66756f] hover:text-[#1a2420]'"
        >
            Active Queue
        </Link>
        <Link
            :href="urls.records"
            class="inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-extrabold transition"
            :class="activeSection === 'records' ? 'bg-white text-[#003629] shadow-sm' : 'text-[#66756f] hover:text-[#1a2420]'"
        >
            Renewal Records
        </Link>
        </div>
    </section>
</template>
