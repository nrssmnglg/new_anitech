<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    statuses: { type: Array, required: true },
    barangays: { type: Array, required: true },
    associations: { type: Array, required: true },
    memberTypes: { type: Array, required: true },
    nextFarmerCode: { type: String, required: true },
    currentYear: { type: Number, required: true },
    lookupUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
    duplicateMatches: { type: Array, default: () => [] },
});

const form = useForm({
    record_mode: 'existing',
    farmer_id: '',
    existing_farmer_code: '',
    historical_year: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',
    birth_date: '',
    sex: '',
    civil_status: '',
    mobile_number: '',
    address: '',
    barangay_id: '',
    association_id: '',
    member_type_id: '',
    status: 'active',
    remarks: '',
    confirm_duplicate_override: false,
});

const search = ref('');
const searchResults = ref([]);
const searched = ref(false);
const searching = ref(false);
const searchError = ref('');
const selectedFarmer = ref(null);

const availableMemberTypes = computed(() => props.memberTypes.filter(item =>
    form.record_mode === 'new' || ['OM', 'OSC'].includes(item.code)));

const transactionType = computed(() => {
    if (form.record_mode === 'existing') return 'Renewal';
    const code = props.memberTypes.find(item => String(item.key || item.id) === String(form.member_type_id))?.code;
    return code ? (['OM', 'OSC'].includes(code) ? 'Renewal' : 'Application') : 'Select a member type';
});

const yearAlreadyRecorded = computed(() => selectedFarmer.value?.recordedYears?.some(year => Number(year) === Number(form.historical_year)) ?? false);

const selectedMemberType = computed(() => props.memberTypes.find(item => String(item.key || item.id) === String(form.member_type_id)) ?? null);

const readyToSave = computed(() => Boolean(
    form.historical_year
    && form.member_type_id
    && !yearAlreadyRecorded.value
    && (form.record_mode === 'new' || selectedFarmer.value)
));

const steps = [
    { key: 'farmer', label: '1. Identify Farmer', caption: 'Search or new record' },
    { key: 'transaction', label: '2. Set Transaction', caption: 'Year & member type' },
    { key: 'details', label: '3. Details & Save', caption: 'Profile & confirmation' },
];

const stepState = computed(() => ({
    farmer: form.record_mode === 'new' || selectedFarmer.value ? 'complete' : 'current',
    transaction: form.record_mode === 'new' || selectedFarmer.value
        ? (form.historical_year && form.member_type_id ? 'complete' : 'current')
        : 'pending',
    details: form.record_mode === 'new'
        ? (form.historical_year && form.member_type_id ? 'current' : 'pending')
        : (readyToSave.value ? 'complete' : 'pending'),
}));

async function searchFarmers() {
    searchError.value = '';
    if (search.value.trim().length < 2) {
        searchError.value = 'Enter at least two characters of the farmer code or name.';
        return;
    }
    searching.value = true;
    try {
        const { data } = await axios.get(props.lookupUrl, { params: { search: search.value.trim() } });
        searchResults.value = data.farmers || [];
        searched.value = true;
    } catch {
        searchError.value = 'Could not search farmer records. Please try again.';
    } finally {
        searching.value = false;
    }
}

function selectFarmer(farmer) {
    selectedFarmer.value = farmer;
    form.record_mode = 'existing';
    form.farmer_id = farmer.id;
    form.existing_farmer_code = farmer.farmerCode;
    const code = ['NSC', 'OSC'].includes(farmer.memberType) ? 'OSC' : 'OM';
    const memberType = props.memberTypes.find(item => item.code === code);
    form.member_type_id = memberType ? String(memberType.key || memberType.id) : '';
    form.clearErrors();
    searchResults.value = [];
}

function newFarmer() {
    selectedFarmer.value = null;
    form.record_mode = 'new';
    form.farmer_id = '';
    form.existing_farmer_code = '';
    form.member_type_id = '';
    form.clearErrors();
}

function switchToExisting() {
    form.record_mode = 'existing';
    form.clearErrors();
}

function changeFarmer() {
    selectedFarmer.value = null;
    form.record_mode = 'existing';
    form.farmer_id = '';
    form.existing_farmer_code = '';
    form.member_type_id = '';
    form.historical_year = '';
    searched.value = false;
    searchResults.value = [];
}

const filteredAssociations = computed(() => {
    if (!form.barangay_id) {
        return props.associations;
    }

    return props.associations.filter((association) => String(association.barangay_key || association.barangay_id) === String(form.barangay_id));
});

function syncAssociation() {
    if (!form.barangay_id) {
        form.association_id = '';
        return;
    }

    const matches = filteredAssociations.value.some((association) => String(association.key || association.id) === String(form.association_id));

    if (!matches) {
        form.association_id = filteredAssociations.value[0] ? String(filteredAssociations.value[0].key || filteredAssociations.value[0].id) : '';
    }
}

function submit() {
    form.post(props.storeUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Encode Old Record" />

    <AdminLayout title="Encode Old Record">
        <div class="mx-auto w-full max-w-[1536px] space-y-4 pb-12">
            <!-- Header Row -->
            <div class="flex flex-col gap-3 px-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="indexUrl" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 transition hover:text-[#003629]">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                            </svg>
                            <span>Farmer Registry</span>
                        </Link>
                        <span class="text-slate-300">/</span>
                        <span class="text-xs font-semibold text-slate-700">Encode Record</span>
                    </div>
                    <h1 class="text-xl font-bold tracking-tight text-[#0f172a] sm:text-2xl">Encode Old Record</h1>
                </div>

                <!-- Context Badges -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex items-center gap-1.5 rounded-lg border border-[#dde4de] bg-white px-3 py-1.5 text-xs shadow-xs">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-slate-400">Record Origin</span>
                        <span class="font-bold text-[#003629]">Old Record</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 rounded-lg border border-[#dde4de] bg-white px-3 py-1.5 text-xs shadow-xs">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-slate-400">Current Year</span>
                        <span class="font-bold text-[#0f172a]">{{ currentYear }}</span>
                    </div>
                    <div v-if="form.record_mode === 'new'" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs text-emerald-800 shadow-xs">
                        <span class="text-[0.62rem] font-bold uppercase tracking-wider text-emerald-600">Next Farmer Code</span>
                        <span class="font-mono font-bold">{{ nextFarmerCode }}</span>
                    </div>
                </div>
            </div>

            <!-- Stepper Progress Tracker -->
            <ol class="grid grid-cols-1 overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-xs sm:grid-cols-3">
                <li
                    v-for="(step, index) in steps"
                    :key="step.key"
                    class="flex items-center gap-3 border-b border-[#f1f5f9] p-3.5 sm:border-b-0 sm:border-r last:border-b-0 last:border-r-0 transition-colors"
                    :class="stepState[step.key] === 'current' ? 'bg-[#f4f8f6]' : ''"
                >
                    <span
                        class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold transition-colors"
                        :class="stepState[step.key] === 'complete' ? 'bg-[#003629] text-white' : stepState[step.key] === 'current' ? 'bg-[#e0eee9] text-[#003629] ring-2 ring-[#003629]' : 'bg-slate-100 text-slate-400'"
                    >
                        <svg v-if="stepState[step.key] === 'complete'" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                        </svg>
                        <span v-else>{{ index + 1 }}</span>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold" :class="stepState[step.key] === 'current' ? 'text-[#003629]' : 'text-slate-800'">{{ step.label }}</p>
                        <p class="truncate text-[0.68rem] text-slate-500">{{ step.caption }}</p>
                    </div>
                </li>
            </ol>

            <!-- Duplicate check validation error -->
            <div v-if="form.errors.duplicate_check" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
                {{ form.errors.duplicate_check }}
            </div>

            <!-- Duplicate matches warning alert -->
            <div v-if="duplicateMatches && duplicateMatches.length" class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 shadow-xs">
                <div class="flex items-start gap-3">
                    <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-amber-100 text-amber-700">
                        <svg viewBox="0 0 20 20" class="h-5 w-5" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex-1 space-y-3">
                        <div>
                            <p class="text-[0.62rem] font-bold uppercase tracking-wider text-amber-700">Duplicate Check</p>
                            <h3 class="text-sm font-bold text-amber-950">Possible existing farmer records found</h3>
                            <p class="mt-0.5 text-xs text-amber-800">Review these potential matches before encoding a new profile to avoid accidental duplicate records.</p>
                        </div>

                        <div class="space-y-2">
                            <div v-for="match in duplicateMatches" :key="match.id" class="flex flex-col gap-2 rounded-lg border border-amber-200/80 bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold text-slate-900">{{ match.full_name }}</p>
                                    <p class="text-[0.7rem] font-mono text-slate-500">{{ match.farmer_code }}<span v-if="match.barangay_name"> · {{ match.barangay_name }}</span><span v-if="match.member_type"> · {{ match.member_type }}</span></p>
                                    <p class="mt-1 text-[0.68rem] font-medium text-amber-700">{{ match.reasons.join(', ') }}</p>
                                </div>
                                <a v-if="match.show_url" :href="match.show_url" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-[#003629] underline hover:text-[#004f3b]">
                                    <span>Open record</span>
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 0 0-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 0 0 .75-.75v-4a.75.75 0 0 1 1.5 0v4A2.25 2.25 0 0 1 12.75 17h-8.5A2.25 2.25 0 0 1 2 14.75v-8.5A2.25 2.25 0 0 1 4.25 4h4a.75.75 0 0 1 0 1.5h-4Z" clip-rule="evenodd" />
                                        <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 0 0 1.06 1.06l7.996-7.996V9.25a.75.75 0 0 0 1.5 0V4.25a.75.75 0 0 0-.75-.75h-5a.75.75 0 0 0 0 1.5h3.436L6.194 12.753Z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-start gap-2.5 pt-1 text-xs font-medium text-amber-950">
                            <input v-model="form.confirm_duplicate_override" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-amber-300 text-[#003629] focus:ring-[#003629]">
                            <span>These records are different people. Continue saving this old record.</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Step 1: Identify Farmer (Lookup & Mode Selection) -->
            <section class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
                <!-- Card Header -->
                <div class="flex items-center justify-between border-b border-[#f1f5f9] px-5 py-3.5">
                    <div class="flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-[#003629]"></span>
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-slate-400">Step 1</span>
                        <span class="text-slate-200">·</span>
                        <h2 class="text-xs font-bold text-[#0f172a]">Identify Farmer Record</h2>
                    </div>

                    <!-- Mode Toggle Segmented Buttons -->
                    <div class="inline-flex rounded-lg border border-[#dde4de] bg-[#f8faf9] p-0.5">
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-xs font-bold transition"
                            :class="form.record_mode === 'existing' ? 'bg-[#003629] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            @click="switchToExisting"
                        >
                            Existing Farmer
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-xs font-bold transition"
                            :class="form.record_mode === 'new' ? 'bg-[#003629] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            @click="newFarmer"
                        >
                            New Historical Profile
                        </button>
                    </div>
                </div>

                <!-- Existing Mode -->
                <div v-if="form.record_mode === 'existing'" class="p-5">
                    <!-- If Farmer is already selected -->
                    <div v-if="selectedFarmer" class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3.5">
                            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#003629] text-sm font-bold text-white shadow-xs">
                                {{ selectedFarmer.fullName?.charAt(0) || 'F' }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-bold text-slate-900">{{ selectedFarmer.fullName }}</p>
                                    <span class="inline-flex items-center rounded-md border border-emerald-300 bg-emerald-100/70 px-2 py-0.5 font-mono text-[0.65rem] font-bold text-emerald-800">
                                        {{ selectedFarmer.farmerCode }}
                                    </span>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                    <span class="rounded border border-emerald-200 bg-white/80 px-2 py-0.5 text-[0.7rem] font-medium">
                                        Member Type: <strong class="text-slate-800">{{ selectedFarmer.memberType || 'N/A' }}</strong>
                                    </span>
                                    <span class="text-slate-300">·</span>
                                    <span>Recorded Years:</span>
                                    <span v-if="selectedFarmer.recordedYears && selectedFarmer.recordedYears.length" class="inline-flex flex-wrap gap-1">
                                        <span v-for="yr in selectedFarmer.recordedYears" :key="yr" class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-[0.68rem] font-semibold text-slate-700">
                                            {{ yr }}
                                        </span>
                                    </span>
                                    <span v-else class="italic text-slate-400">None yet</span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50"
                            @click="changeFarmer"
                        >
                            Change Farmer
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div v-else class="space-y-4">
                        <div class="space-y-1.5">
                            <label for="farmer-search-input" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                Farmer code or name
                            </label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <div class="relative flex-1">
                                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                                    </svg>
                                    <input
                                        id="farmer-search-input"
                                        v-model="search"
                                        type="search"
                                        aria-label="Farmer code or name"
                                        placeholder="Enter farmer code (e.g. 2026-0001) or full name..."
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                        @keydown.enter.prevent="searchFarmers"
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-[#003629] px-4 text-xs font-semibold text-white shadow-xs transition hover:bg-[#00483a] disabled:opacity-50"
                                    :disabled="searching"
                                    @click="searchFarmers"
                                >
                                    <svg v-if="searching" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>{{ searching ? 'Searching…' : 'Search' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Search Error -->
                        <div v-if="searchError" role="alert" class="rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs font-medium text-rose-700">
                            {{ searchError }}
                        </div>

                        <!-- Search Results -->
                        <div v-if="searchResults.length" class="space-y-2">
                            <p class="text-[0.62rem] font-bold uppercase tracking-wider text-slate-500">Matching Farmer Records ({{ searchResults.length }})</p>
                            <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-[#dde4de]">
                                <div
                                    v-for="farmer in searchResults"
                                    :key="farmer.id"
                                    class="flex flex-col gap-3 bg-white p-3.5 transition hover:bg-[#f9fbfa] sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-100 font-bold text-xs text-[#003629]">
                                            {{ farmer.fullName?.charAt(0) || 'F' }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-900">{{ farmer.fullName }}</span>
                                                <span class="font-mono text-[0.65rem] font-semibold text-slate-500">{{ farmer.farmerCode }}</span>
                                                <span v-if="farmer.memberType" class="rounded bg-slate-100 px-1.5 py-0.5 text-[0.62rem] font-bold text-slate-600">{{ farmer.memberType }}</span>
                                            </div>
                                            <p class="mt-0.5 text-[0.68rem] text-slate-500">
                                                Recorded years: {{ farmer.recordedYears?.join(', ') || 'None' }}
                                            </p>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex h-8 items-center justify-center rounded-lg border border-[#003629] bg-white px-3.5 text-xs font-bold text-[#003629] shadow-xs transition hover:bg-[#003629] hover:text-white"
                                        @click="selectFarmer(farmer)"
                                    >
                                        Use this farmer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- No results state -->
                        <div v-else-if="searched" class="rounded-xl border border-dashed border-slate-200 bg-[#f9fbfa] p-5 text-center">
                            <svg class="mx-auto h-7 w-7 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <p class="mt-2 text-xs font-bold text-slate-800">No matching farmer record found</p>
                            <p class="mt-0.5 text-[0.7rem] text-slate-500">Check the spelling, or proceed with encoding a new historical farmer profile.</p>
                            <button
                                type="button"
                                class="mt-3 inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-[#003629] px-3.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a]"
                                @click="newFarmer"
                            >
                                This is a new farmer record
                            </button>
                        </div>
                    </div>

                    <!-- Farmer selection error -->
                    <p v-if="form.errors.farmer_id || form.errors.existing_farmer_code" role="alert" class="mt-3 text-xs font-medium text-rose-600">
                        {{ form.errors.farmer_id || form.errors.existing_farmer_code }}
                    </p>
                </div>

                <!-- New Farmer Mode Banner -->
                <div v-else class="p-5">
                    <div class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-600 text-white shadow-xs">
                                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="currentColor">
                                    <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" />
                                </svg>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-emerald-950">New Historical Profile</span>
                                <span class="rounded bg-emerald-200/80 px-2 py-0.5 font-mono text-[0.68rem] font-bold text-emerald-800">
                                    Preview: {{ nextFarmerCode }}
                                </span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50"
                            @click="changeFarmer"
                        >
                            Search Existing Instead
                        </button>
                    </div>
                </div>
            </section>

            <!-- Main Form & Sticky Sidebar Layout (when farmer is determined) -->
            <div v-if="form.record_mode === 'new' || selectedFarmer" class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                <form class="space-y-4" @submit.prevent="submit">
                    <!-- Main Form Container -->
                    <div class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white p-5 shadow-xs space-y-5">
                        <!-- Historical Transaction Fields -->
                        <div class="space-y-4">
                            <div class="grid gap-3.5 sm:grid-cols-2">
                                <div>
                                    <label for="historical_year" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Year to record
                                    </label>
                                    <input
                                        id="historical_year"
                                        v-model="form.historical_year"
                                        type="number"
                                        min="1900"
                                        :max="currentYear"
                                        required
                                        aria-label="Year to record"
                                        placeholder="e.g. 2024"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    />
                                    <p v-if="form.errors.historical_year" role="alert" class="mt-1 text-[0.7rem] font-medium text-rose-600">
                                        {{ form.errors.historical_year }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border p-3"
                                    :class="transactionType === 'Renewal' ? 'border-emerald-200 bg-emerald-50/60' : transactionType === 'Application' ? 'border-sky-200 bg-sky-50/60' : 'border-[#dde4de] bg-[#f9fbfa]'"
                                >
                                    <p class="text-[0.62rem] font-bold uppercase tracking-wider text-slate-500">Transaction Type</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="text-sm font-bold" :class="transactionType === 'Renewal' ? 'text-emerald-800' : transactionType === 'Application' ? 'text-sky-800' : 'text-slate-600'">
                                            {{ transactionType }}
                                        </span>
                                        <span class="rounded px-1.5 py-0.5 text-[0.65rem] font-semibold" :class="transactionType === 'Renewal' ? 'bg-emerald-200/70 text-emerald-900' : 'bg-sky-200/70 text-sky-900'">
                                            {{ form.record_mode === 'existing' ? 'Renewal for existing farmer' : 'Based on member type' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="yearAlreadyRecorded" role="alert" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-700">
                                This farmer already has a transaction recorded for {{ form.historical_year }}. Select another year.
                            </div>

                            <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label for="member_type_id" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Member Type
                                    </label>
                                    <select
                                        id="member_type_id"
                                        v-model="form.member_type_id"
                                        aria-label="Member Type"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    >
                                        <option value="">Select member type</option>
                                        <option v-for="item in availableMemberTypes" :key="item.key || item.id" :value="String(item.key || item.id)">
                                            {{ item.code }} — {{ item.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.member_type_id" class="mt-1 text-[0.7rem] font-medium text-rose-600">
                                        {{ form.errors.member_type_id }}
                                    </p>
                                </div>

                                <div v-if="form.record_mode === 'new'">
                                    <label for="status" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Registry Status
                                    </label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        aria-label="Registry Status"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    >
                                        <option v-for="item in statuses" :key="item.value" :value="item.value">
                                            {{ item.label }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.status" class="mt-1 text-[0.7rem] font-medium text-rose-600">
                                        {{ form.errors.status }}
                                    </p>
                                </div>

                                <div class="rounded-lg border border-[#dde4de] bg-[#f8faf9] px-3 py-2">
                                    <p class="text-[0.62rem] font-bold uppercase tracking-wider text-slate-400">Record Origin</p>
                                    <p class="mt-0.5 text-xs font-bold text-slate-800">Old Record</p>
                                </div>

                                <div v-if="form.record_mode === 'new'" class="rounded-lg border border-[#dde4de] bg-[#f8faf9] px-3 py-2 sm:col-span-2 lg:col-span-3">
                                    <p class="text-[0.62rem] font-bold uppercase tracking-wider text-slate-400">Official Registration Date</p>
                                    <p class="mt-0.5 text-xs font-bold text-slate-800">
                                        {{ form.historical_year ? `February 14, ${form.historical_year}` : 'Set historical year above' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Personal & Location Fields (Only for New Farmer) -->
                        <template v-if="form.record_mode === 'new'">
                            <div class="border-t border-slate-100"></div>

                            <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label for="first_name" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        First Name
                                    </label>
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        aria-label="First Name"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    />
                                    <p v-if="form.errors.first_name" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.first_name }}</p>
                                </div>

                                <div>
                                    <label for="middle_name" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Middle Name (Optional)
                                    </label>
                                    <input
                                        id="middle_name"
                                        v-model="form.middle_name"
                                        type="text"
                                        aria-label="Middle Name"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    />
                                    <p v-if="form.errors.middle_name" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.middle_name }}</p>
                                </div>

                                <div>
                                    <label for="last_name" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Last Name
                                    </label>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        aria-label="Last Name"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    />
                                    <p v-if="form.errors.last_name" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.last_name }}</p>
                                </div>

                                <div>
                                    <label for="suffix" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Suffix (Optional)
                                    </label>
                                    <input
                                        id="suffix"
                                        v-model="form.suffix"
                                        type="text"
                                        aria-label="Suffix"
                                        placeholder="e.g. Jr., III"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    />
                                    <p v-if="form.errors.suffix" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.suffix }}</p>
                                </div>

                                <div>
                                    <label for="birth_date" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Birth Date
                                    </label>
                                    <input
                                        id="birth_date"
                                        v-model="form.birth_date"
                                        type="date"
                                        aria-label="Birth Date"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    />
                                    <p v-if="form.errors.birth_date" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.birth_date }}</p>
                                </div>

                                <div>
                                    <label for="sex" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Sex
                                    </label>
                                    <select
                                        id="sex"
                                        v-model="form.sex"
                                        aria-label="Sex"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    >
                                        <option value="">Select sex</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    <p v-if="form.errors.sex" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.sex }}</p>
                                </div>

                                <div>
                                    <label for="civil_status" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Civil Status (Optional)
                                    </label>
                                    <select
                                        id="civil_status"
                                        v-model="form.civil_status"
                                        aria-label="Civil Status"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    >
                                        <option value="">Select status</option>
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="widowed">Widowed</option>
                                        <option value="separated">Separated</option>
                                    </select>
                                    <p v-if="form.errors.civil_status" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.civil_status }}</p>
                                </div>

                                <div>
                                    <label for="mobile_number" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Mobile Number
                                    </label>
                                    <input
                                        id="mobile_number"
                                        v-model="form.mobile_number"
                                        type="text"
                                        aria-label="Mobile Number"
                                        placeholder="09XXXXXXXXX"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    />
                                    <p v-if="form.errors.mobile_number" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.mobile_number }}</p>
                                </div>
                            </div>

                            <div class="border-t border-slate-100"></div>

                            <div class="grid gap-3.5 sm:grid-cols-2">
                                <div>
                                    <label for="barangay_id" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Barangay
                                    </label>
                                    <select
                                        id="barangay_id"
                                        v-model="form.barangay_id"
                                        aria-label="Barangay"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                        @change="syncAssociation"
                                    >
                                        <option value="">Select barangay</option>
                                        <option v-for="item in barangays" :key="item.key || item.id" :value="String(item.key || item.id)">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.barangay_id" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.barangay_id }}</p>
                                </div>

                                <div>
                                    <label for="association_id" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Association
                                    </label>
                                    <select
                                        id="association_id"
                                        v-model="form.association_id"
                                        aria-label="Association"
                                        class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    >
                                        <option value="">No association assigned</option>
                                        <option v-for="item in filteredAssociations" :key="item.key || item.id" :value="String(item.key || item.id)">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.association_id" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.association_id }}</p>
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="address" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                        Full Address (Optional)
                                    </label>
                                    <textarea
                                        id="address"
                                        v-model="form.address"
                                        rows="2"
                                        aria-label="Address"
                                        placeholder="Sitio / Purok / Street address..."
                                        class="w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] p-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                                    ></textarea>
                                    <p v-if="form.errors.address" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.address }}</p>
                                </div>
                            </div>
                        </template>

                        <!-- Remarks Field -->
                        <div class="border-t border-slate-100"></div>

                        <div>
                            <label for="remarks" class="block text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">
                                Remarks
                            </label>
                            <textarea
                                id="remarks"
                                v-model="form.remarks"
                                rows="3"
                                aria-label="Remarks"
                                placeholder="Enter any historical context, masterlist book/page reference, or record notes..."
                                class="w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] p-3 text-xs text-[#0f172a] outline-none transition focus:border-[#003629] focus:bg-white focus:ring-2 focus:ring-[#003629]/10"
                            ></textarea>
                            <p v-if="form.errors.remarks" class="mt-1 text-[0.7rem] font-medium text-rose-600">{{ form.errors.remarks }}</p>
                        </div>
                    </div>

                    <!-- Sticky Save Bar -->
                    <div class="sticky bottom-3 z-10 flex items-center justify-between gap-3 rounded-xl border border-[#dde4de] bg-white/95 p-3 shadow-md backdrop-blur">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <span class="flex h-2 w-2 rounded-full" :class="readyToSave ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                            <span>{{ readyToSave ? 'Ready to record historical entry' : 'Complete required fields to save' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="indexUrl"
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-[#003629] px-5 text-xs font-bold text-white shadow-xs transition hover:bg-[#00483a] disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="form.processing || !readyToSave"
                            >
                                <svg v-if="form.processing" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                <span>{{ form.processing ? 'Saving…' : 'Save Old Record' }}</span>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Right Column Sticky Sidebar -->
                <aside class="space-y-4 xl:sticky xl:top-4">
                    <!-- Record Summary Card -->
                    <div class="overflow-hidden rounded-2xl border border-[#dde4de] bg-white shadow-xs">
                        <div class="border-b border-[#f1f5f9] px-4 py-3 bg-[#f8faf9]">
                            <div class="flex items-center gap-2">
                                <span class="flex h-2 w-2 rounded-full bg-[#003629]"></span>
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-slate-400">Record Summary</span>
                                <span class="text-slate-200">·</span>
                                <h3 class="text-xs font-bold text-[#0f172a]">Before You Save</h3>
                            </div>
                        </div>

                        <dl class="divide-y divide-slate-100 px-4 text-xs">
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Record Mode</dt>
                                <dd class="font-bold text-slate-800">
                                    {{ form.record_mode === 'new' ? 'New Farmer Profile' : 'Existing Farmer' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Farmer</dt>
                                <dd class="font-bold text-right text-slate-900 truncate max-w-[180px]">
                                    {{ selectedFarmer?.fullName || ([form.first_name, form.last_name].filter(Boolean).join(' ') || 'New Farmer Record') }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Farmer Code</dt>
                                <dd class="font-mono font-bold text-slate-800">
                                    {{ selectedFarmer?.farmerCode || nextFarmerCode }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Historical Year</dt>
                                <dd class="font-mono font-bold text-slate-800">
                                    {{ form.historical_year || 'Not set' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Member Type</dt>
                                <dd class="font-semibold text-slate-800">
                                    {{ selectedMemberType?.code || 'Not set' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Transaction Type</dt>
                                <dd class="font-bold" :class="transactionType === 'Renewal' ? 'text-emerald-700' : transactionType === 'Application' ? 'text-sky-700' : 'text-slate-500'">
                                    {{ transactionType }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Official Date</dt>
                                <dd class="font-medium text-slate-700">
                                    {{ form.historical_year ? `Feb 14, ${form.historical_year}` : 'Not set' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </div>
    </AdminLayout>
</template>
