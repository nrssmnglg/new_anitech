<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UserForm from '../../../Components/Admin/Users/UserForm.vue';

const props = defineProps({
    user: { type: Object, required: true },
    roleOptions: { type: Array, required: true },
    statusOptions: { type: Array, required: true },
    farmerOptions: { type: Array, required: true },
    employeeIdPreview: { type: String, required: true },
    canArchive: { type: Boolean, required: true },
    urls: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    role: props.user.role ?? '',
    status: props.user.status ?? 'Active',
    farmer_id: props.user.farmer_id ? String(props.user.farmer_id) : '',
    password: '',
    password_confirmation: '',
    job_title: props.user.job_title ?? '',
    contact_number: props.user.contact_number ?? '',
    employee_id: props.user.employee_id ?? '',
});

function submit() {
    form.put(props.urls.update, {
        preserveScroll: true,
    });
}

function archiveUser() {
    if (!props.urls.archive || form.processing) {
        return;
    }

    if (!window.confirm(`Archive ${props.user.name}?`)) {
        return;
    }

    router.delete(props.urls.archive, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit User" />

    <AdminLayout title="Edit User">
        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">User management</p>
                        <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">Edit User</h1>
                    </div>
                    <a :href="urls.show" class="inline-flex h-8 items-center justify-center rounded-md border border-white/25 px-3 text-[0.68rem] font-semibold text-white transition hover:bg-white/10">
                        View User
                    </a>
                </div>
            </section>

            <UserForm
                :form="form"
                :role-options="roleOptions"
                :status-options="statusOptions"
                :farmer-options="farmerOptions"
                :employee-id-preview="employeeIdPreview"
                :is-create="false"
                submit-label="Update User"
                :disabled="form.processing"
                :back-url="urls.index"
                @submit="submit"
            />

            <section v-if="canArchive" class="flex flex-col gap-2 rounded-lg border border-[#efcbc5] bg-[#fff8f6] p-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xs font-semibold text-[#8f2f22]">Archive account</h2>
                    <p class="mt-0.5 text-[0.65rem] text-[#8f5a52]">Remove this account from active user access.</p>
                </div>
                <div>
                    <button type="button" class="inline-flex h-8 items-center justify-center rounded-md border border-[#a83d2a]/20 bg-white px-3 text-[0.68rem] font-semibold text-[#a83d2a] transition hover:bg-[#fff1ee]" @click="archiveUser">
                        Archive User
                    </button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
