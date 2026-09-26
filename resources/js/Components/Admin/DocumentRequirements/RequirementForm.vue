<script setup>
import { computed } from 'vue';

const NEW_DOCUMENT_TYPE_VALUE = '__new__';

const props = defineProps({
    form: { type: Object, required: true },
    transactionTypeOptions: { type: Array, required: true },
    documentTypeOptions: { type: Array, required: true },
    submitLabel: { type: String, required: true },
    heading: { type: String, required: true },
    description: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

defineEmits(['submit', 'cancel']);

const isCreatingDocumentType = computed(() => props.form.document_type_id === NEW_DOCUMENT_TYPE_VALUE);
</script>

<template>
    <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd]">
                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#0f6b45]" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-[#0f172a]">{{ heading }}</h2>
                    <p v-if="description" class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ description }}</p>
                </div>
            </div>
        </div>

        <form class="space-y-4 p-5" @submit.prevent="$emit('submit')">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1.5">
                    <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Transaction Type</span>
                    <select
                        v-model="form.transaction_type"
                        :disabled="disabled"
                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <option v-for="option in transactionTypeOptions.filter((option) => option.value !== 'All')" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.transaction_type" class="text-xs font-medium text-rose-600">{{ form.errors.transaction_type }}</p>
                </label>

                <label class="space-y-1.5">
                    <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Document Type</span>
                    <select
                        v-model="form.document_type_id"
                        :disabled="disabled"
                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <option value="">Select document type</option>
                        <option :value="NEW_DOCUMENT_TYPE_VALUE">+ Add new document type</option>
                        <option v-for="option in documentTypeOptions" :key="option.id" :value="String(option.id)">
                            {{ option.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.document_type_id" class="text-xs font-medium text-rose-600">{{ form.errors.document_type_id }}</p>
                </label>
            </div>

            <div v-if="isCreatingDocumentType" class="rounded-xl border border-[#d1fae5] bg-[#ecfdf5] p-3.5">
                <label class="space-y-1.5">
                    <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#065f46]">New Document Type Name</span>
                    <input
                        v-model="form.new_document_type_name"
                        :disabled="disabled"
                        type="text"
                        placeholder="e.g. Barangay Clearance, Death Certificate, Valid ID"
                        class="h-9 w-full rounded-lg border border-[#a7f3d0] bg-white px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                    <p v-if="form.errors.new_document_type_name" class="text-xs font-medium text-rose-600">{{ form.errors.new_document_type_name }}</p>
                </label>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#e2eae4] bg-[#f9fbfa] p-3.5 transition-all duration-150 hover:border-[#cbd8cf] hover:bg-[#f4f7f5]">
                    <input
                        v-model="form.is_required"
                        :disabled="disabled"
                        type="checkbox"
                        class="h-4 w-4 rounded border-[#0f5b46]/30 text-[#0f5b46] focus:ring-[#0f5b46]/20 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                    <div>
                        <p class="text-xs font-bold text-[#0f172a]">Required document</p>
                        <p class="text-[0.68rem] text-[#64748b]">Must be submitted to complete this transaction.</p>
                    </div>
                </label>

                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#e2eae4] bg-[#f9fbfa] p-3.5 transition-all duration-150 hover:border-[#cbd8cf] hover:bg-[#f4f7f5]">
                    <input
                        v-model="form.is_active"
                        :disabled="disabled"
                        type="checkbox"
                        class="h-4 w-4 rounded border-[#0f5b46]/30 text-[#0f5b46] focus:ring-[#0f5b46]/20 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                    <div>
                        <p class="text-xs font-bold text-[#0f172a]">Active in checklist</p>
                        <p class="text-[0.68rem] text-[#64748b]">Visible to staff and members during document filing.</p>
                    </div>
                </label>
            </div>

            <div class="flex gap-2 border-t border-[#edf2ee] pt-4 sm:justify-end">
                <button
                    type="button"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-4 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="disabled"
                    @click="$emit('cancel')"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="disabled"
                >
                    {{ form.processing ? 'Saving...' : submitLabel }}
                </button>
            </div>
        </form>
    </section>
</template>
