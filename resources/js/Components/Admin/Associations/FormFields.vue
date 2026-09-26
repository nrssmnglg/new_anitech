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
    <div class="grid gap-4 md:grid-cols-2">
        <label class="space-y-1.5">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Barangay</span>
            <select v-model="form.barangay_id" class="h-10 w-full rounded-lg border border-[#d7e0db] bg-[#f9fbfa] px-3.5 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#14745a] focus:bg-white focus:ring-2 focus:ring-[#14745a]/10">
                <option value="">Select barangay</option>
                <option v-for="barangay in barangays" :key="barangay.id" :value="barangay.id">
                    {{ barangay.name }}{{ barangay.code ? ` (${barangay.code})` : '' }}
                </option>
            </select>
            <p v-if="form.errors.barangay_id" class="flex items-center gap-1 text-xs font-medium text-rose-600">
                <svg viewBox="0 0 16 16" class="h-3 w-3 shrink-0" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-4.75a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-1.5 0v4.5ZM8 11.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                {{ form.errors.barangay_id }}
            </p>
        </label>

        <label class="space-y-1.5">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Status</span>
            <select v-model="form.status" class="h-10 w-full rounded-lg border border-[#d7e0db] bg-[#f9fbfa] px-3.5 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#14745a] focus:bg-white focus:ring-2 focus:ring-[#14745a]/10">
                <option v-for="(label, value) in statusOptions" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="form.errors.status" class="flex items-center gap-1 text-xs font-medium text-rose-600">
                <svg viewBox="0 0 16 16" class="h-3 w-3 shrink-0" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-4.75a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-1.5 0v4.5ZM8 11.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                {{ form.errors.status }}
            </p>
        </label>

        <label class="space-y-1.5 md:col-span-2">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Association Name</span>
            <input v-model="form.name" type="text" placeholder="Enter association name" class="h-10 w-full rounded-lg border border-[#d7e0db] bg-[#f9fbfa] px-3.5 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#14745a] focus:bg-white focus:ring-2 focus:ring-[#14745a]/10">
            <p v-if="form.errors.name" class="flex items-center gap-1 text-xs font-medium text-rose-600">
                <svg viewBox="0 0 16 16" class="h-3 w-3 shrink-0" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-4.75a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-1.5 0v4.5ZM8 11.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                {{ form.errors.name }}
            </p>
        </label>

        <label class="space-y-1.5 md:col-span-2">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Association Code</span>
            <input :value="form.code || 'Generated when saved'" type="text" readonly class="h-10 w-full cursor-not-allowed rounded-lg border border-[#e2e8f0] bg-[#f1f5f9] px-3.5 text-xs text-[#94a3b8] outline-none">
            <p v-if="form.errors.code" class="flex items-center gap-1 text-xs font-medium text-rose-600">
                <svg viewBox="0 0 16 16" class="h-3 w-3 shrink-0" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-4.75a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-1.5 0v4.5ZM8 11.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                {{ form.errors.code }}
            </p>
        </label>

        <!-- President picker (only on edit) -->
        <div v-if="presidentCandidates !== null" class="space-y-2 md:col-span-2">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Association President</span>
            <div class="relative">
                <div class="relative">
                    <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94a3b8]" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd"/>
                    </svg>
                    <input
                        v-model="presidentSearch"
                        type="search"
                        autocomplete="off"
                        placeholder="Search farmer by name or code"
                        class="h-10 w-full appearance-none rounded-lg border border-[#d7e0db] bg-[#f9fbfa] pl-10 pr-3.5 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#14745a] focus:bg-white focus:ring-2 focus:ring-[#14745a]/10"
                        @focus="presidentPickerOpen = true"
                        @blur="closePresidentPicker"
                    >
                </div>

                <!-- Dropdown -->
                <div v-if="presidentPickerOpen" class="absolute z-30 mt-1.5 max-h-64 w-full overflow-y-auto rounded-xl border border-[#d7e0db] bg-white p-1.5 shadow-xl shadow-[#0f372a]/10">
                    <button
                        v-for="farmer in filteredPresidentCandidates"
                        :key="farmer.id"
                        type="button"
                        class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left transition-all duration-150 hover:bg-[#eef6f2]"
                        @mousedown.prevent="selectPresident(farmer)"
                    >
                        <span class="flex items-center gap-2.5 min-w-0">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd] text-[0.55rem] font-bold text-[#0f6b45]">
                                {{ farmer.name?.charAt(0)?.toUpperCase() || 'F' }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-semibold text-[#173d31]">{{ farmer.name }}</span>
                                <span class="mt-0.5 block truncate text-[0.62rem] text-[#94a3b8]">{{ farmer.farmerCode }}</span>
                            </span>
                        </span>
                        <span class="shrink-0 text-[0.62rem] text-[#94a3b8]">{{ farmer.mobileNumber }}</span>
                    </button>
                    <p v-if="filteredPresidentCandidates.length === 0" class="px-3 py-6 text-center text-xs text-[#94a3b8]">No matching farmer in this association.</p>
                </div>
            </div>

            <!-- Selected president card -->
            <div v-if="selectedPresident" class="flex items-center justify-between gap-3 rounded-xl border border-[#cde2d7] bg-gradient-to-r from-[#f2faf6] to-[#f0f9f4] px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[#d4eddd] to-[#c0e6ca] text-[0.6rem] font-bold text-[#0f6b45]">
                        {{ selectedPresident.name?.charAt(0)?.toUpperCase() || 'F' }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-[#12372a]">{{ selectedPresident.name }}</p>
                        <p class="mt-0.5 truncate text-[0.62rem] text-[#94a3b8]">{{ selectedPresident.farmerCode }} · {{ selectedPresident.mobileNumber }}</p>
                    </div>
                </div>
                <button type="button" class="inline-flex h-7 shrink-0 items-center rounded-md border border-[#fecdd3] bg-[#fff1f2] px-2.5 text-[0.62rem] font-bold text-[#e11d48] transition-all duration-150 hover:bg-[#ffe4e6]" @click="clearPresident">Remove</button>
            </div>

            <p class="text-[0.62rem] text-[#94a3b8]">Searches only registered farmers assigned to this association.</p>
            <p v-if="form.errors.president_farmer_id" class="flex items-center gap-1 text-xs font-medium text-rose-600">
                <svg viewBox="0 0 16 16" class="h-3 w-3 shrink-0" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm-.75-4.75a.75.75 0 0 0 1.5 0v-4.5a.75.75 0 0 0-1.5 0v4.5ZM8 11.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                {{ form.errors.president_farmer_id }}
            </p>
        </div>

        <!-- Placeholder for Create mode (before president can be selected) -->
        <label v-else class="space-y-1.5 md:col-span-2">
            <span class="text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">Association President</span>
            <input value="Save the association first, then select its president while editing" type="text" readonly class="h-10 w-full cursor-not-allowed rounded-lg border border-[#e2e8f0] bg-[#f1f5f9] px-3.5 text-xs text-[#94a3b8] outline-none">
        </label>
    </div>
</template>
