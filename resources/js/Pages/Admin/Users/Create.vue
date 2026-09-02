<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UserForm from '../../../Components/Admin/Users/UserForm.vue';

const props = defineProps({
    user: { type: Object, required: true },
    roleOptions: { type: Array, required: true },
    statusOptions: { type: Array, required: true },
    farmerOptions: { type: Array, required: true },
    employeeIdPreview: { type: String, required: true },
    urls: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    role: props.user.role ?? '',
    status: props.user.status ?? 'Active',
    farmer_id: props.user.farmer_id ? String(props.user.farmer_id) : '',
    job_title: props.user.job_title ?? '',
    contact_number: props.user.contact_number ?? '',
    employee_id: props.user.employee_id ?? '',
});

function submit() {
    form.post(props.urls.store, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Add User" />

    <AdminLayout title="Add User">
        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">User management</p>
                <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">Add User</h1>
            </section>

            <UserForm
                :form="form"
                :role-options="roleOptions"
                :status-options="statusOptions"
                :farmer-options="farmerOptions"
                :employee-id-preview="employeeIdPreview"
                :is-create="true"
                submit-label="Save User"
                :disabled="form.processing"
                :back-url="urls.index"
                @submit="submit"
            />
        </div>
    </AdminLayout>
</template>
