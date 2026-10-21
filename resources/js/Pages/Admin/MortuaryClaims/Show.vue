<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    claim: { type: Object, required: true },
    permissions: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const pageErrors = computed(() => page.props.errors || {});
const auditItems = computed(() => {
    const items = [
        {
            title: 'Claim Filed',
            subtitle: `${props.claim.filedBy || 'No staff recorded'} • ${props.claim.claimDate || 'No date'}`,
            tone: 'primary',
        },
    ];

    if (props.claim.approvedBy) {
        items.unshift({
            title: 'Claim Released',
            subtitle: `${props.claim.releasedBy || props.claim.approvedBy} • ${props.claim.claimDate || 'No date'}`,
            tone: 'secondary',
        });
    }

    return items;
});

const statusTone = computed(() => {
    if (props.claim.status.value === 'released' || props.claim.status.value === 'approved') return 'bg-[#f0fdf4] text-[#15803d] border border-[#bbf7d0]';
    if (props.claim.status.value === 'rejected') return 'bg-[#fff1f2] text-[#be123c] border border-[#fecdd3]';
    return 'bg-[#fefce8] text-[#854d0e] border border-[#fef08a]';
});
</script>

<template>
    <Head :title="`Claim ${claim.claimReference}`" />

    <AdminLayout title="Mortuary Claim Details">
        <div class="mx-auto max-w-[1536px] space-y-4">
            <!-- Sleek Top Header Row -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold tracking-tight text-[#0f172a] sm:text-2xl">{{ claim.claimReference }}</h1>
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold" :class="statusTone">
                            {{ claim.status.label }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="urls.queue"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-3.5 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                    >
                        Back to Queue
                    </Link>
                    <Link
                        v-if="urls.farmerShow"
                        :href="urls.farmerShow"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97]"
                    >
                        Open Farmer Profile
                    </Link>
                </div>
            </div>

            <!-- Flash & Error alerts -->
            <div v-if="flashSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
                {{ flashSuccess }}
            </div>

            <div v-if="pageErrors.mortuary || pageErrors.remarks" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
                {{ pageErrors.mortuary || pageErrors.remarks }}
            </div>

            <!-- 4 Clean Compact Summary Cards -->
            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <article class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-xs">
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Claim Amount</span>
                    <p class="mt-1 font-mono text-base font-extrabold text-[#014d3c]">
                        PHP {{ Number(claim.claimAmount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                    </p>
                </article>

                <article class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-xs">
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Claim Reference</span>
                    <p class="mt-1 font-mono text-base font-bold text-[#0f172a] truncate">{{ claim.claimReference }}</p>
                </article>

                <article class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-xs">
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Filed By</span>
                    <p class="mt-1 text-base font-bold text-[#0f172a] truncate">{{ claim.filedBy || 'Staff' }}</p>
                </article>

                <article class="rounded-xl border border-[#dde4de] bg-white p-3.5 shadow-xs">
                    <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Ledger Year &amp; Status</span>
                    <p class="mt-1 text-xs font-bold text-[#0f172a] truncate">
                        {{ claim.ledger.year || 'N/A' }} <span class="font-normal text-[#64748b]">({{ claim.ledger.paymentStatusLabel }})</span>
                    </p>
                </article>
            </section>

            <!-- Main Content Grid -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Left 2 Columns: Claim Particulars & Document Checklist -->
                <div class="space-y-4 lg:col-span-2">
                    <!-- Claim & Member Particulars Card -->
                    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
                        <div class="flex items-center justify-between border-b border-[#f1f5f9] px-4 py-3">
                            <h2 class="text-xs font-bold text-[#0f172a]">Claim &amp; Deceased Member Information</h2>
                            <span class="rounded-md bg-[#e6f5ec] px-2 py-0.5 text-[0.68rem] font-bold text-[#0f6b45]">
                                {{ claim.ledger.mortuaryEligible ? 'Qualified Benefit' : 'Non-Qualified' }}
                            </span>
                        </div>

                        <div class="grid gap-3 p-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Deceased Farmer</span>
                                <p class="mt-0.5 text-xs font-bold text-[#0f172a]">{{ claim.farmer.fullName }}</p>
                                <p class="font-mono text-[0.68rem] text-[#64748b]">{{ claim.farmer.farmerCode }}</p>
                            </div>
                            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Farmer Status</span>
                                <p class="mt-0.5 text-xs font-semibold text-[#0f172a]">{{ claim.farmer.statusLabel || 'Registered' }}</p>
                                <p class="text-[0.68rem] text-[#64748b]">Active masterlist registry record</p>
                            </div>
                            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Application Date</span>
                                <p class="mt-0.5 text-xs font-semibold text-[#0f172a]">{{ claim.claimDate || 'Not recorded' }}</p>
                            </div>
                            <div class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Approved &amp; Released By</span>
                                <p class="mt-0.5 text-xs font-semibold text-[#0f172a]">{{ claim.releasedBy || claim.approvedBy || 'Pending Release' }}</p>
                            </div>
                            <div v-if="claim.remarks" class="rounded-xl border border-[#f1f5f9] bg-[#f8fafc] p-3 sm:col-span-2">
                                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Officer Remarks</span>
                                <p class="mt-0.5 text-xs italic text-[#475569]">{{ claim.remarks }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Document Checklist Section -->
                    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
                        <div class="flex items-center justify-between border-b border-[#f1f5f9] px-4 py-3">
                            <h2 class="text-xs font-bold text-[#0f172a]">Document Checklist Verification</h2>
                            <span
                                class="inline-flex items-center rounded-md px-2 py-0.5 text-[0.62rem] font-bold"
                                :class="claim.approvalRequirementsComplete ? 'bg-[#f0fdf4] text-[#15803d] border border-[#bbf7d0]' : 'bg-[#fefce8] text-[#854d0e] border border-[#fef08a]'"
                            >
                                {{ claim.approvalRequirementsComplete ? 'All Requirements Met' : 'Incomplete Documents' }}
                            </span>
                        </div>

                        <div class="p-4 space-y-2">
                            <div
                                v-for="item in claim.checklist.items"
                                :key="item.code"
                                class="flex items-center justify-between rounded-xl border border-[#f1f5f9] bg-[#f8fafc] px-3.5 py-2.5 text-xs"
                            >
                                <span class="font-medium text-[#0f172a]">{{ item.label }}</span>
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[0.62rem] font-bold"
                                    :class="item.received ? 'bg-[#f0fdf4] text-[#15803d]' : 'bg-[#f1f5f9] text-[#64748b]'"
                                >
                                    {{ item.received ? '✓ Verified' : 'Missing' }}
                                </span>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right 1 Column: Beneficiary Details & Audit Trail -->
                <div class="space-y-4">
                    <!-- Beneficiary Details Card -->
                    <section class="rounded-2xl border border-[#dde4de] bg-white p-4 shadow-xs">
                        <div class="border-b border-[#f1f5f9] pb-3">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Beneficiary Information</span>
                            <div class="mt-2 flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#e6f5ec] text-xs font-bold text-[#0f6b45]">
                                    {{ claim.claimer.name?.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || 'CL' }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-[#0f172a]">{{ claim.claimer.name || 'Not recorded' }}</p>
                                    <p class="text-[0.68rem] font-semibold text-[#014d3c]">{{ claim.claimer.relationship || 'Relationship not recorded' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-[#64748b]">Contact Number</span>
                                <span class="font-semibold text-[#0f172a]">{{ claim.claimer.contactNumber || 'None' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-[#64748b]">Address</span>
                                <span class="font-semibold text-right text-[#0f172a]">{{ claim.claimer.address || 'None' }}</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-[#f1f5f9] pt-2">
                                <span class="text-[#64748b]">Released By</span>
                                <span class="font-semibold text-[#0f172a]">{{ claim.releasedBy || 'Pending' }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- Audit Trail Timeline Card -->
                    <section class="rounded-2xl border border-[#dde4de] bg-white p-4 shadow-xs">
                        <div class="border-b border-[#f1f5f9] pb-2.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Audit History</span>
                        </div>

                        <div class="mt-3 relative pl-5 space-y-3 before:absolute before:bottom-1 before:left-2 before:top-1.5 before:w-0.5 before:bg-[#e2e8f0]">
                            <div v-for="item in auditItems" :key="item.title" class="relative text-xs">
                                <div
                                    class="absolute -left-5 top-1 h-2.5 w-2.5 rounded-full ring-2 ring-white"
                                    :class="item.tone === 'secondary' ? 'bg-[#15803d]' : 'bg-[#014d3c]'"
                                ></div>
                                <p class="font-bold text-[#0f172a]">{{ item.title }}</p>
                                <p class="text-[0.68rem] text-[#64748b]">{{ item.subtitle }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
