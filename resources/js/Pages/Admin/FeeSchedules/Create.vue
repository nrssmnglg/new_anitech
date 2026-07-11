<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import FormFields from '../../../Components/Admin/FeeSchedules/FormFields.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    feeSchedule: { type: Object, required: true },
    activateByDefault: { type: Boolean, required: true },
    feeRules: { type: Array, required: true },
    memberTypeOptions: { type: Array, required: true },
    urls: { type: Object, required: true },
});

const form = useForm({
    member_type_id: props.feeSchedule.member_type_id ? String(props.feeSchedule.member_type_id) : '',
    year: props.feeSchedule.year,
    renewal_deadline: props.feeSchedule.renewal_deadline,
    membership_fee: props.feeSchedule.membership_fee,
    annual_due: props.feeSchedule.annual_due,
    mortuary_fee: props.feeSchedule.mortuary_fee,
    effective_from: props.feeSchedule.effective_from,
    effective_to: props.feeSchedule.effective_to,
    is_active: props.activateByDefault || props.feeSchedule.is_active,
});

function submit() {
    if (form.processing) {
        return;
    }

    form.transform((data) => ({
        ...data,
        member_type_id: data.member_type_id ? Number(data.member_type_id) : null,
        is_active: Boolean(data.is_active),
        effective_to: data.effective_to || null,
    }));

    form.post(props.urls.store, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Add Fee Schedule" />

    <AdminLayout title="Add Fee Schedule">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[1.35rem] border border-[#dde4de] bg-white shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                <div class="border-b border-[#edf2ee] bg-[#fbfcfb] px-6 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <h1 class="text-2xl font-black tracking-[-0.03em] text-[#0f172a]">Add Fee Schedule</h1>
                            <p class="mt-2 text-sm text-[#64748b]">Create a yearly fee configuration for membership assessments and renewal processing.</p>
                        </div>

                        <Link :href="urls.index" class="inline-flex items-center justify-center rounded-xl border border-[#dbe3dd] bg-white px-4 py-2.5 text-sm font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]" :class="{ 'pointer-events-none opacity-60': form.processing }">
                            Back to Fee Schedules
                        </Link>
                    </div>
                </div>

                <form class="space-y-6 p-6" @submit.prevent="submit">
                    <section class="rounded-[1.2rem] border border-[#e9efeb] bg-[#fbfcfb] p-6">
                        <div class="mb-6">
                            <h2 class="text-lg font-black text-[#0f172a]">Schedule Details</h2>
                            <p class="mt-2 text-sm text-[#64748b]">Set the base fees that the system will apply across member categories.</p>
                        </div>
                        <FormFields :form="form" :member-type-options="memberTypeOptions" />
                    </section>

                    <section class="rounded-[1.2rem] border border-[#efe3bf] bg-[#fffaf0] p-6">
                        <h2 class="text-lg font-black text-[#5b4a1c]">How The System Applies These Fees</h2>
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <article v-for="rule in feeRules" :key="rule.label" class="rounded-2xl border border-[#f1e6c8] bg-white p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.08em] text-[#8a6218]">{{ rule.label }}</p>
                                <p class="mt-2 text-sm text-[#5b4a1c]">{{ rule.description }}</p>
                            </article>
                        </div>
                    </section>

                    <div class="flex flex-col gap-3 rounded-2xl border border-[#edf2ee] bg-[#fbfcfb] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-[#64748b]">The active schedule is used by payment assessments and renewal calculations immediately.</p>
                        <div class="flex gap-3">
                            <Link :href="urls.index" class="inline-flex items-center justify-center rounded-xl border border-[#dbe3dd] bg-white px-5 py-3 text-sm font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]" :class="{ 'pointer-events-none opacity-60': form.processing }">
                                Cancel
                            </Link>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#014d3c] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#01362a] disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Save Fee Schedule' }}
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
