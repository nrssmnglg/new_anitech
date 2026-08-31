<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    farmer: { type: Object, required: true },
    defaultYear: { type: Number, required: true },
    amountDue: { type: Number, required: true },
    paymentBreakdown: { type: Object, required: true },
    storeUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
    showFarmerUrl: { type: String, required: true },
});

const form = useForm({
    farmer_id: props.farmer.id,
    year: props.defaultYear,
    remarks: '',
    payment_method: 'cash',
    reference_no: '',
    amount_paid: props.amountDue,
    paid_at: new Date(Date.now() - (new Date().getTimezoneOffset() * 60 * 1000)).toISOString().slice(0, 16),
});

function submit() {
    form.post(props.storeUrl, {
        preserveScroll: true,
    });
}

const memberTypeCode = computed(() => props.farmer.memberType?.code || 'N/A');
const memberTypeName = computed(() => props.farmer.memberType?.name || 'Not assigned');
</script>

<template>
    <Head title="Process Renewal" />

    <AdminLayout title="Process Renewal">
        <div>
            <section class="grid gap-4 xl:grid-cols-12">
                <div class="xl:col-span-8">
                    <section class="overflow-hidden rounded-[12px] border border-[#cbd4cf] bg-white">
                        <div class="flex flex-col gap-2 border-b border-[#e1e3e2] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-[#003629]">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#eef5f1] text-[#003629]">+</span>
                                Renewal Setup
                            </h2>
                        </div>

                        <form class="space-y-4 px-4 py-4" @submit.prevent="submit">
                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="space-y-1.5">
                                    <span class="block text-[0.68rem] font-black uppercase tracking-[0.1em] text-[#5f6c67]">Renewal Year</span>
                                    <input v-model="form.year" type="number" min="2000" :max="new Date().getFullYear() + 1" class="w-full rounded-md border border-[#c0c9c3] bg-white px-3 py-2 text-xs text-[#191c1c] outline-none transition focus:border-[#003629]">
                                </label>

                                <label class="space-y-1.5">
                                    <span class="block text-[0.68rem] font-black uppercase tracking-[0.1em] text-[#5f6c67]">Renewal Type</span>
                                    <input value="Walk-In" type="text" readonly class="w-full cursor-not-allowed rounded-md border border-[#d4dad7] bg-[#eceeed] px-3 py-2 text-xs text-[#5f6c67]">
                                </label>
                            </div>

                            <label class="space-y-1.5">
                                <span class="block text-[0.68rem] font-black uppercase tracking-[0.1em] text-[#5f6c67]">Remarks</span>
                                <textarea v-model="form.remarks" rows="3" placeholder="Optional renewal notes..." class="w-full resize-none rounded-md border border-[#c0c9c3] bg-white px-3 py-2 text-xs text-[#191c1c] outline-none transition focus:border-[#003629]"></textarea>
                            </label>

                            <div class="grid gap-3 border-t border-[#e1e3e2] pt-3 md:grid-cols-2">
                                <label class="space-y-1.5">
                                    <span class="block text-[0.68rem] font-black uppercase tracking-[0.1em] text-[#5f6c67]">Payment Method</span>
                                    <select v-model="form.payment_method" class="w-full rounded-md border border-[#c0c9c3] bg-white px-3 py-2 text-xs text-[#191c1c] outline-none focus:border-[#003629]">
                                        <option value="cash">Cash</option>
                                        <option value="gcash">GCash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </label>
                                <label class="space-y-1.5">
                                    <span class="block text-[0.68rem] font-black uppercase tracking-[0.1em] text-[#5f6c67]">Reference Number</span>
                                    <input v-model="form.reference_no" type="text" placeholder="OR No. / Transaction ID" class="w-full rounded-md border border-[#c0c9c3] bg-white px-3 py-2 text-xs text-[#191c1c] outline-none focus:border-[#003629]">
                                </label>
                                <label class="space-y-1.5">
                                    <span class="block text-[0.68rem] font-black uppercase tracking-[0.1em] text-[#5f6c67]">Amount Paid</span>
                                    <input v-model="form.amount_paid" type="number" min="0.01" step="0.01" class="w-full rounded-md border border-[#c0c9c3] bg-white px-3 py-2 text-xs text-[#191c1c] outline-none focus:border-[#003629]">
                                </label>
                                <label class="space-y-1.5">
                                    <span class="block text-[0.68rem] font-black uppercase tracking-[0.1em] text-[#5f6c67]">Payment Date</span>
                                    <input v-model="form.paid_at" type="datetime-local" class="w-full rounded-md border border-[#c0c9c3] bg-white px-3 py-2 text-xs text-[#191c1c] outline-none focus:border-[#003629]">
                                </label>
                            </div>

                            <div class="flex flex-col gap-2 border-t border-[#e1e3e2] pt-3 sm:flex-row sm:items-center sm:justify-end">
                                <Link :href="indexUrl" class="inline-flex h-9 items-center justify-center rounded-md border border-[#d7e0db] px-4 text-xs font-normal text-[#5f6c67] transition hover:bg-[#f5f8f6]">
                                    Cancel
                                </Link>
                                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#003629] px-5 text-xs font-semibold text-white transition hover:bg-[#0e4638] disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                                    {{ form.processing ? 'Processing...' : 'Create & Complete Renewal' }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="xl:col-span-4">
                    <section class="overflow-hidden rounded-[12px] border border-[#cbd4cf] bg-white">
                        <div class="h-10 bg-[linear-gradient(135deg,_#1b4d3e_0%,_#416918_100%)]"></div>
                        <div class="relative px-4 pb-4">
                            <div class="-mt-6 mx-auto flex h-12 w-12 items-center justify-center rounded-full border-2 border-white bg-[#c9eadf] text-sm font-black text-[#16332c]">
                                {{ farmer.fullName?.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() }}
                            </div>

                            <div class="mt-2 text-center">
                                <h2 class="text-sm font-semibold text-[#191c1c]">{{ farmer.fullName }}</h2>
                                <p class="mt-1 text-[0.62rem] font-semibold uppercase tracking-[0.1em] text-[#003629]">Active Member</p>
                            </div>

                            <div class="mt-3 grid gap-2 border-t border-[#e1e3e2] pt-3 sm:grid-cols-2 xl:grid-cols-1">
                                <div class="rounded-md bg-[#f2f4f3] px-3 py-2">
                                    <div>
                                        <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Farmer Code</p>
                                        <p class="mt-1 text-sm font-semibold text-[#191c1c]">{{ farmer.farmerCode || 'Unassigned' }}</p>
                                    </div>
                                </div>
                                <div class="rounded-md bg-[#f2f4f3] px-3 py-2">
                                    <div>
                                        <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Member Type</p>
                                        <p class="mt-1 text-sm font-semibold text-[#191c1c]">{{ memberTypeCode }} - {{ memberTypeName }}</p>
                                    </div>
                                </div>
                                <div class="rounded-md bg-[#f2f4f3] px-3 py-2 sm:col-span-2 xl:col-span-1">
                                    <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Association</p>
                                    <p class="mt-1 text-xs font-medium leading-5 text-[#191c1c]">{{ farmer.association || 'No association' }}</p>
                                </div>
                                <div class="rounded-md border border-[#dfe5e1] bg-white px-3 py-2 sm:col-span-2 xl:col-span-1">
                                    <p class="text-[0.62rem] font-black uppercase tracking-[0.12em] text-[#7a8781]">Payment Breakdown</p>
                                    <div class="mt-2 space-y-1.5 text-xs">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-[#5f6c67]">Annual Due</span>
                                            <span class="font-medium text-[#191c1c]">PHP {{ Number(paymentBreakdown.annualDue || 0).toFixed(2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-[#5f6c67]">Mortuary Fee</span>
                                            <span class="font-medium text-[#191c1c]">PHP {{ Number(paymentBreakdown.mortuaryFee || 0).toFixed(2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 border-t border-[#e1e3e2] pt-1.5">
                                            <span class="font-semibold text-[#003629]">Total Due</span>
                                            <span class="font-semibold text-[#003629]">PHP {{ Number(paymentBreakdown.total || 0).toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 text-center">
                                <Link :href="showFarmerUrl" class="inline-flex text-xs font-normal text-[#003629] transition hover:underline">
                                    Back to Farmer Record
                                </Link>
                            </div>
                        </div>
                    </section>

                </div>
            </section>
        </div>
    </AdminLayout>
</template>
