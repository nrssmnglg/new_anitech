<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import EditSectionCard from '../../../Components/Admin/Farmers/EditSectionCard.vue';

const props = defineProps({
    statuses: { type: Array, required: true },
    barangays: { type: Array, required: true },
    associations: { type: Array, required: true },
    memberTypes: { type: Array, required: true },
    nextFarmerCode: { type: String, required: true },
    storeUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
    duplicateMatches: { type: Array, default: () => [] },
});

const form = useForm({
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
    status: 'pending',
    registered_at: new Date().toISOString().slice(0, 10),
    remarks: '',
    confirm_duplicate_override: false,
});

const filteredAssociations = computed(() => {
    if (!form.barangay_id) {
        return props.associations;
    }

    return props.associations.filter((association) => String(association.barangay_id) === String(form.barangay_id));
});

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

function submit() {
    form.post(props.storeUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Encode Old Record" />

    <AdminLayout title="Encode Old Record">
        <div class="space-y-7 bg-[radial-gradient(circle_at_top_left,_rgba(186,238,217,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(165,213,119,0.12),_transparent_22%)]">
            <section class="rounded-[28px] border border-[#dbe7e0] bg-white/90 px-6 py-6 shadow-[0_24px_80px_rgba(18,53,42,0.08)]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.28em] text-[#7a8781]">Farmer Registry</p>
                        <h1 class="text-3xl font-black tracking-[-0.03em] text-[#16352c]">Encode Old Record</h1>
                        <p class="max-w-3xl text-sm text-[#5f6c67]">Create a farmer registry record directly for old manual records without sending it to the membership application queue.</p>
                    </div>
                    <div class="rounded-[22px] border border-[#d7e0db] bg-[#f5f8f6] px-5 py-4">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Next Farmer Code</p>
                        <p class="mt-2 text-lg font-extrabold text-[#16352c]">{{ nextFarmerCode }}</p>
                    </div>
                </div>
            </section>

            <section v-if="form.errors.duplicate_check" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
                {{ form.errors.duplicate_check }}
            </section>

            <section v-if="duplicateMatches.length" class="rounded-[28px] border border-amber-200 bg-amber-50/80 px-6 py-6 shadow-[0_20px_60px_rgba(120,84,14,0.08)]">
                <div class="space-y-4">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.26em] text-[#8a6a16]">Duplicate Check</p>
                        <h2 class="mt-2 text-xl font-black text-[#16352c]">Possible existing farmer records found</h2>
                    </div>

                    <div class="space-y-3">
                        <div v-for="match in duplicateMatches" :key="match.id" class="rounded-[22px] border border-amber-200 bg-white px-4 py-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-sm font-extrabold text-[#16352c]">{{ match.full_name }}</p>
                                    <p class="mt-1 text-xs text-[#5f6c67]">{{ match.farmer_code }}<span v-if="match.barangay_name"> • {{ match.barangay_name }}</span><span v-if="match.member_type"> • {{ match.member_type }}</span></p>
                                </div>
                                <Link v-if="match.show_url" :href="match.show_url" class="text-xs font-bold text-[#8a6a16] transition hover:text-[#6e5411]">
                                    Open record
                                </Link>
                            </div>
                            <p class="mt-3 text-xs font-medium text-[#745d28]">{{ match.reasons.join(', ') }}</p>
                        </div>
                    </div>

                    <label class="inline-flex items-start gap-3 text-sm text-[#4f5d58]">
                        <input v-model="form.confirm_duplicate_override" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-[#003629] focus:ring-[#003629]">
                        <span>These records are different people. Continue saving this old record.</span>
                    </label>
                </div>
            </section>

            <form class="space-y-6" @submit.prevent="submit">
                <EditSectionCard eyebrow="Registry Details" title="Assignment and status">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] px-4 py-3">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Record Origin</p>
                            <p class="mt-2 text-sm font-semibold text-[#191c1c]">Old Record</p>
                        </div>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Member Type</span>
                            <select v-model="form.member_type_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">Select member type</option>
                                <option v-for="item in memberTypes" :key="item.id" :value="String(item.id)">{{ item.code }} - {{ item.name }}</option>
                            </select>
                            <p v-if="form.errors.member_type_id" class="text-xs font-medium text-rose-600">{{ form.errors.member_type_id }}</p>
                        </label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Registry Status</span>
                            <select v-model="form.status" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                            <p v-if="form.errors.status" class="text-xs font-medium text-rose-600">{{ form.errors.status }}</p>
                        </label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Registered At</span>
                            <input v-model="form.registered_at" type="date" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                            <p v-if="form.errors.registered_at" class="text-xs font-medium text-rose-600">{{ form.errors.registered_at }}</p>
                        </label>
                    </div>
                </EditSectionCard>

                <EditSectionCard eyebrow="Identity" title="Personal details">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                </EditSectionCard>

                <EditSectionCard eyebrow="Location" title="Contact and assignment">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Mobile Number</span><input v-model="form.mobile_number" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"><p v-if="form.errors.mobile_number" class="text-xs font-medium text-rose-600">{{ form.errors.mobile_number }}</p></label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Barangay</span>
                            <select v-model="form.barangay_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white" @change="syncAssociation">
                                <option value="">Select barangay</option>
                                <option v-for="item in barangays" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                            </select>
                            <p v-if="form.errors.barangay_id" class="text-xs font-medium text-rose-600">{{ form.errors.barangay_id }}</p>
                        </label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Association</span>
                            <select v-model="form.association_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">No association assigned</option>
                                <option v-for="item in filteredAssociations" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                            </select>
                            <p v-if="form.errors.association_id" class="text-xs font-medium text-rose-600">{{ form.errors.association_id }}</p>
                        </label>
                        <label class="space-y-2 md:col-span-2 xl:col-span-4">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Address</span>
                            <textarea v-model="form.address" rows="4" class="w-full rounded-[20px] border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></textarea>
                            <p v-if="form.errors.address" class="text-xs font-medium text-rose-600">{{ form.errors.address }}</p>
                        </label>
                        <label class="space-y-2 md:col-span-2 xl:col-span-4">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Remarks</span>
                            <textarea v-model="form.remarks" rows="4" class="w-full rounded-[20px] border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></textarea>
                            <p v-if="form.errors.remarks" class="text-xs font-medium text-rose-600">{{ form.errors.remarks }}</p>
                        </label>
                    </div>
                </EditSectionCard>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <Link :href="indexUrl" class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-5 py-3 text-sm font-bold text-[#697772] transition hover:bg-[#f4f7f5]">
                        Cancel
                    </Link>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-[#003629] px-6 py-3 text-sm font-extrabold text-white shadow-[0_16px_30px_rgba(0,54,41,0.16)] transition hover:bg-[#0d4637]" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Old Record' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
