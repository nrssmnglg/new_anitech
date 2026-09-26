<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    barangays: { type: Array, required: true },
    statusOptions: { type: Object, required: true },
    presidentCandidates: { type: Array, default: null },
});

const presidentSearch = ref('');
const presidentPickerOpen = ref(false);
const selectedPresident = computed(() => (props.presidentCandidates || []).find(
    (farmer) => String(farmer.id) === String(props.form.president_farmer_id),
) || null);
const filteredPresidentCandidates = computed(() => {
    const search = presidentSearch.value.trim().toLowerCase();

    return (props.presidentCandidates || [])
        .filter((farmer) => search === ''
            || farmer.name.toLowerCase().includes(search)
            || farmer.farmerCode.toLowerCase().includes(search))
        .slice(0, 8);
});

function selectPresident(farmer) {
    props.form.president_farmer_id = farmer.id;
    presidentSearch.value = '';
    presidentPickerOpen.value = false;
}

function clearPresident() {
    props.form.president_farmer_id = '';
    presidentSearch.value = '';
    presidentPickerOpen.value = false;
}

function closePresidentPicker() {
    window.setTimeout(() => {
        presidentPickerOpen.value = false;
    }, 150);
}
</script>

<template>
    <div class="grid gap-3 md:grid-cols-2">
        <label class="space-y-1">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Barangay</span>
            <select v-model="form.barangay_id" class="h-9 w-full rounded-lg border border-[#d7e0db] bg-white px-3 text-xs text-on-surface outline-none transition focus:border-[#14745a] focus:ring-2 focus:ring-[#14745a]/10">
                <option value="">Select barangay</option>
                <option v-for="barangay in barangays" :key="barangay.id" :value="barangay.id">
                    {{ barangay.name }}{{ barangay.code ? ` (${barangay.code})` : '' }}
                </option>
            </select>
            <p v-if="form.errors.barangay_id" class="text-xs font-medium text-error">{{ form.errors.barangay_id }}</p>
        </label>

        <label class="space-y-1">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Status</span>
            <select v-model="form.status" class="h-9 w-full rounded-lg border border-[#d7e0db] bg-white px-3 text-xs text-on-surface outline-none transition focus:border-[#14745a] focus:ring-2 focus:ring-[#14745a]/10">
                <option v-for="(label, value) in statusOptions" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="form.errors.status" class="text-sm font-medium text-error">{{ form.errors.status }}</p>
        </label>

        <label class="space-y-1 md:col-span-2">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Association Name</span>
            <input v-model="form.name" type="text" class="h-9 w-full rounded-lg border border-[#d7e0db] bg-white px-3 text-xs text-on-surface outline-none transition focus:border-[#14745a] focus:ring-2 focus:ring-[#14745a]/10">
            <p v-if="form.errors.name" class="text-sm font-medium text-error">{{ form.errors.name }}</p>
        </label>

        <label class="space-y-1 md:col-span-2">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Association Code</span>
            <input :value="form.code || 'Generated when saved'" type="text" readonly class="h-9 w-full cursor-not-allowed rounded-lg border border-[#d7e0db] bg-[#f2f5f3] px-3 text-xs text-[#68756f] outline-none">
            <p v-if="form.errors.code" class="text-sm font-medium text-error">{{ form.errors.code }}</p>
        </label>

        <div v-if="presidentCandidates !== null" class="space-y-1 md:col-span-2">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Association President</span>
            <div class="relative">
                <div class="relative">
                    <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#819087]" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input v-model="presidentSearch" type="search" autocomplete="off" placeholder="Search farmer by name or code" class="h-10 w-full appearance-none rounded-lg border border-[#d7e0db] bg-white pl-9 pr-3 text-xs text-on-surface outline-none transition focus:border-[#14745a] focus:ring-2 focus:ring-[#14745a]/10" @focus="presidentPickerOpen = true" @blur="closePresidentPicker">
                </div>
                <div v-if="presidentPickerOpen" class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-[#d7e0db] bg-white p-1 shadow-[0_12px_30px_rgba(15,55,42,0.16)]">
                    <button v-for="farmer in filteredPresidentCandidates" :key="farmer.id" type="button" class="flex w-full items-center justify-between gap-3 rounded px-3 py-2 text-left transition hover:bg-[#eef6f2]" @mousedown.prevent="selectPresident(farmer)">
                        <span class="min-w-0"><span class="block truncate text-xs font-semibold text-[#173d31]">{{ farmer.name }}</span><span class="mt-0.5 block truncate text-[0.65rem] text-[#68756f]">{{ farmer.farmerCode }}</span></span>
                        <span class="shrink-0 text-[0.65rem] text-[#68756f]">{{ farmer.mobileNumber }}</span>
                    </button>
                    <p v-if="filteredPresidentCandidates.length === 0" class="px-3 py-5 text-center text-xs text-[#68756f]">No matching farmer in this association.</p>
                </div>
            </div>
            <div v-if="selectedPresident" class="flex items-center justify-between gap-3 rounded-lg border border-[#cde2d7] bg-[#f2faf6] px-3 py-2">
                <div class="min-w-0"><p class="truncate text-xs font-semibold text-[#12372a]">{{ selectedPresident.name }}</p><p class="mt-0.5 truncate text-[0.65rem] text-[#68756f]">{{ selectedPresident.farmerCode }} · {{ selectedPresident.mobileNumber }}</p></div>
                <button type="button" class="shrink-0 text-[0.68rem] font-semibold text-[#a13d36] hover:underline" @click="clearPresident">Remove</button>
            </div>
            <p class="text-[0.65rem] text-[#68756f]">Searches only registered farmers assigned to this association.</p>
            <p v-if="form.errors.president_farmer_id" class="text-sm font-medium text-error">{{ form.errors.president_farmer_id }}</p>
        </div>

        <label v-else class="space-y-1 md:col-span-2">
            <span class="text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Association President</span>
            <input value="Save the association first, then select its president while editing" type="text" readonly class="h-9 w-full cursor-not-allowed rounded-lg border border-[#d7e0db] bg-[#f2f5f3] px-3 text-xs text-[#68756f] outline-none">
        </label>
    </div>
</template>
