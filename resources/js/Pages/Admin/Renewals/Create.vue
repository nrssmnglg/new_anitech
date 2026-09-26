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

const initials = computed(() => {
    return String(props.farmer.fullName || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase() || '?';
});

const currencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
});
</script>

<template>
    <Head :title="`Process Renewal - ${farmer.fullName}`" />

    <AdminLayout title="Process Renewal">
        <div class="mx-auto w-full max-w-[1536px] space-y-4 pb-10">
            <!-- Page Header Row -->
            <div class="flex flex-col gap-3 px-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="indexUrl" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 transition hover:text-[#003629]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                            </svg>
                            <span>Renewal Processing</span>
                        </Link>
                        <span class="text-slate-300">/</span>
                        <span class="text-xs font-semibold text-slate-700">Process Renewal</span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold tracking-tight text-[#0f172a] sm:text-2xl">{{ farmer.fullName }}</h1>
                        <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-xs font-bold text-slate-700">
                            {{ farmer.farmerCode }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="showFarmerUrl"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 active:scale-95"
                    >
                        View Record
                    </Link>
                    <Link
                        :href="indexUrl"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#003629] px-4 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a] active:scale-95"
                    >
                        Back to Queue
                    </Link>
                </div>
            </div>

            <!-- Main Layout Grid -->
            <div class="grid gap-4 xl:grid-cols-12">
                <!-- Renewal Form (8 columns) -->
                <div class="xl:col-span-8">
                    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-2 w-2 rounded-full bg-[#014d3c]"></span>
                                <h2 class="text-xs font-bold text-[#0f172a]">Renewal Setup &amp; Payment Collection</h2>
                            </div>
                            <span class="rounded-lg bg-[#f0fdf4] px-2 py-0.5 text-xs font-bold text-[#15803d]">
                                {{ currencyFormatter.format(Number(amountDue || 0)) }} Due
                            </span>
                        </div>

                        <form class="p-5 space-y-4" @submit.prevent="submit">
                            <!-- Setup Section -->
                            <div class="grid gap-3.5 sm:grid-cols-2">
                                <label class="block space-y-1.5">
                                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Renewal Year</span>
                                    <input
                                        v-model="form.year"
                                        type="number"
                                        min="2000"
                                        :max="new Date().getFullYear() + 1"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                    >
                                </label>

                                <div class="space-y-1.5">
                                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Intake Channel</span>
                                    <div class="flex h-9 items-center rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-3 text-xs font-semibold text-[#475569]">
                                        Walk-In / Over-the-Counter
                                    </div>
                                </div>
                            </div>

                            <label class="block space-y-1.5">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Remarks &amp; Notes</span>
                                <textarea
                                    v-model="form.remarks"
                                    rows="2"
                                    placeholder="Optional notes or observations regarding this renewal..."
                                    class="w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] p-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                ></textarea>
                            </label>

                            <!-- Payment Section -->
                            <div class="border-t border-[#f1f5f9] pt-4">
                                <p class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-[#64748b] mb-3">Payment Details</p>
                                <div class="grid gap-3.5 sm:grid-cols-2">
                                    <label class="block space-y-1.5">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Payment Method</span>
                                        <select
                                            v-model="form.payment_method"
                                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                        >
                                            <option value="cash">Cash</option>
                                            <option value="gcash">GCash</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </label>

                                    <label class="block space-y-1.5">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Reference Number</span>
                                        <input
                                            v-model="form.reference_no"
                                            type="text"
                                            placeholder="OR No. / Reference Code"
                                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                        >
                                    </label>

                                    <label class="block space-y-1.5">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Amount Paid (PHP)</span>
                                        <input
                                            v-model="form.amount_paid"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs font-bold text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                        >
                                    </label>

                                    <label class="block space-y-1.5">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Payment Timestamp</span>
                                        <input
                                            v-model="form.paid_at"
                                            type="datetime-local"
                                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                        >
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-end gap-2 border-t border-[#f1f5f9] pt-4">
                                <Link
                                    :href="indexUrl"
                                    class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-4 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Processing Renewal...' : 'Complete & Record Renewal' }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- Farmer Profile & Breakdown Card (4 columns) -->
                <div class="xl:col-span-4 space-y-4">
                    <section class="rounded-2xl border border-[#dde4de] bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-[#e6f5ec] text-sm font-bold text-[#0f6b45]">
                                {{ initials }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm font-bold text-[#0f172a]">{{ farmer.fullName }}</h3>
                                <div class="mt-0.5 flex items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-md bg-[#f0fdf4] px-1.5 py-0.5 text-[0.62rem] font-bold text-[#15803d]">Active Member</span>
                                    <span class="font-mono text-[0.68rem] text-[#64748b]">{{ farmer.farmerCode || 'Unassigned' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Details -->
                        <div class="mt-4 space-y-2 border-t border-[#f1f5f9] pt-3.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-[#64748b]">Member Type</span>
                                <span class="font-semibold text-[#0f172a]">{{ memberTypeCode }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-[#64748b]">Association</span>
                                <span class="max-w-[180px] truncate font-semibold text-[#0f172a]" :title="farmer.association">{{ farmer.association || 'None assigned' }}</span>
                            </div>
                        </div>

                        <!-- Itemized Payment Breakdown Card -->
                        <div class="mt-4 rounded-xl border border-[#bbf7d0] bg-[#f0fdf4]/50 p-3.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#166534]">Payment Breakdown</span>
                            <div class="mt-2 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between text-[#475569]">
                                    <span>Annual Membership Due</span>
                                    <span class="font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(paymentBreakdown.annualDue || 0)) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[#475569]">
                                    <span>Mortuary Contribution</span>
                                    <span class="font-bold text-[#0f172a]">{{ currencyFormatter.format(Number(paymentBreakdown.mortuaryFee || 0)) }}</span>
                                </div>
                                <div class="flex items-center justify-between border-t border-[#bbf7d0] pt-2 text-[#014d3c]">
                                    <span class="font-bold">Total Payable</span>
                                    <span class="text-sm font-black">{{ currencyFormatter.format(Number(paymentBreakdown.total || 0)) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <Link :href="showFarmerUrl" class="text-xs font-semibold text-[#014d3c] hover:underline">
                                &larr; Return to Farmer Profile
                            </Link>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
