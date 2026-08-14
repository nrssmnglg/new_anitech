<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    notes: { type: Array, required: true },
    submitUrl: { type: String, required: true },
    title: { type: String, default: 'Internal Staff Notes' },
    compact: { type: Boolean, default: false },
});

const form = useForm({
    body: '',
});

function submitNote() {
    form.post(props.submitUrl, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <section :class="compact ? 'border-t border-[#dbe2de] pt-4' : 'rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)]'">
        <div :class="compact ? '' : 'border-b border-[#e4ebe7] pb-4'">
            <p :class="compact ? 'text-sm font-bold text-[#4f5e58]' : 'text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]'">
                {{ compact ? title : 'Internal Only' }}
            </p>
        </div>

        <form :class="compact ? 'mt-2 grid gap-2 md:grid-cols-[minmax(0,1fr)_auto] md:items-start' : 'mt-4 space-y-3'" @submit.prevent="submitNote">
            <div>
                <textarea
                    v-model="form.body"
                    :rows="compact ? 2 : 4"
                    placeholder="Add an internal staff note..."
                    :class="compact ? 'w-full rounded-xl border border-[#d7e0db] bg-[#f8faf9] px-3 py-2 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white' : 'w-full rounded-[20px] border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white'"
                />
                <p v-if="form.errors.body" class="mt-1 text-sm text-rose-600">{{ form.errors.body }}</p>
            </div>
            <div :class="compact ? '' : 'flex justify-end'">
                <button type="submit" :class="compact ? 'inline-flex w-full items-center justify-center rounded-xl bg-[#003629] px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-[#0d4637] md:w-auto' : 'inline-flex items-center justify-center rounded-2xl bg-[#003629] px-5 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]'" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Note' }}
                </button>
            </div>
        </form>

        <div v-if="notes.length > 0" :class="compact ? 'mt-3 space-y-2' : 'mt-5 space-y-3'">
            <article v-for="note in notes" :key="note.id" :class="compact ? 'rounded-xl border border-[#e4ebe7] bg-[#f8faf9] px-3 py-2.5' : 'rounded-[20px] border border-[#e4ebe7] bg-[#f8faf9] p-4'">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-bold text-[#14202c]">{{ note.createdBy }}</p>
                    <p class="text-xs text-[#6c7772]">{{ note.createdAt }}</p>
                </div>
                <p :class="compact ? 'mt-1.5 whitespace-pre-line text-sm leading-5 text-[#42515b]' : 'mt-3 whitespace-pre-line text-sm leading-6 text-[#42515b]'">{{ note.body }}</p>
            </article>
        </div>

        <div v-if="!compact && notes.length === 0" class="mt-5 rounded-[20px] border border-dashed border-[#dbe2de] bg-[#fbfdfc] px-4 py-8 text-center text-sm text-[#71808b]">
            No internal notes recorded yet.
        </div>
    </section>
</template>
