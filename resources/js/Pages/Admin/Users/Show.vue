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
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[28px] border border-[#0f5b46]/15 bg-[linear-gradient(135deg,rgba(0,54,41,0.96),rgba(15,91,70,0.94)_55%,rgba(144,196,126,0.84))] px-6 py-7 text-white shadow-[0_24px_60px_rgba(0,54,41,0.22)]">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-md">
                            <span class="h-2.5 w-2.5 rounded-full bg-[#d7f7a8] shadow-[0_0_18px_rgba(215,247,168,0.85)]"></span>
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.28em] text-white/85">User Management</span>
                        </div>
                        <h1 class="mt-5 text-4xl font-black tracking-[-0.045em] sm:text-5xl">{{ user.name }}</h1>
                        <p class="mt-3 max-w-2xl text-[1rem] leading-8 text-white/78">Review account details, linked records, and office profile information for this user.</p>
                    </div>
                    <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                        <Link
                            v-if="urls.edit"
                            :href="urls.edit"
                            class="inline-flex min-h-[52px] items-center justify-center rounded-2xl bg-white px-6 py-3 text-sm font-extrabold text-[#0f5b46] shadow-[0_16px_30px_rgba(0,0,0,0.12)] transition hover:-translate-y-0.5 hover:bg-[#f3fbf6]"
                        >
                            Edit User
                        </Link>
                        <Link
                            :href="urls.index"
                            class="inline-flex min-h-[52px] items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-6 py-3 text-sm font-extrabold text-white backdrop-blur transition hover:bg-white/15"
                        >
                            Back to Users
                        </Link>
                    </div>
                </div>
            </section>

            <section v-if="generatedCredentials" class="rounded-3xl border border-[#a8832a]/15 bg-[#fff9ec] p-6 shadow-[0_18px_40px_rgba(168,131,42,0.08)]">
                <h2 class="text-xl font-black text-[#5b4a1c]">Generated Login Details</h2>
                <p class="mt-2 text-sm text-[#6f5618]">Copy these now. The generated password is only shown once.</p>
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-[#d4a63b]/18 bg-white/80 p-4">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#8a6218]">Email</p>
                        <p class="mt-2 font-semibold text-[#5b4a1c]">{{ user.email }}</p>
                    </div>
                    <div class="rounded-2xl border border-[#d4a63b]/18 bg-white/80 p-4">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#8a6218]">Default Password</p>
                        <p class="mt-2 font-semibold text-[#5b4a1c]">{{ generatedCredentials.password }}</p>
                    </div>
                    <div v-if="generatedCredentials.employee_id" class="rounded-2xl border border-[#d4a63b]/18 bg-white/80 p-4">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#8a6218]">Employee ID</p>
                        <p class="mt-2 font-semibold text-[#5b4a1c]">{{ generatedCredentials.employee_id }}</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_18px_40px_rgba(0,54,41,0.08)]">
                    <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-5">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-xl font-black text-primary">Account Overview</h2>
                            <span class="rounded-full px-3 py-1 text-[0.72rem] font-bold" :class="user.status === 'Active' ? 'bg-secondary-container/55 text-on-secondary-container' : 'bg-surface-container-high text-on-surface-variant'">
                                {{ user.status }}
                            </span>
                        </div>
                    </div>
                    <div class="grid gap-4 p-6 md:grid-cols-2">
                        <div class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4"><p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Name</p><p class="mt-2 font-semibold text-[#1b4337]">{{ user.name }}</p></div>
                        <div class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4"><p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Email</p><p class="mt-2 font-semibold text-[#1b4337]">{{ user.email }}</p></div>
                        <div class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4"><p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Role</p><p class="mt-2 font-semibold text-[#1b4337]">{{ user.role }}</p></div>
                        <div class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4"><p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Updated</p><p class="mt-2 font-semibold text-[#1b4337]">{{ user.updatedAt }}</p></div>
                        <div class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4 md:col-span-2"><p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Created</p><p class="mt-2 font-semibold text-[#1b4337]">{{ user.createdAt }}</p></div>
                    </div>
                </section>

                <section class="rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_18px_40px_rgba(0,54,41,0.08)]">
                    <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-5">
                        <h2 class="text-xl font-black text-primary">Account Classification</h2>
                    </div>
                    <div class="p-6">
                        <div class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4">
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Account Type</p>
                            <p class="mt-2 font-semibold text-[#1b4337]">{{ user.role === 'Farmer' ? 'Farmer Account' : 'Office Account' }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <section v-if="user.officeProfile" class="rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_18px_40px_rgba(0,54,41,0.08)]">
                <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-5">
                    <h2 class="text-xl font-black text-primary">Office Profile</h2>
                </div>
                <div class="grid gap-4 p-6 md:grid-cols-3">
                    <div class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4"><p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Employee ID</p><p class="mt-2 font-semibold text-[#1b4337]">{{ user.officeProfile.employeeId || 'Not set' }}</p></div>
                    <div class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4"><p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Job Title</p><p class="mt-2 font-semibold text-[#1b4337]">{{ user.officeProfile.jobTitle || 'Not set' }}</p></div>
                    <div class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4"><p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Contact Number</p><p class="mt-2 font-semibold text-[#1b4337]">{{ user.officeProfile.contactNumber || 'Not set' }}</p></div>
                </div>
            </section>

            <section v-if="urls.archive" class="rounded-3xl border border-[#a83d2a]/12 bg-white shadow-[0_18px_40px_rgba(100,31,18,0.08)]">
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
