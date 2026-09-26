<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, required: true },
    summary: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const filterForm = reactive({
    status: props.filters.status ?? '',
    account_type: props.filters.account_type ?? '',
});

const archivingId = ref(null);

const activeRate = computed(() => {
    if (!props.summary.total) {
        return 0;
    }

    return Math.round((props.summary.active / props.summary.total) * 100);
});

function applyFilters() {
    router.get(props.urls.index, {
        status: filterForm.status || undefined,
        account_type: filterForm.account_type || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.status = '';
    filterForm.account_type = '';
    applyFilters();
}

function archiveUser(user) {
    if (!user.actions.archive || archivingId.value !== null) {
        return;
    }

    if (!window.confirm(`Archive ${user.name}?`)) {
        return;
    }

    archivingId.value = user.id;

    router.delete(user.actions.archive, {
        preserveScroll: true,
        onFinish: () => {
            archivingId.value = null;
        },
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

function statusTone(status) {
    return status === 'Active'
        ? 'bg-[#dcfce7] text-[#15803d]'
        : 'bg-[#f1f5f9] text-[#64748b]';
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
    <Head title="User Management" />

    <AdminLayout title="User Management">
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-24 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Account Administration</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">User Management</h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Summary Pills -->
                        <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Total</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.total }}</p>
                            </article>
                            <article class="border-x border-white/[0.08] px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Active</p>
                                <p class="mt-0.5 text-lg font-bold leading-none text-[#7ddfb8]">{{ summary.active }}</p>
                            </article>
                            <article class="border-r border-white/[0.08] px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Farmers</p>
                                <p class="mt-0.5 text-lg font-bold leading-none text-[#fbbf24]">{{ summary.farmers }}</p>
                            </article>
                            <article class="px-4 py-2.5 text-center">
                                <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Office</p>
                                <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.staff }}</p>
                            </article>
                        </div>

                        <Link :href="urls.create" class="group inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-[0.7rem] font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-90" fill="currentColor">
                                <path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/>
                            </svg>
                            Add User
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Filters Bar -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_auto] xl:items-end">
                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select
                            v-model="filterForm.status"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option v-for="option in filterOptions.statuses" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Account Type</span>
                        <select
                            v-model="filterForm.account_type"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option v-for="option in filterOptions.accountTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <div class="flex items-center gap-2 xl:justify-end">
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97]"
                            @click="applyFilters"
                        >
                            Apply
                        </button>
                        <button
                            v-if="filterForm.status || filterForm.account_type"
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-3.5 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]"
                            @click="resetFilters"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </section>

            <!-- User Records Table -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-5 py-3.5">
                    <div>
                        <h2 class="text-[0.8rem] font-bold text-[#0f172a]">User Records</h2>
                        <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ users.total }} account{{ users.total === 1 ? '' : 's' }} registered</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#e8eeea] bg-gradient-to-b from-[#f8faf9] to-[#f3f6f4] text-left text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                                <th class="px-5 py-3">User</th>
                                <th class="px-5 py-3">Role</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Linked Record</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="group border-b border-[#edf2ee] transition-all duration-150 hover:bg-[#f6faf8]">
                                <td class="px-5 py-3.5 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[0.7rem] font-bold text-[#0f6b45] shadow-sm shadow-[#0f6b45]/10">
                                            {{ initials(user.name) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-[#0f172a]">{{ user.name }}</p>
                                            <p class="text-[0.68rem] text-[#64748b]">{{ user.email }}</p>
                                            <p class="mt-0.5 text-[0.6rem] text-[#94a3b8]">Created {{ user.createdAt }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-3.5 align-middle">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center rounded-lg border px-2.5 py-0.5 text-[0.62rem] font-bold" :class="roleTone(user.role)">
                                            {{ user.role }}
                                        </span>
                                        <p class="text-[0.65rem] text-[#64748b]">{{ user.accountType }}</p>
                                    </div>
                                </td>

                                <td class="px-5 py-3.5 align-middle">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[0.62rem] font-bold" :class="statusTone(user.status)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="user.status === 'Active' ? 'bg-[#22c55e]' : 'bg-[#94a3b8]'"></span>
                                        {{ user.status }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 align-middle">
                                    <div v-if="user.officeProfile" class="space-y-0.5">
                                        <p class="font-bold text-[#0f172a]">{{ user.officeProfile.employeeId || 'Office Profile' }}</p>
                                        <p class="text-[0.65rem] text-[#64748b]">{{ user.officeProfile.jobTitle || 'No job title set' }}</p>
                                    </div>
                                    <div v-else-if="user.farmer" class="space-y-0.5">
                                        <p class="font-bold text-[#0f172a]">{{ user.farmer.code }}</p>
                                        <span class="inline-flex items-center gap-1 text-[0.62rem] font-semibold text-[#0f6b45]">
                                            <span class="h-1 w-1 rounded-full bg-[#0f6b45]"></span>
                                            Mobile login linked
                                        </span>
                                    </div>
                                    <p v-else class="text-xs text-[#94a3b8]">—</p>
                                </td>

                                <td class="px-5 py-3.5 align-middle text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-70 transition-opacity duration-200 group-hover:opacity-100">
                                        <Link
                                            :href="user.actions.show"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#014d3c] transition-all duration-150 hover:bg-[#e6f5ec]"
                                            title="View account"
                                            aria-label="View account"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                                <circle cx="12" cy="12" r="2.5" />
                                            </svg>
                                        </Link>

                                        <Link
                                            :href="user.actions.edit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#475569] transition-all duration-150 hover:bg-[#f1f5f9]"
                                            title="Edit account"
                                            aria-label="Edit account"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                            </svg>
                                        </Link>

                                        <button
                                            v-if="user.actions.archive"
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#dc2626] transition-all duration-150 hover:bg-[#fef2f2] disabled:cursor-not-allowed disabled:opacity-50"
                                            :disabled="archivingId !== null"
                                            title="Archive account"
                                            aria-label="Archive account"
                                            @click="archiveUser(user)"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" :class="{ 'animate-pulse': archivingId === user.id }" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 10v6M14 10v6"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-xs flex-col items-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0faf5]">
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                <circle cx="9" cy="7" r="4"/>
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-[#334155]">No user accounts found</p>
                                        <p class="mt-1 text-xs text-[#94a3b8]">No user accounts match the current filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Numbered Pill Pagination -->
                <div v-if="users.last_page > 1" class="flex flex-col gap-2 border-t border-[#edf2ee] bg-gradient-to-b from-[#fbfcfb] to-[#f8faf9] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.68rem] text-[#64748b]">Showing <span class="font-semibold text-[#334155]">{{ users.from || 0 }}</span>–<span class="font-semibold text-[#334155]">{{ users.to || 0 }}</span> of <span class="font-semibold text-[#334155]">{{ users.total }}</span></p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template v-for="link in users.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] text-[#cbd5e1]" v-html="link.label" />
                            <Link v-else :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-[0.68rem] font-semibold transition-all duration-200" :class="link.active ? 'bg-[#014d3c] text-white shadow-sm' : 'text-[#64748b] hover:bg-[#f1f5f9]'" preserve-scroll preserve-state v-html="link.label" />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
