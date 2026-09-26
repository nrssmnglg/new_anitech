<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    activeFilterCount: { type: Number, required: true },
    totalPages: { type: Number, required: true },
    filteredTargetCount: { type: Number, required: true },
});

const emit = defineEmits(['apply', 'reset', 'open-export']);

const showFilters = ref(true);

const associations = computed(() => {
    if (!props.form.barangay_id) {
        return props.filterOptions.associations || [];
    }

    return (props.filterOptions.associations || []).filter(
        (association) => String(association.barangay_key || association.barangay_id) === String(props.form.barangay_id),
    );
});

watch(() => props.form.barangay_id, () => {
    if (props.form.association_id && !associations.value.some(
        (association) => String(association.key || association.id) === String(props.form.association_id),
    )) {
        props.form.association_id = '';
    }
});

const selectedBarangayName = computed(() => {
    if (!props.form.barangay_id) return '';
    return (props.filterOptions.barangays || []).find(
        (b) => String(b.key || b.id) === String(props.form.barangay_id),
    )?.name || '';
});

const selectedAssociationName = computed(() => {
    if (!props.form.association_id) return '';
    return (props.filterOptions.associations || []).find(
        (a) => String(a.key || a.id) === String(props.form.association_id),
    )?.name || '';
});

const selectedStatusLabel = computed(() => {
    if (!props.form.status) return '';
    return (props.filterOptions.statuses || []).find(
        (s) => s.value === props.form.status,
    )?.label || props.form.status;
});

const selectedMemberTypeName = computed(() => {
    if (!props.form.member_type_id) return '';
    const mt = (props.filterOptions.memberTypes || []).find(
        (m) => String(m.key || m.id) === String(props.form.member_type_id),
    );
    return mt ? `${mt.code} - ${mt.name}` : '';
});

const qualityOptions = [
    { value: '', label: 'All records' },
    { value: 'has_issues', label: 'Any quality issue' },
    { value: 'duplicate', label: 'Duplicate profile' },
    { value: 'invalid_mobile', label: 'Invalid mobile' },
    { value: 'mismatch', label: 'Info mismatch' },
    { value: 'incomplete', label: 'Incomplete profile' },
];

const selectedQualityLabel = computed(() => {
    if (!props.form.quality) return '';
    return qualityOptions.find((q) => q.value === props.form.quality)?.label || props.form.quality;
});

function removeFilter(key) {
    props.form[key] = '';
    emit('apply');
}
</script>

<template>
    <section class="rounded-xl border border-[#dde4de] bg-white shadow-sm transition-all duration-200">
        <!-- Toggle button header -->
        <button
            type="button"
            class="flex w-full items-center justify-between px-4 py-3 text-left transition-colors hover:bg-[#f9fbfa]"
            @click="showFilters = !showFilters"
        >
            <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#014d3c]/8 text-[#014d3c]">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                        <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="text-xs font-bold text-[#0f172a]">Registry Filters</span>
                <span v-if="activeFilterCount" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#014d3c] px-1.5 text-[0.6rem] font-bold text-white">
                    {{ activeFilterCount }}
                </span>
                <span class="text-xs text-[#94a3b8]">
                    · {{ filteredTargetCount }} record{{ filteredTargetCount === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[0.68rem] font-semibold text-[#64748b]">
                    {{ showFilters ? 'Hide filters' : 'Show filters' }}
                </span>
                <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#94a3b8] transition-transform duration-200" :class="showFilters ? 'rotate-180' : ''" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06-0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </div>
        </button>

        <!-- Collapsible filter body -->
        <div v-show="showFilters" class="border-t border-[#edf2ee] px-4 pb-4 pt-3.5">
            <form class="space-y-3" @submit.prevent="$emit('apply')">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <!-- Search input -->
                    <label class="space-y-1.5 xl:col-span-2">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Search Records</span>
                        <div class="relative">
                            <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#94a3b8]" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                            </svg>
                            <input
                                v-model="form.search"
                                type="text"
                                placeholder="Search by code, name, mobile..."
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-7 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                            <button
                                v-if="form.search"
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#64748b]"
                                @click="form.search = ''; $emit('apply')"
                            >
                                <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="currentColor"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/></svg>
                            </button>
                        </div>
                    </label>

                    <!-- Barangay -->
                    <label class="space-y-1.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Barangay</span>
                        <select
                            v-model="form.barangay_id"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option value="">All Barangays</option>
                            <option v-for="barangay in filterOptions.barangays" :key="barangay.key || barangay.id" :value="String(barangay.key || barangay.id)">
                                {{ barangay.name }}
                            </option>
                        </select>
                    </label>

                    <!-- Association -->
                    <label class="space-y-1.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Association</span>
                        <select
                            v-model="form.association_id"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option value="">All Associations</option>
                            <option v-for="association in associations" :key="association.key || association.id" :value="String(association.key || association.id)">
                                {{ association.name }}
                            </option>
                        </select>
                    </label>

                    <!-- Status -->
                    <label class="space-y-1.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select
                            v-model="form.status"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            @change="$emit('apply')"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </label>

                    <!-- Member Type -->
                    <label class="space-y-1.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Member Type</span>
                        <select
                            v-model="form.member_type_id"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                            <option value="">All Member Types</option>
                            <option v-for="memberType in filterOptions.memberTypes" :key="memberType.key || memberType.id" :value="String(memberType.key || memberType.id)">
                                {{ memberType.code }} - {{ memberType.name }}
                            </option>
                        </select>
                    </label>
                </div>

                <!-- Secondary filters & Actions row -->
                <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-1.5 text-xs text-[#64748b]">
                            <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Quality:</span>
                            <select
                                v-model="form.quality"
                                class="h-8 rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-2.5 text-xs text-[#0f172a] outline-none transition-all focus:border-[#014d3c] focus:bg-white"
                                @change="$emit('apply')"
                            >
                                <option v-for="q in qualityOptions" :key="q.value" :value="q.value">{{ q.label }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-[#dde4de] bg-[#f9fbfa] px-3.5 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                            @click="$emit('reset')"
                        >
                            <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/></svg>
                            Clear Filters
                        </button>
                        <button
                            type="submit"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#014d3c] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97]"
                        >
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
                            </svg>
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>

            <!-- Active filter chips -->
            <div v-if="activeFilterCount > 0" class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-[#edf2ee] pt-2.5">
                <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Active:</span>
                <span v-if="form.search" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Search: <strong>{{ form.search }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('search')">&times;</button>
                </span>
                <span v-if="form.barangay_id" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Barangay: <strong>{{ selectedBarangayName }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('barangay_id')">&times;</button>
                </span>
                <span v-if="form.association_id" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Association: <strong>{{ selectedAssociationName }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('association_id')">&times;</button>
                </span>
                <span v-if="form.status" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Status: <strong>{{ selectedStatusLabel }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('status')">&times;</button>
                </span>
                <span v-if="form.member_type_id" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Type: <strong>{{ selectedMemberTypeName }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('member_type_id')">&times;</button>
                </span>
                <span v-if="form.quality" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Quality: <strong>{{ selectedQualityLabel }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('quality')">&times;</button>
                </span>
            </div>
        </div>
    </section>
</template>
