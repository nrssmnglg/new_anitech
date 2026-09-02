<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
    generatedCredentials: { type: Object, default: null },
    urls: { type: Object, required: true },
});

function archiveUser() {
    if (!props.urls.archive) {
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
    <Head title="User Account" />

    <AdminLayout title="User Account">
        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">User account</p>
                        <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">{{ user.name }}</h1>
                    </div>
                    <div class="flex gap-2">
                        <Link v-if="urls.edit" :href="urls.edit" class="inline-flex h-8 items-center justify-center rounded-md bg-white px-3 text-[0.68rem] font-semibold text-[#014d3c] transition hover:bg-[#f3fbf6]">Edit User</Link>
                        <Link :href="urls.index" class="inline-flex h-8 items-center justify-center rounded-md border border-white/25 px-3 text-[0.68rem] font-semibold text-white transition hover:bg-white/10">User List</Link>
                    </div>
                </div>
            </section>

            <section v-if="generatedCredentials" class="rounded-lg border border-[#ead9a9] bg-[#fff9ec] p-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-[#5b4a1c]">Generated login details</h2>
                    <p class="text-[0.65rem] text-[#6f5618]">Copy now—the password is shown once.</p>
                </div>
                <div class="mt-2 grid gap-2 md:grid-cols-3">
                    <div class="rounded-md bg-white/80 px-3 py-2">
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#8a6218]">Email</p>
                        <p class="mt-0.5 text-xs font-semibold text-[#5b4a1c]">{{ user.email }}</p>
                    </div>
                    <div class="rounded-md bg-white/80 px-3 py-2">
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#8a6218]">Default Password</p>
                        <p class="mt-0.5 text-xs font-semibold text-[#5b4a1c]">{{ generatedCredentials.password }}</p>
                    </div>
                    <div v-if="generatedCredentials.employee_id" class="rounded-md bg-white/80 px-3 py-2">
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#8a6218]">Employee ID</p>
                        <p class="mt-0.5 text-xs font-semibold text-[#5b4a1c]">{{ generatedCredentials.employee_id }}</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-3 xl:grid-cols-[1.5fr_0.7fr]">
                <section class="rounded-lg border border-[#dbe4de] bg-white">
                    <div class="border-b border-[#edf2ee] px-4 py-3">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-sm font-semibold text-primary">Account overview</h2>
                            <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-semibold" :class="user.status === 'Active' ? 'bg-secondary-container/55 text-on-secondary-container' : 'bg-surface-container-high text-on-surface-variant'">
                                {{ user.status }}
                            </span>
                        </div>
                    </div>
                    <div class="grid gap-2 p-4 md:grid-cols-2">
                        <div v-for="item in [{ label: 'Name', value: user.name }, { label: 'Email', value: user.email }, { label: 'Role', value: user.role }, { label: 'Updated', value: user.updatedAt }, { label: 'Created', value: user.createdAt }]" :key="item.label" class="rounded-md bg-[#f6f8f7] px-3 py-2" :class="{ 'md:col-span-2': item.label === 'Created' }">
                            <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">{{ item.label }}</p>
                            <p class="mt-0.5 text-xs font-semibold text-[#1b4337]">{{ item.value }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-[#dbe4de] bg-white">
                    <div class="border-b border-[#edf2ee] px-4 py-3">
                        <h2 class="text-sm font-semibold text-primary">Classification</h2>
                    </div>
                    <div class="p-4">
                        <div class="rounded-md bg-[#f6f8f7] px-3 py-2">
                            <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Account Type</p>
                            <p class="mt-0.5 text-xs font-semibold text-[#1b4337]">{{ user.role === 'Farmer' ? 'Farmer Account' : 'Office Account' }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <section v-if="user.officeProfile" class="rounded-lg border border-[#dbe4de] bg-white">
                <div class="border-b border-[#edf2ee] px-4 py-3">
                    <h2 class="text-sm font-semibold text-primary">Office profile</h2>
                </div>
                <div class="grid gap-2 p-4 md:grid-cols-3">
                    <div v-for="item in [{ label: 'Employee ID', value: user.officeProfile.employeeId }, { label: 'Job Title', value: user.officeProfile.jobTitle }, { label: 'Contact Number', value: user.officeProfile.contactNumber }]" :key="item.label" class="rounded-md bg-[#f6f8f7] px-3 py-2">
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">{{ item.label }}</p>
                        <p class="mt-0.5 text-xs font-semibold text-[#1b4337]">{{ item.value || 'Not set' }}</p>
                    </div>
                </div>
            </section>

            <section v-if="urls.archive" class="flex flex-col gap-2 rounded-lg border border-[#efcbc5] bg-[#fff8f6] p-3 sm:flex-row sm:items-center sm:justify-between">
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
