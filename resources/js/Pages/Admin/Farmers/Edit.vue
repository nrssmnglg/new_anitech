<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import EditHero from '../../../Components/Admin/Farmers/EditHero.vue';
import EditSectionCard from '../../../Components/Admin/Farmers/EditSectionCard.vue';

const props = defineProps({
    farmer: { type: Object, required: true },
    statuses: { type: Array, required: true },
    barangays: { type: Array, required: true },
    associations: { type: Array, required: true },
    memberTypes: { type: Array, required: true },
    updateUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
    showUrl: { type: String, required: true },
});

const form = useForm({
    first_name: props.farmer.profile.firstName || '',
    middle_name: props.farmer.profile.middleName || '',
    last_name: props.farmer.profile.lastName || '',
    suffix: props.farmer.profile.suffix || '',
    birth_date: props.farmer.profile.birthDate || '',
    sex: props.farmer.profile.sex || '',
    civil_status: props.farmer.profile.civilStatus || '',
    mobile_number: props.farmer.profile.mobileNumber || '',
    address: props.farmer.profile.address || '',
    barangay_id: String(props.farmer.barangay?.id || ''),
    association_id: String(props.farmer.association?.id || ''),
    member_type_id: String(props.farmer.memberType?.id || ''),
    status: props.farmer.status.value,
    registered_at: props.farmer.registeredAt ? new Date(props.farmer.registeredAt).toISOString().slice(0, 10) : '',
    remarks: '',
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
    form.put(props.updateUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Edit ${farmer.fullName}`" />

    <AdminLayout title="Edit Farmer Record">
        <div class="space-y-7 bg-[radial-gradient(circle_at_top_left,_rgba(186,238,217,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(165,213,119,0.12),_transparent_22%)]">
            <EditHero :farmer="farmer" :show-url="showUrl" />

            <form class="space-y-6" @submit.prevent="submit">
                <EditSectionCard eyebrow="Registry Details" title="Assignment and status">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Member Type</span>
                            <select v-model="form.member_type_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">Select member type</option>
                                <option v-for="item in memberTypes" :key="item.id" :value="String(item.id)">{{ item.code }} - {{ item.name }}</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Registry Status</span>
                            <select v-model="form.status" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Registered At</span>
                            <input v-model="form.registered_at" type="date" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                        </label>
                        <div class="rounded-[20px] border border-[#e3eae6] bg-[#f7faf8] px-4 py-3">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Current Membership</p>
                            <p class="mt-2 text-sm font-semibold text-[#191c1c]">{{ farmer.membershipStatusLabel || 'Not set' }}</p>
                        </div>
                    </div>
                </EditSectionCard>

                <EditSectionCard eyebrow="Identity" title="Personal details">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">First Name</span><input v-model="form.first_name" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></label>
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Middle Name</span><input v-model="form.middle_name" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></label>
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Last Name</span><input v-model="form.last_name" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></label>
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Suffix</span><input v-model="form.suffix" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></label>
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Birth Date</span><input v-model="form.birth_date" type="date" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Sex</span>
                            <select v-model="form.sex" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">Select sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
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
                        </label>
                    </div>
                </EditSectionCard>

                <EditSectionCard eyebrow="Location" title="Contact and assignment">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-2"><span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Mobile Number</span><input v-model="form.mobile_number" type="text" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Barangay</span>
                            <select v-model="form.barangay_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white" @change="syncAssociation">
                                <option value="">Select barangay</option>
                                <option v-for="item in barangays" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                            </select>
                        </label>
                        <label class="space-y-2">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Association</span>
                            <select v-model="form.association_id" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white">
                                <option value="">No association assigned</option>
                                <option v-for="item in filteredAssociations" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                            </select>
                        </label>
                        <label class="space-y-2 md:col-span-2 xl:col-span-4">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#7a8781]">Address</span>
                            <textarea v-model="form.address" rows="4" class="w-full rounded-[20px] border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#376757] focus:bg-white"></textarea>
                        </label>
                    </div>
                </EditSectionCard>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <Link :href="indexUrl" class="inline-flex items-center justify-center rounded-2xl border border-[#d7e0db] px-5 py-3 text-sm font-bold text-[#697772] transition hover:bg-[#f4f7f5]">
                        Cancel
                    </Link>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-[#003629] px-6 py-3 text-sm font-extrabold text-white shadow-[0_16px_30px_rgba(0,54,41,0.16)] transition hover:bg-[#0d4637]" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
