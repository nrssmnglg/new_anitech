<script setup>
defineProps({
    form: { type: Object, required: true },
    barangays: { type: Array, required: true },
    statusOptions: { type: Object, required: true },
});
</script>

<template>
    <div class="grid gap-5 md:grid-cols-2">
        <label class="space-y-2">
            <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Barangay</span>
            <select v-model="form.barangay_id" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                <option value="">Select barangay</option>
                <option v-for="barangay in barangays" :key="barangay.id" :value="barangay.id">
                    {{ barangay.name }}{{ barangay.code ? ` (${barangay.code})` : '' }}
                </option>
            </select>
            <p v-if="form.errors.barangay_id" class="text-sm font-medium text-error">{{ form.errors.barangay_id }}</p>
        </label>

        <label class="space-y-2">
            <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Status</span>
            <select v-model="form.status" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                <option v-for="(label, value) in statusOptions" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="form.errors.status" class="text-sm font-medium text-error">{{ form.errors.status }}</p>
        </label>

        <label class="space-y-2 md:col-span-2">
            <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Association Name</span>
            <input v-model="form.name" type="text" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
            <p v-if="form.errors.name" class="text-sm font-medium text-error">{{ form.errors.name }}</p>
        </label>

        <label class="space-y-2 md:col-span-2">
            <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Association Code</span>
            <input v-model="form.code" type="text" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
            <p v-if="form.errors.code" class="text-sm font-medium text-error">{{ form.errors.code }}</p>
        </label>

        <label class="space-y-2 md:col-span-2">
            <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Association President</span>
            <input v-model="form.president_name" type="text" placeholder="Name of association president" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
            <p v-if="form.errors.president_name" class="text-sm font-medium text-error">{{ form.errors.president_name }}</p>
        </label>
    </div>
</template>
