<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    claims: { type: Object, required: true },
    activeSection: { type: String, required: true },
});

const compactMode = ref(false);

function statusBadge(value) {
    const val = String(value || '').toLowerCase();
    if (val === 'approved' || val === 'released' || val === 'completed') {
        return 'bg-[#f0fdf4] text-[#15803d] border border-[#bbf7d0]';
    }
    if (val === 'rejected') {
        return 'bg-[#fff1f2] text-[#be123c] border border-[#fecdd3]';
    }
    if (val === 'eligible' || val === 'active') {
        return 'bg-[#e6f5ec] text-[#0f6b45] border border-[#bbf7d0]';
    }
    return 'bg-[#fefce8] text-[#854d0e] border border-[#fef08a]';
}

function initials(name) {
    return String(name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part[0])
        .join('')
        .toUpperCase() || 'FC';
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
        <!-- Table Header Toolbar -->
        <div class="flex flex-col gap-2 border-b border-[#f1f5f9] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-[#014d3c]"></span>
                <h2 class="text-xs font-bold text-[#0f172a]">
                    {{ activeSection === 'records' ? 'Filed Claim Records' : 'Eligible Farmers for Claim Filing' }}
                </h2>
                <span class="rounded-md bg-[#f1f5f3] px-2 py-0.5 text-[0.68rem] font-semibold text-[#014d3c]">
                    {{ claims.total }} {{ activeSection === 'records' ? 'record' : 'farmer' }}{{ claims.total === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="text-[0.68rem] font-semibold text-[#64748b] hover:text-[#014d3c] transition"
                    @click="compactMode = !compactMode"
                >
                    {{ compactMode ? 'Comfortable view' : 'Compact view' }}
                </button>
                <span class="text-xs text-[#94a3b8]">|</span>
                <span class="text-[0.68rem] text-[#64748b]">Showing {{ claims.data.length }}</span>
            </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <!-- Queue Table Header -->
                <thead v-if="activeSection === 'queue'">
                    <tr class="border-b border-[#dde4de] bg-[#f8faf9] text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                        <th class="px-4 py-2.5">Farmer</th>
                        <th class="px-4 py-2.5">Member Type</th>
                        <th class="px-4 py-2.5">Ledger Year</th>
                        <th class="px-4 py-2.5 text-center">Contributed</th>
                        <th class="px-4 py-2.5">Expected Claim</th>
                        <th class="px-4 py-2.5">Eligibility</th>
                        <th class="px-4 py-2.5 text-right">Action</th>
                    </tr>
                </thead>

                <!-- Records Table Header -->
                <thead v-else>
                    <tr class="border-b border-[#dde4de] bg-[#f8faf9] text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                        <th class="px-4 py-2.5">Reference</th>
                        <th class="px-4 py-2.5">Farmer</th>
                        <th class="px-4 py-2.5">Ledger Info</th>
                        <th class="px-4 py-2.5">Claim Date</th>
                        <th class="px-4 py-2.5">Claim Amount</th>
                        <th class="px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5 text-right">Action</th>
                    </tr>
                </thead>

                <!-- Body -->
                <tbody class="divide-y divide-[#f1f5f9]">
                    <tr v-for="claim in claims.data" :key="claim.id" class="transition hover:bg-[#fbfcfb]">
                        <!-- Queue Row -->
                        <template v-if="activeSection === 'queue'">
                            <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#e6f5ec] text-[0.65rem] font-bold text-[#0f6b45]">
                                        {{ initials(claim.farmer.fullName) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-[#0f172a]">{{ claim.farmer.fullName }}</p>
                                        <div class="flex items-center gap-1.5 text-[0.65rem] text-[#64748b]">
                                            <span class="font-mono text-[#014d3c] font-semibold">{{ claim.farmer.farmerCode }}</span>
                                            <span v-if="claim.farmer.barangay" class="truncate">• {{ claim.farmer.barangay }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 text-[#475569]" :class="compactMode ? 'py-2' : 'py-3'">
                                {{ claim.farmer.memberType?.name || claim.farmer.memberType?.code || 'Standard' }}
                            </td>
                            <td class="px-4 font-mono font-semibold text-[#0f172a]" :class="compactMode ? 'py-2' : 'py-3'">
                                {{ claim.ledger.year || 'No ledger' }}
                            </td>
                            <td class="px-4 text-center font-bold text-[#0f172a]" :class="compactMode ? 'py-2' : 'py-3'">
                                {{ claim.ledger.contributionYears || 0 }} {{ Number(claim.ledger.contributionYears || 0) === 1 ? 'year' : 'years' }}
                            </td>
                            <td class="px-4 font-bold text-[#014d3c]" :class="compactMode ? 'py-2' : 'py-3'">
                                PHP {{ Number(claim.claimAmount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </td>
                            <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[0.62rem] font-bold capitalize" :class="statusBadge(claim.status.value)">
                                    {{ claim.status.label }}
                                </span>
                            </td>
                            <td class="px-4 text-right" :class="compactMode ? 'py-2' : 'py-3'">
                                <Link
                                    :href="claim.actions.createUrl"
                                    class="inline-flex h-8 items-center justify-center rounded-lg bg-[#014d3c] px-3.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#01362a] active:scale-[0.97]"
                                >
                                    Process
                                </Link>
                            </td>
                        </template>

                        <!-- Records Row -->
                        <template v-else>
                            <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                                <span class="font-mono text-xs font-bold text-[#014d3c]">{{ claim.claimReference }}</span>
                                <p class="mt-0.5 text-[0.65rem] text-[#64748b]">By: {{ claim.filedBy || 'Staff' }}</p>
                            </td>
                            <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                                <p class="font-bold text-[#0f172a]">{{ claim.farmer.fullName }}</p>
                                <p class="font-mono text-[0.65rem] text-[#64748b]">{{ claim.farmer.farmerCode }}</p>
                            </td>
                            <td class="px-4 text-[#475569]" :class="compactMode ? 'py-2' : 'py-3'">
                                <span class="font-semibold text-[#0f172a]">{{ claim.ledger.year ? `Ledger ${claim.ledger.year}` : 'No ledger' }}</span>
                                <p class="mt-0.5 text-[0.65rem] text-[#64748b]">{{ claim.ledger.paymentStatus || 'Settled' }}</p>
                            </td>
                            <td class="px-4 text-[#475569]" :class="compactMode ? 'py-2' : 'py-3'">
                                {{ claim.claimDate || 'Not recorded' }}
                            </td>
                            <td class="px-4 font-mono font-bold text-[#0f172a]" :class="compactMode ? 'py-2' : 'py-3'">
                                PHP {{ Number(claim.claimAmount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </td>
                            <td class="px-4" :class="compactMode ? 'py-2' : 'py-3'">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[0.62rem] font-bold capitalize" :class="statusBadge(claim.status.value)">
                                    {{ claim.status.label }}
                                </span>
                            </td>
                            <td class="px-4 text-right" :class="compactMode ? 'py-2' : 'py-3'">
                                <Link
                                    :href="claim.actions.showUrl"
                                    class="inline-flex h-7 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-2.5 text-xs font-bold text-[#014d3c] transition hover:bg-[#f1f5f3]"
                                >
                                    View Record
                                </Link>
                            </td>
                        </template>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="claims.data.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-[#f1f5f3] text-[#014d3c]">
                                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="mt-2 text-xs font-bold text-[#0f172a]">
                                {{ activeSection === 'records' ? 'No mortuary records found' : 'No eligible farmers waiting' }}
                            </p>
                            <p class="mt-0.5 text-[0.7rem] text-[#64748b]">
                                {{ activeSection === 'records' ? 'No claim records match the current filters.' : 'All eligible farmers have been processed.' }}
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col gap-2 border-t border-[#f1f5f9] bg-[#f8faf9] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-[0.68rem] text-[#64748b]">Page {{ claims.current_page }} of {{ claims.last_page || 1 }}</span>
            <div class="flex flex-wrap items-center gap-1.5">
                <template v-for="link in claims.links" :key="link.label">
                    <span v-if="!link.url" class="inline-flex h-7 items-center rounded-md border border-[#dde4de] px-2 text-[0.68rem] text-[#94a3b8]" v-html="link.label" />
                    <Link
                        v-else
                        :href="link.url"
                        class="inline-flex h-7 items-center rounded-md border px-2 text-[0.68rem] font-bold transition"
                        :class="link.active ? 'border-[#014d3c] bg-[#014d3c] text-white shadow-xs' : 'border-[#dde4de] bg-white text-[#475569] hover:bg-[#f1f5f3]'"
                        preserve-scroll
                        preserve-state
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </section>
</template>
