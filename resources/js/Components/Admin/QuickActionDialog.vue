<script setup>
const props = defineProps({
    open: { type: Boolean, required: true },
    title: { type: String, required: true },
    noteLabel: { type: String, required: true },
    submitLabel: { type: String, required: true },
    note: { type: String, required: true },
    placeholder: { type: String, default: '' },
    processing: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const emit = defineEmits(['close', 'submit', 'update:note']);
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#0f172a]/45 px-4 py-6" @click.self="emit('close')">
        <div class="w-full max-w-lg rounded-lg border border-[#dbe2de] bg-white p-4 shadow-[0_20px_55px_rgba(15,23,42,0.18)]">
            <div class="flex items-center justify-between gap-3 border-b border-[#edf2ef] pb-3">
                <div>
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.1em] text-[#7a8781]">Record action</p>
                    <h3 class="mt-0.5 text-sm font-semibold text-[#14202c]">{{ title }}</h3>
                </div>
                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#dbe2de] text-lg leading-none text-[#60706a] transition hover:bg-[#f5f8f6]" aria-label="Close dialog" @click="emit('close')">
                    &times;
                </button>
            </div>

            <label class="mt-3 block space-y-1">
                <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#33424c]">{{ noteLabel }}</span>
                <textarea
                    :value="note"
                    rows="4"
                    class="w-full resize-none rounded-md border border-[#d7e0db] bg-[#f8faf9] px-3 py-2 text-xs leading-5 text-[#1a2420] outline-none focus:border-[#17634d]"
                    :placeholder="placeholder"
                    @input="emit('update:note', $event.target.value)"
                />
            </label>
            <p v-if="error" class="mt-1 text-xs text-[#cf3657]">{{ error }}</p>

            <div class="mt-3 flex flex-wrap justify-end gap-2 border-t border-[#edf2ef] pt-3">
                <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-[#dbe2de] px-3 text-xs font-semibold text-[#5f6b66]" @click="emit('close')">
                    Cancel
                </button>
                <button type="button" class="inline-flex h-9 items-center justify-center rounded-md bg-[#003629] px-4 text-xs font-semibold text-white transition hover:bg-[#0d4637] disabled:opacity-60" :disabled="processing" @click="emit('submit')">
                    {{ submitLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
