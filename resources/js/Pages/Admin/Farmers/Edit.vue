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
    first_name: props.farmer.profile?.firstName || '',
    middle_name: props.farmer.profile?.middleName || '',
    last_name: props.farmer.profile?.lastName || '',
    suffix: props.farmer.profile?.suffix || '',
    birth_date: props.farmer.profile?.birthDate || '',
    sex: props.farmer.profile?.sex || '',
    civil_status: props.farmer.profile?.civilStatus || '',
    mobile_number: props.farmer.profile?.mobileNumber || '',
    address: props.farmer.profile?.address || '',
    barangay_id: String(props.farmer.barangay?.key || props.farmer.barangay?.id || ''),
    association_id: String(props.farmer.association?.key || props.farmer.association?.id || ''),
    member_type_id: String(props.farmer.memberType?.key || props.farmer.memberType?.id || ''),
    status: props.farmer.status.value,
    registered_at: props.farmer.registeredAt ? new Date(props.farmer.registeredAt).toISOString().slice(0, 10) : '',
    remarks: '',
});

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
    form.put(props.updateUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Edit ${farmer.fullName}`" />

    <AdminLayout title="Edit Farmer Record">
        <div class="space-y-4">
            <EditHero :farmer="farmer" :show-url="showUrl" :index-url="indexUrl" />

            <form class="space-y-4" @submit.prevent="submit">
                <!-- Section 1: Registry Assignment & Status -->
                <EditSectionCard eyebrow="Registry Details" title="Assignment and Status">
                    <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Member Type</span>
                            <select
                                v-model="form.member_type_id"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                                <option value="">Select member type</option>
                                <option v-for="item in memberTypes" :key="item.key || item.id" :value="String(item.key || item.id)">
                                    {{ item.code }} - {{ item.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Registry Status</span>
                            <select
                                v-model="form.status"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                                <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Registration Date</span>
                            <input
                                v-model="form.registered_at"
                                type="date"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </label>

                        <div class="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-3 flex flex-col justify-center">
                            <span class="text-[0.6rem] font-bold uppercase tracking-wider text-[#64748b]">Membership Status</span>
                            <p class="mt-0.5 text-xs font-bold text-[#014d3c]">{{ farmer.membershipStatusLabel || 'Not set' }}</p>
                        </div>
                    </div>
                </EditSectionCard>

                <!-- Section 2: Personal Details -->
                <EditSectionCard eyebrow="Identity" title="Personal Details">
                    <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">First Name</span>
                            <input
                                v-model="form.first_name"
                                type="text"
                                placeholder="Given name"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Middle Name</span>
                            <input
                                v-model="form.middle_name"
                                type="text"
                                placeholder="Middle name"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Last Name</span>
                            <input
                                v-model="form.last_name"
                                type="text"
                                placeholder="Family name"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Suffix</span>
                            <input
                                v-model="form.suffix"
                                type="text"
                                placeholder="Jr., Sr., III, etc."
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Birth Date</span>
                            <input
                                v-model="form.birth_date"
                                type="date"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Sex / Gender</span>
                            <select
                                v-model="form.sex"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                                <option value="">Select sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </label>

                        <label class="block space-y-1.5 sm:col-span-2">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Civil Status</span>
                            <select
                                v-model="form.civil_status"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                                <option value="">Select status</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="widowed">Widowed</option>
                                <option value="separated">Separated</option>
                            </select>
                        </label>
                    </div>
                </EditSectionCard>

                <!-- Section 3: Contact and Location -->
                <EditSectionCard eyebrow="Location &amp; Contact" title="Geographic &amp; Outreach Details">
                    <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Mobile Phone</span>
                            <input
                                v-model="form.mobile_number"
                                type="text"
                                placeholder="09xxxxxxxxx"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Barangay</span>
                            <select
                                v-model="form.barangay_id"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                                @change="syncAssociation"
                            >
                                <option value="">Select barangay</option>
                                <option v-for="item in barangays" :key="item.key || item.id" :value="String(item.key || item.id)">
                                    {{ item.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block space-y-1.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Association</span>
                            <select
                                v-model="form.association_id"
                                class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] px-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            >
                                <option value="">No association assigned</option>
                                <option v-for="item in filteredAssociations" :key="item.key || item.id" :value="String(item.key || item.id)">
                                    {{ item.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block space-y-1.5 sm:col-span-2 lg:col-span-3">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-[#64748b]">Street Address</span>
                            <textarea
                                v-model="form.address"
                                rows="2"
                                placeholder="House no., street, purok, sitio..."
                                class="w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] p-3 text-xs text-[#0f172a] outline-none transition focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                            ></textarea>
                        </label>
                    </div>
                </EditSectionCard>

                <!-- Form Bottom Actions -->
                <div class="flex items-center justify-end gap-2 pt-2">
                    <Link
                        :href="indexUrl"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dde4de] bg-white px-4 text-xs font-semibold text-[#64748b] transition hover:bg-[#f1f5f3] hover:text-[#0f172a]"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#01362a] active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
