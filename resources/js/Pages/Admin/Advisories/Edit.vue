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
        barangay_id: data.audience_type === 'barangay' && data.barangay_id ? data.barangay_id : null,
        member_type_id: data.audience_type === 'group' && data.member_type_id ? data.member_type_id : null,
        _method: 'put',
    })).post(props.urls.update, {
        preserveScroll: true,
        forceFormData: true,
        onError: async () => {
            await nextTick();
            document.querySelector('.advisory-form .text-red-600, .advisory-form .text-rose-600, .advisory-form [role="alert"]')
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
        <AdvisoryForm
            :form="form"
            :reference="reference"
            heading="Edit Advisory"
            submit-label="Save Changes"
            :cancel-href="urls.show"
            :disabled="submitting || form.processing"
            @submit="submit"
            @remove-attachment="removeAttachment"
        />
    </AdminLayout>
</template>
