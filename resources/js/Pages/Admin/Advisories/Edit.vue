<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import AdvisoryForm from '../../../Components/Admin/Advisories/AdvisoryForm.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    advisory: { type: Object, required: true },
    reference: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const form = useForm({
    title: props.advisory.title || '',
    content: props.advisory.content || '',
    audience_type: props.advisory.audience_type || 'all',
    barangay_id: props.advisory.barangay_id || '',
    member_type_id: props.advisory.member_type_id || '',
    attachments: [],
    existingAttachments: props.advisory.existingAttachments || [],
});
const submitting = ref(false);

function submit() {
    if (submitting.value || form.processing) {
        return;
    }

    submitting.value = true;

    form.transform((data) => ({
        ...data,
        barangay_id: data.audience_type === 'barangay' && data.barangay_id ? Number(data.barangay_id) : null,
        member_type_id: data.audience_type === 'group' && data.member_type_id ? Number(data.member_type_id) : null,
        _method: 'put',
    })).post(props.urls.update, {
        preserveScroll: true,
        forceFormData: true,
        onError: async () => {
            await nextTick();
            document.querySelector('.advisory-form .text-red-600, .advisory-form [role="alert"]')
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}

function removeAttachment(attachment) {
    if (!window.confirm(`Remove ${attachment.name}?`)) {
        return;
    }

    router.delete(attachment.deleteUrl, {
        preserveScroll: true,
        onSuccess: () => {
            form.existingAttachments = form.existingAttachments.filter((item) => item.id !== attachment.id);
        },
    });
}
</script>

<template>
    <Head title="Edit Advisory" />

    <AdminLayout title="Edit Advisory">
        <div class="space-y-3">
            <AdvisoryForm
                :form="form"
                :reference="reference"
                heading="Edit Advisory"
                description=""
                submit-label="Save Changes"
                :cancel-href="urls.show"
                :disabled="submitting || form.processing"
                @submit="submit"
            />

            <section v-if="form.existingAttachments.length" class="overflow-hidden rounded-lg border border-[#0f5b46]/12 bg-white">
                <div class="border-b border-[#0f5b46]/10 px-4 py-3">
                    <h2 class="text-sm font-semibold text-primary">Current attachments</h2>
                </div>
                <div class="space-y-2 p-4">
                    <article v-for="attachment in form.existingAttachments" :key="attachment.id" class="flex flex-col gap-2 rounded-md border border-[#0f5b46]/10 bg-[#f8fbf9] px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-bold text-primary">{{ attachment.name }}</p>
                            <p class="text-xs text-on-surface-variant">{{ attachment.uploadedAt || 'No upload timestamp' }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <a :href="attachment.downloadUrl" target="_blank" rel="noopener" class="font-semibold text-[#2f7d5e] transition hover:underline">Open</a>
                            <button type="button" class="font-semibold text-[#a83d2a] transition hover:underline" @click="removeAttachment(attachment)">
                                Remove
                            </button>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
