<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const flashError = computed(() => page.props.flash?.error || '');

const mode = ref('create');
const editingRequirementId = ref(null);
const deletingId = ref(null);
const navigating = ref(false);
const searchQuery = ref('');

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

const filteredRequirements = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.requirements;
    }
    const q = searchQuery.value.toLowerCase().trim();
    return props.requirements.filter((req) => {
        return (
            req.documentType?.label?.toLowerCase().includes(q) ||
            req.documentType?.code?.toLowerCase().includes(q) ||
            req.transactionType?.toLowerCase().includes(q)
        );
    });
});

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

    // Scroll smoothly to form
    const formElem = document.getElementById('requirement-form-card');
    if (formElem) {
        formElem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
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
    if (isBusy.value || !requirement.canDelete || !requirement.actions.deleteUrl) {
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
    switch (value) {
        case 'Mortuary':
            return 'bg-[#f5f3ff] text-[#7c3aed] border-[#ddd6fe]';
        case 'Renewal':
            return 'bg-[#ecfdf5] text-[#059669] border-[#a7f3d0]';
        case 'Reactivation':
            return 'bg-[#fffbeb] text-[#d97706] border-[#fde68a]';
        case 'Application':
        default:
            return 'bg-[#eff6ff] text-[#2563eb] border-[#bfdbfe]';
    }
}

function getTransactionCount(value) {
    const item = props.summary.transactionCounts?.find((t) => t.value === value);
    return item ? item.count : 0;
}
</script>

<template>
    <Head title="Document Requirements" />

    <AdminLayout title="Document Requirements">
        <div class="space-y-4">
            <!-- Hero Header -->
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-5 text-white shadow-lg shadow-[#003629]/15">
                <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/[0.04]"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-white/[0.03]"></div>
                <div class="pointer-events-none absolute right-24 top-6 h-24 w-24 rounded-full bg-white/[0.02]"></div>

                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-[#7ddfb8]/80">Membership Configuration</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-[-0.02em]">Document Requirements</h1>
                        </div>
                    </div>

                    <!-- Summary Pills -->
                    <div class="flex overflow-hidden rounded-xl border border-white/[0.12] bg-white/[0.06] backdrop-blur-sm">
                        <article class="px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Total</p>
                            <p class="mt-0.5 text-lg font-bold leading-none">{{ summary.total }}</p>
                        </article>
                        <article class="border-x border-white/[0.08] px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Active</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-[#7ddfb8]">{{ summary.active }}</p>
                        </article>
                        <article class="border-r border-white/[0.08] px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Required</p>
                            <p class="mt-0.5 text-lg font-bold leading-none text-[#fbbf24]">{{ summary.required }}</p>
                        </article>
                        <article class="px-4 py-2.5 text-center">
                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.1em] text-white/50">Filtered</p>
                            <p class="mt-0.5 text-lg font-bold leading-none">{{ visibleTransactionCount }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Flash alerts -->
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <section v-if="flashSuccess" class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-sm">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-emerald-600" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ flashSuccess }}</span>
                </section>
            </transition>
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <section v-if="flashError" class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50/90 px-4 py-3 text-xs font-semibold text-rose-800 shadow-sm">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-rose-600" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ flashError }}</span>
                </section>
            </transition>

            <!-- Transaction Scope Filter -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-[0.08em] text-[#64748b]">Transaction Scope</h2>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-[#dbe3dd] bg-white px-3 text-[0.68rem] font-semibold text-[#0f6b45] transition-all duration-200 hover:border-[#c2ccc5] hover:bg-[#f4f7f5] disabled:opacity-50"
                        :disabled="isBusy"
                        @click="refreshRecords"
                    >
                        <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" :class="{ 'animate-spin': navigating }" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-[#edf2ee] bg-[#fbfcfb] p-3">
                    <button
                        v-for="option in transactionTypeOptions"
                        :key="option.value"
                        type="button"
                        class="group inline-flex items-center gap-2 rounded-lg border px-3.5 py-1.5 text-xs font-bold transition-all duration-200"
                        :class="filters.transaction_type === option.value
                            ? 'border-[#014d3c] bg-[#014d3c] text-white shadow-sm'
                            : 'border-[#dbe4de] bg-white text-[#64748b] hover:border-[#c2ccc5] hover:bg-[#f4f7f5]'"
                        :disabled="isBusy"
                        @click="applyTransactionFilter(option.value)"
                    >
                        <span>{{ option.label }}</span>
                        <span
                            class="inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[0.62rem] font-bold"
                            :class="filters.transaction_type === option.value
                                ? 'bg-white/20 text-white'
                                : 'bg-[#eef2f0] text-[#64748b] group-hover:bg-[#e2e8e5]'"
                        >
                            {{ option.value === 'All' ? summary.total : getTransactionCount(option.value) }}
                        </span>
                    </button>
                </div>
            </section>

            <!-- Requirement Form Card -->
            <div id="requirement-form-card">
                <RequirementForm
                    v-if="mode === 'create'"
                    :key="formKey"
                    :form="createForm"
                    :transaction-type-options="transactionTypeOptions"
                    :document-type-options="documentTypeOptions"
                    heading="Add Document Requirement"
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
                    :heading="`Edit Requirement: ${editingRequirement?.documentType?.label || ''} (${editingRequirement?.transactionType || ''})`"
                    description=""
                    submit-label="Save Changes"
                    :disabled="isBusy"
                    @submit="submitEdit(editingRequirement)"
                    @cancel="cancelEdit"
                />
            </div>

            <!-- Configured Requirements Table -->
            <section class="overflow-hidden rounded-xl border border-[#dde4de] bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-[#edf2ee] px-5 py-3.5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-[0.8rem] font-bold text-[#0f172a]">Configured Requirements</h2>
                        <p class="mt-0.5 text-[0.68rem] text-[#64748b]">{{ filteredRequirements.length }} requirement{{ filteredRequirements.length === 1 ? '' : 's' }} listed</p>
                    </div>

                    <div class="relative w-full max-w-xs">
                        <span class="sr-only">Search requirements</span>
                        <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#94a3b8]" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.58 3.58a.75.75 0 1 1-1.06 1.06l-3.58-3.58A7 7 0 0 1 2 9Z" clip-rule="evenodd"/>
                        </svg>
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Filter by document name or code..."
                            class="h-9 w-full rounded-lg border border-[#dbe3dd] bg-[#f9fbfa] pl-9 pr-3 text-xs text-[#0f172a] outline-none transition-all duration-200 focus:border-[#014d3c] focus:bg-white focus:ring-2 focus:ring-[#014d3c]/10"
                        >
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#e8eeea] bg-gradient-to-b from-[#f8faf9] to-[#f3f6f4] text-left text-[0.6rem] font-bold uppercase tracking-[0.1em] text-[#64748b]">
                                <th class="px-5 py-3">Transaction</th>
                                <th class="px-5 py-3">Document Type</th>
                                <th class="px-5 py-3">Rule Type</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-center">Usage</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="requirement in filteredRequirements"
                                :key="requirement.id"
                                class="group border-b border-[#edf2ee] transition-all duration-150 hover:bg-[#f6faf8]"
                                :class="{ 'bg-emerald-50/40': editingRequirementId === requirement.id }"
                            >
                                <td class="px-5 py-3 align-middle">
                                    <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-[0.62rem] font-bold" :class="transactionTone(requirement.transactionType)">
                                        {{ requirement.transactionType }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 align-middle">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#f0faf5] text-[#0f6b45]">
                                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-[#0f172a]">{{ requirement.documentType.label }}</p>
                                            <p v-if="requirement.documentType.code" class="text-[0.6rem] font-medium text-[#94a3b8]">{{ requirement.documentType.code }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-3 align-middle">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[0.62rem] font-bold"
                                        :class="requirement.isRequired ? 'bg-[#fef3c7] text-[#b45309]' : 'bg-[#f1f5f9] text-[#64748b]'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="requirement.isRequired ? 'bg-[#f59e0b]' : 'bg-[#94a3b8]'"></span>
                                        {{ requirement.isRequired ? 'Mandatory' : 'Optional' }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 align-middle">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[0.62rem] font-bold"
                                        :class="requirement.isActive ? 'bg-[#dcfce7] text-[#15803d]' : 'bg-[#f1f5f9] text-[#64748b]'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="requirement.isActive ? 'bg-[#22c55e]' : 'bg-[#94a3b8]'"></span>
                                        {{ requirement.isActive ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 align-middle text-center">
                                    <span
                                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg px-2 text-[0.62rem] font-bold"
                                        :class="requirement.usageCount > 0 ? 'bg-[#f0fdf4] text-[#166534]' : 'bg-[#f8faf9] text-[#94a3b8]'"
                                        :title="`${requirement.usageCount} transaction record(s) currently link to this document requirement`"
                                    >
                                        {{ requirement.usageCount }} file{{ requirement.usageCount === 1 ? '' : 's' }}
                                    </span>
                                </td>

                                <td class="px-5 py-3 align-middle text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#475569] transition-all duration-150 hover:bg-[#f1f5f9] disabled:cursor-not-allowed disabled:opacity-50"
                                            :disabled="isBusy"
                                            title="Edit requirement"
                                            @click="startEdit(requirement)"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                            </svg>
                                        </button>

                                        <button
                                            v-if="requirement.canDelete"
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#dc2626] transition-all duration-150 hover:bg-[#fef2f2] disabled:cursor-not-allowed disabled:opacity-50"
                                            :disabled="isBusy"
                                            title="Delete requirement"
                                            @click="destroyRequirement(requirement)"
                                        >
                                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 10v6M14 10v6"/>
                                            </svg>
                                        </button>

                                        <span
                                            v-else
                                            class="inline-flex h-8 items-center justify-center rounded-lg px-2 text-[0.62rem] font-bold text-[#94a3b8]"
                                            :title="`Used by ${requirement.usageCount} transaction record(s); deactivate instead of deleting`"
                                        >
                                            In Use
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="filteredRequirements.length === 0">
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-xs flex-col items-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0faf5]">
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#86cfac]" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-[#334155]">{{ searchQuery ? 'No matching requirements' : 'No document requirements found' }}</p>
                                        <p class="mt-1 text-xs text-[#94a3b8]">{{ searchQuery ? `No results found for "${searchQuery}".` : 'Add a document requirement above to get started.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
