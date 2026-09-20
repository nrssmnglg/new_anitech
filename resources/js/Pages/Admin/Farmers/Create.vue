<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import EditSectionCard from '../../../Components/Admin/Farmers/EditSectionCard.vue';

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
        searchResults.value = data.farmers;
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
        <div class="old-record-compact mx-auto w-full max-w-[1440px] space-y-5 pb-10">
            <section class="overflow-hidden rounded-xl bg-[#073f33] text-white shadow-sm">
                <div class="flex flex-col gap-5 px-6 py-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div>
                        <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#a9d7c7]">
                            <span class="h-px w-7 bg-[#79bba4]"></span>
                            Farmer registry
                        </div>
                        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Encode a historical record</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-[#cbe3da]">Search before encoding. Existing farmers receive a renewal for the selected year; new records follow the selected member type.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-center sm:min-w-[310px]">
                        <div class="rounded-lg border border-white/15 bg-white/[0.07] px-4 py-3">
                            <p class="text-[0.62rem] font-semibold uppercase tracking-[0.12em] text-[#a9d7c7]">Record origin</p>
                            <p class="mt-1 text-sm font-semibold">Old Record</p>
                        </div>
                        <div class="rounded-lg border border-white/15 bg-white/[0.07] px-4 py-3">
                            <p class="text-[0.62rem] font-semibold uppercase tracking-[0.12em] text-[#a9d7c7]">Current year</p>
                            <p class="mt-1 text-sm font-semibold">{{ currentYear }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <ol class="grid overflow-hidden rounded-xl border border-[#dce5e0] bg-white sm:grid-cols-3">
                <li v-for="(step, index) in [{ key: 'farmer', label: 'Find farmer', caption: 'Search or create' }, { key: 'transaction', label: 'Set transaction', caption: 'Year and member type' }, { key: 'details', label: 'Review & save', caption: 'Confirm the record' }]" :key="step.key" class="flex items-center gap-3 border-b border-[#e5ebe8] px-5 py-4 last:border-0 sm:border-b-0 sm:border-r">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold" :class="stepState[step.key] === 'complete' ? 'bg-[#0b7658] text-white' : stepState[step.key] === 'current' ? 'bg-[#dcefe8] text-[#07513e] ring-2 ring-[#70ab96]' : 'bg-[#eef2f0] text-[#81908a]'">{{ stepState[step.key] === 'complete' ? '✓' : index + 1 }}</span>
                    <span><strong class="block text-sm text-[#1b2c26]">{{ step.label }}</strong><small class="text-xs text-[#718079]">{{ step.caption }}</small></span>
                </li>
            </ol>

            <section class="rounded-xl border border-[#dce5e0] bg-white shadow-[0_8px_30px_rgba(15,60,45,0.04)]">
                <div class="border-b border-[#e4ebe7] px-5 py-4 sm:px-6">
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-[#197356]">Step 1</p>
                    <h2 class="mt-1 text-lg font-semibold text-[#172a24]">Find the farmer first</h2>
                    <p class="mt-1 text-sm text-[#697871]">Search by farmer code or name to avoid creating a duplicate profile.</p>
                </div>
                <div class="space-y-4 p-5 sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <label class="relative flex-1">
                            <span class="sr-only">Farmer code or name</span>
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#718079]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input v-model="search" type="search" placeholder="Enter farmer code or full name" class="h-11 w-full rounded-lg border border-[#cfdad5] bg-[#f8faf9] pl-10 pr-4 text-sm outline-none transition focus:border-[#167356] focus:bg-white focus:ring-2 focus:ring-[#167356]/10" @keydown.enter.prevent="searchFarmers">
                        </label>
                        <button type="button" class="h-11 rounded-lg bg-[#07513e] px-6 text-sm font-semibold text-white transition hover:bg-[#0a634c] disabled:cursor-wait disabled:opacity-60" :disabled="searching" @click="searchFarmers">{{ searching ? 'Searching…' : 'Search records' }}</button>
                    </div>
                    <p class="text-xs text-[#78867f]">Always search first, even when the farmer appears new in the masterlist.</p>
                </div>
                <p v-if="searchError" role="alert" class="mx-5 mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 sm:mx-6">{{ searchError }}</p>
                <ul v-if="searchResults.length" class="mx-5 mb-5 divide-y divide-[#e5ebe8] overflow-hidden rounded-lg border border-[#dce5e0] sm:mx-6">
                    <li v-for="farmer in searchResults" :key="farmer.id" class="flex flex-col gap-3 bg-white px-4 py-3 transition hover:bg-[#f8fbf9] sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#e4f2ed] text-sm font-bold text-[#07513e]">{{ farmer.fullName?.charAt(0) }}</span>
                            <div><p class="text-sm font-semibold text-[#172a24]">{{ farmer.fullName }}</p><p class="mt-0.5 text-xs text-[#718079]">{{ farmer.farmerCode }} · {{ farmer.memberType }} · Years: {{ farmer.recordedYears.join(', ') || 'None' }}</p></div>
                        </div>
                        <button type="button" class="rounded-lg border border-[#91b5a7] px-4 py-2 text-xs font-semibold text-[#07513e] hover:bg-[#edf7f3]" @click="selectFarmer(farmer)">Use this farmer</button>
                    </li>
                </ul>
                <div v-else-if="searched" class="mx-5 mb-5 rounded-lg border border-dashed border-[#b9c9c2] bg-[#f8faf9] p-5 text-center sm:mx-6">
                    <p class="text-sm font-semibold text-[#263a33]">No matching farmer found</p>
                    <p class="mt-1 text-xs text-[#718079]">Check the spelling once more before creating a profile.</p>
                    <button type="button" class="mt-3 rounded-lg bg-[#07513e] px-4 py-2 text-sm font-semibold text-white" @click="newFarmer">Create a new farmer record</button>
                </div>
                <div v-if="selectedFarmer" class="mx-5 mb-5 flex flex-col gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 sm:mx-6 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Selected existing farmer</p><p class="mt-1 text-sm font-semibold text-[#16352c]">{{ selectedFarmer.fullName }} <span class="font-normal text-[#587068]">· {{ selectedFarmer.farmerCode }}</span></p><p class="mt-1 text-xs text-[#587068]">Recorded years: {{ selectedFarmer.recordedYears.join(', ') || 'None' }}</p></div>
                    <button type="button" class="text-xs font-semibold text-[#07513e] underline underline-offset-4" @click="changeFarmer">Change farmer</button>
                </div>
                <p v-if="form.errors.farmer_id || form.errors.existing_farmer_code" role="alert" class="mx-5 mb-5 text-sm text-rose-600 sm:mx-6">{{ form.errors.farmer_id || form.errors.existing_farmer_code }}</p>
            </section>

            <section v-if="form.errors.duplicate_check" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-medium text-rose-700">
                {{ form.errors.duplicate_check }}
            </section>

            <section v-if="duplicateMatches.length" class="rounded-lg border border-amber-200 bg-amber-50/80 p-4">
                <div class="space-y-3">
                    <div>
                        <p class="text-[0.62rem] font-semibold uppercase tracking-[0.08em] text-[#8a6a16]">Duplicate Check</p>
                        <h2 class="mt-1 text-sm font-semibold text-[#16352c]">Possible existing farmer records found</h2>
                    </div>

                    <div class="space-y-3">
                        <div v-for="match in duplicateMatches" :key="match.id" class="rounded-md border border-amber-200 bg-white px-3 py-2.5">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-sm font-extrabold text-[#16352c]">{{ match.full_name }}</p>
                                    <p class="mt-1 text-xs text-[#5f6c67]">{{ match.farmer_code }}<span v-if="match.barangay_name"> • {{ match.barangay_name }}</span><span v-if="match.member_type"> • {{ match.member_type }}</span></p>
                                </div>
                                <Link v-if="match.show_url" :href="match.show_url" class="text-xs font-bold text-[#8a6a16] transition hover:text-[#6e5411]">
                                    Open record
                                </Link>
                            </div>
                            <p class="mt-1.5 text-[0.68rem] font-medium text-[#745d28]">{{ match.reasons.join(', ') }}</p>
                        </div>
                    </div>

                    <label class="inline-flex items-start gap-2.5 text-xs text-[#4f5d58]">
                        <input v-model="form.confirm_duplicate_override" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-[#003629] focus:ring-[#003629]">
                        <span>These records are different people. Continue saving this old record.</span>
                    </label>
                </div>
            </section>

            <div v-if="form.record_mode === 'new' || selectedFarmer" class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <form class="space-y-4" @submit.prevent="submit">
                <EditSectionCard eyebrow="Registry Details" title="Assignment and status">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-lg border border-[#dce5e0] bg-[#f4f8f6] px-3 py-2.5">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Record Origin</p>
                            <p class="mt-2 text-sm font-semibold text-[#191c1c]">Old Record</p>
                        </div>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Member Type</span>
                            <select v-model="form.member_type_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">Select member type</option>
                                <option v-for="item in availableMemberTypes" :key="item.key || item.id" :value="String(item.key || item.id)">{{ item.code }} - {{ item.name }}</option>
                            </select>
                            <p v-if="form.errors.member_type_id" class="text-xs font-medium text-rose-600">{{ form.errors.member_type_id }}</p>
                        </label>
                        <label v-if="form.record_mode === 'new'" class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Registry Status</span>
                            <select v-model="form.status" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                            <p v-if="form.errors.status" class="text-xs font-medium text-rose-600">{{ form.errors.status }}</p>
                        </label>
                        <div v-if="form.record_mode === 'new'" class="rounded-lg border border-[#dce5e0] bg-[#f4f8f6] px-3 py-2.5">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Registered at</p>
                            <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ form.historical_year ? `February 14, ${form.historical_year}` : 'Select a historical year' }}</p>
                        </div>
                    </div>
                </EditSectionCard>

                <EditSectionCard eyebrow="Masterlist" title="Historical transaction">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-2"><span>Year to record</span><input v-model="form.historical_year" type="number" min="1900" :max="currentYear" required placeholder="e.g. 2025" class="w-full"></label>
                        <div class="rounded-lg border px-4 py-3" :class="transactionType === 'Renewal' ? 'border-emerald-200 bg-emerald-50' : transactionType === 'Application' ? 'border-sky-200 bg-sky-50' : 'border-[#dce5e0] bg-[#f7faf8]'">
                            <p class="text-[0.62rem] font-bold uppercase tracking-[0.12em] text-[#718079]">Transaction created</p>
                            <p class="mt-1 text-base font-semibold text-[#172a24]">{{ transactionType }}</p>
                            <p class="mt-1 text-xs text-[#60716a]">{{ form.record_mode === 'existing' ? 'Existing farmers are recorded as renewals.' : 'Based on the selected member type.' }}</p>
                        </div>
                    </div>
                    <p v-if="form.errors.historical_year" role="alert" class="mt-2 text-sm text-rose-600">{{ form.errors.historical_year }}</p>
                    <p v-if="yearAlreadyRecorded" role="alert" class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">This farmer already has a transaction for {{ form.historical_year }}. Select another year.</p>
                    <div class="mt-4 grid gap-2 text-xs sm:grid-cols-2">
                        <p class="rounded-lg bg-[#f4f8f6] px-3 py-2 text-[#52645d]"><strong class="text-[#21362e]">OM / OSC</strong> → Renewal</p>
                        <p class="rounded-lg bg-[#f4f8f6] px-3 py-2 text-[#52645d]"><strong class="text-[#21362e]">NM / NSC</strong> → Application</p>
                    </div>
                </EditSectionCard>

                <EditSectionCard v-if="form.record_mode === 'new'" eyebrow="Identity" title="Personal details">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">First Name</span><input v-model="form.first_name" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"><p v-if="form.errors.first_name" class="text-xs font-medium text-rose-600">{{ form.errors.first_name }}</p></label>
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Middle Name</span><input v-model="form.middle_name" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"><p v-if="form.errors.middle_name" class="text-xs font-medium text-rose-600">{{ form.errors.middle_name }}</p></label>
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Last Name</span><input v-model="form.last_name" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"><p v-if="form.errors.last_name" class="text-xs font-medium text-rose-600">{{ form.errors.last_name }}</p></label>
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Suffix</span><input v-model="form.suffix" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"><p v-if="form.errors.suffix" class="text-xs font-medium text-rose-600">{{ form.errors.suffix }}</p></label>
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Birth Date</span><input v-model="form.birth_date" type="date" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"><p v-if="form.errors.birth_date" class="text-xs font-medium text-rose-600">{{ form.errors.birth_date }}</p></label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Sex</span>
                            <select v-model="form.sex" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">Select sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <p v-if="form.errors.sex" class="text-xs font-medium text-rose-600">{{ form.errors.sex }}</p>
                        </label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Civil Status (Optional)</span>
                            <select v-model="form.civil_status" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">Select status</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="widowed">Widowed</option>
                                <option value="separated">Separated</option>
                            </select>
                            <p v-if="form.errors.civil_status" class="text-xs font-medium text-rose-600">{{ form.errors.civil_status }}</p>
                        </label>
                    </div>
                </EditSectionCard>

                <EditSectionCard v-if="form.record_mode === 'new'" eyebrow="Location" title="Contact and assignment">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Mobile Number</span><input v-model="form.mobile_number" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"><p v-if="form.errors.mobile_number" class="text-xs font-medium text-rose-600">{{ form.errors.mobile_number }}</p></label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Barangay</span>
                            <select v-model="form.barangay_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white" @change="syncAssociation">
                                <option value="">Select barangay</option>
                                <option v-for="item in barangays" :key="item.key || item.id" :value="String(item.key || item.id)">{{ item.name }}</option>
                            </select>
                            <p v-if="form.errors.barangay_id" class="text-xs font-medium text-rose-600">{{ form.errors.barangay_id }}</p>
                        </label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Association</span>
                            <select v-model="form.association_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">No association assigned</option>
                                <option v-for="item in filteredAssociations" :key="item.key || item.id" :value="String(item.key || item.id)">{{ item.name }}</option>
                            </select>
                            <p v-if="form.errors.association_id" class="text-xs font-medium text-rose-600">{{ form.errors.association_id }}</p>
                        </label>
                        <label class="space-y-2 md:col-span-2 xl:col-span-4">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Address (Optional)</span>
                            <textarea v-model="form.address" rows="4" placeholder="Leave blank if not available" class="w-full rounded-[20px] border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></textarea>
                            <p v-if="form.errors.address" class="text-xs font-medium text-rose-600">{{ form.errors.address }}</p>
                        </label>
                        <label class="space-y-2 md:col-span-2 xl:col-span-4">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Remarks</span>
                            <textarea v-model="form.remarks" rows="4" class="w-full rounded-[20px] border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></textarea>
                            <p v-if="form.errors.remarks" class="text-xs font-medium text-rose-600">{{ form.errors.remarks }}</p>
                        </label>
                    </div>
                </EditSectionCard>

                <div class="sticky bottom-3 flex items-center justify-between gap-3 rounded-xl border border-[#dce5e0] bg-white/95 p-3 shadow-[0_12px_35px_rgba(16,55,42,0.13)] backdrop-blur">
                    <p class="hidden text-xs text-[#6a7973] sm:block">Review the summary before saving.</p>
                    <div class="ml-auto flex gap-2">
                    <Link :href="indexUrl" class="inline-flex h-9 items-center justify-center rounded-md border border-[#d7e0db] px-4 text-xs font-semibold text-[#697772] transition hover:bg-[#f4f7f5]">
                        Cancel
                    </Link>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#07513e] px-5 text-xs font-semibold text-white transition hover:bg-[#0a634c] disabled:cursor-not-allowed disabled:opacity-50" :disabled="form.processing || !readyToSave">
                        {{ form.processing ? 'Saving…' : `Save ${transactionType}` }}
                    </button>
                    </div>
                </div>
            </form>

            <aside class="space-y-4 xl:sticky xl:top-4">
                <section class="overflow-hidden rounded-xl border border-[#dce5e0] bg-white shadow-[0_8px_30px_rgba(15,60,45,0.05)]">
                    <div class="border-b border-[#e4ebe7] bg-[#f6f9f7] px-5 py-4"><p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-[#197356]">Record summary</p><h2 class="mt-1 font-semibold text-[#172a24]">Before you save</h2></div>
                    <dl class="divide-y divide-[#e8eeeb] px-5 text-sm">
                        <div class="flex justify-between gap-4 py-3"><dt class="text-[#718079]">Farmer</dt><dd class="text-right font-medium text-[#21362e]">{{ selectedFarmer?.fullName || ([form.first_name, form.last_name].filter(Boolean).join(' ') || 'New farmer') }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-[#718079]">Farmer code</dt><dd class="font-medium text-[#21362e]">{{ selectedFarmer?.farmerCode || nextFarmerCode }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-[#718079]">Historical date</dt><dd class="font-medium text-[#21362e]">{{ form.historical_year ? `Feb 14, ${form.historical_year}` : 'Not set' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-[#718079]">Member type</dt><dd class="font-medium text-[#21362e]">{{ selectedMemberType?.code || 'Not set' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-[#718079]">Transaction</dt><dd class="font-semibold text-[#07513e]">{{ transactionType }}</dd></div>
                    </dl>
                </section>
                <section class="rounded-xl border border-[#d8e7e1] bg-[#eef7f3] p-5">
                    <p class="text-sm font-semibold text-[#173a2e]">Automatic records</p>
                    <ul class="mt-3 space-y-2 text-xs text-[#526b61]"><li>✓ Payment assessment</li><li>✓ Paid cash payment</li><li>✓ Membership ledger</li><li>✓ Audit trail</li><li>✓ No document checklist</li></ul>
                </section>
                <p class="px-1 text-xs leading-5 text-[#718079]">Encode only a year in which the farmer appears in the historical masterlist. The system will not create later years automatically.</p>
            </aside>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.old-record-compact :deep(label) { gap: 0.25rem; }
.old-record-compact :deep(label > span),
.old-record-compact :deep(.text-\[0\.68rem\]) {
    font-size: 0.6rem !important;
    font-weight: 600 !important;
    letter-spacing: 0.07em !important;
}
.old-record-compact :deep(input:not([type='checkbox'])),
.old-record-compact :deep(select) {
    height: 2.25rem !important;
    border-radius: 0.375rem !important;
    padding: 0 0.75rem !important;
    font-size: 0.75rem !important;
}
.old-record-compact :deep(textarea) {
    border-radius: 0.375rem !important;
    padding: 0.625rem 0.75rem !important;
    font-size: 0.75rem !important;
    line-height: 1.25rem !important;
}
</style>
