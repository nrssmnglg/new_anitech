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
    <section class="rounded-lg border border-[#dde4de] bg-white">
        <div class="flex items-center justify-between border-b border-[#edf2ee] px-4 py-3">
            <div>
                <h2 class="text-sm font-semibold text-[#0f172a]">{{ heading }}</h2>
                <p v-if="description" class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ description }}</p>
            </div>
        </div>

        <form class="space-y-3 p-4" @submit.prevent="$emit('submit')">
            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1">
                    <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Transaction Type</span>
                    <select v-model="form.transaction_type" :disabled="disabled" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] disabled:cursor-not-allowed disabled:opacity-60">
                        <option v-for="option in transactionTypeOptions.filter((option) => option.value !== 'All')" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.transaction_type" class="text-xs font-medium text-error">{{ form.errors.transaction_type }}</p>
                </label>

                <label class="space-y-1">
                    <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Document Type</span>
                    <select v-model="form.document_type_id" :disabled="disabled" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] disabled:cursor-not-allowed disabled:opacity-60">
                        <option value="">Select document type</option>
                        <option :value="NEW_DOCUMENT_TYPE_VALUE">+ Add new document type</option>
                        <option v-for="option in documentTypeOptions" :key="option.id" :value="String(option.id)">
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.document_type_id" class="text-xs font-medium text-error">{{ form.errors.document_type_id }}</p>
                </label>
            </div>

            <div v-if="isCreatingDocumentType">
                <label class="space-y-1">
                    <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">New Document Name</span>
                    <input
                        v-model="form.new_document_type_name"
                        :disabled="disabled"
                        type="text"
                        placeholder="e.g. Residency Certificate"
                        class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                    <p v-if="form.errors.new_document_type_name" class="text-xs font-medium text-error">{{ form.errors.new_document_type_name }}</p>
                </label>
            </div>

            <div class="grid gap-2 md:grid-cols-2">
                <label class="flex items-center gap-2.5 rounded-md border border-[#e9efeb] bg-[#fbfcfb] px-3 py-2.5">
                    <input v-model="form.is_required" :disabled="disabled" type="checkbox" class="h-4 w-4 rounded border-[#0f5b46]/25 text-[#0f5b46] focus:ring-[#0f5b46]/20 disabled:cursor-not-allowed disabled:opacity-60">
                    <div>
                        <p class="text-xs font-semibold text-[#0f172a]">Required document</p>
                        <p class="text-[0.65rem] text-[#64748b]">Include this document in the required checklist.</p>
                    </div>
                </label>

                <label class="flex items-center gap-2.5 rounded-md border border-[#e9efeb] bg-[#fbfcfb] px-3 py-2.5">
                    <input v-model="form.is_active" :disabled="disabled" type="checkbox" class="h-4 w-4 rounded border-[#0f5b46]/25 text-[#0f5b46] focus:ring-[#0f5b46]/20 disabled:cursor-not-allowed disabled:opacity-60">
                    <div>
                        <p class="text-xs font-semibold text-[#0f172a]">Active in checklist</p>
                        <p class="text-[0.65rem] text-[#64748b]">Use this requirement without deleting it.</p>
                    </div>
                </label>
            </div>

            <div class="flex gap-2 border-t border-[#edf2ee] pt-3 sm:justify-end">
                    <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#64748b] transition hover:bg-[#f4f7f5] disabled:cursor-not-allowed disabled:opacity-60" :disabled="disabled" @click="$emit('cancel')">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#014d3c] px-4 text-xs font-semibold text-white transition hover:bg-[#01362a] disabled:cursor-not-allowed disabled:opacity-60" :disabled="disabled">
                        {{ form.processing ? 'Saving...' : submitLabel }}
                    </button>
            </div>
        </form>
    </section>
</template>
