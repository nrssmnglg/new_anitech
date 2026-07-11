<script setup>
import { computed } from 'vue';

const NEW_DOCUMENT_TYPE_VALUE = '__new__';

const props = defineProps({
    form: { type: Object, required: true },
    transactionTypeOptions: { type: Array, required: true },
    documentTypeOptions: { type: Array, required: true },
    submitLabel: { type: String, required: true },
    heading: { type: String, required: true },
    description: { type: String, required: true },
    disabled: { type: Boolean, default: false },
});

defineEmits(['submit', 'cancel']);

const isCreatingDocumentType = computed(() => props.form.document_type_id === NEW_DOCUMENT_TYPE_VALUE);
</script>

<template>
    <section class="rounded-[1.35rem] border border-[#dde4de] bg-white shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
        <div class="border-b border-[#edf2ee] bg-[#fbfcfb] px-6 py-5">
            <h2 class="text-lg font-black text-[#0f172a]">{{ heading }}</h2>
            <p class="mt-2 text-sm text-[#64748b]">{{ description }}</p>
        </div>

        <form class="space-y-6 p-6" @submit.prevent="$emit('submit')">
            <div class="grid gap-5 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Transaction Type</span>
                    <select v-model="form.transaction_type" :disabled="disabled" class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c] disabled:cursor-not-allowed disabled:opacity-60">
                        <option v-for="option in transactionTypeOptions.filter((option) => option.value !== 'All')" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.transaction_type" class="text-sm font-medium text-error">{{ form.errors.transaction_type }}</p>
                </label>

                <label class="space-y-2">
                    <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Document Type</span>
                    <select v-model="form.document_type_id" :disabled="disabled" class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c] disabled:cursor-not-allowed disabled:opacity-60">
                        <option value="">Select document type</option>
                        <option :value="NEW_DOCUMENT_TYPE_VALUE">+ Add new document type</option>
                        <option v-for="option in documentTypeOptions" :key="option.id" :value="String(option.id)">
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.document_type_id" class="text-sm font-medium text-error">{{ form.errors.document_type_id }}</p>
                </label>
            </div>

            <div v-if="isCreatingDocumentType" class="grid gap-5">
                <label class="space-y-2">
                    <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">New Document Name</span>
                    <input
                        v-model="form.new_document_type_name"
                        :disabled="disabled"
                        type="text"
                        placeholder="e.g. Residency Certificate"
                        class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                    <p v-if="form.errors.new_document_type_name" class="text-sm font-medium text-error">{{ form.errors.new_document_type_name }}</p>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="flex items-center gap-3 rounded-2xl border border-[#e9efeb] bg-[#fbfcfb] px-4 py-4">
                    <input v-model="form.is_required" :disabled="disabled" type="checkbox" class="h-4 w-4 rounded border-[#0f5b46]/25 text-[#0f5b46] focus:ring-[#0f5b46]/20 disabled:cursor-not-allowed disabled:opacity-60">
                    <div>
                        <p class="text-sm font-semibold text-[#0f172a]">Required document</p>
                        <p class="text-xs text-[#64748b]">Uncheck this if the document is optional for the selected transaction.</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 rounded-2xl border border-[#e9efeb] bg-[#fbfcfb] px-4 py-4">
                    <input v-model="form.is_active" :disabled="disabled" type="checkbox" class="h-4 w-4 rounded border-[#0f5b46]/25 text-[#0f5b46] focus:ring-[#0f5b46]/20 disabled:cursor-not-allowed disabled:opacity-60">
                    <div>
                        <p class="text-sm font-semibold text-[#0f172a]">Active in checklist</p>
                        <p class="text-xs text-[#64748b]">Only active and required records are used by the membership and renewal flows.</p>
                    </div>
                </label>
            </div>

            <div class="flex flex-col gap-3 rounded-2xl border border-[#edf2ee] bg-[#fbfcfb] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex gap-3">
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-[#dbe3dd] bg-white px-5 py-3 text-sm font-semibold text-[#64748b] transition hover:bg-[#f4f7f5] disabled:cursor-not-allowed disabled:opacity-60" :disabled="disabled" @click="$emit('cancel')">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#01362a] disabled:cursor-not-allowed disabled:opacity-60" :disabled="disabled">
                        {{ form.processing ? 'Saving...' : submitLabel }}
                    </button>
                </div>
            </div>
        </form>
    </section>
</template>
