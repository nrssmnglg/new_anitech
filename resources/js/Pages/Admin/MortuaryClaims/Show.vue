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
            subtitle: `${props.claim.filedBy || 'System'} • ${props.claim.claimDate || 'No date'}`,
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
    if (props.claim.status.value === 'released' || props.claim.status.value === 'approved') return 'bg-[#eef7e3] text-[#416918]';
    if (props.claim.status.value === 'rejected') return 'bg-[#ffdad6] text-[#93000a]';
    return 'bg-[#fff3dc] text-[#a86100]';
});
</script>

<template>
    <Head :title="claim.claimReference" />

    <AdminLayout title="Mortuary Claim Details">
        <div class="space-y-8 overflow-x-hidden">
            <section class="relative overflow-hidden bg-[#003629] px-8 py-10 text-white">
                <div class="absolute inset-0 bg-[radial-gradient(at_0%_0%,_rgba(27,77,62,1)_0px,_transparent_50%),radial-gradient(at_100%_100%,_rgba(22,51,44,1)_0px,_transparent_50%)]"></div>
                <div class="absolute inset-0 opacity-5 [background-image:radial-gradient(circle,_#fff_1px,_transparent_1px)] [background-size:40px_40px]"></div>
                <div class="relative mx-auto flex max-w-7xl flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div>
                        <Link :href="urls.queue" class="inline-flex items-center gap-2 text-sm font-bold text-[#baeed9] hover:underline">
                            Back to Claim Queue
                        </Link>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <h1 class="text-5xl font-black tracking-[-0.04em]">{{ claim.claimReference }}</h1>
                            <span class="rounded-full px-4 py-2 text-xs font-black uppercase tracking-[0.18em]" :class="statusTone">{{ claim.status.label }}</span>
                        </div>
                        <p class="mt-3 text-base text-[#9ed1bd]">
                            Filed on {{ claim.claimDate || 'Not recorded' }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link v-if="urls.farmerShow" :href="urls.farmerShow" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/20">
                            Open Farmer Record
                        </Link>
                    </div>
                </div>
            </section>

            <section v-if="flashSuccess" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ flashSuccess }}
            </section>

            <section v-if="pageErrors.mortuary || pageErrors.remarks" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
                {{ pageErrors.mortuary || pageErrors.remarks }}
            </section>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <article class="flex items-center gap-4 rounded-xl border border-[#c0c9c3] bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#c0f190] text-[#466f1e]">P</div>
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#707974]">Claim Amount</p>
                        <p class="mt-2 text-2xl font-black text-[#191c1c]">PHP {{ Number(claim.claimAmount || 0).toFixed(2) }}</p>
                    </div>
                </article>
                <article class="flex items-center gap-4 rounded-xl border border-[#c0c9c3] bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#c9eadf] text-[#2f4c44]">R</div>
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#707974]">Reference</p>
                        <p class="mt-2 text-2xl font-black text-[#191c1c]">{{ claim.claimReference }}</p>
                    </div>
                </article>
                <article class="flex items-center gap-4 rounded-xl border border-[#c0c9c3] bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#baeed9] text-[#1d4f40]">F</div>
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#707974]">Filed By</p>
                        <p class="mt-2 text-2xl font-black text-[#191c1c]">{{ claim.filedBy }}</p>
                    </div>
                </article>
                <article class="flex items-center gap-4 rounded-xl border border-[#c0c9c3] bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#a5d577] text-[#2a5000]">Y</div>
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#707974]">Ledger Year</p>
                        <p class="mt-2 text-2xl font-black text-[#191c1c]">{{ claim.ledger.year || 'N/A' }} <span class="text-base font-bold text-[#5f6c67]">({{ claim.ledger.paymentStatusLabel }})</span></p>
                    </div>
                </article>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <section class="overflow-hidden rounded-xl border border-[#c0c9c3] bg-white shadow-sm">
                        <div class="flex items-center gap-2 border-b border-[#c0c9c3] bg-[#f2f4f3] px-6 py-4">
                            <h2 class="text-xl font-black text-[#191c1c]">Claim & Ledger Information</h2>
                        </div>
                        <div class="grid gap-8 px-6 py-6 md:grid-cols-2">
                            <div class="space-y-4">
                                <h3 class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#707974]">Claim Particulars</h3>
                                <div class="grid grid-cols-2 gap-y-4 text-sm">
                                    <div class="text-[#707974]">Ref Number</div>
                                    <div class="font-semibold text-[#191c1c]">{{ claim.claimReference }}</div>
                                    <div class="text-[#707974]">Application Date</div>
                                    <div class="text-[#191c1c]">{{ claim.claimDate || 'Not recorded' }}</div>
                                    <div class="text-[#707974]">Status</div>
                                    <div class="font-semibold text-[#191c1c]">{{ claim.status.label }}</div>
                                    <div class="text-[#707974]">Remarks</div>
                                    <div class="text-[#191c1c]">{{ claim.remarks || 'No remarks recorded.' }}</div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#707974]">Deceased Member Details</h3>
                                <div class="grid grid-cols-2 gap-y-4 text-sm">
                                    <div class="text-[#707974]">Farmer Name</div>
                                    <div class="font-semibold text-[#191c1c]">{{ claim.farmer.fullName }}</div>
                                    <div class="text-[#707974]">Registry Code</div>
                                    <div class="text-[#191c1c]">{{ claim.farmer.farmerCode }}</div>
                                    <div class="text-[#707974]">Farmer Status</div>
                                    <div class="text-[#191c1c]">{{ claim.farmer.statusLabel || 'Not recorded' }}</div>
                                    <div class="text-[#707974]">Eligibility</div>
                                    <div class="font-bold text-[#416918]">{{ claim.ledger.mortuaryEligible ? 'QUALIFIED' : 'NOT QUALIFIED' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="mx-6 mb-6 rounded-lg border-l-4 border-[#707974] bg-[#f2f4f3] p-4">
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#707974]">Officer Remarks</p>
                            <p class="mt-2 text-sm italic text-[#191c1c]">{{ claim.remarks || 'No remarks recorded.' }}</p>
                        </div>
                    </section>

                    <div class="grid gap-6 md:grid-cols-2">
                        <section class="rounded-xl border border-[#c0c9c3] bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-black text-[#191c1c]">Document Checklist</h2>
                                <span class="rounded-full px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em]" :class="claim.approvalRequirementsComplete ? 'bg-[#eef7e3] text-[#416918]' : 'bg-[#fff3dc] text-[#a86100]'">
                                    {{ claim.approvalRequirementsComplete ? 'Complete' : 'Incomplete' }}
                                </span>
                            </div>
                            <div class="mt-6 space-y-3">
                                <div
                                    v-for="item in claim.checklist.items"
                                    :key="item.code"
                                    class="flex items-center justify-between rounded-lg border border-[#c0c9c3] bg-[#f2f4f3] p-4"
                                >
                                    <span class="text-sm font-medium text-[#191c1c]">{{ item.label }}</span>
                                    <span class="text-xs font-black uppercase tracking-[0.12em]" :class="item.received ? 'text-[#416918]' : 'text-[#707974]'">
                                        {{ item.received ? 'Verified' : 'Missing' }}
                                    </span>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-[#c0c9c3] bg-white p-6 shadow-sm">
                            <h2 class="text-xl font-black text-[#191c1c]">Beneficiary Details</h2>
                            <div class="mt-6 flex items-center gap-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full border border-[#c0c9c3] bg-[#f2f4f3] text-xl font-black text-[#2f4c44]">
                                    {{ claim.claimer.name?.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || 'CL' }}
                                </div>
                                <div>
                                    <p class="text-xl font-black text-[#191c1c]">{{ claim.claimer.name || 'Not recorded' }}</p>
                                    <p class="text-sm font-bold text-[#416918]">{{ claim.claimer.relationship || 'Relationship not recorded' }}</p>
                                </div>
                            </div>
                            <div class="mt-6 space-y-4 border-t border-[#c0c9c3] pt-6">
                                <div class="text-sm text-[#191c1c]">{{ claim.claimer.contactNumber || 'No contact number' }}</div>
                                <div class="text-sm text-[#191c1c]">{{ claim.claimer.address || 'No address recorded' }}</div>
                                <div class="text-sm text-[#191c1c]">Released By: {{ claim.releasedBy || 'System' }}</div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="space-y-6">
                    <section class="rounded-xl border border-[#c0c9c3] bg-[#eef5f1] p-6 shadow-sm">
                        <h2 class="text-xl font-black text-[#416918]">Claim Released</h2>
                        <p class="mt-3 text-sm leading-7 text-[#2f4c44]">
                            This claim was automatically approved and released during filing after the required documents and beneficiary details were completed.
                        </p>
                    </section>

                    <section class="rounded-xl border border-[#c0c9c3] bg-[#eceeed] p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-black uppercase tracking-[0.18em] text-[#191c1c]">Audit Trail</h2>
                            <span class="text-xs text-[#707974]">Real-time</span>
                        </div>
                        <div class="relative mt-6 space-y-6 before:absolute before:bottom-2 before:left-[9px] before:top-2 before:w-[2px] before:bg-[#c0c9c3] before:content-['']">
                            <div v-for="item in auditItems" :key="item.title" class="relative pl-8">
                                <div class="absolute left-0 top-1 z-10 flex h-5 w-5 items-center justify-center rounded-full border-2 border-[#eceeed]" :class="item.tone === 'secondary' ? 'bg-[#c0f190] text-[#466f1e]' : 'bg-[#baeed9] text-[#1d4f40]'">
                                    <span class="text-[10px] font-black">{{ item.tone === 'secondary' ? 'OK' : 'IN' }}</span>
                                </div>
                                <p class="text-sm font-semibold text-[#191c1c]">{{ item.title }}</p>
                                <p class="text-[11px] text-[#707974]">{{ item.subtitle }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
