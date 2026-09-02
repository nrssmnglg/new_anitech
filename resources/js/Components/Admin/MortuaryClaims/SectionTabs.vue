<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    activeSection: { type: String, required: true },
    urls: { type: Object, required: true },
});
</script>

<template>
    <section class="border-b border-[#dfe5e1]">
        <div class="flex items-center gap-5">
            <Link
                :href="urls.queue"
                class="inline-flex border-b-2 px-1 py-2 text-xs font-semibold transition"
                :class="activeSection === 'queue' ? 'border-[#003629] text-[#003629]' : 'border-transparent text-[#66756f] hover:text-[#1a2420]'"
            >
                Claim Queue
            </Link>
            <Link
                :href="urls.records"
                class="inline-flex border-b-2 px-1 py-2 text-xs font-semibold transition"
                :class="activeSection === 'records' ? 'border-[#003629] text-[#003629]' : 'border-transparent text-[#66756f] hover:text-[#1a2420]'"
            >
                Mortuary Records
            </Link>
        </div>
    </section>
</template>
