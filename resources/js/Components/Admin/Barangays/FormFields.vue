<script setup>
defineProps({
    form: { type: Object, required: true },
    statusOptions: { type: Object, required: true },
});
</script>

<template>
    <div class="grid gap-3 md:grid-cols-2">
        <label class="space-y-1">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Barangay Name</span>
            <input v-model="form.name" type="text" class="h-9 w-full rounded-md border border-outline-variant bg-white px-3 text-xs text-on-surface outline-none transition focus:border-primary focus:ring-1 focus:ring-primary/20">
            <p v-if="form.errors.name" class="text-xs font-medium text-rose-600">{{ form.errors.name }}</p>
        </label>

        <label class="space-y-1">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Barangay Code</span>
            <input :value="form.code || 'Generated when saved'" type="text" readonly class="h-9 w-full cursor-not-allowed rounded-md border border-outline-variant bg-[#f2f5f3] px-3 text-xs text-[#68756f] outline-none">
            <p v-if="form.errors.code" class="text-xs font-medium text-rose-600">{{ form.errors.code }}</p>
        </label>

        <label class="space-y-1 md:col-span-2">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Status</span>
            <select v-model="form.status" class="h-9 w-full rounded-md border border-outline-variant bg-white px-3 text-xs text-on-surface outline-none transition focus:border-primary focus:ring-1 focus:ring-primary/20">
                <option v-for="(label, value) in statusOptions" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="form.errors.status" class="text-xs font-medium text-rose-600">{{ form.errors.status }}</p>
        </label>
    </div>
</template>
