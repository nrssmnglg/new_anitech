<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const emit = defineEmits(['apply', 'reset']);

const showFilters = ref(true);

const activeFilterCount = computed(() => {
    let count = 0;
    if (props.form.search) count++;
    if (props.form.source) count++;
    if (props.form.status) count++;
    if (props.form.year) count++;
    if (props.form.barangay_id) count++;
    return count;
});

const selectedSourceName = computed(() => {
    if (!props.form.source) return '';
    return (props.filterOptions.sources || []).find((s) => s.value === props.form.source)?.label || props.form.source;
});

const selectedStatusName = computed(() => {
    if (!props.form.status) return '';
    return (props.filterOptions.statuses || []).find((s) => s.value === props.form.status)?.label || props.form.status;
});

const selectedBarangayName = computed(() => {
    if (!props.form.barangay_id) return '';
    return (props.filterOptions.barangays || []).find((b) => String(b.id) === String(props.form.barangay_id))?.name || '';
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
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#003629]/10 text-[#003629]">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                        <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="text-xs font-bold text-[#0f172a]">Queue Filter Panel</span>
                <span
                    v-if="activeFilterCount"
                    class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#003629] px-1.5 text-[0.6rem] font-bold text-white"
                >
                    {{ activeFilterCount }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[0.68rem] font-semibold text-[#64748b]">
                    {{ showFilters ? 'Hide filters' : 'Show filters' }}
                </span>
                <svg
                    viewBox="0 0 20 20"
                    class="h-4 w-4 text-[#94a3b8] transition-transform duration-200"
                    :class="showFilters ? 'rotate-180' : ''"
                    fill="currentColor"
                >
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06-0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </div>
        </button>

        <!-- Collapsible filter body -->
        <div v-show="showFilters" class="border-t border-[#edf2ee] px-4 pb-4 pt-3.5">
            <form class="space-y-3" @submit.prevent="$emit('apply')">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <!-- Search input -->
                    <label class="space-y-1.5 xl:col-span-1">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Search Records</span>
                        <div class="relative">
                            <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#94a3b8]" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                            </svg>
                            <input
                                v-model="form.search"
                                type="text"
                                placeholder="App #, code, name..."
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-7 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                            >
                            <button
                                v-if="form.search"
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#64748b]"
                                @click="form.search = ''; $emit('apply')"
                            >
                                <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="currentColor">
                                    <path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z" />
                                </svg>
                            </button>
                        </div>
                    </label>

                    <!-- Source -->
                    <label class="space-y-1.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Registration Source</span>
                        <select
                            v-model="form.source"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                            @change="$emit('apply')"
                        >
                            <option value="">All Sources</option>
                            <option v-for="source in filterOptions.sources" :key="source.value" :value="source.value">
                                {{ source.label }}
                            </option>
                        </select>
                    </label>

                    <!-- Status -->
                    <label class="space-y-1.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Application Status</span>
                        <select
                            v-model="form.status"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                            @change="$emit('apply')"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="status in filterOptions.statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </label>

                    <!-- Year -->
                    <label class="space-y-1.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Fiscal Year</span>
                        <select
                            v-model="form.year"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                            @change="$emit('apply')"
                        >
                            <option value="">All Years</option>
                            <option v-for="year in filterOptions.years" :key="year.value" :value="year.value">
                                {{ year.label }}
                            </option>
                        </select>
                    </label>

                    <!-- Barangay -->
                    <label class="space-y-1.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Barangay Location</span>
                        <select
                            v-model="form.barangay_id"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                            @change="$emit('apply')"
                        >
                            <option value="">All Barangays</option>
                            <option v-for="barangay in filterOptions.barangays" :key="barangay.id" :value="String(barangay.id)">
                                {{ barangay.name }}
                            </option>
                        </select>
                    </label>
                </div>

                <!-- Secondary row with Reset & Apply buttons -->
                <div class="flex items-center justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-[#dde4de] bg-[#f9fbfa] px-3.5 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                        @click="$emit('reset')"
                    >
                        <svg viewBox="0 0 16 16" class="h-3 w-3" fill="currentColor">
                            <path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z" />
                        </svg>
                        Reset
                    </button>
                    <button
                        type="submit"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#003629] px-4 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#00483a] hover:shadow-md active:scale-[0.97]"
                    >
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
                        </svg>
                        Apply Filters
                    </button>
                </div>
            </form>

            <!-- Active filter chips -->
            <div v-if="activeFilterCount > 0" class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-[#edf2ee] pt-2.5">
                <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Active:</span>
                <span v-if="form.search" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Search: <strong>{{ form.search }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('search')">&times;</button>
                </span>
                <span v-if="form.source" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Source: <strong>{{ selectedSourceName }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('source')">&times;</button>
                </span>
                <span v-if="form.status" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Status: <strong>{{ selectedStatusName }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('status')">&times;</button>
                </span>
                <span v-if="form.year" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Year: <strong>{{ form.year }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('year')">&times;</button>
                </span>
                <span v-if="form.barangay_id" class="inline-flex items-center gap-1 rounded-lg border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[0.68rem] text-[#334155]">
                    Barangay: <strong>{{ selectedBarangayName }}</strong>
                    <button type="button" class="ml-0.5 text-[#94a3b8] hover:text-[#dc2626]" @click="removeFilter('barangay_id')">&times;</button>
                </span>
            </div>
        </div>
    </section>
</template>
