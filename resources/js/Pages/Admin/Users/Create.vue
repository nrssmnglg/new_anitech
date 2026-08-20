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
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[28px] border border-primary/15 bg-[linear-gradient(135deg,rgba(0,54,41,0.96),rgba(15,91,70,0.94)_55%,rgba(144,196,126,0.84))] px-6 py-7 text-white shadow-[0_24px_60px_rgba(0,54,41,0.22)]">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-md">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#d7f7a8] shadow-[0_0_18px_rgba(215,247,168,0.85)]"></span>
                        <span class="text-[0.68rem] font-black uppercase tracking-[0.28em] text-white/85">User Management</span>
                    </div>
                    <h1 class="mt-5 text-4xl font-black tracking-[-0.045em] sm:text-5xl">Create User Account</h1>
                    <p class="mt-3 max-w-2xl text-[1rem] leading-8 text-white/78">Add a new administrator, staff, or farmer-linked account using the current access rules and office profile structure.</p>
                </div>
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
