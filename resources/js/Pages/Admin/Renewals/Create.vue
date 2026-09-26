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
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/[0.15] text-white shadow-sm backdrop-blur-sm">
                            <svg viewBox="0 0 20 20" class="h-6 w-6 text-[#7ddfb8]" fill="currentColor">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.451a.75.75 0 0 0 0-1.5H4.5a.75.75 0 0 0-.75.75v3.75a.75.75 0 0 0 1.5 0v-2.199l.312.311a7 7 0 0 0 11.75-3.418.75.75 0 0 0-1.5-.048ZM4.688 8.576a5.5 5.5 0 0 1 9.201-2.466l.312.311H11.75a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 .75-.75V3.421a.75.75 0 0 0-1.5 0v2.199l-.312-.311A7 7 0 0 0 2.688 8.727a.75.75 0 0 0 1.5.048l.5-.199Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Membership Renewal</p>
                                <span class="rounded-md bg-white/10 px-2 py-0.5 font-mono text-[0.62rem] font-semibold text-white/90">
                                    {{ farmer.farmerCode }}
                                </span>
                            </div>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em] text-white sm:text-2xl">{{ farmer.fullName }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="showFarmerUrl"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/[0.18] bg-white/[0.08] px-3.5 text-xs font-semibold text-white shadow-sm backdrop-blur-sm transition-all duration-200 hover:bg-white/15 active:scale-[0.97]"
                        >
                            View Record
                        </Link>
                        <Link
                            :href="indexUrl"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-xs font-bold text-[#003629] shadow-sm transition-all duration-200 hover:bg-[#f0faf5] hover:shadow-md active:scale-[0.97]"
                        >
                            Renewal List
                        </Link>
                    </div>
                </div>
            </section>

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
                <div class="xl:col-span-4">
                    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-sm">
                        <!-- Top Accent Banner -->
                        <div class="h-14 bg-gradient-to-r from-[#003629] via-[#00483a] to-[#014d3c]"></div>

                        <div class="relative px-5 pb-5">
                            <!-- Avatar Overlap -->
                            <div class="-mt-7 mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border-2 border-white bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-base font-bold text-[#0f6b45] shadow-md">
                                {{ initials }}
                            </div>

                            <div class="mt-2 text-center">
                                <h3 class="text-sm font-bold text-[#0f172a]">{{ farmer.fullName }}</h3>
                                <p class="mt-0.5 text-[0.62rem] font-bold uppercase tracking-wider text-[#15803d]">Active Member</p>
                            </div>

                            <!-- Profile Details -->
                            <div class="mt-4 space-y-2.5 border-t border-[#f1f5f9] pt-4">
                                <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-2.5 flex items-center justify-between">
                                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Farmer Code</span>
                                    <span class="font-mono text-xs font-bold text-[#0f172a]">{{ farmer.farmerCode || 'Unassigned' }}</span>
                                </div>

                                <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-2.5 flex items-center justify-between">
                                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Member Type</span>
                                    <span class="text-xs font-semibold text-[#0f172a]">{{ memberTypeCode }}</span>
                                </div>

                                <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-2.5">
                                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Association</span>
                                    <p class="mt-0.5 text-xs font-semibold text-[#0f172a]">{{ farmer.association || 'No association assigned' }}</p>
                                </div>
                            </div>

                            <!-- Itemized Payment Breakdown Card -->
                            <div class="mt-4 rounded-xl border border-[#bbf7d0] bg-gradient-to-br from-[#f0fdf4] to-white p-3.5">
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
                                <Link :href="showFarmerUrl" class="text-xs font-bold text-[#014d3c] hover:underline">
                                    &larr; Return to Farmer Profile
                                </Link>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
