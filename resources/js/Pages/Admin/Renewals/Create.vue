<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    farmer: { type: Object, required: true },
    defaultYear: { type: Number, required: true },
    storeUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
    showFarmerUrl: { type: String, required: true },
});

const form = useForm({
    farmer_id: props.farmer.id,
    year: props.defaultYear,
    remarks: '',
});

function submit() {
    form.post(props.storeUrl, {
        preserveScroll: true,
    });
}

const memberTypeCode = computed(() => props.farmer.memberType?.code || 'N/A');
const memberTypeName = computed(() => props.farmer.memberType?.name || 'Not assigned');
</script>

<template>
    <Head title="Process Renewal" />

    <AdminLayout title="Process Renewal">
        <div class="space-y-8">


            <section class="-mt-20 grid gap-6 xl:grid-cols-12">
                <div class="space-y-6 xl:col-span-8">
                    <section class="overflow-hidden rounded-[24px] border border-[#dddeda] bg-white shadow-[0_16px_34px_rgba(15,23,42,0.05)]">
                        <div class="flex flex-col gap-3 border-b border-[#e1e3e2] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="flex items-center gap-3 text-xl font-bold text-[#003629]">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#eef5f1] text-[#003629]">+</span>
                                Renewal Setup
                            </h2>
                            <span class="inline-flex items-center rounded-full bg-[#c0f190] px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[#466f1e]">
                                Step 1 of 1
                            </span>
                        </div>

                        <form class="space-y-6 px-6 py-6" @submit.prevent="submit">
                            <div class="grid gap-5 md:grid-cols-2">
                                <label class="space-y-2">
                                    <span class="block text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#5f6c67]">Renewal Year</span>
                                    <input v-model="form.year" type="number" min="2000" :max="new Date().getFullYear() + 1" class="w-full rounded-xl border border-[#c0c9c3] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#003629] focus:bg-white">
                                    <p class="text-[0.72rem] italic text-[#7a8781]">The fiscal year for this membership period.</p>
                                </label>

                                <label class="space-y-2">
                                    <span class="block text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#5f6c67]">Renewal Type</span>
                                    <input value="Walk-In" type="text" readonly class="w-full cursor-not-allowed rounded-xl border border-[#d4dad7] bg-[#eceeed] px-4 py-3 text-sm text-[#5f6c67]">
                                    <p class="text-[0.72rem] italic text-[#7a8781]">Preset for manual administrative entry.</p>
                                </label>
                            </div>

                            <label class="space-y-2">
                                <span class="block text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#5f6c67]">Remarks & Documentation</span>
                                <textarea v-model="form.remarks" rows="4" placeholder="Enter any specific notes regarding this renewal transaction..." class="w-full resize-none rounded-xl border border-[#c0c9c3] bg-[#f8faf9] px-4 py-3 text-sm text-[#191c1c] outline-none transition focus:border-[#003629] focus:bg-white"></textarea>
                            </label>

                            <div class="flex flex-col gap-3 border-t border-[#e1e3e2] pt-5 sm:flex-row sm:items-center sm:justify-between">
                                <Link :href="indexUrl" class="inline-flex items-center gap-2 text-sm font-bold text-[#003629] transition hover:underline">
                                    Cancel and Go Back
                                </Link>
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#003629] px-6 py-3 text-sm font-extrabold text-white shadow-[0_12px_24px_rgba(0,54,41,0.16)] transition hover:bg-[#0e4638] disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                                    {{ form.processing ? 'Saving...' : 'Create Renewal Record' }}
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="flex items-start gap-4 rounded-[24px] border border-[#d6e3dc] bg-[#f4faf7] p-6">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white text-[#003629] shadow-sm">
                            i
                        </div>
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-[0.18em] text-[#003629]">Verification Protocol</h3>
                            <p class="mt-2 text-sm leading-7 text-[#5f6c67]">
                                Processing this renewal will verify the farmer's current standing in the registry. Confirm that the physical records match the details in the summary panel before finalizing the renewal.
                            </p>
                        </div>
                    </section>
                </div>

                <div class="space-y-6 xl:col-span-4">
                    <section class="overflow-hidden rounded-[24px] border border-[#dddeda] bg-white shadow-[0_16px_34px_rgba(15,23,42,0.05)]">
                        <div class="h-24 bg-[linear-gradient(135deg,_#1b4d3e_0%,_#416918_100%)]"></div>
                        <div class="relative px-6 pb-6">
                            <div class="-mt-10 mx-auto flex h-20 w-20 items-center justify-center rounded-full border-4 border-white bg-[#c9eadf] text-xl font-black text-[#16332c] shadow-md">
                                {{ farmer.fullName?.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() }}
                            </div>

                            <div class="mt-4 text-center">
                                <h2 class="text-xl font-bold text-[#191c1c]">{{ farmer.fullName }}</h2>
                                <p class="mt-1 text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#003629]">Active Member</p>
                            </div>

                            <div class="mt-6 space-y-3 border-t border-[#e1e3e2] pt-6">
                                <div class="flex items-center justify-between rounded-xl bg-[#f2f4f3] p-4">
                                    <div>
                                        <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Farmer Code</p>
                                        <p class="mt-1 text-sm font-semibold text-[#191c1c]">{{ farmer.farmerCode || 'Unassigned' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between rounded-xl bg-[#f2f4f3] p-4">
                                    <div>
                                        <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7a8781]">Member Type</p>
                                        <p class="mt-1 text-sm font-semibold text-[#191c1c]">{{ memberTypeCode }} - {{ memberTypeName }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 text-center">
                                <Link :href="showFarmerUrl" class="inline-flex items-center gap-2 text-sm font-bold text-[#003629] transition hover:underline">
                                    Back To Farmer Record
                                </Link>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[24px] border border-[#dddeda] bg-white p-6 shadow-[0_16px_34px_rgba(15,23,42,0.05)]">
                        <h3 class="text-sm font-black uppercase tracking-[0.18em] text-[#191c1c]">Registry Insights</h3>
                        <div class="mt-5 space-y-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-[#5f6c67]">Default Renewal Year</span>
                                <span class="font-semibold text-[#191c1c]">{{ defaultYear }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-[#5f6c67]">Member Type</span>
                                <span class="font-semibold text-[#416918]">{{ memberTypeCode }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-[#eceeed]">
                                <div class="h-full w-[96%] rounded-full bg-[#416918]"></div>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
