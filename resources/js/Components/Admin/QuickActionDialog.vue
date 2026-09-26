<script setup>
defineProps({
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
    <div v-if="open" class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs" @click.self="emit('close')">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-2xl transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-[#dde4de] bg-[#f9fbfa] px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                            <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                            <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Application Action</span>
                        <h3 class="text-sm font-bold text-[#0f172a]">{{ title }}</h3>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#dde4de] text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                    @click="emit('close')"
                >
                    &times;
                </button>
            </div>

            <!-- Body -->
            <div class="p-5">
                <label class="block space-y-1.5">
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">{{ noteLabel }}</span>
                    <textarea
                        :value="note"
                        rows="4"
                        class="w-full resize-none rounded-xl border border-[#dde4de] bg-[#f9fbfa] p-3 text-xs leading-5 text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                        :placeholder="placeholder"
                        @input="emit('update:note', $event.target.value)"
                    />
                </label>
                <p v-if="error" class="mt-1.5 text-xs font-semibold text-rose-600">{{ error }}</p>

                <!-- Footer buttons -->
                <div class="mt-5 flex items-center justify-end gap-2 border-t border-[#edf2ee] pt-4">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-xl border border-[#dde4de] bg-white px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-xl bg-[#003629] px-4 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a] disabled:opacity-60"
                        :disabled="processing"
                        @click="emit('submit')"
                    >
                        {{ processing ? 'Processing...' : submitLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
