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
    <div class="space-y-3">
        <section class="rounded-[12px] border border-[#cbd4cf] bg-white p-3.5">
            <p class="text-[0.7rem] font-black uppercase tracking-[0.08em] text-[#263d35]">Registry Actions</p>
            <p v-if="farmer?.inactiveReason" class="mt-2 rounded-[16px] border border-[#f0d5d5] bg-[#fff6f6] px-3 py-2.5 text-xs text-[#8f3f3f]">
                {{ farmer.inactiveReason }}
            </p>
            <div class="mt-3 grid gap-2.5">
                <Link :href="urls.createApplication" class="inline-flex items-center justify-between rounded-md bg-[#003629] px-3.5 py-2.5 text-xs font-extrabold text-white transition hover:bg-[#0d4637]">
                    <span>New Membership</span>
                    <span>&rsaquo;</span>
                </Link>
                <Link
                    v-if="renewal.isAvailable"
                    :href="urls.renewalCreate"
                    class="inline-flex items-center justify-between rounded-md bg-[#69eab6] px-3.5 py-2.5 text-xs font-extrabold text-[#0e2000] transition hover:opacity-90"
                >
                    <span>Process Renewal</span>
                    <span>&rsaquo;</span>
                </Link>
                <button
                    v-else
                    type="button"
                    class="inline-flex items-center justify-between rounded-md bg-[#69eab6] px-3.5 py-2.5 text-xs font-extrabold text-[#0e2000] transition hover:opacity-90"
                    @click="handleRenewalClick"
                >
                    <span>Process Renewal</span>
                    <span>&rsaquo;</span>
                </button>
                <button
                    v-if="permissions.canReactivate && farmer?.status?.value === 'inactive'"
                    type="button"
                    class="inline-flex items-center justify-between rounded-[16px] border border-[#d7e0db] bg-[#f4faf6] px-4 py-3 text-xs font-extrabold text-[#003629] transition hover:bg-[#ebf6ef]"
                    @click="reactivateFarmer"
                >
                    <span>Reactivate Farmer</span>
                    <span>&rsaquo;</span>
                </button>
                <div class="grid grid-cols-2 gap-2.5">
                    <Link v-if="permissions.canEdit" :href="urls.edit" class="inline-flex items-center justify-center rounded-[16px] border border-[#d7e0db] px-4 py-2.5 text-xs font-bold text-[#191c1c] transition hover:bg-[#f4f7f5]">
                        Edit
                    </Link>
                    <Link :href="urls.index" class="inline-flex items-center justify-center rounded-[16px] border border-[#d7e0db] px-4 py-2.5 text-xs font-bold text-[#191c1c] transition hover:bg-[#f4f7f5]">
                        Back
                    </Link>
                </div>
            </div>
        </section>

        <div
            v-if="showRenewalDialog"
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f172a]/45 px-4"
            @click.self="showRenewalDialog = false"
        >
            <div class="w-full max-w-md rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_20px_56px_rgba(15,23,42,0.18)]">
                <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Renewal Status</p>
                <h3 class="mt-2 text-lg font-black text-[#1a2420]">{{ renewal.dialogTitle || 'Farmer Not Eligible for Renewal' }}</h3>
                <p class="mt-2 text-xs leading-6 text-[#5f6c67]">{{ renewal.dialogMessage || renewal.disabledReason || 'This farmer cannot be processed for renewal right now.' }}</p>
                <div class="mt-5 flex justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-[16px] bg-[#003629] px-4 py-2.5 text-xs font-extrabold text-white transition hover:bg-[#0d4637]"
                        @click="showRenewalDialog = false"
                    >
                        OK
                    </button>
                </div>
            </div>
        </div>

        <section class="min-h-[180px] rounded-[12px] border border-[#cbd4cf] bg-white p-3.5">
            <p class="text-[0.7rem] font-black uppercase tracking-[0.08em] text-[#263d35]">Latest Activity</p>
            <div class="mt-3 space-y-2 text-xs">
                <div v-for="activity in activities" :key="activity.key" class="rounded-md border border-[#d8e0dc] bg-[#f7f9fa] p-2.5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-semibold text-[#191c1c]">{{ activity.title }}</p>
                        <span class="shrink-0 text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#376757]">{{ activity.type }}</span>
                    </div>
                    <p v-if="activity.subtitle" class="mt-1 text-[0.7rem] text-[#5f6c67]">{{ activity.subtitle }}</p>
                    <p class="mt-1 text-[0.68rem] text-[#7a8781]">{{ activity.occurredAt }}</p>
                </div>
                <p v-if="!activities.length" class="py-3 text-center text-[0.7rem] text-[#7a8781]">No activity recorded yet.</p>
            </div>
        </section>
    </div>
</template>
