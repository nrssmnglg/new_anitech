<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    reference: { type: Object, required: true },
    heading: { type: String, required: true },
    description: { type: String, required: true },
    submitLabel: { type: String, required: true },
    cancelHref: { type: String, required: true },
    disabled: { type: Boolean, default: false },
});

const showBarangay = computed(() => props.form.audience_type === 'barangay');
const showMemberType = computed(() => props.form.audience_type === 'group');

const audienceCards = computed(() => ([
    {
        value: 'all',
        title: 'Global/All',
        description: 'Notify all registered farmers',
    },
    {
        value: 'barangay',
        title: 'Specific Barangay',
        description: 'Target localized areas',
    },
    {
        value: 'group',
        title: 'Member Group',
        description: 'Specific farmer classifications',
    },
]));

function updateAttachments(event) {
    props.form.attachments = Array.from(event.target.files || []);
}
</script>

<template>
    <section class="overflow-hidden rounded-[2rem] border border-[#dbe4de] bg-[#f8fbf9] shadow-[0_20px_45px_rgba(0,54,41,0.08)]">
        <div class="bg-[linear-gradient(135deg,#114739,#195642)] px-6 py-6 text-white">
            <a :href="cancelHref" class="inline-flex items-center gap-2 text-sm font-semibold text-[#cdeed4] transition hover:text-white">
                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2">
                    <path d="M15 18 9 12l6-6" />
                </svg>
                <span>Back to Advisories</span>
            </a>
            <div class="mt-5 max-w-3xl">
                <h1 class="text-3xl font-black tracking-[-0.04em] text-white sm:text-4xl">{{ heading }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/82">{{ description }}</p>
            </div>
        </div>

        <form class="space-y-6 p-5 sm:p-6" @submit.prevent="$emit('submit')">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="space-y-5">
                    <section class="rounded-[1.6rem] border border-[#dbe4de] bg-white p-5 shadow-[0_10px_25px_rgba(15,91,70,0.04)]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-[#edf4ef] text-[#537569]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2">
                                    <circle cx="12" cy="12" r="8" />
                                    <path d="M12 8v4l2.5 2.5" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-black text-[#335548]">Basic Information</h2>
                        </div>

                        <div class="mt-5 space-y-5">
                            <label class="block space-y-2">
                                <span class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#71808b]">Advisory Title</span>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    placeholder="e.g. Seasonal Irrigation Guidelines for Q3"
                                    class="w-full rounded-2xl border border-[#dce4de] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/10"
                                    :disabled="disabled"
                                >
                                <p v-if="form.errors.title" class="text-sm font-medium text-red-600">{{ form.errors.title }}</p>
                            </label>

                            <label class="block space-y-2">
                                <span class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#71808b]">Content</span>
                                <div class="overflow-hidden rounded-2xl border border-[#dce4de] bg-white">
                                    <div class="flex items-center gap-4 border-b border-[#edf2ee] bg-[#f7f8f7] px-4 py-3 text-xs font-bold text-[#6b7280]">
                                        <span>B</span>
                                        <span>I</span>
                                        <span>U</span>
                                        <span>Link</span>
                                    </div>
                                    <textarea
                                        v-model="form.content"
                                        rows="14"
                                        placeholder="Describe the advisory details in depth..."
                                        class="w-full resize-none border-0 bg-white px-4 py-4 text-sm text-[#0f172a] outline-none"
                                        :disabled="disabled"
                                    />
                                </div>
                                <p v-if="form.errors.content" class="text-sm font-medium text-red-600">{{ form.errors.content }}</p>
                            </label>
                        </div>
                    </section>

                    <section class="rounded-[1.6rem] border border-[#dbe4de] bg-white p-5 shadow-[0_10px_25px_rgba(15,91,70,0.04)]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-[#edf4ef] text-[#537569]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2">
                                    <path d="M8 7h8M8 12h8M8 17h5" />
                                    <path d="M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-black text-[#335548]">Attachments</h2>
                        </div>

                        <div class="mt-5 space-y-4">
                            <label class="block">
                                <input
                                    type="file"
                                    multiple
                                    class="hidden"
                                    :disabled="disabled"
                                    @change="updateAttachments"
                                >
                                <div class="flex min-h-[14rem] cursor-pointer flex-col items-center justify-center rounded-[1.4rem] border border-dashed border-[#cfdad3] bg-[#fbfcfb] px-6 py-8 text-center transition hover:bg-[#f6faf7]">
                                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#eef5f1] text-[#335548]">
                                        <svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="2">
                                            <path d="M7 17.5A4.5 4.5 0 1 1 8 8.7 5.5 5.5 0 0 1 19.5 10 4 4 0 0 1 18 17.5H7Z" />
                                            <path d="M12 9v6" />
                                            <path d="m9.5 11.5 2.5-2.5 2.5 2.5" />
                                        </svg>
                                    </span>
                                    <p class="mt-4 text-sm font-semibold text-[#435762]">Click to upload or drag and drop</p>
                                    <p class="mt-1 text-xs text-[#71808b]">PDF, PNG, JPG up to 10MB</p>
                                </div>
                            </label>

                            <p v-if="form.errors.attachments" class="text-sm font-medium text-red-600">{{ form.errors.attachments }}</p>
                            <p v-if="form.errors['attachments.0']" class="text-sm font-medium text-red-600">{{ form.errors['attachments.0'] }}</p>

                            <div v-if="form.attachments.length" class="rounded-2xl border border-[#dce4de] bg-[#f8fbf9] p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#71808b]">Queued Uploads</p>
                                <ul class="mt-3 space-y-2 text-sm text-[#0f172a]">
                                    <li v-for="file in form.attachments" :key="`${file.name}-${file.size}`">{{ file.name }}</li>
                                </ul>
                            </div>

                            <div v-if="form.existingAttachments?.length" class="rounded-2xl border border-[#dce4de] bg-[#f8fbf9] p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#71808b]">Current Attachments</p>
                                <ul class="mt-3 space-y-2 text-sm text-[#0f172a]">
                                    <li v-for="attachment in form.existingAttachments" :key="attachment.id">
                                        {{ attachment.name }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-5">
                    <section class="rounded-[1.6rem] border border-[#dbe4de] bg-white p-5 shadow-[0_10px_25px_rgba(15,91,70,0.04)]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-[#edf4ef] text-[#537569]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current" stroke-width="2">
                                    <path d="M8 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 1a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                    <path d="M3.5 18a4.5 4.5 0 0 1 9 0M13 18a3.5 3.5 0 0 1 7 0" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-black text-[#335548]">Audience Targeting</h2>
                        </div>

                        <div class="mt-5 space-y-3">
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#71808b]">Select Audience Type</p>

                            <button
                                v-for="option in audienceCards"
                                :key="option.value"
                                type="button"
                                class="flex w-full items-start gap-3 rounded-2xl border px-4 py-4 text-left transition"
                                :class="form.audience_type === option.value ? 'border-[#7ca092] bg-[#eef7f2] shadow-[0_6px_18px_rgba(15,91,70,0.06)]' : 'border-[#e4e9e6] bg-white hover:bg-[#fafcfb]'"
                                :disabled="disabled"
                                @click="form.audience_type = option.value"
                            >
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border" :class="form.audience_type === option.value ? 'border-[#0f5b46] bg-[#0f5b46] text-white' : 'border-[#cbd5cf] bg-white text-transparent'">
                                    <svg viewBox="0 0 20 20" class="h-3 w-3 fill-current">
                                        <circle cx="10" cy="10" r="10" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-[#0f172a]">{{ option.title }}</span>
                                    <span class="mt-1 block text-xs text-[#71808b]">{{ option.description }}</span>
                                </span>
                            </button>

                            <p v-if="form.errors.audience_type" class="text-sm font-medium text-red-600">{{ form.errors.audience_type }}</p>
                        </div>

                        <div v-if="showBarangay" class="mt-4 space-y-2">
                            <span class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#71808b]">Barangay</span>
                            <select
                                v-model="form.barangay_id"
                                class="w-full rounded-2xl border border-[#dce4de] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#0f5b46]"
                                :disabled="disabled"
                            >
                                <option value="">Select barangay</option>
                                <option v-for="barangay in reference.barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                            </select>
                            <p v-if="form.errors.barangay_id" class="text-sm font-medium text-red-600">{{ form.errors.barangay_id }}</p>
                        </div>

                        <div v-if="showMemberType" class="mt-4 space-y-2">
                            <span class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#71808b]">Member Group</span>
                            <select
                                v-model="form.member_type_id"
                                class="w-full rounded-2xl border border-[#dce4de] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#0f5b46]"
                                :disabled="disabled"
                            >
                                <option value="">Select member type</option>
                                <option v-for="memberType in reference.memberTypes" :key="memberType.id" :value="String(memberType.id)">{{ memberType.label }}</option>
                            </select>
                            <p v-if="form.errors.member_type_id" class="text-sm font-medium text-red-600">{{ form.errors.member_type_id }}</p>
                        </div>

                        <div class="mt-6 space-y-3">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-[#014d3c] px-5 py-3 text-sm font-black text-white transition hover:bg-[#01362a] disabled:cursor-not-allowed disabled:opacity-60" :disabled="disabled">
                                {{ disabled ? 'Saving...' : submitLabel }}
                            </button>
                            <a :href="cancelHref" class="inline-flex w-full items-center justify-center rounded-2xl border border-[#dde4de] bg-white px-5 py-3 text-sm font-semibold text-[#7b8790] transition hover:bg-[#f5f7f6]">
                                Cancel
                            </a>
                        </div>
                    </section>
                </aside>
            </div>
        </form>
    </section>
</template>
