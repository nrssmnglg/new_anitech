<script setup>
defineProps({
    form: { type: Object, required: true },
    statusOptions: { type: Object, required: true },
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <label class="space-y-1.5">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Barangay Name</span>
            <input v-model="form.name" type="text" placeholder="Enter barangay name" class="h-10 w-full rounded-lg border border-[#d7e0db] bg-[#f9fbfa] px-3.5 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#14745a] focus:bg-white focus:ring-2 focus:ring-[#14745a]/10">
            <p v-if="form.errors.name" class="flex items-center gap-1 text-xs font-medium text-rose-600">
                <svg viewBox="0 0 16 16" class="h-3 w-3 shrink-0" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-4.75a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-1.5 0v4.5ZM8 11.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                {{ form.errors.name }}
            </p>
        </label>

        <label class="space-y-1.5">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Barangay Code</span>
            <input :value="form.code || 'Generated when saved'" type="text" readonly class="h-10 w-full cursor-not-allowed rounded-lg border border-[#e2e8f0] bg-[#f1f5f9] px-3.5 text-xs text-[#94a3b8] outline-none">
            <p v-if="form.errors.code" class="flex items-center gap-1 text-xs font-medium text-rose-600">
                <svg viewBox="0 0 16 16" class="h-3 w-3 shrink-0" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-4.75a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-1.5 0v4.5ZM8 11.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                {{ form.errors.code }}
            </p>
        </label>

        <label class="space-y-1.5 md:col-span-2">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Status</span>
            <select v-model="form.status" class="h-10 w-full rounded-lg border border-[#d7e0db] bg-[#f9fbfa] px-3.5 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#14745a] focus:bg-white focus:ring-2 focus:ring-[#14745a]/10">
                <option v-for="(label, value) in statusOptions" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="form.errors.status" class="flex items-center gap-1 text-xs font-medium text-rose-600">
                <svg viewBox="0 0 16 16" class="h-3 w-3 shrink-0" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-4.75a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-1.5 0v4.5ZM8 11.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                {{ form.errors.status }}
            </p>
        </label>
    </div>
</template>
