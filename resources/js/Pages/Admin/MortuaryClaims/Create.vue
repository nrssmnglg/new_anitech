<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    eligibleLedgers: { type: Array, required: true },
    requirements: { type: Array, required: true },
    selectedLedgerId: { type: String, default: null },
    storeUrl: { type: String, required: true },
    queueUrl: { type: String, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const pageErrors = computed(() => page.props.errors || {});
const requirementEntries = Object.fromEntries(
    props.requirements.map((requirement) => [requirement.code, { is_received: false }]),
);

const selectedLedgerId = ref(props.selectedLedgerId || props.eligibleLedgers[0]?.id || null);

const selectedLedger = computed(() => (
    props.eligibleLedgers.find((ledger) => ledger.id === selectedLedgerId.value)
    || props.eligibleLedgers[0]
    || null
));

const selectedInitials = computed(() => (
    selectedLedger.value?.farmer.fullName
        ?.split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase() || 'MC'
));

const form = useForm({
    membership_ledger_id: selectedLedger.value?.id || '',
    claim_date: new Date().toISOString().slice(0, 10),
    claim_amount: selectedLedger.value?.expectedClaimAmount || '',
    claimer_name: '',
    claimer_relationship: '',
    claimer_contact_number: '',
    claimer_address: '',
    requirements: requirementEntries,
    remarks: '',
});

watch(selectedLedger, (newLedger) => {
    if (newLedger) {
        form.membership_ledger_id = newLedger.id;
        form.claim_amount = newLedger.expectedClaimAmount;
    }
});

const checklistComplete = computed(() => (
    props.requirements
        .filter((requirement) => requirement.isRequired)
        .every((requirement) => form.requirements?.[requirement.code]?.is_received)
));

function submit() {
    form.post(props.storeUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="File Mortuary Claim" />

    <AdminLayout title="File Mortuary Claim">
        <div class="mx-auto max-w-[1536px] space-y-4">
            <!-- Sleek Top Header Row -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-[#0f172a] sm:text-2xl">File Mortuary Claim</h1>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="queueUrl"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-3.5 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                    >
                        &larr; Back to Claim Queue
                    </Link>
                </div>
            </div>

            <!-- Flash alerts -->
            <div v-if="flashSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
                {{ flashSuccess }}
            </div>

            <!-- Validation & System Error alerts -->
            <div v-if="pageErrors.mortuary || form.errors.mortuary || form.errors.membership_ledger_id" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
                {{ pageErrors.mortuary || form.errors.mortuary || form.errors.membership_ledger_id }}
            </div>

            <section v-if="eligibleLedgers.length === 0" class="rounded-2xl border border-[#dde4de] bg-white p-10 text-center shadow-xs">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#f1f5f3] text-[#014d3c]">
                    <svg viewBox="0 0 20 20" class="h-6 w-6" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h2 class="mt-3 text-sm font-bold text-[#0f172a]">No Eligible Farmers Available</h2>
                <p class="mx-auto mt-1 max-w-lg text-xs text-[#64748b]">
                    A mortuary claim can only be created for an active member with settled mortuary contributions and no existing open claim.
                </p>
                <div class="mt-4">
                    <Link :href="queueUrl" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-xs">
                        Return to Queue
                    </Link>
                </div>
            </section>

            <template v-else>
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <!-- Left Sidebar (4 cols) -->
                    <div class="space-y-4 xl:col-span-4">
                        <!-- Active Context Card -->
                        <section class="rounded-2xl border border-[#dde4de] bg-white p-5 shadow-xs">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e6f5ec] text-sm font-bold text-[#0f6b45]">
                                    {{ selectedInitials }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-sm font-bold text-[#0f172a]">{{ selectedLedger?.farmer.fullName }}</h3>
                                    <div class="mt-0.5 flex items-center gap-1.5">
                                        <span class="inline-flex items-center rounded-md bg-[#f0fdf4] px-1.5 py-0.5 text-[0.62rem] font-bold text-[#15803d]">Eligible</span>
                                        <span class="font-mono text-[0.68rem] text-[#64748b]">{{ selectedLedger?.farmer.farmerCode || 'No code' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Claim Amounts Grid -->
                            <div class="mt-4 grid grid-cols-2 gap-2.5">
                                <div class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4]/50 p-3">
                                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#166534]">Expected Claim</span>
                                    <p class="mt-1 font-mono text-base font-extrabold text-[#014d3c]">
                                        PHP {{ Number(selectedLedger?.expectedClaimAmount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                                    </p>
                                </div>
                                <div class="rounded-xl border border-[#dde4de] bg-[#f8fafc] p-3">
                                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Contributions</span>
                                    <p class="mt-1 text-base font-extrabold text-[#0f172a]">
                                        {{ selectedLedger?.contributionYears || 0 }} <span class="text-xs font-normal text-[#64748b]">{{ Number(selectedLedger?.contributionYears || 0) === 1 ? 'year' : 'years' }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Farmer Profile Particulars -->
                            <div class="mt-4 space-y-2 border-t border-[#f1f5f9] pt-3.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">Member Type</span>
                                    <span class="font-semibold text-[#0f172a]">{{ selectedLedger?.farmer.memberType?.name || selectedLedger?.farmer.memberType?.code || 'Standard' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">Ledger Year</span>
                                    <span class="font-semibold text-[#0f172a]">{{ selectedLedger?.year || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#64748b]">Payment Status</span>
                                    <span class="font-bold text-[#15803d]">{{ selectedLedger?.paymentStatusLabel || 'Settled' }}</span>
                                </div>
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-[#64748b]">Barangay</span>
                                    <span class="font-semibold text-right text-[#0f172a]">{{ selectedLedger?.farmer.barangay || 'Not recorded' }}</span>
                                </div>
                                <div v-if="selectedLedger?.farmer.association" class="flex items-start justify-between gap-2">
                                    <span class="text-[#64748b]">Association</span>
                                    <span class="font-semibold text-right text-[#0f172a]">{{ selectedLedger.farmer.association }}</span>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Right Column: Primary Filing Form (8 cols) -->
                    <div class="xl:col-span-8">
                        <form class="rounded-2xl border border-[#dde4de] bg-white shadow-xs" @submit.prevent="submit">
                            <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-2 w-2 rounded-full bg-[#014d3c]"></span>
                                    <h2 class="text-xs font-bold text-[#0f172a]">Mortuary Benefit Filing Form</h2>
                                </div>
                                <span class="text-[0.68rem] text-[#64748b]">Complete all required checklist items</span>
                            </div>

                            <div class="p-5 space-y-4">
                                <!-- Document Checklist Section -->
                                <div v-if="requirements.length" class="rounded-xl border border-[#dde4de] bg-[#f8fafc] p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Document Verification Checklist</span>
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-[0.62rem] font-bold"
                                            :class="checklistComplete ? 'bg-[#f0fdf4] text-[#15803d] border border-[#bbf7d0]' : 'bg-[#fff1f2] text-[#be123c] border border-[#fecdd3]'"
                                        >
                                            {{ checklistComplete ? 'Requirements Complete' : 'Pending Documents' }}
                                        </span>
                                    </div>

                                    <div class="mt-3 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                                        <label
                                            v-for="requirement in requirements"
                                            :key="requirement.code"
                                            class="flex cursor-pointer items-start gap-2.5 rounded-lg border bg-white p-3 transition"
                                            :class="form.requirements[requirement.code]?.is_received ? 'border-[#014d3c]/40 bg-[#f0fdf4]/20' : (form.errors['requirements.' + requirement.code + '.is_received'] ? 'border-rose-300 ring-1 ring-rose-200 bg-rose-50/30' : 'border-[#dde4de] hover:border-[#cbd5e1]')"
                                        >
                                            <input
                                                v-model="form.requirements[requirement.code].is_received"
                                                type="checkbox"
                                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#014d3c] focus:ring-[#014d3c]"
                                            >
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-[#0f172a]">{{ requirement.label }}</p>
                                                <p class="text-[0.65rem]" :class="form.errors['requirements.' + requirement.code + '.is_received'] ? 'text-rose-600 font-semibold' : 'text-[#64748b]'">
                                                    {{ form.errors['requirements.' + requirement.code + '.is_received'] ? 'This document is required before filing' : (requirement.isRequired ? 'Required document' : 'Optional attachment') }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Claim Meta (Date & Readonly Amount) -->
                                <div class="grid gap-3.5 sm:grid-cols-2">
                                    <label class="block space-y-1">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Claim Date</span>
                                        <input
                                            v-model="form.claim_date"
                                            type="date"
                                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                        >
                                    </label>

                                    <label class="block space-y-1">
                                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Calculated Claim Amount</span>
                                        <div class="flex h-9 items-center justify-between rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-3 text-xs">
                                            <span class="text-[#64748b]">Computed Entitlement:</span>
                                            <span class="font-mono font-bold text-[#014d3c]">
                                                PHP {{ Number(form.claim_amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                                            </span>
                                        </div>
                                    </label>
                                </div>

                                <!-- Beneficiary / Claimer Information -->
                                <div class="border-t border-[#f1f5f9] pt-4">
                                    <p class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-[#64748b] mb-3">Beneficiary / Claimer Information</p>
                                    <div class="grid gap-3.5 sm:grid-cols-2">
                                        <label class="block space-y-1">
                                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Claimer Full Name</span>
                                            <input
                                                v-model="form.claimer_name"
                                                type="text"
                                                placeholder="e.g. Maria Santos"
                                                class="h-9 w-full rounded-lg border bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                                :class="form.errors.claimer_name ? 'border-rose-300 ring-1 ring-rose-200' : 'border-[#dbe3dd]'"
                                            >
                                            <p v-if="form.errors.claimer_name" class="text-[0.65rem] font-semibold text-rose-600">{{ form.errors.claimer_name }}</p>
                                        </label>

                                        <label class="block space-y-1">
                                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Relationship to Deceased</span>
                                            <input
                                                v-model="form.claimer_relationship"
                                                type="text"
                                                placeholder="e.g. Spouse, Son, Daughter"
                                                class="h-9 w-full rounded-lg border bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                                :class="form.errors.claimer_relationship ? 'border-rose-300 ring-1 ring-rose-200' : 'border-[#dbe3dd]'"
                                            >
                                            <p v-if="form.errors.claimer_relationship" class="text-[0.65rem] font-semibold text-rose-600">{{ form.errors.claimer_relationship }}</p>
                                        </label>

                                        <label class="block space-y-1">
                                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Contact Number</span>
                                            <input
                                                v-model="form.claimer_contact_number"
                                                type="text"
                                                placeholder="09xx xxx xxxx"
                                                class="h-9 w-full rounded-lg border bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                                :class="form.errors.claimer_contact_number ? 'border-rose-300 ring-1 ring-rose-200' : 'border-[#dbe3dd]'"
                                            >
                                            <p v-if="form.errors.claimer_contact_number" class="text-[0.65rem] font-semibold text-rose-600">{{ form.errors.claimer_contact_number }}</p>
                                        </label>

                                        <label class="block space-y-1">
                                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Current Address</span>
                                            <input
                                                v-model="form.claimer_address"
                                                type="text"
                                                placeholder="Barangay, Municipality"
                                                class="h-9 w-full rounded-lg border bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                                :class="form.errors.claimer_address ? 'border-rose-300 ring-1 ring-rose-200' : 'border-[#dbe3dd]'"
                                            >
                                            <p v-if="form.errors.claimer_address" class="text-[0.65rem] font-semibold text-rose-600">{{ form.errors.claimer_address }}</p>
                                        </label>
                                    </div>
                                </div>

                                <!-- Remarks -->
                                <label class="block space-y-1">
                                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Remarks & Field Observations</span>
                                    <textarea
                                        v-model="form.remarks"
                                        rows="2"
                                        placeholder="Optional context, certificate validation details, or notes..."
                                        class="w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] p-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                    ></textarea>
                                </label>

                                <!-- Form Actions -->
                                <div class="flex items-center justify-between border-t border-[#f1f5f9] pt-4">
                                    <p class="text-xs" :class="checklistComplete ? 'text-[#15803d] font-semibold' : 'text-[#be123c]'">
                                        {{ checklistComplete ? '✓ Ready for claim filing.' : '⚠ Incomplete required checklist.' }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <Link
                                            :href="queueUrl"
                                            class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-4 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                                        >
                                            Cancel
                                        </Link>
                                        <button
                                            type="submit"
                                            class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="form.processing || !selectedLedger"
                                        >
                                            {{ form.processing ? 'Submitting Claim...' : 'Confirm & File Claim' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>
