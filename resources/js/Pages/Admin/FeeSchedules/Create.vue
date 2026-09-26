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

    form.post(props.urls.store, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Add Fee Schedule" />

    <AdminLayout title="Add Fee Schedule">
        <div class="space-y-4">
            <!-- Hero header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>

                <div class="relative flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Fee Configuration</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Add Fee Schedule</h1>
                        </div>
                    </div>
                    <Link :href="urls.index" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/20 px-3.5 text-[0.7rem] font-semibold text-white/90 transition-all duration-200 hover:bg-white/10 hover:text-white">
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.47 4.47a.75.75 0 1 1-1.06 1.06l-5.75-5.75a.75.75 0 0 1 0-1.06l5.75-5.75a.75.75 0 1 1 1.06 1.06L5.61 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                        All Schedules
                    </Link>
                </div>
            </section>

            <!-- Form card -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <form class="divide-y divide-[#edf2ee]" @submit.prevent="submit">
                    <div class="px-5 py-4">
                        <div class="flex items-center gap-3 pb-4">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#e6f5ec] to-[#d4eddd]">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#0f6b45]" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 7h20M2 12h20M2 17h20"/></svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#0f172a]">Schedule Details</h2>
                        </div>
                        <FormFields :form="form" :member-type-options="memberTypeOptions" />
                    </div>

                    <!-- Fee rules -->
                    <div v-if="feeRules.length" class="bg-[#fffbeb] px-5 py-4">
                        <div class="flex items-center gap-3 pb-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#fef3c7]">
                                <svg viewBox="0 0 20 20" class="h-4 w-4 text-[#d97706]" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                            </div>
                            <h2 class="text-xs font-bold text-[#92400e]">Fee Rules</h2>
                        </div>
                        <div class="grid gap-2 md:grid-cols-2">
                            <article v-for="rule in feeRules" :key="rule.label" class="rounded-lg border border-[#fde68a]/40 bg-white px-3.5 py-2.5">
                                <p class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-[#b45309]">{{ rule.label }}</p>
                                <p class="mt-1 text-[0.68rem] leading-4 text-[#78350f]">{{ rule.description }}</p>
                            </article>
                        </div>
                    </div>

                    <div class="flex gap-2 bg-[#fbfcfb] px-5 py-3.5 sm:justify-end">
                        <Link :href="urls.index" class="inline-flex h-9 items-center justify-center rounded-lg border border-[#dbe3dd] bg-white px-4 text-xs font-semibold text-[#64748b] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5]">
                            Cancel
                        </Link>
                        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#014d3c] px-5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#01362a] hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Fee Schedule' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
