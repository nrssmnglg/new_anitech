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
        member_type_id: data.member_type_id || null,
        is_active: Boolean(data.is_active),
        effective_to: data.effective_to || null,
    }));

    form.put(props.urls.update, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit Fee Schedule" />

    <AdminLayout title="Edit Fee Schedule">
        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">Fee configuration</p>
                        <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">Edit Fee Schedule</h1>
                    </div>

                    <Link :href="urls.index" class="inline-flex h-8 items-center justify-center rounded-md border border-white/25 px-3 text-[0.68rem] font-semibold text-white transition hover:bg-white/10" :class="{ 'pointer-events-none opacity-60': form.processing }">
                        Fee Schedules
                    </Link>
                </div>
            </section>

            <section class="rounded-lg border border-[#dde4de] bg-white p-4">
                <form class="space-y-4" @submit.prevent="submit">
                    <section>
                        <div class="mb-3 border-b border-[#edf2ee] pb-2.5">
                            <h2 class="text-sm font-semibold text-[#0f172a]">Schedule details</h2>
                        </div>
                        <FormFields :form="form" :member-type-options="memberTypeOptions" />
                    </section>

                    <section v-if="feeRules.length" class="rounded-md border border-[#efe3bf] bg-[#fffaf0] p-3">
                        <h2 class="text-xs font-semibold text-[#5b4a1c]">Fee rules</h2>
                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                            <article v-for="rule in feeRules" :key="rule.label" class="rounded-md bg-white px-3 py-2">
                                <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#8a6218]">{{ rule.label }}</p>
                                <p class="mt-1 text-[0.68rem] leading-4 text-[#5b4a1c]">{{ rule.description }}</p>
                            </article>
                        </div>
                    </section>

                    <div class="flex gap-2 border-t border-[#edf2ee] pt-3 sm:justify-end">
                        <Link :href="urls.index" class="inline-flex h-9 items-center justify-center rounded-md border border-[#dbe3dd] bg-white px-3 text-xs font-semibold text-[#64748b] transition hover:bg-[#f4f7f5]" :class="{ 'pointer-events-none opacity-60': form.processing }">
                            Cancel
                        </Link>
                        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-[#014d3c] px-4 text-xs font-semibold text-white transition hover:bg-[#01362a] disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
