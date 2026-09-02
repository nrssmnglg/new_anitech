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
    <section class="overflow-hidden rounded-lg border border-[#dfe5e1] bg-white">
        <div class="flex flex-col gap-2 border-b border-[#e4ebe7] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-[#191c1c]">{{ activeSection === 'records' ? 'Filed claims' : 'Eligible farmers' }}</h2>
                <p class="mt-0.5 text-[0.68rem] text-[#697772]">{{ claims.total }} {{ activeSection === 'records' ? 'claim record' : 'eligible farmer' }}{{ claims.total === 1 ? '' : 's' }}</p>
            </div>
            <div class="text-[0.68rem] text-[#697772]">Showing {{ claims.data.length }}</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#e5ece8] text-xs">
                <thead v-if="activeSection === 'queue'" class="bg-[#f2f4f3]">
                    <tr class="text-left text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#6d7873]">
                        <th class="px-4 py-2.5">Farmer</th>
                        <th class="px-4 py-2.5">Member Type</th>
                        <th class="px-4 py-2.5">Ledger</th>
                        <th class="px-4 py-2.5 text-center">Years</th>
                        <th class="px-4 py-2.5">Expected Claim</th>
                        <th class="px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5 text-right">Action</th>
                    </tr>
                </thead>
                <thead v-else class="bg-[#f2f4f3]">
                    <tr class="text-left text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#6d7873]">
                        <th class="px-4 py-2.5">Reference</th><th class="px-4 py-2.5">Farmer</th><th class="px-4 py-2.5">Ledger</th><th class="px-4 py-2.5">Claim Date</th><th class="px-4 py-2.5">Amount</th><th class="px-4 py-2.5">Status</th><th class="px-4 py-2.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf2ef]">
                    <tr v-for="claim in claims.data" :key="claim.id" class="transition hover:bg-[#fbfdfc]">
                        <template v-if="activeSection === 'queue'">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-[#e4f0e9] text-[0.62rem] font-semibold text-[#245444]">
                                        {{ claim.farmer.fullName?.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || 'F' }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-[#191c1c]">{{ claim.farmer.fullName }}</p>
                                        <p class="mt-0.5 text-[0.62rem] text-[#7b8882]">{{ claim.farmer.farmerCode }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-[#34423d]">{{ claim.farmer.memberType?.name || claim.farmer.memberType?.code || 'Not set' }}</td>
                            <td class="px-4 py-2.5 text-[#34423d]">{{ claim.ledger.year || 'No ledger' }}</td>
                            <td class="px-4 py-2.5 text-center text-[#34423d]">{{ claim.ledger.contributionYears || 0 }}</td>
                            <td class="px-4 py-2.5 font-semibold text-[#191c1c]">PHP {{ Number(claim.claimAmount || 0).toFixed(2) }}</td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex rounded-md px-2 py-1 text-[0.58rem] font-semibold" :class="statusBadge(claim.status.value)">
                                    {{ claim.status.label }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <Link :href="claim.actions.createUrl" class="inline-flex h-8 items-center rounded-md bg-[#003629] px-3 text-[0.65rem] font-semibold text-white transition hover:bg-[#0d4637]">
                                    Process
                                </Link>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-4 py-2.5">
                                <p class="whitespace-nowrap font-mono text-[0.66rem] font-semibold text-[#31584a]">{{ claim.claimReference }}</p>
                                <p class="mt-0.5 text-[0.62rem] text-[#707974]">Filed by {{ claim.filedBy }}</p>
                            </td>
                            <td class="px-4 py-2.5">
                                <p class="font-semibold text-[#191c1c]">{{ claim.farmer.fullName }}</p>
                                <p class="mt-0.5 text-[0.62rem] text-[#707974]">{{ claim.farmer.farmerCode }}</p>
                            </td>
                            <td class="px-4 py-2.5 text-[#34423d]">
                                <p class="font-semibold text-[#191c1c]">{{ claim.ledger.year ? `Ledger ${claim.ledger.year}` : 'No ledger' }}</p>
                                <p class="mt-0.5 text-[0.62rem] text-[#707974]">{{ claim.ledger.paymentStatus }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-[#34423d]">{{ claim.claimDate || 'Not recorded' }}</td>
                            <td class="px-4 py-2.5 font-semibold text-[#191c1c]">PHP {{ Number(claim.claimAmount || 0).toFixed(2) }}</td>
                            <td class="px-4 py-2.5"><span class="inline-flex rounded-md px-2 py-1 text-[0.58rem] font-semibold" :class="statusBadge(claim.status.value)">
                                    {{ claim.status.label }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <Link :href="claim.actions.showUrl" class="inline-flex h-8 items-center rounded-md bg-[#003629] px-3 text-[0.65rem] font-semibold text-white transition hover:bg-[#0d4637]">
                                    View Record
                                </Link>
                            </td>
                        </template>
                    </tr>
                    <tr v-if="claims.data.length === 0">
                        <td colspan="7" class="px-4 py-10 text-center text-xs text-[#6a7872]">
                            {{ activeSection === 'records' ? 'No mortuary records matched the current filters.' : 'No eligible farmers are waiting for claim filing.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-[#e4ebe7] bg-[#f8faf9] px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-[0.68rem] text-[#65736d]">Page {{ claims.current_page }} of {{ claims.last_page || 1 }}</span>
            <div class="flex flex-wrap items-center gap-2">
                <template v-for="link in claims.links" :key="link.label">
                    <span v-if="!link.url" class="inline-flex h-8 items-center rounded-md border border-[#dbe2de] px-2.5 text-[0.68rem] text-[#9aa6a1]" v-html="link.label" />
                    <Link
                        v-else
                        :href="link.url"
                        class="inline-flex h-8 items-center rounded-md border px-2.5 text-[0.68rem] font-semibold transition"
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
