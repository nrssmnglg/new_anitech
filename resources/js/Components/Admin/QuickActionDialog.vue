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
    <div v-if="open" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#0f172a]/45 px-4">
        <div class="w-full max-w-xl rounded-[28px] bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.22)]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Quick Action</p>
                    <h3 class="mt-2 text-xl font-bold text-[#14202c]">{{ title }}</h3>
                </div>
                <button type="button" class="rounded-2xl border border-[#dbe2de] px-3 py-2 text-sm font-bold text-[#60706a]" @click="emit('close')">
                    Close
                </button>
            </div>

            <label class="mt-5 block space-y-2">
                <span class="text-sm font-bold text-[#33424c]">{{ noteLabel }}</span>
                <textarea
                    :value="note"
                    rows="5"
                    class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none"
                    :placeholder="placeholder"
                    @input="emit('update:note', $event.target.value)"
                />
            </label>
            <p v-if="error" class="mt-2 text-sm text-[#cf3657]">{{ error }}</p>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" class="rounded-2xl border border-[#dbe2de] px-4 py-3 text-sm font-bold text-[#5f6b66]" @click="emit('close')">
                    Cancel
                </button>
                <button type="button" class="rounded-2xl bg-[#003629] px-5 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]" :disabled="processing" @click="emit('submit')">
                    {{ submitLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
