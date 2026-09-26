<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    farmer: { type: Object, required: false, default: null },
    activities: { type: Array, default: () => [] },
    latestApplication: { type: Object, default: null },
    latestPayment: { type: Object, default: null },
    latestLedger: { type: Object, default: null },
    urls: { type: Object, required: true },
    permissions: { type: Object, required: true },
    renewal: { type: Object, required: true },
});

const showRenewalDialog = ref(false);

function handleRenewalClick() {
    if (props.renewal?.isAvailable) {
        return;
    }

    showRenewalDialog.value = true;
}

function reactivateFarmer() {
    if (!props.urls?.reactivate || !window.confirm('Reactivate this farmer record?')) {
        return;
    }

    router.post(props.urls.reactivate, {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="space-y-4">
        <!-- Actions Card -->
        <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
            <div class="border-b border-[#f1f5f9] px-4 py-3">
                <h3 class="text-xs font-bold text-[#0f172a]">Registry Actions</h3>
            </div>

            <div class="p-4 space-y-3">
                <!-- Inactive warning banner if applicable -->
                <div v-if="farmer?.inactiveReason" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                    <p class="font-bold text-rose-900">Record Inactive</p>
                    <p class="mt-0.5 text-[0.68rem] text-rose-700">{{ farmer.inactiveReason }}</p>
                </div>

                <!-- Process Renewal Button -->
                <Link
                    v-if="renewal.isAvailable"
                    :href="urls.renewalCreate"
                    class="group flex w-full items-center justify-between rounded-xl bg-gradient-to-r from-[#014d3c] to-[#0d6e55] px-4 py-3 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:shadow-md hover:from-[#01362a] hover:to-[#095945] active:scale-[0.98]"
                >
                    <span class="flex items-center gap-2">
                        <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#7ddfb8]" fill="currentColor">
                            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.451a.75.75 0 0 0 0-1.5H4.5a.75.75 0 0 0-.75.75v3.75a.75.75 0 0 0 1.5 0v-2.199l.312.311a7 7 0 0 0 11.75-3.418.75.75 0 0 0-1.5-.048ZM4.688 8.576a5.5 5.5 0 0 1 9.201-2.466l.312.311H11.75a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 .75-.75V3.421a.75.75 0 0 0-1.5 0v2.199l-.312-.311A7 7 0 0 0 2.688 8.727a.75.75 0 0 0 1.5.048l.5-.199Z" clip-rule="evenodd" />
                        </svg>
                        Process Renewal
                    </span>
                    <svg viewBox="0 0 20 20" class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                </Link>
                <button
                    v-else
                    type="button"
                    class="group flex w-full items-center justify-between rounded-xl border border-[#dde4de] bg-[#f8fafc] px-4 py-3 text-xs font-semibold text-[#64748b] transition-all hover:border-[#cbd5e1] hover:bg-[#f1f5f9]"
                    @click="handleRenewalClick"
                >
                    <span class="flex items-center gap-2">
                        <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#94a3b8]" fill="currentColor">
                            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.451a.75.75 0 0 0 0-1.5H4.5a.75.75 0 0 0-.75.75v3.75a.75.75 0 0 0 1.5 0v-2.199l.312.311a7 7 0 0 0 11.75-3.418.75.75 0 0 0-1.5-.048ZM4.688 8.576a5.5 5.5 0 0 1 9.201-2.466l.312.311H11.75a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 .75-.75V3.421a.75.75 0 0 0-1.5 0v2.199l-.312-.311A7 7 0 0 0 2.688 8.727a.75.75 0 0 0 1.5.048l.5-.199Z" clip-rule="evenodd" />
                        </svg>
                        Check Renewal Eligibility
                    </span>
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#d97706]">Unavailable</span>
                </button>

                <!-- New Application Link -->
                <Link
                    :href="urls.createApplication"
                    class="group flex w-full items-center justify-between rounded-xl border border-[#dde4de] bg-white px-4 py-2.5 text-xs font-semibold text-[#0f172a] transition-all hover:border-[#bbf7d0] hover:bg-[#f0fdf4]"
                >
                    <span class="flex items-center gap-2">
                        <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#15803d]" fill="currentColor"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                        New Membership Application
                    </span>
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#94a3b8] transition-transform group-hover:translate-x-0.5" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                </Link>

                <!-- Reactivate Button if inactive -->
                <button
                    v-if="permissions.canReactivate && farmer?.status?.value === 'inactive'"
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-4 py-2.5 text-xs font-bold text-[#15803d] transition-all hover:bg-[#dcfce7]"
                    @click="reactivateFarmer"
                >
                    <span>Reactivate Farmer Record</span>
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                </button>

                <!-- Edit and Back buttons -->
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <Link
                        v-if="permissions.canEdit"
                        :href="urls.edit"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-[#dde4de] bg-white px-3 text-xs font-semibold text-[#0f172a] shadow-sm transition-all hover:border-[#cbd5e1] hover:bg-[#f8fafc]"
                    >
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-[#64748b]" fill="currentColor"><path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/></svg>
                        Edit
                    </Link>
                    <Link
                        :href="urls.index"
                        class="inline-flex h-9 items-center justify-center rounded-xl border border-[#dde4de] bg-white px-3 text-xs font-semibold text-[#64748b] shadow-sm transition-all hover:border-[#cbd5e1] hover:bg-[#f8fafc]"
                    >
                        Back
                    </Link>
                </div>
            </div>
        </section>

        <!-- Renewal Dialog Modal -->
        <div
            v-if="showRenewalDialog"
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f172a]/50 px-4 backdrop-blur-sm"
            @click.self="showRenewalDialog = false"
        >
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-[#e2e8f0] bg-white p-6 shadow-2xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fef3c7] text-[#b45309]">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                </div>
                <h3 class="mt-3 text-base font-bold text-[#0f172a]">
                    {{ renewal.dialogTitle || 'Renewal Ineligible' }}
                </h3>
                <p class="mt-2 text-xs leading-5 text-[#64748b]">
                    {{ renewal.dialogMessage || renewal.disabledReason || 'This farmer cannot be processed for renewal under current policies.' }}
                </p>
                <div class="mt-5 flex justify-end">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-xl bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97]"
                        @click="showRenewalDialog = false"
                    >
                        Understood
                    </button>
                </div>
            </div>
        </div>

        <!-- Latest Activities Widget -->
        <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
            <div class="border-b border-[#f1f5f9] px-4 py-3">
                <h3 class="text-xs font-bold text-[#0f172a]">Latest Activities</h3>
            </div>
            <div class="p-4 space-y-2.5">
                <div
                    v-for="activity in activities"
                    :key="activity.key"
                    class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3 transition hover:bg-[#f1f5f9]"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-bold text-[#0f172a]">{{ activity.title }}</p>
                        <span class="rounded-md bg-white px-1.5 py-0.5 text-[0.58rem] font-bold uppercase tracking-wider text-[#014d3c] shadow-xs">
                            {{ activity.type }}
                        </span>
                    </div>
                    <p v-if="activity.subtitle" class="mt-1 text-[0.68rem] text-[#64748b]">{{ activity.subtitle }}</p>
                    <p class="mt-1 text-[0.62rem] text-[#94a3b8]">{{ activity.occurredAt }}</p>
                </div>
                <p v-if="!activities.length" class="py-4 text-center text-xs text-[#94a3b8]">No activities recorded yet.</p>
            </div>
        </section>
    </div>
</template>
