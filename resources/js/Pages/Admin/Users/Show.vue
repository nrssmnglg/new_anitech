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

function initials(name) {
    return String(name || 'User')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('') || 'US';
}

function roleTone(role) {
    if (role === 'Administrator' || role === 'Admin') {
        return 'bg-[#eff6ff] text-[#2563eb] border-[#bfdbfe]';
    }

    if (role === 'Staff') {
        return 'bg-[#ecfdf5] text-[#059669] border-[#a7f3d0]';
    }

    return 'bg-[#fffbeb] text-[#d97706] border-[#fde68a]';
}
</script>

<template>
    <Head :title="user.name" />

    <AdminLayout :title="user.name">
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] text-sm font-bold text-[#7ddfb8] backdrop-blur-sm shadow-inner">
                            {{ initials(user.name) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Account Details</p>
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[0.6rem] font-bold" :class="user.status === 'Active' ? 'bg-[#dcfce7] text-[#15803d]' : 'bg-white/20 text-white'">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="user.status === 'Active' ? 'bg-[#22c55e]' : 'bg-white/60'"></span>
                                    {{ user.status }}
                                </span>
                            </div>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">{{ user.name }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link v-if="urls.edit" :href="urls.edit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                            </svg>
                            Edit User
                        </Link>
                        <Link :href="urls.index" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 px-3.5 text-[0.7rem] font-semibold text-white/90 transition-all duration-200 hover:bg-white/10 hover:text-white">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.47 4.47a.75.75 0 1 1-1.06 1.06l-5.75-5.75a.75.75 0 0 1 0-1.06l5.75-5.75a.75.75 0 1 1 1.06 1.06L5.61 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/>
                            </svg>
                            All Users
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Generated Credentials Alert (Shown on creation) -->
            <section v-if="generatedCredentials" class="overflow-hidden rounded-xl border border-[#fde68a] bg-[#fffbeb] shadow-sm">
                <div class="flex items-center gap-3 border-b border-[#fde68a]/60 px-5 py-3.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#fef3c7] text-[#d97706]">
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-[#92400e]">Generated Login Credentials</h2>
                        <p class="text-[0.68rem] text-[#78350f]">Copy this password now — it is only displayed once for security reasons.</p>
                    </div>
                </div>

                <div class="grid gap-3 p-5 sm:grid-cols-3">
                    <div class="rounded-xl border border-[#fde68a]/50 bg-white p-3.5 shadow-sm">
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-[#b45309]">Email</p>
                        <p class="mt-1 text-xs font-bold text-[#0f172a] select-all">{{ user.email }}</p>
                    </div>
                    <div class="rounded-xl border border-[#fde68a]/50 bg-white p-3.5 shadow-sm">
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-[#b45309]">Default Password</p>
                        <p class="mt-1 font-mono text-xs font-bold text-[#0f172a] select-all">{{ generatedCredentials.password }}</p>
                    </div>
                    <div v-if="generatedCredentials.employee_id" class="rounded-xl border border-[#fde68a]/50 bg-white p-3.5 shadow-sm">
                        <p class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-[#b45309]">Employee ID</p>
                        <p class="mt-1 font-mono text-xs font-bold text-[#0f172a] select-all">{{ generatedCredentials.employee_id }}</p>
                    </div>
                </div>
            </section>

            <!-- Details Grid -->
            <div class="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
                <!-- Account Overview Card -->
                <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-[#edf2ee] px-5 py-3.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd]">
                            <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#0f6b45]" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h2 class="text-sm font-bold text-[#0f172a]">Account Overview</h2>
                    </div>

                    <div class="grid gap-3 p-5 sm:grid-cols-2">
                        <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Full Name</p>
                            <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ user.name }}</p>
                        </div>

                        <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Email Address</p>
                            <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ user.email }}</p>
                        </div>

                        <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Assigned Role</p>
                            <div class="mt-1">
                                <span class="inline-flex items-center rounded-lg border px-2.5 py-0.5 text-[0.65rem] font-bold" :class="roleTone(user.role)">
                                    {{ user.role }}
                                </span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Account Status</p>
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-0.5 text-[0.65rem] font-bold" :class="user.status === 'Active' ? 'bg-[#dcfce7] text-[#15803d]' : 'bg-[#f1f5f9] text-[#64748b]'">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="user.status === 'Active' ? 'bg-[#22c55e]' : 'bg-[#94a3b8]'"></span>
                                    {{ user.status }}
                                </span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Created At</p>
                            <p class="mt-1 text-xs font-semibold text-[#0f172a]">{{ user.createdAt }}</p>
                        </div>

                        <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Last Updated</p>
                            <p class="mt-1 text-xs font-semibold text-[#0f172a]">{{ user.updatedAt }}</p>
                        </div>
                    </div>
                </section>

                <!-- Linked Record Card -->
                <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-[#edf2ee] px-5 py-3.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#eff6ff] to-[#dbeafe]">
                            <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#2563eb]" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </div>
                        <h2 class="text-sm font-bold text-[#0f172a]">Linked Record</h2>
                    </div>

                    <div class="p-5">
                        <div v-if="user.officeProfile" class="space-y-3">
                            <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                                <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Employee ID</p>
                                <p class="mt-1 font-mono text-xs font-bold text-[#0f172a]">{{ user.officeProfile.employeeId || 'Not assigned' }}</p>
                            </div>

                            <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                                <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Job Title</p>
                                <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ user.officeProfile.jobTitle || 'Not set' }}</p>
                            </div>

                            <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                                <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Contact Number</p>
                                <p class="mt-1 text-xs font-bold text-[#0f172a]">{{ user.officeProfile.contactNumber || 'Not set' }}</p>
                            </div>
                        </div>

                        <div v-else-if="user.farmer" class="space-y-3">
                            <div class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-3.5">
                                <p class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Farmer Code</p>
                                <p class="mt-1 font-mono text-xs font-bold text-[#0f172a]">{{ user.farmer.code }}</p>
                            </div>

                            <div class="flex items-center gap-2 rounded-xl border border-[#d1fae5] bg-[#ecfdf5] p-3.5 text-xs font-semibold text-[#065f46]">
                                <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-[#059669]" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                                Mobile Portal Login Linked
                            </div>
                        </div>

                        <div v-else class="rounded-xl border border-[#e8eeea] bg-[#f9fbfa] p-5 text-center">
                            <p class="text-xs font-semibold text-[#64748b]">Standard User Account</p>
                            <p class="mt-1 text-[0.68rem] text-[#94a3b8]">No external farmer profile or employee record linked.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Archive Account Danger Zone -->
            <section v-if="urls.archive && user.canArchive" class="flex flex-col gap-3 rounded-xl border border-rose-200/80 bg-rose-50/50 p-4 sm:flex-row sm:items-center sm:justify-between">
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
