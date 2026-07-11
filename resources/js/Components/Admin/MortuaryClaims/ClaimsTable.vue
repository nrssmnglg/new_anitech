<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    claims: { type: Object, required: true },
    activeSection: { type: String, required: true },
});

function statusBadge(value) {
    if (value === 'approved' || value === 'released') return 'bg-[#dff1cf] text-[#416918]';
    if (value === 'rejected') return 'bg-[#ffdad6] text-[#93000a]';
    if (value === 'eligible') return 'bg-[#c9eadf] text-[#16332c]';
    return 'bg-[#eceeed] text-[#404945]';
}
</script>

<template>
    <section class="overflow-hidden rounded-[24px] border border-[#dfe5e1] bg-white shadow-[0_12px_30px_rgba(15,23,42,0.05)]">
        <div class="flex flex-col gap-3 border-b border-[#e4ebe7] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">{{ activeSection === 'records' ? 'Mortuary Records' : 'Claim Queue' }}</p>
                <h2 class="mt-1 text-xl font-bold text-[#191c1c]">{{ activeSection === 'records' ? 'Filed claim history' : 'Eligible farmers' }}</h2>
            </div>
            <div class="text-sm text-[#697772]">Showing {{ claims.data.length }} of {{ claims.total }}</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#e5ece8] text-sm">
                <thead v-if="activeSection === 'queue'" class="bg-[#f2f4f3]">
                    <tr class="text-left text-[0.72rem] font-black uppercase tracking-[0.16em] text-[#6d7873]">
                        <th class="px-6 py-4">Farmer Name / Code</th>
                        <th class="px-6 py-4">Member Type</th>
                        <th class="px-6 py-4">Ledger Year</th>
                        <th class="px-6 py-4 text-center">Contr. Years</th>
                        <th class="px-6 py-4">Expected Claim</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <thead v-else class="bg-[#f2f4f3]">
                    <tr class="text-left text-[0.72rem] font-black uppercase tracking-[0.16em] text-[#6d7873]">
                        <th class="px-6 py-4">Reference</th>
                        <th class="px-6 py-4">Farmer</th>
                        <th class="px-6 py-4">Ledger</th>
                        <th class="px-6 py-4">Claim Date</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf2ef]">
                    <tr v-for="claim in claims.data" :key="claim.id" class="transition hover:bg-[#fbfdfc]">
                        <template v-if="activeSection === 'queue'">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[#d9ecd7] text-sm font-black text-[#2a5000]">
                                        {{ claim.farmer.fullName?.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || 'F' }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-[#191c1c]">{{ claim.farmer.fullName }}</p>
                                        <p class="mt-1 text-[0.78rem] text-[#7b8882]">{{ claim.farmer.farmerCode }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-[#34423d]">{{ claim.farmer.memberType?.name || claim.farmer.memberType?.code || 'Not set' }}</td>
                            <td class="px-6 py-4 text-[#34423d]">{{ claim.ledger.year || 'No ledger' }}</td>
                            <td class="px-6 py-4 text-center text-[#34423d]">{{ claim.ledger.contributionYears || 0 }}</td>
                            <td class="px-6 py-4 font-bold text-[#191c1c]">PHP {{ Number(claim.claimAmount || 0).toFixed(2) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em]" :class="statusBadge(claim.status.value)">
                                    {{ claim.status.label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="claim.actions.createUrl" class="inline-flex items-center rounded-xl text-sm font-bold text-[#003629] transition hover:underline">
                                    Process
                                </Link>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-6 py-4">
                                <p class="font-bold text-[#191c1c]">{{ claim.claimReference }}</p>
                                <p class="mt-1 text-[0.78rem] text-[#707974]">Filed by {{ claim.filedBy }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-[#191c1c]">{{ claim.farmer.fullName }}</p>
                                <p class="mt-1 text-[0.78rem] text-[#707974]">{{ claim.farmer.farmerCode }}</p>
                            </td>
                            <td class="px-6 py-4 text-[#34423d]">
                                <p class="font-semibold text-[#191c1c]">{{ claim.ledger.year ? `Ledger ${claim.ledger.year}` : 'No ledger' }}</p>
                                <p class="mt-1 text-[0.78rem] text-[#707974]">{{ claim.ledger.paymentStatus }}</p>
                            </td>
                            <td class="px-6 py-4 text-[#34423d]">{{ claim.claimDate || 'Not recorded' }}</td>
                            <td class="px-6 py-4 font-bold text-[#191c1c]">PHP {{ Number(claim.claimAmount || 0).toFixed(2) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.14em]" :class="statusBadge(claim.status.value)">
                                    {{ claim.status.label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="claim.actions.showUrl" class="inline-flex items-center rounded-xl text-sm font-bold text-[#003629] transition hover:underline">
                                    View
                                </Link>
                            </td>
                        </template>
                    </tr>
                    <tr v-if="claims.data.length === 0">
                        <td colspan="7" class="px-6 py-16 text-center text-sm text-[#6a7872]">
                            {{ activeSection === 'records' ? 'No mortuary records matched the current filters.' : 'No eligible farmers are waiting for claim filing.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-4 border-t border-[#e4ebe7] bg-[#f8faf9] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-sm text-[#65736d]">Page {{ claims.current_page }} of {{ claims.last_page || 1 }}</span>
            <div class="flex flex-wrap items-center gap-2">
                <template v-for="link in claims.links" :key="link.label">
                    <span v-if="!link.url" class="inline-flex items-center rounded-xl border border-[#dbe2de] px-3 py-2 text-sm text-[#9aa6a1]" v-html="link.label" />
                    <Link
                        v-else
                        :href="link.url"
                        class="inline-flex items-center rounded-xl border px-3 py-2 text-sm font-bold transition"
                        :class="link.active ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#dbe2de] text-[#5f6b66] hover:bg-white'"
                        preserve-scroll
                        preserve-state
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </section>
</template>
