<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
        <div class="mortuary-claim-process space-y-3">
            <section class="claim-process-hero relative overflow-hidden rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="absolute inset-0 bg-[radial-gradient(at_0%_0%,_rgba(27,77,62,0.9)_0px,_transparent_50%),radial-gradient(at_100%_100%,_rgba(22,51,44,0.9)_0px,_transparent_50%),radial-gradient(at_50%_50%,_rgba(65,105,24,0.18)_0px,_transparent_50%)]"></div>
                <div class="absolute inset-0 opacity-10 [background-image:linear-gradient(rgba(255,255,255,0.4)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.4)_1px,transparent_1px)] [background-size:40px_40px]"></div>
                <div class="relative mx-auto flex max-w-7xl flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-5xl font-black tracking-[-0.04em]">File Mortuary Claim</h1>
                    </div>

                    <Link :href="queueUrl" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20">
                        Back to Claim Queue
                    </Link>
                </div>
            </section>

            <section v-if="flashSuccess" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ flashSuccess }}
            </section>

            <section v-if="eligibleLedgers.length === 0" class="rounded-xl border border-[#e1e3e2] bg-white p-10 text-center shadow-sm">
                <p class="text-[0.74rem] font-black uppercase tracking-[0.24em] text-[#8a756f]">No Eligible Farmers</p>
                <h2 class="mt-3 text-2xl font-black text-[#211a18]">Nothing can be filed yet</h2>
                <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-[#6f5d57]">
                    A mortuary claim can only be created for a farmer with settled mortuary contributions and no existing active mortuary record.
                </p>
            </section>

            <template v-else>
                <section class="-mt-10 px-0">
                    <div class="mx-auto max-w-7xl grid-cols-12 gap-6 xl:grid">
                        <div class="col-span-4 space-y-6">
                            <section class="rounded-xl border border-[#e1e3e2] border-l-4 border-l-[#003629] bg-[#eef5f1] p-6 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.05)]">
                                <h2 class="text-[0.8rem] font-black uppercase tracking-[0.2em] text-[#003629]">Active Context</h2>
                                <div class="mt-5 space-y-5">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#003629] text-lg font-black text-white">
                                            {{ selectedInitials }}
                                        </div>
                                        <div>
                                            <p class="text-sm text-[#6f5d57]">Farmer Selection</p>
                                            <p class="text-2xl font-black text-[#191c1c]">{{ selectedLedger?.farmer.fullName || 'No farmer selected' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <p class="text-sm text-[#6f5d57]">Ref Code</p>
                                            <p class="font-bold text-[#191c1c]">{{ selectedLedger?.farmer.farmerCode || 'No code' }}</p>
                                        </div>
                                        <div class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-[#003629]">
                                            Selected
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 border-t border-[#003629]/10 pt-5">
                                        <div class="rounded-lg bg-white p-4">
                                            <p class="text-xs uppercase tracking-[0.14em] text-[#6f5d57]">Expected Claim</p>
                                            <p class="mt-2 text-2xl font-black text-[#003629]">PHP {{ Number(selectedLedger?.expectedClaimAmount || 0).toFixed(2) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-white p-4">
                                            <p class="text-xs uppercase tracking-[0.14em] text-[#6f5d57]">Contributing Years</p>
                                            <p class="mt-2 text-2xl font-black text-[#003629]">{{ selectedLedger?.contributionYears || 0 }}</p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-xl border border-[#e1e3e2] bg-white p-6 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.05)]">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-black text-[#191c1c]">Farmer Profile</h2>
                                </div>
                                <div class="mt-6 space-y-6">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.14em] text-[#6f5d57]">Ledger Year</p>
                                        <p class="mt-2 text-sm font-bold text-[#191c1c]">Latest settled renewal: {{ selectedLedger?.year || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.14em] text-[#6f5d57]">Member Type</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="rounded-full bg-[#1b4d3e]/10 px-3 py-1 text-xs font-black uppercase tracking-[0.12em] text-[#1b4d3e]">
                                                {{ selectedLedger?.farmer.memberType?.code || 'N/A' }}
                                            </span>
                                            <span class="rounded-full bg-[#c0f190]/20 px-3 py-1 text-xs font-black uppercase tracking-[0.12em] text-[#466f1e]">
                                                {{ selectedLedger?.farmer.memberType?.name || 'Not set' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.14em] text-[#6f5d57]">Payment Status</p>
                                        <p class="mt-2 text-sm font-bold text-[#416918]">{{ selectedLedger?.paymentStatusLabel || 'No payment status' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.14em] text-[#6f5d57]">Location</p>
                                        <p class="mt-2 text-sm text-[#191c1c]">{{ selectedLedger?.farmer.barangay || 'No barangay' }}{{ selectedLedger?.farmer.association ? ` - ${selectedLedger.farmer.association}` : '' }}</p>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="col-span-8 mt-6 xl:mt-0">
                            <form class="rounded-xl border border-[#e1e3e2] bg-white shadow-[0_4px_20px_-5px_rgba(0,0,0,0.05)]" @submit.prevent="submit">
                                <div class="flex flex-col gap-3 border-b border-[#e1e3e2] bg-[#f2f4f3] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h2 class="text-xl font-black text-[#191c1c]">Primary Filing Form</h2>
                                    </div>
                                    <span class="text-xs italic text-[#707974]">Required fields marked with complete validation</span>
                                </div>

                                <div class="space-y-8 p-6">
                                    <section v-if="requirements.length" class="rounded-xl border border-dashed border-[#c0c9c3] bg-[#ffffff] p-6">
                                        <div class="flex items-center justify-between gap-4">
                                            <h3 class="text-sm font-black uppercase tracking-[0.18em] text-[#404945]">Document Checklist</h3>
                                            <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.14em]" :class="checklistComplete ? 'bg-[#dff1cf] text-[#416918]' : 'bg-[#ffdad6] text-[#93000a]'">
                                                {{ checklistComplete ? 'Verified' : 'Incomplete' }}
                                            </span>
                                        </div>
                                        <div class="mt-5 grid gap-4 md:grid-cols-3">
                                            <label
                                                v-for="requirement in requirements"
                                                :key="requirement.code"
                                                class="cursor-pointer rounded-lg border border-transparent bg-[#f8faf9] p-4 transition hover:border-[#003629]"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <input v-model="form.requirements[requirement.code].is_received" type="checkbox" class="mt-1 h-5 w-5 rounded border-[#c0c9c3] text-[#003629] focus:ring-[#003629]">
                                                    <div>
                                                        <p class="font-bold text-[#191c1c]">{{ requirement.label }}</p>
                                                        <p class="text-xs text-[#6f5d57]">{{ requirement.isRequired ? 'Required for filing' : 'Optional supporting document' }}</p>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </section>

                                    <div class="grid gap-6 md:grid-cols-2">
                                        <label class="space-y-2">
                                            <span class="text-sm font-bold text-[#404945]">Claim Date</span>
                                                <input v-model="form.claim_date" type="date" class="w-full rounded-xl border border-[#c0c9c3] bg-[#f8faf9] px-4 py-3 text-sm outline-none transition focus:border-[#003629] focus:ring-2 focus:ring-[#003629]/20">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-sm font-bold text-[#404945]">Claim Amount (PHP)</span>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-[#707974]">PHP</span>
                                                <input
                                                    v-model="form.claim_amount"
                                                    type="number"
                                                    readonly
                                                    aria-readonly="true"
                                                    title="Claim amount is calculated from the farmer's settled mortuary contributions"
                                                    class="w-full cursor-not-allowed rounded-xl border border-[#c0c9c3] bg-[#eef1ef] py-3 pl-14 pr-4 text-sm font-semibold text-[#404945] outline-none"
                                                >
                                            </div>
                                        </label>
                                    </div>

                                    <section class="space-y-6">
                                        <div class="grid gap-6 md:grid-cols-2">
                                            <label class="space-y-2">
                                                <span class="text-sm font-bold text-[#404945]">Full Name of Claimer</span>
                                                <input
                                                    v-model="form.claimer_name"
                                                    type="text"
                                                    placeholder="First M. Last"
                                                    class="w-full rounded-xl border bg-[#f8faf9] px-4 py-3 text-sm outline-none transition focus:border-[#003629] focus:ring-2 focus:ring-[#003629]/20"
                                                    :class="form.errors.claimer_name ? 'border-rose-400' : 'border-[#c0c9c3]'"
                                                >
                                                <p v-if="form.errors.claimer_name" class="text-sm font-medium text-rose-700">{{ form.errors.claimer_name }}</p>
                                            </label>
                                            <label class="space-y-2">
                                                <span class="text-sm font-bold text-[#404945]">Relationship</span>
                                                <input
                                                    v-model="form.claimer_relationship"
                                                    type="text"
                                                    placeholder="Spouse, child, sibling, parent"
                                                    class="w-full rounded-xl border bg-[#f8faf9] px-4 py-3 text-sm outline-none transition focus:border-[#003629] focus:ring-2 focus:ring-[#003629]/20"
                                                    :class="form.errors.claimer_relationship ? 'border-rose-400' : 'border-[#c0c9c3]'"
                                                >
                                                <p v-if="form.errors.claimer_relationship" class="text-sm font-medium text-rose-700">{{ form.errors.claimer_relationship }}</p>
                                            </label>
                                        </div>
                                        <div class="grid gap-6 md:grid-cols-2">
                                            <label class="space-y-2">
                                                <span class="text-sm font-bold text-[#404945]">Contact Number</span>
                                                <input
                                                    v-model="form.claimer_contact_number"
                                                    type="text"
                                                    placeholder="+63 9xx xxx xxxx"
                                                    class="w-full rounded-xl border bg-[#f8faf9] px-4 py-3 text-sm outline-none transition focus:border-[#003629] focus:ring-2 focus:ring-[#003629]/20"
                                                    :class="form.errors.claimer_contact_number ? 'border-rose-400' : 'border-[#c0c9c3]'"
                                                >
                                                <p v-if="form.errors.claimer_contact_number" class="text-sm font-medium text-rose-700">{{ form.errors.claimer_contact_number }}</p>
                                            </label>
                                            <label class="space-y-2">
                                                <span class="text-sm font-bold text-[#404945]">Current Address</span>
                                                <input
                                                    v-model="form.claimer_address"
                                                    type="text"
                                                    placeholder="Street, Barangay, City"
                                                    class="w-full rounded-xl border bg-[#f8faf9] px-4 py-3 text-sm outline-none transition focus:border-[#003629] focus:ring-2 focus:ring-[#003629]/20"
                                                    :class="form.errors.claimer_address ? 'border-rose-400' : 'border-[#c0c9c3]'"
                                                >
                                                <p v-if="form.errors.claimer_address" class="text-sm font-medium text-rose-700">{{ form.errors.claimer_address }}</p>
                                            </label>
                                        </div>
                                        <label class="space-y-2">
                                            <span class="text-sm font-bold text-[#404945]">Remarks & Processing Notes</span>
                                            <textarea v-model="form.remarks" rows="4" placeholder="Enter any additional context or field observations..." class="w-full rounded-xl border border-[#c0c9c3] bg-[#f8faf9] px-4 py-3 text-sm outline-none transition focus:border-[#003629] focus:ring-2 focus:ring-[#003629]/20"></textarea>
                                        </label>
                                    </section>

                                    <div class="flex flex-col gap-4 border-t border-[#e1e3e2] pt-6 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-sm" :class="checklistComplete ? 'text-[#416918]' : 'text-[#ba1a1a]'">
                                            {{ checklistComplete ? 'All required checklist items are confirmed.' : 'Complete all required checklist items before filing.' }}
                                        </p>
                                        <div class="flex flex-col gap-3 sm:flex-row">
                                            <Link :href="queueUrl" class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-bold text-[#5f6c67] transition hover:bg-[#f2f4f3]">
                                                Cancel
                                            </Link>
                                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#003629] px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#003629]/20 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing || !selectedLedger">
                                                {{ form.processing ? 'Filing...' : 'File Claim' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </template>
        </div>
    </AdminLayout>
</template>

<style scoped>
.claim-process-hero > div:not(.relative) { display: none; }
.claim-process-hero > .relative { max-width: none; align-items: center; gap: 0.75rem; }
.claim-process-hero h1 { margin-top: 0.15rem; font-size: 1.25rem; line-height: 1.5rem; font-weight: 600; }
.claim-process-hero .text-\[0\.72rem\] { font-size: 0.58rem; font-weight: 600; letter-spacing: 0.1em; }
.claim-process-hero a { min-height: 2rem; border-radius: 0.375rem; padding: 0 0.75rem; font-size: 0.68rem; font-weight: 600; }
.mortuary-claim-process section { box-shadow: none; }
.mortuary-claim-process .-mt-10 { margin-top: 0; }
.mortuary-claim-process .max-w-7xl { max-width: none; }
.mortuary-claim-process .grid-cols-12 { gap: 0.75rem; }
.mortuary-claim-process .col-span-4,
.mortuary-claim-process .col-span-8 { gap: 0.75rem; }
.mortuary-claim-process section.rounded-xl { border-radius: 0.5rem; padding: 0.875rem; }
.mortuary-claim-process section.rounded-xl h2 { font-size: 0.875rem; font-weight: 600; }
.mortuary-claim-process form > div:first-child { padding: 0.75rem 1rem; }
.mortuary-claim-process form > div:nth-child(2) { gap: 0.75rem; padding: 1rem; }
.mortuary-claim-process label { gap: 0.25rem; }
.mortuary-claim-process label > span { font-size: 0.6rem; font-weight: 600; }
.mortuary-claim-process input:not([type='checkbox']),
.mortuary-claim-process select { min-height: 2.25rem; border-radius: 0.375rem; padding-top: 0; padding-bottom: 0; font-size: 0.75rem; }
.mortuary-claim-process textarea { border-radius: 0.375rem; padding: 0.75rem; font-size: 0.75rem; }
.mortuary-claim-process button { border-radius: 0.375rem; }

/* Keep the filing workflow visible without excessive vertical scrolling. */
.mortuary-claim-process .col-span-4 > section { padding: 0.75rem; }
.mortuary-claim-process .col-span-4 > section > div.mt-5,
.mortuary-claim-process .col-span-4 > section > div.mt-6 { margin-top: 0.625rem; }
.mortuary-claim-process .col-span-4 .space-y-5 > :not([hidden]) ~ :not([hidden]),
.mortuary-claim-process .col-span-4 .space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: 0.625rem; }
.mortuary-claim-process .col-span-4 .h-14.w-14 { height: 2.5rem; width: 2.5rem; font-size: 0.75rem; }
.mortuary-claim-process .col-span-4 .gap-4 { gap: 0.625rem; }
.mortuary-claim-process .col-span-4 .text-2xl { font-size: 0.9rem; line-height: 1.25rem; }
.mortuary-claim-process .col-span-4 .border-t.pt-5 { padding-top: 0.625rem; }
.mortuary-claim-process .col-span-4 .rounded-lg.p-4 { padding: 0.625rem; }
.mortuary-claim-process .col-span-4 .mt-2 { margin-top: 0.25rem; }
.mortuary-claim-process .col-span-4 p { line-height: 1.25rem; }

.mortuary-claim-process form > div:nth-child(2) > section:first-child { padding: 0.75rem; }
.mortuary-claim-process form > div:nth-child(2) > section:first-child .mt-5 { margin-top: 0.625rem; }
.mortuary-claim-process form > div:nth-child(2) > section:first-child .grid { gap: 0.5rem; }
.mortuary-claim-process form > div:nth-child(2) > section:first-child label { padding: 0.625rem; }
.mortuary-claim-process form > div:nth-child(2) > section:first-child input[type='checkbox'] { height: 1rem; width: 1rem; }
.mortuary-claim-process form .grid.gap-6 { gap: 0.75rem; }
.mortuary-claim-process form section.space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: 0.75rem; }
.mortuary-claim-process form textarea { min-height: 4rem; }
.mortuary-claim-process form .border-t.pt-6 { padding-top: 0.75rem; }
.mortuary-claim-process form .border-t.pt-6,
.mortuary-claim-process form .border-t.pt-6 > div { gap: 0.5rem; }
.mortuary-claim-process form .border-t.pt-6 a,
.mortuary-claim-process form .border-t.pt-6 button { min-height: 2.25rem; padding: 0 1rem; }

@media (min-width: 1280px) {
    .mortuary-claim-process .col-span-4 { grid-column: span 3 / span 3; }
    .mortuary-claim-process .col-span-8 { grid-column: span 9 / span 9; }
}
</style>
