<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RequirementForm from '../../../Components/Admin/DocumentRequirements/RequirementForm.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const NEW_DOCUMENT_TYPE_VALUE = '__new__';

const props = defineProps({
    filters: { type: Object, required: true },
    transactionTypeOptions: { type: Array, required: true },
    documentTypeOptions: { type: Array, required: true },
    summary: { type: Object, required: true },
    requirements: { type: Array, required: true },
    formDefaults: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const mode = ref('create');
const editingRequirementId = ref(null);
const deletingId = ref(null);
const navigating = ref(false);

const createForm = useForm({
    transaction_type: props.formDefaults.transaction_type,
    document_type_id: props.formDefaults.document_type_id,
    new_document_type_name: '',
    is_required: props.formDefaults.is_required,
    is_active: props.formDefaults.is_active,
});

const editForm = useForm({
    transaction_type: 'Application',
    document_type_id: '',
    new_document_type_name: '',
    is_required: true,
    is_active: true,
});

function transformedPayload(data) {
    return {
        ...data,
        transaction_type_filter: props.filters.transaction_type,
        document_type_id: data.document_type_id && data.document_type_id !== NEW_DOCUMENT_TYPE_VALUE ? data.document_type_id : null,
        is_required: Boolean(data.is_required),
        is_active: Boolean(data.is_active),
    };
}

const editingRequirement = computed(() => props.requirements.find((item) => item.id === editingRequirementId.value) ?? null);
const isBusy = computed(() => createForm.processing || editForm.processing || deletingId.value !== null || navigating.value);
const formKey = computed(() => (mode.value === 'edit' ? `edit-${editingRequirementId.value}` : 'create'));
const visibleTransactionCount = computed(() => props.requirements.length);

function submitCreate() {
    if (isBusy.value) {
        return;
    }

    createForm.transform(transformedPayload).post(props.urls.store, {
        preserveScroll: true,
        onSuccess: () => {
            resetCreateForm();
        },
    });
}

function startEdit(requirement) {
    if (isBusy.value) {
        return;
    }

    mode.value = 'edit';
    editingRequirementId.value = requirement.id;
    editForm.transaction_type = requirement.transactionType;
    editForm.document_type_id = requirement.documentType.id ? String(requirement.documentType.id) : '';
    editForm.new_document_type_name = '';
    editForm.is_required = requirement.isRequired;
    editForm.is_active = requirement.isActive;
    editForm.clearErrors();
}

function cancelEdit() {
    mode.value = 'create';
    editingRequirementId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function submitEdit(requirement) {
    if (isBusy.value || !requirement) {
        return;
    }

    editForm.transform(transformedPayload).put(requirement.actions.updateUrl, {
        preserveScroll: true,
        onSuccess: () => {
            cancelEdit();
        },
    });
}

function destroyRequirement(requirement) {
    if (isBusy.value) {
        return;
    }

    if (!window.confirm(`Delete ${requirement.documentType.label} from ${requirement.transactionType.toLowerCase()} requirements?`)) {
        return;
    }

    deletingId.value = requirement.id;

    router.delete(requirement.actions.deleteUrl, {
        data: {
            transaction_type_filter: props.filters.transaction_type,
        },
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
            if (editingRequirementId.value === requirement.id) {
                cancelEdit();
            }
        },
    });
}

function applyTransactionFilter(transactionType) {
    if (isBusy.value || props.filters.transaction_type === transactionType) {
        return;
    }

    cancelEdit();
    resetCreateForm(transactionType === 'All' ? 'Application' : transactionType);
    navigating.value = true;

    router.get(props.urls.index, { transaction_type: transactionType }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            navigating.value = false;
        },
    });
}

function refreshRecords() {
    if (isBusy.value) {
        return;
    }

    cancelEdit();
    navigating.value = true;

    router.get(props.urls.index, { transaction_type: props.filters.transaction_type }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            navigating.value = false;
        },
    });
}

function resetCreateForm(transactionType = props.formDefaults.transaction_type) {
    createForm.reset();
    createForm.transaction_type = transactionType;
    createForm.document_type_id = '';
    createForm.new_document_type_name = '';
    createForm.is_required = true;
    createForm.is_active = true;
    createForm.clearErrors();
}

function transactionTone(value) {
    if (value === 'Renewal') {
        return 'bg-[#eef7f2] text-[#0f5b46]';
    }

    if (value === 'Reactivation') {
        return 'bg-[#fff4dc] text-[#b46d00]';
    }

    return 'bg-[#e7eefc] text-[#3454a1]';
}
</script>

<template>
    <Head title="Document Requirements" />

    <AdminLayout title="Document Requirements">
        <div class="space-y-3">
            <section class="rounded-xl bg-[#003629] px-4 py-3.5 text-white">
                <p class="text-[0.58rem] font-semibold uppercase tracking-[0.12em] text-white/60">Membership configuration</p>
                <h1 class="mt-0.5 text-xl font-semibold tracking-[-0.02em]">Document Requirements</h1>
            </section>

            <section class="grid grid-cols-2 gap-2 lg:grid-cols-4">
                <article class="rounded-lg border border-[#dbe4de] bg-white px-3 py-2.5">
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Total Rules</p>
                    <h2 class="mt-1 text-xl font-semibold leading-none text-[#0f172a]">{{ summary.total }}</h2>
                </article>
                <article class="rounded-lg border border-[#dbe4de] bg-white px-3 py-2.5">
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Active Rules</p>
                    <h2 class="mt-1 text-xl font-semibold leading-none text-[#0f5b46]">{{ summary.active }}</h2>
                </article>
                <article class="rounded-lg border border-[#dbe4de] bg-white px-3 py-2.5">
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Required Rules</p>
                    <h2 class="mt-1 text-xl font-semibold leading-none text-[#b46d00]">{{ summary.required }}</h2>
                </article>
                <article class="rounded-lg border border-[#dbe4de] bg-white px-3 py-2.5">
                    <p class="text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">Visible Records</p>
                    <h2 class="mt-1 text-xl font-semibold leading-none text-[#0f172a]">{{ visibleTransactionCount }}</h2>
                </article>
            </section>

            <section class="rounded-lg border border-[#dde4de] bg-white p-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-[#0f172a]">Transaction scope</h2>
                    <button type="button" class="inline-flex h-8 items-center justify-center rounded-md border border-[#dbe3dd] px-3 text-[0.68rem] font-semibold text-[#0f5b46] transition hover:bg-[#f4f7f5] disabled:opacity-60" :disabled="isBusy" @click="refreshRecords">
                        Refresh
                    </button>
                </div>

                <div class="mt-2 flex flex-wrap gap-2">
                    <button
                        v-for="option in transactionTypeOptions"
                        :key="option.value"
                        type="button"
                        class="h-8 rounded-md border px-3 text-[0.68rem] font-semibold transition"
                        :class="filters.transaction_type === option.value ? 'border-[#014d3c] bg-[#014d3c] text-white' : 'border-[#dbe4de] bg-white text-[#64748b] hover:bg-[#f4f7f5]'"
                        :disabled="isBusy"
                        @click="applyTransactionFilter(option.value)"
                    >
                        {{ option.label }}
                    </button>
                </div>

                <div class="mt-2 grid gap-2 md:grid-cols-3">
                    <article v-for="transaction in summary.transactionCounts" :key="transaction.value" class="rounded-md bg-[#f6f8f7] px-3 py-2">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold text-[#0f172a]">{{ transaction.label }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-semibold" :class="transactionTone(transaction.value)">{{ transaction.count }}</span>
                        </div>
                    </article>
                </div>
            </section>

            <RequirementForm
                v-if="mode === 'create'"
                :key="formKey"
                :form="createForm"
                :transaction-type-options="transactionTypeOptions"
                :document-type-options="documentTypeOptions"
                heading="Add Requirement"
                description=""
                submit-label="Add Requirement"
                :disabled="isBusy"
                @submit="submitCreate"
                @cancel="resetCreateForm()"
            />

            <RequirementForm
                v-else
                :key="formKey"
                :form="editForm"
                :transaction-type-options="transactionTypeOptions"
                :document-type-options="documentTypeOptions"
                heading="Edit Configured Requirement"
                description=""
                submit-label="Save Changes"
                :disabled="isBusy"
                @submit="submitEdit(editingRequirement)"
                @cancel="cancelEdit"
            />

            <section class="overflow-hidden rounded-lg border border-[#dde4de] bg-white">
                <div class="border-b border-[#edf2ee] px-4 py-3">
                    <h2 class="text-sm font-semibold text-[#0f172a]">Configured requirements</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead class="bg-[#f9fbfa]">
                            <tr class="border-b border-[#e8eeea] text-left text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-[#64748b]">
                                <th class="px-4 py-2.5">Transaction</th>
                                <th class="px-4 py-2.5">Document Type</th>
                                <th class="px-4 py-2.5">Required</th>
                                <th class="px-4 py-2.5">Status</th>
                                <th class="px-4 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="requirement in requirements" :key="requirement.id" class="border-b border-[#edf2ee] transition hover:bg-[#fcfefd]">
                                <td class="px-4 py-3 align-middle">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[0.62rem] font-semibold" :class="transactionTone(requirement.transactionType)">
                                        {{ requirement.transactionType }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <p class="font-semibold text-[#0f172a]">{{ requirement.documentType.label }}</p>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-semibold" :class="requirement.isRequired ? 'bg-[#d9f4c2] text-[#50761b]' : 'bg-[#eceff1] text-[#5e6c74]'">
                                        {{ requirement.isRequired ? 'Required' : 'Optional' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-semibold" :class="requirement.isActive ? 'bg-[#eef7f2] text-[#0f5b46]' : 'bg-[#eceff1] text-[#5e6c74]'">
                                        {{ requirement.isActive ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center justify-end gap-3 text-xs">
                                        <button type="button" class="font-medium text-[#014d3c] transition hover:underline disabled:cursor-not-allowed disabled:opacity-50" :disabled="isBusy" @click="startEdit(requirement)">
                                            Edit
                                        </button>
                                        <button type="button" class="font-medium text-[#c05c3c] transition hover:underline disabled:cursor-not-allowed disabled:opacity-50" :disabled="isBusy" @click="destroyRequirement(requirement)">
                                            {{ deletingId === requirement.id ? 'Deleting...' : 'Delete' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="requirements.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-xs text-[#64748b]">No document requirements found for the selected transaction type.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
