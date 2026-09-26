<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    roleOptions: { type: Array, required: true },
    statusOptions: { type: Array, required: true },
    farmerOptions: { type: Array, required: true },
    employeeIdPreview: { type: String, required: true },
    isCreate: { type: Boolean, required: true },
    submitLabel: { type: String, required: true },
    disabled: { type: Boolean, default: false },
    backUrl: { type: String, required: true },
});

const emit = defineEmits(['submit']);

const isOfficeRole = computed(() => ['Admin', 'Staff'].includes(props.form.role));
const isFarmerRole = computed(() => props.form.role === 'Farmer');
const farmerSearch = ref(
    props.farmerOptions.find((option) => String(option.value) === String(props.form.farmer_id))?.label ?? '',
);
const farmerSelectorOpen = ref(false);
const farmerSearchInput = ref(null);
let farmerSelectorCloseTimer;

const selectedFarmerLabel = computed(() => (
    props.farmerOptions.find((option) => String(option.value) === String(props.form.farmer_id))?.label ?? ''
));

const filteredFarmerOptions = computed(() => {
    const search = farmerSearch.value.trim().toLocaleLowerCase();

    if (!search) {
        return props.farmerOptions;
    }

    return props.farmerOptions.filter((option) => option.label.toLocaleLowerCase().includes(search));
});

function openFarmerSelector() {
    if (!isFarmerRole.value || props.disabled) {
        return;
    }

    clearTimeout(farmerSelectorCloseTimer);
    farmerSearch.value = '';
    farmerSelectorOpen.value = true;
}

function closeFarmerSelector() {
    farmerSelectorCloseTimer = setTimeout(() => {
        farmerSelectorOpen.value = false;
        farmerSearch.value = selectedFarmerLabel.value;
    }, 150);
}

function chooseFarmer(option) {
    if (option.disabled) {
        return;
    }

    props.form.farmer_id = String(option.value);
    farmerSearch.value = option.label;
    farmerSelectorOpen.value = false;
    farmerSearchInput.value?.blur();
}

const currentEmployeeIdPreview = computed(() => {
    if (!isOfficeRole.value) {
        return 'Not required for farmer accounts';
    }

    if (props.form.employee_id) {
        return props.form.employee_id;
    }

    if (!props.isCreate) {
        return props.employeeIdPreview;
    }

    const prefix = props.form.role === 'Admin' ? 'ADM' : 'STF';
    const parts = props.employeeIdPreview.split('-');

    if (parts.length !== 3) {
        return props.employeeIdPreview;
    }

    return `${prefix}-${parts[1]}-${parts[2]}`;
});
</script>

<template>
    <form class="space-y-4" @submit.prevent="emit('submit')">
        <div class="grid gap-4 xl:grid-cols-[1.35fr_0.9fr]">
            <!-- Account Details Card -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-[#edf2ee] px-5 py-3.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd]">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#0f6b45]" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h2 class="text-sm font-bold text-[#0f172a]">Account Details</h2>
                </div>

                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <label class="space-y-1.5 md:col-span-2">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Full Name</span>
                        <input
                            v-model="form.name"
                            :disabled="disabled"
                            type="text"
                            placeholder="Enter full name"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                        <p v-if="form.errors.name" class="text-xs font-medium text-rose-600">{{ form.errors.name }}</p>
                    </label>

                    <label class="space-y-1.5 md:col-span-2">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Email Address</span>
                        <input
                            v-model="form.email"
                            :disabled="disabled"
                            type="email"
                            placeholder="name@anitech.ph"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                        <p v-if="form.errors.email" class="text-xs font-medium text-rose-600">{{ form.errors.email }}</p>
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Role</span>
                        <select
                            v-model="form.role"
                            :disabled="disabled"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <option value="">Select role</option>
                            <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <p v-if="form.errors.role" class="text-xs font-medium text-rose-600">{{ form.errors.role }}</p>
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Status</span>
                        <select
                            v-model="form.status"
                            :disabled="disabled"
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <p v-if="form.errors.status" class="text-xs font-medium text-rose-600">{{ form.errors.status }}</p>
                    </label>

                    <div class="space-y-1.5 md:col-span-2">
                        <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Linked Farmer Record</span>
                        <div class="relative">
                            <input
                                ref="farmerSearchInput"
                                v-model="farmerSearch"
                                :disabled="disabled || !isFarmerRole"
                                type="search"
                                role="combobox"
                                aria-autocomplete="list"
                                :aria-expanded="farmerSelectorOpen"
                                aria-controls="farmer-record-options"
                                :placeholder="selectedFarmerLabel || 'Search by farmer name or code...'"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-3 pr-9 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                                @focus="openFarmerSelector"
                                @input="farmerSelectorOpen = true"
                                @blur="closeFarmerSelector"
                            >
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#94a3b8]">
                                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                            </span>
                            <div v-if="farmerSelectorOpen" id="farmer-record-options" role="listbox" class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-[#dde4de] bg-white p-1.5 shadow-xl">
                                <button
                                    v-for="option in filteredFarmerOptions"
                                    :key="option.value"
                                    type="button"
                                    role="option"
                                    :aria-selected="String(form.farmer_id) === String(option.value)"
                                    :disabled="option.disabled"
                                    class="block w-full rounded-lg px-3 py-2 text-left text-xs text-[#0f172a] transition hover:bg-[#f0faf5] disabled:cursor-not-allowed disabled:opacity-45"
                                    :class="{ 'bg-[#e6f5ec] font-bold text-[#003629]': String(form.farmer_id) === String(option.value) }"
                                    @mousedown.prevent="chooseFarmer(option)"
                                >
                                    {{ option.label }}
                                </button>
                                <p v-if="filteredFarmerOptions.length === 0" class="px-4 py-3 text-xs text-[#94a3b8]">No farmer records match your search.</p>
                            </div>
                        </div>
                        <p v-if="form.errors.farmer_id" class="text-xs font-medium text-rose-600">{{ form.errors.farmer_id }}</p>
                    </div>
                </div>
            </section>

            <!-- Right Column Cards -->
            <div class="space-y-4">
                <!-- Security Card -->
                <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-[#edf2ee] px-5 py-3.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#fef3c7] to-[#fde68a]">
                            <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#b45309]" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h2 class="text-sm font-bold text-[#0f172a]">Security</h2>
                    </div>

                    <div class="p-5">
                        <div v-if="isCreate" class="rounded-xl border border-[#fde68a]/50 bg-[#fffbeb] p-3.5">
                            <div class="flex items-start gap-2.5">
                                <svg viewBox="0 0 20 20" class="mt-0.5 h-4 w-4 shrink-0 text-[#d97706]" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-xs font-bold text-[#92400e]">Default Password Generation</p>
                                    <p class="mt-0.5 text-[0.68rem] leading-4 text-[#78350f]">A secure initial password will be automatically generated upon creation and displayed once on the confirmation screen.</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="space-y-3">
                            <label class="block space-y-1.5">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">New Password</span>
                                <input
                                    v-model="form.password"
                                    :disabled="disabled"
                                    type="password"
                                    placeholder="Leave blank to keep unchanged"
                                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                <p v-if="form.errors.password" class="text-xs font-medium text-rose-600">{{ form.errors.password }}</p>
                            </label>

                            <label class="block space-y-1.5">
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Confirm New Password</span>
                                <input
                                    v-model="form.password_confirmation"
                                    :disabled="disabled"
                                    type="password"
                                    placeholder="Confirm new password"
                                    class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Office Profile Card -->
                <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-[#edf2ee] px-5 py-3.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#eff6ff] to-[#dbeafe]">
                            <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#2563eb]" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h2 class="text-sm font-bold text-[#0f172a]">Office Profile</h2>
                    </div>

                    <div class="space-y-3 p-5">
                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Employee ID</span>
                            <input
                                :value="currentEmployeeIdPreview"
                                disabled
                                type="text"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f4f7f5] px-3 text-xs font-semibold text-[#64748b]"
                            >
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Job Title</span>
                            <input
                                v-model="form.job_title"
                                :disabled="disabled || !isOfficeRole"
                                type="text"
                                placeholder="e.g. Field Officer, Admin Clerk"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                            <p v-if="form.errors.job_title" class="text-xs font-medium text-rose-600">{{ form.errors.job_title }}</p>
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">Contact Number</span>
                            <input
                                v-model="form.contact_number"
                                :disabled="disabled || !isOfficeRole"
                                type="text"
                                placeholder="09XX XXX XXXX"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                            <p v-if="form.errors.contact_number" class="text-xs font-medium text-rose-600">{{ form.errors.contact_number }}</p>
                        </label>
                    </div>
                </section>
            </div>
        </div>

        <!-- Form Actions Footer -->
        <div class="flex gap-2 rounded-xl border border-[#dde4de] bg-[#fbfcfb] px-5 py-3.5 sm:justify-end">
            <a
                :href="backUrl"
                class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-4 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]"
            >
                Cancel
            </a>
            <button
                type="submit"
                :disabled="disabled"
                class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ form.processing ? 'Saving...' : submitLabel }}
            </button>
        </div>
    </form>
</template>
