<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import CreateReapplyBanner from '../../../Components/Admin/MembershipApplications/CreateReapplyBanner.vue';
import CreateSectionCard from '../../../Components/Admin/MembershipApplications/CreateSectionCard.vue';
import CreateSteps from '../../../Components/Admin/MembershipApplications/CreateSteps.vue';

const props = defineProps({
    entryMode: { type: String, default: 'application' },
    statuses: { type: Object, required: true },
    canSelectManualStatus: { type: Boolean, required: true },
    barangays: { type: Array, required: true },
    associations: { type: Array, required: true },
    nextFarmerCode: { type: String, required: true },
    requiredDocuments: { type: Array, required: true },
    reapplyApplication: { type: Object, default: null },
    memberTypes: { type: Array, required: true },
    memberTypePreview: { type: Object, required: true },
    formDefaults: { type: Object, required: true },
    storeUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
});

const page = usePage();

const form = useForm({
    source: props.formDefaults.source,
    reapply_from_application_id: props.formDefaults.reapply_from_application_id,
    registered_at: props.formDefaults.registered_at,
    status: props.formDefaults.status,
    application_remarks: props.formDefaults.application_remarks || '',
    first_name: props.formDefaults.first_name || '',
    middle_name: props.formDefaults.middle_name || '',
    last_name: props.formDefaults.last_name || '',
    suffix: props.formDefaults.suffix || '',
    birth_date: props.formDefaults.birth_date || '',
    sex: props.formDefaults.sex || '',
    civil_status: props.formDefaults.civil_status || '',
    mobile_number: props.formDefaults.mobile_number || '',
    member_type_id: props.formDefaults.member_type_id || '',
    barangay_id: props.formDefaults.barangay_id || '',
    association_id: props.formDefaults.association_id || '',
    address: props.formDefaults.address || '',
    remarks: props.formDefaults.remarks || '',
    documents: props.formDefaults.documents || {},
});

const flashSuccess = computed(() => page.props.flash?.success || '');
const displayFarmerCode = computed(() => props.nextFarmerCode);
const isOldRecordMode = computed(() => props.entryMode === 'old-record');
const pageHeading = computed(() => (isOldRecordMode.value ? 'Encode Old Record' : 'New Membership Application'));
const submitLabel = computed(() => {
    if (reapplyApplication.value) {
        return 'Save Replacement Application';
    }

    return isOldRecordMode.value ? 'Save Old Record' : 'Save Membership Application';
});

const filteredAssociations = computed(() => {
    if (!form.barangay_id) {
        return props.associations;
    }

    return props.associations.filter((association) => String(association.barangay_id) === String(form.barangay_id));
});

const memberTypeLabel = computed(() => {
    const age = calculateAge(form.birth_date);

    if (age !== null && age >= Number(props.memberTypePreview.senior_age || 60)) {
        return `${props.memberTypePreview.senior_code} - ${props.memberTypePreview.senior_label}`;
    }

    return `${props.memberTypePreview.default_code} - ${props.memberTypePreview.default_label}`;
});

const resolvedMemberTypeId = computed(() => {
    if (form.member_type_id) {
        return String(form.member_type_id);
    }

    const age = calculateAge(form.birth_date);
    const targetCode = age !== null && age >= Number(props.memberTypePreview.senior_age || 60)
        ? props.memberTypePreview.senior_code
        : props.memberTypePreview.default_code;

    return String(props.memberTypes.find((type) => type.code === targetCode)?.id || '');
});

function calculateAge(birthDateValue) {
    if (!birthDateValue) {
        return null;
    }

    const birthDate = new Date(`${birthDateValue}T00:00:00`);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age -= 1;
    }

    return age;
}

function syncAssociation() {
    if (!form.barangay_id) {
        form.association_id = '';
        return;
    }

    const matches = filteredAssociations.value.some((association) => String(association.id) === String(form.association_id));

    if (!matches) {
        form.association_id = filteredAssociations.value[0] ? String(filteredAssociations.value[0].id) : '';
    }
}

function syncMemberType() {
    form.member_type_id = resolvedMemberTypeId.value;
}

function submit() {
    syncMemberType();
    form.post(props.storeUrl, {
        preserveScroll: true,
    });
}

const reapplyApplication = computed(() => props.reapplyApplication);

syncMemberType();
</script>

<template>
    <Head :title="pageHeading" />

    <AdminLayout :title="pageHeading">
        <div class="membership-create-compact mx-auto w-full max-w-[1536px] space-y-4">
            <CreateSteps />

            <CreateReapplyBanner v-if="reapplyApplication" :reapply-application="reapplyApplication" />

            <section v-if="flashSuccess" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-medium text-emerald-800">
                {{ flashSuccess }}
            </section>

            <section v-if="form.errors.duplicate_check" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-medium text-rose-700">
                {{ form.errors.duplicate_check }}
            </section>

            <form class="space-y-4" @submit.prevent="submit">
                <CreateSectionCard eyebrow="Application Details" title="">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Farmer Code</span>
                            <input :value="displayFarmerCode" type="text" readonly class="w-full rounded-2xl border border-[#d7e0db] bg-[#f1f5f2] px-4 py-3 text-sm font-bold text-[#5f6c67]">
                        </label>

                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Registration Date</span>
                            <input v-model="form.registered_at" type="date" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            <p v-if="form.errors.registered_at" class="text-xs font-medium text-rose-600">{{ form.errors.registered_at }}</p>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Member Type</span>
                            <input :value="memberTypeLabel" type="text" readonly class="w-full rounded-2xl border border-[#d7e0db] bg-[#f1f5f2] px-4 py-3 text-sm font-bold text-[#003629]">
                        </label>

                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Farmer Status</span>
                            <template v-if="canSelectManualStatus">
                                <select v-model="form.status" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                    <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </template>
                            <template v-else>
                                <input value="Pending" type="text" readonly class="w-full rounded-2xl border border-[#d7e0db] bg-[#f1f5f2] px-4 py-3 text-sm font-bold text-[#5f6c67]">
                            </template>
                            <p v-if="form.errors.status" class="text-xs font-medium text-rose-600">{{ form.errors.status }}</p>
                        </label>

                        <label class="space-y-2 md:col-span-2 xl:col-span-4">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Application Remarks</span>
                            <textarea v-model="form.application_remarks" rows="3" placeholder="Optional office note for this membership application" class="w-full rounded-[20px] border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></textarea>
                            <p v-if="form.errors.application_remarks" class="text-xs font-medium text-rose-600">{{ form.errors.application_remarks }}</p>
                        </label>
                    </div>
                </CreateSectionCard>

                <CreateSectionCard eyebrow="Personal Information" title="">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">First Name</span>
                            <input v-model="form.first_name" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            <p v-if="form.errors.first_name" class="text-xs font-medium text-rose-600">{{ form.errors.first_name }}</p>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Middle Name</span>
                            <input v-model="form.middle_name" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            <p v-if="form.errors.middle_name" class="text-xs font-medium text-rose-600">{{ form.errors.middle_name }}</p>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Last Name</span>
                            <input v-model="form.last_name" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            <p v-if="form.errors.last_name" class="text-xs font-medium text-rose-600">{{ form.errors.last_name }}</p>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Suffix</span>
                            <input v-model="form.suffix" type="text" placeholder="Jr., III, etc." class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            <p v-if="form.errors.suffix" class="text-xs font-medium text-rose-600">{{ form.errors.suffix }}</p>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Birth Date</span>
                            <input v-model="form.birth_date" type="date" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            <p v-if="form.errors.birth_date" class="text-xs font-medium text-rose-600">{{ form.errors.birth_date }}</p>
                        </label>

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
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Civil Status</span>
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
                </CreateSectionCard>

                <CreateSectionCard eyebrow="Contact And Location" title="">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Mobile Number</span>
                            <input v-model="form.mobile_number" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            <p v-if="form.errors.mobile_number" class="text-xs font-medium text-rose-600">{{ form.errors.mobile_number }}</p>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Barangay</span>
                            <select v-model="form.barangay_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white" @change="syncAssociation">
                                <option value="">Select barangay</option>
                                <option v-for="barangay in barangays" :key="barangay.id" :value="String(barangay.id)">{{ barangay.name }}</option>
                            </select>
                            <p v-if="form.errors.barangay_id" class="text-xs font-medium text-rose-600">{{ form.errors.barangay_id }}</p>
                        </label>

                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Association</span>
                            <select v-model="form.association_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">No association assigned</option>
                                <option v-for="association in filteredAssociations" :key="association.id" :value="String(association.id)">{{ association.name }}</option>
                            </select>
                            <p v-if="form.errors.association_id" class="text-xs font-medium text-rose-600">{{ form.errors.association_id }}</p>
                        </label>

                        <label class="space-y-2 md:col-span-2 xl:col-span-4">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Address</span>
                            <textarea v-model="form.address" rows="3" class="w-full rounded-[20px] border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></textarea>
                            <p v-if="form.errors.address" class="text-xs font-medium text-rose-600">{{ form.errors.address }}</p>
                        </label>
                    </div>
                </CreateSectionCard>

                <section class="grid items-start gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
                    <CreateSectionCard eyebrow="Farmer Notes" title="">
                        <div>
                            <textarea v-model="form.remarks" rows="4" class="w-full rounded-[20px] border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></textarea>
                            <p v-if="form.errors.remarks" class="mt-2 text-xs font-medium text-rose-600">{{ form.errors.remarks }}</p>
                        </div>
                    </CreateSectionCard>

                    <CreateSectionCard eyebrow="" title="Required documents">
                        <div class="space-y-2">
                            <label
                                v-for="document in requiredDocuments"
                                :key="document.value"
                                class="flex items-start gap-2.5 rounded-md border border-[#e3eae6] bg-[#f7faf8] px-3 py-2.5"
                            >
                                <input
                                    v-model="form.documents[document.value].is_received"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-slate-300 text-[#003629] focus:ring-[#003629]"
                                >
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-[#191c1c]">{{ document.label }}</p>
                                    <p class="mt-0.5 text-[0.68rem] text-[#78857f]">Mark if already received during intake.</p>
                                </div>
                            </label>
                        </div>
                    </CreateSectionCard>
                </section>

                <div class="flex justify-end gap-2 border-t border-[#e3e9e6] pt-3">
                    <Link :href="indexUrl" class="inline-flex h-9 items-center justify-center rounded-md border border-[#d7e0db] px-4 text-xs font-semibold text-[#697772] transition hover:bg-[#f4f7f5]">
                        Cancel
                    </Link>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#003629] px-4 text-xs font-semibold text-white transition hover:bg-[#0d4637]" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.membership-create-compact :deep(label) { gap: 0.25rem; }
.membership-create-compact :deep(label > span) { font-size: 0.6rem !important; font-weight: 600 !important; letter-spacing: 0.07em !important; }
.membership-create-compact :deep(input:not([type='checkbox'])),
.membership-create-compact :deep(select) { height: 2.25rem !important; border-radius: 0.375rem !important; padding: 0 0.75rem !important; font-size: 0.75rem !important; }
.membership-create-compact :deep(textarea) { border-radius: 0.375rem !important; padding: 0.625rem 0.75rem !important; font-size: 0.75rem !important; line-height: 1.25rem !important; }
</style>
