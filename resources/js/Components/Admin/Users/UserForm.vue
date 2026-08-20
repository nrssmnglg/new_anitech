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
    <form class="space-y-6" @submit.prevent="emit('submit')">
        <div class="grid gap-6 xl:grid-cols-[1.4fr_0.95fr]">
            <section class="rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_18px_40px_rgba(0,54,41,0.08)]">
                <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-5">
                    <h2 class="text-xl font-black text-primary">Account Details</h2>
                </div>

                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <label class="space-y-2 md:col-span-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Full Name</span>
                        <input v-model="form.name" :disabled="disabled" type="text" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15 disabled:cursor-not-allowed disabled:opacity-60">
                        <p v-if="form.errors.name" class="text-sm font-medium text-error">{{ form.errors.name }}</p>
                    </label>

                    <label class="space-y-2 md:col-span-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Email</span>
                        <input v-model="form.email" :disabled="disabled" type="email" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15 disabled:cursor-not-allowed disabled:opacity-60">
                        <p v-if="form.errors.email" class="text-sm font-medium text-error">{{ form.errors.email }}</p>
                    </label>

                    <label class="space-y-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Role</span>
                        <select v-model="form.role" :disabled="disabled" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15 disabled:cursor-not-allowed disabled:opacity-60">
                            <option value="">Select role</option>
                            <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <p v-if="form.errors.role" class="text-sm font-medium text-error">{{ form.errors.role }}</p>
                    </label>

                    <label class="space-y-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Status</span>
                        <select v-model="form.status" :disabled="disabled" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15 disabled:cursor-not-allowed disabled:opacity-60">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <p v-if="form.errors.status" class="text-sm font-medium text-error">{{ form.errors.status }}</p>
                    </label>

                    <div class="space-y-2 md:col-span-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Linked Farmer Record</span>
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
                                :placeholder="selectedFarmerLabel || 'Search by farmer name or code'"
                                class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 pr-10 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15 disabled:cursor-not-allowed disabled:opacity-60"
                                @focus="openFarmerSelector"
                                @input="farmerSelectorOpen = true"
                                @blur="closeFarmerSelector"
                            >
                            <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-[#0f5b46]">⌕</span>
                            <div v-if="farmerSelectorOpen" id="farmer-record-options" role="listbox" class="absolute z-20 mt-2 max-h-64 w-full overflow-y-auto rounded-2xl border border-[#0f5b46]/15 bg-white p-1 shadow-xl shadow-[#003629]/15">
                                <button
                                    v-for="option in filteredFarmerOptions"
                                    :key="option.value"
                                    type="button"
                                    role="option"
                                    :aria-selected="String(form.farmer_id) === String(option.value)"
                                    :disabled="option.disabled"
                                    class="block w-full rounded-xl px-4 py-2.5 text-left text-sm text-on-surface transition hover:bg-[#eef5f1] disabled:cursor-not-allowed disabled:opacity-45"
                                    :class="{ 'bg-[#e1f0e9] font-bold text-[#003629]': String(form.farmer_id) === String(option.value) }"
                                    @mousedown.prevent="chooseFarmer(option)"
                                >
                                    {{ option.label }}
                                </button>
                                <p v-if="filteredFarmerOptions.length === 0" class="px-4 py-3 text-sm text-on-surface-variant">No farmer records match your search.</p>
                            </div>
                        </div>
                        <p v-if="form.errors.farmer_id" class="text-sm font-medium text-error">{{ form.errors.farmer_id }}</p>
                    </div>

                </div>
            </section>

            <div class="space-y-6">
                <section class="rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_18px_40px_rgba(0,54,41,0.08)]">
                    <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-5">
                        <h2 class="text-xl font-black text-primary">Security</h2>
                        <p class="mt-2 text-sm text-on-surface-variant">{{ isCreate ? 'A default password is generated automatically after the account is created.' : 'Leave password fields blank to keep the current password.' }}</p>
                    </div>

                    <div class="grid gap-5 p-6">
                        <div v-if="isCreate" class="rounded-2xl border border-[#a8832a]/15 bg-[#fff9ec] px-4 py-4 text-sm text-[#6f5618]">
                            The system will generate a default password and show it once after saving the new account.
                        </div>

                        <template v-else>
                            <label class="space-y-2">
                                <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Password</span>
                                <input v-model="form.password" :disabled="disabled" type="password" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15 disabled:cursor-not-allowed disabled:opacity-60">
                                <p v-if="form.errors.password" class="text-sm font-medium text-error">{{ form.errors.password }}</p>
                            </label>

                            <label class="space-y-2">
                                <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Confirm Password</span>
                                <input v-model="form.password_confirmation" :disabled="disabled" type="password" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15 disabled:cursor-not-allowed disabled:opacity-60">
                            </label>
                        </template>
                    </div>
                </section>

                <section class="rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_18px_40px_rgba(0,54,41,0.08)]">
                    <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-5">
                        <h2 class="text-xl font-black text-primary">Office Profile</h2>
                    </div>

                    <div class="grid gap-5 p-6">
                        <label class="space-y-2">
                            <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Employee ID</span>
                            <input :value="currentEmployeeIdPreview" disabled type="text" class="w-full rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] px-4 py-3 text-sm text-on-surface-variant">
                        </label>

                        <label class="space-y-2">
                            <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Job Title</span>
                            <input v-model="form.job_title" :disabled="disabled || !isOfficeRole" type="text" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15 disabled:cursor-not-allowed disabled:opacity-60">
                            <p v-if="form.errors.job_title" class="text-sm font-medium text-error">{{ form.errors.job_title }}</p>
                        </label>

                        <label class="space-y-2">
                            <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Contact Number</span>
                            <input v-model="form.contact_number" :disabled="disabled || !isOfficeRole" type="text" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15 disabled:cursor-not-allowed disabled:opacity-60">
                            <p v-if="form.errors.contact_number" class="text-sm font-medium text-error">{{ form.errors.contact_number }}</p>
                        </label>
                    </div>
                </section>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-3xl border border-[#0f5b46]/12 bg-[#f5faf7] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex gap-3">
                <a :href="backUrl" class="inline-flex items-center justify-center rounded-2xl border border-[#0f5b46]/15 bg-white px-5 py-3 text-sm font-semibold text-[#0f5b46] transition hover:bg-[#eef5f1]">
                    Cancel
                </a>
                <button type="submit" :disabled="disabled" class="inline-flex items-center justify-center rounded-2xl bg-[#0f5b46] px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#0f5b46]/20 transition hover:bg-[#0b4938] disabled:cursor-not-allowed disabled:opacity-60">
                    {{ form.processing ? 'Saving...' : submitLabel }}
                </button>
            </div>
        </div>
    </form>
</template>
