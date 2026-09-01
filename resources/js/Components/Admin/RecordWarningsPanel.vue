<script setup>
const props = defineProps({
    title: { type: String, default: 'Record Warnings' },
    description: { type: String, default: 'Review these blockers before approving the record.' },
    warnings: { type: Array, required: true },
    sectionTargets: { type: Object, default: () => ({}) },
});

function toneClasses(type) {
    if (type === 'danger') {
        return 'border-rose-200 bg-rose-50 text-rose-700';
    }

    if (type === 'warning') {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    return 'border-slate-200 bg-slate-50 text-slate-700';
}

function warningTarget(key) {
    return props.sectionTargets?.[key] || '';
}

function jumpToWarning(key) {
    const target = warningTarget(key);

    if (!target) {
        return;
    }

    const element = document.getElementById(target);

    if (!element) {
        return;
    }

    element.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
}
</script>

<template>
    <section
        class="rounded-lg border"
        :class="warnings.length ? 'border-[#f1d3b6] bg-white' : 'border-[#dbe2de] bg-white'"
    >
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b px-4 py-2.5" :class="warnings.length ? 'border-[#f3e3d4]' : 'border-[#e4ebe7]'">
            <p class="text-[0.62rem] font-semibold uppercase tracking-[0.08em] text-[#7a8781]">Approval Blockers</p>
            <h2 v-if="title" class="text-xs font-semibold text-[#1a2420]">{{ title }}</h2>
            <p v-if="description" class="text-[0.68rem] text-[#6c7772]">{{ description }}</p>
        </div>

        <div class="space-y-2 p-3">
            <button
                v-for="warning in warnings"
                :key="warning.key"
                type="button"
                class="w-full rounded-md border px-3 py-2.5 text-left transition"
                :class="[toneClasses(warning.type), warningTarget(warning.key) ? 'cursor-pointer hover:brightness-[0.98] focus:outline-none focus:ring-2 focus:ring-[#003629]/20' : 'cursor-default']"
                :disabled="!warningTarget(warning.key)"
                @click="jumpToWarning(warning.key)"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[0.62rem] font-semibold uppercase tracking-[0.06em]">{{ warning.label }}</p>
                        <p class="mt-1 text-xs leading-5">{{ warning.message }}</p>
                    </div>
                    <span class="rounded-md bg-white/80 px-2 py-1 text-[0.58rem] font-semibold uppercase tracking-[0.05em]">
                        {{ warningTarget(warning.key) ? 'Open' : (warning.type === 'danger' ? 'Blocker' : 'Review') }}
                    </span>
                </div>
            </button>

            <div v-if="warnings.length === 0" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs text-emerald-800">
                No immediate blockers detected for approval.
            </div>
        </div>
    </section>
</template>
