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
        class="rounded-[24px] border shadow-[0_14px_32px_rgba(15,23,42,0.05)]"
        :class="warnings.length ? 'border-[#f1d3b6] bg-white' : 'border-[#dbe2de] bg-white'"
    >
        <div class="border-b px-5 py-4" :class="warnings.length ? 'border-[#f3e3d4]' : 'border-[#e4ebe7]'">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Approval Blockers</p>
            <h2 class="mt-1 text-lg font-bold text-[#1a2420]">{{ title }}</h2>
            <p class="mt-2 text-sm text-[#6c7772]">{{ description }}</p>
        </div>

        <div class="space-y-3 px-5 py-5">
            <button
                v-for="warning in warnings"
                :key="warning.key"
                type="button"
                class="w-full rounded-[20px] border px-4 py-4 text-left transition"
                :class="[toneClasses(warning.type), warningTarget(warning.key) ? 'cursor-pointer hover:brightness-[0.98] focus:outline-none focus:ring-2 focus:ring-[#003629]/20' : 'cursor-default']"
                :disabled="!warningTarget(warning.key)"
                @click="jumpToWarning(warning.key)"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em]">{{ warning.label }}</p>
                        <p class="mt-2 text-sm leading-6">{{ warning.message }}</p>
                    </div>
                    <span class="rounded-full bg-white/80 px-2.5 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em]">
                        {{ warningTarget(warning.key) ? 'Open' : (warning.type === 'danger' ? 'Blocker' : 'Review') }}
                    </span>
                </div>
            </button>

            <div v-if="warnings.length === 0" class="rounded-[20px] border border-emerald-200 bg-emerald-50 px-4 py-5 text-sm text-emerald-800">
                No immediate blockers detected for approval.
            </div>
        </div>
    </section>
</template>
