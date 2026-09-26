<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Account Administration</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Edit User</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link :href="urls.show" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 px-3.5 text-[0.7rem] font-semibold text-white/90 transition-all duration-200 hover:bg-white/10 hover:text-white" :class="{ 'pointer-events-none opacity-60': form.processing }">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6Z" />
                                <circle cx="12" cy="12" r="2.5" />
                            </svg>
                            View User
                        </Link>
                        <Link :href="urls.index" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 px-3.5 text-[0.7rem] font-semibold text-white/90 transition-all duration-200 hover:bg-white/10 hover:text-white" :class="{ 'pointer-events-none opacity-60': form.processing }">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.47 4.47a.75.75 0 1 1-1.06 1.06l-5.75-5.75a.75.75 0 0 1 0-1.06l5.75-5.75a.75.75 0 1 1 1.06 1.06L5.61 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/>
                            </svg>
                            All Users
                        </Link>
                    </div>
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

            <!-- Archive Account Danger Zone -->
            <section v-if="canArchive" class="flex flex-col gap-3 rounded-xl border border-rose-200/80 bg-rose-50/50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-bold text-rose-900">Archive Account</h2>
                        <p class="text-[0.68rem] text-rose-700">Remove active system and portal access for this user.</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-rose-300 bg-white px-4 text-xs font-bold text-rose-700 shadow-sm transition-all duration-200 hover:bg-rose-50 active:scale-[0.97]"
                    @click="archiveUser"
                >
                    Archive User
                </button>
            </section>
        </div>
    </AdminLayout>
</template>
