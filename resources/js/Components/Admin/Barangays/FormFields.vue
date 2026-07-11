<script setup>
defineProps({
    form: { type: Object, required: true },
    statusOptions: { type: Object, required: true },
});
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2">
        <label class="space-y-2">
            <span class="ml-2 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Barangay Name</span>
            <input v-model="form.name" type="text" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
            <p v-if="form.errors.name" class="text-xs font-medium text-rose-600">{{ form.errors.name }}</p>
        </label>

        <label class="space-y-2">
            <span class="ml-2 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Barangay Code</span>
            <input v-model="form.code" type="text" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
            <p v-if="form.errors.code" class="text-xs font-medium text-rose-600">{{ form.errors.code }}</p>
        </label>

        <label class="space-y-2 md:col-span-2">
            <span class="ml-2 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Status</span>
            <select v-model="form.status" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                <option v-for="(label, value) in statusOptions" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="form.errors.status" class="text-xs font-medium text-rose-600">{{ form.errors.status }}</p>
        </label>
    </div>
</template>
