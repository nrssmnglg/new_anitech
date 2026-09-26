<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
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
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <line x1="20" y1="8" x2="20" y2="14"/>
                                <line x1="23" y1="11" x2="17" y2="11"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Account Administration</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Add User</h1>
                        </div>
                    </div>

                    <Link :href="urls.index" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 px-3.5 text-[0.7rem] font-semibold text-white/90 transition-all duration-200 hover:bg-white/10 hover:text-white" :class="{ 'pointer-events-none opacity-60': form.processing }">
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.47 4.47a.75.75 0 1 1-1.06 1.06l-5.75-5.75a.75.75 0 0 1 0-1.06l5.75-5.75a.75.75 0 1 1 1.06 1.06L5.61 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/>
                        </svg>
                        All Users
                    </Link>
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
