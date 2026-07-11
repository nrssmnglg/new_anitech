<script setup>
import { Head, useForm } from '@inertiajs/vue3';
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
    existingAttachments: [],
});

function submit() {
    form.transform((data) => ({
        ...data,
        barangay_id: data.audience_type === 'barangay' && data.barangay_id ? Number(data.barangay_id) : null,
        member_type_id: data.audience_type === 'group' && data.member_type_id ? Number(data.member_type_id) : null,
    })).post(props.urls.store, {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Create Advisory" />

    <AdminLayout title="Create Advisory">
        <AdvisoryForm
            :form="form"
            :reference="reference"
            heading="Create Advisory"
            description=""
            submit-label="Save Draft"
            :cancel-href="urls.index"
            :disabled="form.processing"
            @submit="submit"
        />
    </AdminLayout>
</template>
