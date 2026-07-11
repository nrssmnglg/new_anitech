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
        ? 'bg-[#d9f4c2] text-[#50761b]'
        : 'bg-[#eceff1] text-[#5e6c74]';
}

function roleTone(role) {
    if (role === 'Admin') {
        return 'bg-[#e7eefc] text-[#3454a1]';
    }

    if (role === 'Staff') {
        return 'bg-[#eef7f2] text-[#0f5b46]';
    }

    return 'bg-[#fff4dc] text-[#b46d00]';
}
</script>

<template>
    <Head title="User Management" />

    <AdminLayout title="User Management">
        <div class="space-y-5">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Total Accounts</p>
                    <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#0f172a]">{{ summary.total }}</h2>
                </article>
                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Active Accounts</p>
                        <span class="text-xs font-bold text-[#50761b]">{{ activeRate }}% active</span>
                    </div>
                    <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#50761b]">{{ summary.active }}</h2>
                </article>
                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Farmer Accounts</p>
                    <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#b46d00]">{{ summary.farmers }}</h2>
                </article>
                <article class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">Office Accounts</p>
                    <h2 class="mt-3 text-[2.2rem] font-black leading-none text-[#0f5b46]">{{ summary.staff }}</h2>
                </article>
            </section>

            <div class="flex justify-end">
                <Link :href="urls.create" class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-4 py-2.5 text-sm font-black text-white transition hover:bg-[#01362a]">
                    Add User
                </Link>
            </div>

            <section class="rounded-[1.35rem] border border-[#dde4de] bg-white p-4 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <div class="grid gap-4 xl:grid-cols-[1fr_1fr_auto] xl:items-end">
                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select v-model="filterForm.status" class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                            <option v-for="option in filterOptions.statuses" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Account Type</span>
                        <select v-model="filterForm.account_type" class="w-full rounded-xl border border-[#dbe3dd] bg-white px-4 py-3 text-sm text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                            <option v-for="option in filterOptions.accountTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <div class="flex items-center gap-3 xl:justify-end">
                        <button type="button" class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-6 py-3 text-sm font-black text-white transition hover:bg-[#01362a]" @click="applyFilters">
                            Apply Filters
                        </button>
                        <button type="button" class="inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]" @click="resetFilters">
                            Reset
                        </button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[1.35rem] border border-[#dde4de] bg-white shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <div class="border-b border-[#edf2ee] bg-[#fbfcfb] px-6 py-4">
                    <h2 class="text-lg font-black text-[#0f172a]">User Records</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="bg-[#f9fbfa]">
                            <tr class="border-b border-[#e8eeea] text-left text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#64748b]">
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Role</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Linked Record</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-b border-[#edf2ee] transition hover:bg-[#fcfefd]">
                                <td class="px-6 py-5 align-top">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#edf4ef] text-sm font-black text-[#0f5b46]">
                                            {{ initials(user.name) }}
                                        </div>
                                        <div class="space-y-1">
                                            <p class="font-bold leading-5 text-[#0f172a]">{{ user.name }}</p>
                                            <p class="text-xs text-[#64748b]">{{ user.email }}</p>
                                            <p class="text-xs text-[#64748b]">Created {{ user.createdAt }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div class="space-y-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold" :class="roleTone(user.role)">
                                            {{ user.role }}
                                        </span>
                                        <p class="text-xs text-[#64748b]">{{ user.accountType }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold" :class="statusTone(user.status)">
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div v-if="user.officeProfile" class="space-y-1">
                                        <p class="font-semibold text-[#0f172a]">{{ user.officeProfile.employeeId || 'Office profile' }}</p>
                                        <p class="text-xs text-[#64748b]">{{ user.officeProfile.jobTitle || 'No job title' }}</p>
                                    </div>
                                    <p v-else class="text-sm text-[#64748b]">{{ user.accountType }}</p>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div class="flex items-center justify-end gap-4 text-sm">
                                        <Link :href="user.actions.show" class="font-medium text-[#014d3c] transition hover:underline">View</Link>
                                        <Link :href="user.actions.edit" class="font-medium text-[#1f2937] transition hover:underline">Edit</Link>
                                        <button v-if="user.actions.archive" type="button" class="font-medium text-[#c05c3c] transition hover:underline disabled:cursor-not-allowed disabled:opacity-50" :disabled="archivingId !== null" @click="archiveUser(user)">
                                            {{ archivingId === user.id ? 'Archiving...' : 'Archive' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center text-sm text-[#64748b]">No user accounts found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-[#edf2ee] bg-[#fbfcfb] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[#64748b]">Showing {{ users.from || 0 }}-{{ users.to || 0 }} of {{ users.total }} entries</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in users.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-[#dbe3dd] px-3 text-sm text-[#9aa6a1]" v-html="link.label" />
                            <Link
                                v-else
                                :href="link.url"
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border px-3 text-sm font-bold transition"
                                :class="link.active ? 'border-[#014d3c] bg-[#014d3c] text-white' : 'border-[#dbe3dd] bg-white text-[#64748b] hover:bg-[#f4f7f5]'"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
