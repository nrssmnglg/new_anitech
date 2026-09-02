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
        <div class="space-y-3">
            <section class="flex flex-col gap-3 rounded-xl bg-[#003629] px-4 py-3.5 text-white sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">Account administration</p>
                    <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">User Management</h1>
                </div>
                <Link :href="urls.create" class="inline-flex h-8 items-center justify-center rounded-md bg-white px-3 text-[0.68rem] font-semibold text-[#014d3c] transition hover:bg-[#f3f7f5]">
                    Add User
                </Link>
            </section>

            <section class="grid grid-cols-2 gap-2 lg:grid-cols-4">
                <article class="rounded-lg border border-[#dbe4de] bg-white px-3 py-2.5">
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Total Accounts</p>
                    <h2 class="mt-1 text-xl font-semibold leading-none text-[#0f172a]">{{ summary.total }}</h2>
                </article>
                <article class="rounded-lg border border-[#dbe4de] bg-white px-3 py-2.5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Active Accounts</p>
                        <span class="text-[0.6rem] font-semibold text-[#50761b]">{{ activeRate }}%</span>
                    </div>
                    <h2 class="mt-1 text-xl font-semibold leading-none text-[#50761b]">{{ summary.active }}</h2>
                </article>
                <article class="rounded-lg border border-[#dbe4de] bg-white px-3 py-2.5">
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Farmer Accounts</p>
                    <h2 class="mt-1 text-xl font-semibold leading-none text-[#b46d00]">{{ summary.farmers }}</h2>
                </article>
                <article class="rounded-lg border border-[#dbe4de] bg-white px-3 py-2.5">
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Office Accounts</p>
                    <h2 class="mt-1 text-xl font-semibold leading-none text-[#0f5b46]">{{ summary.staff }}</h2>
                </article>
            </section>

            <section class="rounded-lg border border-[#dde4de] bg-white p-3">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_auto] xl:items-end">
                    <label class="space-y-1">
                        <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select v-model="filterForm.status" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                            <option v-for="option in filterOptions.statuses" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-1">
                        <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Account Type</span>
                        <select v-model="filterForm.account_type" class="h-9 w-full rounded-md border border-[#dbe3dd] bg-white px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c]">
                            <option v-for="option in filterOptions.accountTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <div class="flex items-center gap-2 xl:justify-end">
                        <button type="button" class="inline-flex h-9 items-center justify-center rounded-md bg-[#014d3c] px-4 text-xs font-semibold text-white transition hover:bg-[#01362a]" @click="applyFilters">
                            Apply
                        </button>
                        <button type="button" class="inline-flex h-9 items-center justify-center rounded-md border border-[#dbe3dd] px-3 text-xs font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]" @click="resetFilters">
                            Reset
                        </button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-[#dde4de] bg-white">
                <div class="flex items-center justify-between border-b border-[#edf2ee] px-4 py-3">
                    <h2 class="text-sm font-semibold text-[#0f172a]">User records</h2>
                    <span class="text-[0.65rem] text-[#64748b]">{{ users.total }} accounts</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead class="bg-[#f9fbfa]">
                            <tr class="border-b border-[#e8eeea] text-left text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">
                                <th class="px-4 py-2.5">User</th>
                                <th class="px-4 py-2.5">Role</th>
                                <th class="px-4 py-2.5">Status</th>
                                <th class="px-4 py-2.5">Linked Record</th>
                                <th class="px-4 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-b border-[#edf2ee] transition hover:bg-[#fcfefd]">
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#edf4ef] text-[0.65rem] font-semibold text-[#0f5b46]">
                                            {{ initials(user.name) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold leading-4 text-[#0f172a]">{{ user.name }}</p>
                                            <p class="text-[0.65rem] text-[#64748b]">{{ user.email }}</p>
                                            <p class="text-[0.6rem] text-[#8a9691]">Created {{ user.createdAt }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="space-y-1">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[0.62rem] font-semibold" :class="roleTone(user.role)">
                                            {{ user.role }}
                                        </span>
                                        <p class="text-[0.62rem] text-[#64748b]">{{ user.accountType }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[0.62rem] font-semibold" :class="statusTone(user.status)">
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div v-if="user.officeProfile" class="space-y-1">
                                        <p class="font-semibold text-[#0f172a]">{{ user.officeProfile.employeeId || 'Office profile' }}</p>
                                        <p class="text-[0.65rem] text-[#64748b]">{{ user.officeProfile.jobTitle || 'No job title' }}</p>
                                    </div>
                                    <div v-else-if="user.farmer" class="space-y-1">
                                        <p class="font-semibold text-[#0f172a]">{{ user.farmer.code }}</p>
                                        <p class="text-[0.65rem] text-[#64748b]">Mobile login linked</p>
                                    </div>
                                    <p v-else class="text-xs text-[#64748b]">{{ user.accountType }}</p>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link :href="user.actions.show" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#dbe4de] text-[#014d3c] transition hover:bg-[#edf6f2]" title="View user" aria-label="View user">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                                <circle cx="12" cy="12" r="2.5" />
                                            </svg>
                                        </Link>
                                        <Link :href="user.actions.edit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#dbe4de] text-[#374151] transition hover:bg-[#f3f5f4]" title="Edit user" aria-label="Edit user">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.5 5.5 4 4M4 20l3.7-.8L19 7.9a1.4 1.4 0 0 0 0-2l-.9-.9a1.4 1.4 0 0 0-2 0L4.8 16.3 4 20Z" />
                                            </svg>
                                        </Link>
                                        <button v-if="user.actions.archive" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-[#efcbc5] text-[#c05c3c] transition hover:bg-[#fff1ee] disabled:cursor-not-allowed disabled:opacity-50" :disabled="archivingId !== null" title="Archive user" aria-label="Archive user" @click="archiveUser(user)">
                                            <svg class="h-4 w-4" :class="{ 'animate-pulse': archivingId === user.id }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5h16M6 7.5V20h12V7.5M3.5 4h17v3.5h-17zM9.5 11.5h5" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-xs text-[#64748b]">No user accounts found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-2 border-t border-[#edf2ee] bg-[#fbfcfb] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.65rem] text-[#64748b]">Showing {{ users.from || 0 }}-{{ users.to || 0 }} of {{ users.total }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in users.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-[#dbe3dd] px-2 text-xs text-[#9aa6a1]" v-html="link.label" />
                            <Link
                                v-else
                                :href="link.url"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-xs font-semibold transition"
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
