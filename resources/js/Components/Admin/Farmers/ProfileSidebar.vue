<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    farmer: { type: Object, required: false, default: null },
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
        <section class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Registry Actions</p>
            <p v-if="farmer?.inactiveReason" class="mt-2 rounded-[16px] border border-[#f0d5d5] bg-[#fff6f6] px-3 py-2.5 text-xs text-[#8f3f3f]">
                {{ farmer.inactiveReason }}
            </p>
            <div class="mt-3 grid gap-2.5">
                <Link :href="urls.createApplication" class="inline-flex items-center justify-between rounded-[16px] bg-[#003629] px-4 py-3 text-xs font-extrabold text-white transition hover:bg-[#0d4637]">
                    <span>New Membership</span>
                    <span>&rsaquo;</span>
                </Link>
                <Link
                    v-if="renewal.isAvailable"
                    :href="urls.renewalCreate"
                    class="inline-flex items-center justify-between rounded-[16px] bg-[#c0f190] px-4 py-3 text-xs font-extrabold text-[#0e2000] transition hover:opacity-90"
                >
                    <span>Process Renewal</span>
                    <span>&rsaquo;</span>
                </Link>
                <button
                    v-else
                    type="button"
                    class="inline-flex items-center justify-between rounded-[16px] bg-[#c0f190] px-4 py-3 text-xs font-extrabold text-[#0e2000] transition hover:opacity-90"
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

        <section class="rounded-[20px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
            <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Latest Activity</p>
            <div class="mt-3 space-y-3 text-xs">
                <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                    <p class="font-bold text-[#191c1c]">Latest Application</p>
                    <p class="mt-1.5 text-[#5f6c67]">{{ latestApplication?.applicationNo || 'No application yet' }}</p>
                    <p class="mt-1 text-xs text-[#7a8781]">{{ latestApplication?.submittedAt || '' }}</p>
                </div>
                <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                    <p class="font-bold text-[#191c1c]">Latest Payment</p>
                    <p class="mt-1.5 text-[#5f6c67]">
                        <span v-if="latestPayment">PHP {{ Number(latestPayment.amountPaid || 0).toFixed(2) }} on {{ latestPayment.paidAt }}</span>
                        <span v-else>No payment yet</span>
                    </p>
                </div>
                <div class="rounded-[16px] border border-[#e3eae6] bg-[#f7faf8] p-3">
                    <p class="font-bold text-[#191c1c]">Latest Ledger</p>
                    <p class="mt-1.5 text-[#5f6c67]">{{ latestLedger?.year || 'No ledger yet' }} {{ latestLedger?.paymentStatus || '' }}</p>
                </div>
            </div>
        </section>
    </div>
</template>
