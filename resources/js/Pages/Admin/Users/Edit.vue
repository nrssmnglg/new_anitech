<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import UserForm from '../../../Components/Admin/Users/UserForm.vue';

const props = defineProps({
    user: { type: Object, required: true },
    roleOptions: { type: Array, required: true },
    statusOptions: { type: Array, required: true },
    employeeIdPreview: { type: String, required: true },
    canArchive: { type: Boolean, required: true },
    urls: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    role: props.user.role ?? '',
    status: props.user.status ?? 'Active',
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
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[28px] border border-primary/15 bg-[linear-gradient(135deg,rgba(0,54,41,0.96),rgba(15,91,70,0.94)_55%,rgba(144,196,126,0.84))] px-6 py-7 text-white shadow-[0_24px_60px_rgba(0,54,41,0.22)]">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-md">
                            <span class="h-2.5 w-2.5 rounded-full bg-[#d7f7a8] shadow-[0_0_18px_rgba(215,247,168,0.85)]"></span>
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.28em] text-white/85">User Management</span>
                        </div>
                        <h1 class="mt-5 text-4xl font-black tracking-[-0.045em] sm:text-5xl">Edit User Account</h1>
                    </div>
                    <a :href="urls.show" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-6 py-3 text-sm font-extrabold text-white backdrop-blur transition hover:bg-white/15">
                        View User
                    </a>
                </div>
            </section>

            <UserForm
                :form="form"
                :role-options="roleOptions"
                :status-options="statusOptions"
                :employee-id-preview="employeeIdPreview"
                :is-create="false"
                submit-label="Update User"
                :disabled="form.processing"
                :back-url="urls.index"
                @submit="submit"
            />

            <section v-if="canArchive" class="rounded-3xl border border-[#a83d2a]/12 bg-white shadow-[0_18px_40px_rgba(100,31,18,0.08)]">
                <div class="border-b border-[#a83d2a]/10 bg-[#fff5f3] px-6 py-5">
                    <h2 class="text-xl font-black text-[#8f2f22]">Archive Account</h2>
                </div>
                <div class="p-6">
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#a83d2a]/15 bg-white px-5 py-3 text-sm font-semibold text-[#a83d2a] transition hover:bg-[#fff1ee]" @click="archiveUser">
                        Archive User
                    </button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
