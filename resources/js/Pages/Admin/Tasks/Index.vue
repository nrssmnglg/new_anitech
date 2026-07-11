<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import QuickActionDialog from '../../../Components/Admin/QuickActionDialog.vue';
import { usePersistentObject } from '../../../Composables/usePersistentUiState';

const props = defineProps({
    tasks: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const quickActionForm = useForm({
    module: '',
    record: '',
    action: '',
    note: '',
});

const quickActionDialog = ref({
    open: false,
    taskKey: null,
    module: '',
    record: '',
    action: '',
    title: '',
    noteLabel: '',
    submitLabel: '',
});

const form = reactive({
    timing: props.filters.timing || '',
    priority: props.filters.priority || '',
    module: props.filters.module || '',
});

usePersistentObject('staff.tasks.filters', form);

function applyFilters() {
    router.get(props.urls.index, { ...form }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    form.timing = '';
    form.priority = '';
    form.module = '';
    applyFilters();
}

const summaryCards = computed(() => [
    { label: 'Total Tasks', value: props.summary.total, tone: 'bg-[#edf4ef] text-[#0f5b46]' },
    { label: 'Pending Today', value: props.summary.pendingToday, tone: 'bg-[#fff1d6] text-[#c57a00]' },
    { label: 'Overdue', value: props.summary.overdue, tone: 'bg-[#ffe4e7] text-[#cf3657]' },
    { label: 'High Priority', value: props.summary.highPriority, tone: 'bg-[#e3f2ff] text-[#0c7cc2]' },
]);

function moduleTone(module) {
    if (module === 'applications') return 'bg-[#dff6ea] text-[#0c7a58]';
    if (module === 'renewals') return 'bg-[#eef7d6] text-[#5f8418]';
    if (module === 'inquiries') return 'bg-[#e3f2ff] text-[#0c7cc2]';
    if (module === 'advisories') return 'bg-[#f3e8ff] text-[#7c3aed]';
    return 'bg-[#fff1d6] text-[#c57a00]';
}

function priorityTone(priority) {
    return priority === 'high_priority'
        ? 'bg-[#ffe4e7] text-[#cf3657]'
        : 'bg-[#eef1ef] text-[#5f6d67]';
}

function submitQuickAction(task, action, note = '') {
    quickActionForm.transform(() => ({
        module: task.module,
        record: task.recordKey,
        action,
        note,
    })).post(route('admin.tasks.quick-action'), {
        preserveScroll: true,
        onSuccess: closeQuickActionDialog,
    });
}

function openQuickActionDialog(task, action) {
    quickActionDialog.value = {
        open: true,
        taskKey: task.key,
        module: task.module,
        record: task.recordKey,
        action,
        title: action === 'request_correction' ? 'Request correction' : 'Forward to admin',
        noteLabel: action === 'request_correction' ? 'Correction notes' : 'Forwarding note',
        submitLabel: action === 'request_correction' ? 'Send correction request' : 'Forward now',
    };

    quickActionForm.module = task.module;
    quickActionForm.record = task.recordKey;
    quickActionForm.action = action;
    quickActionForm.note = '';
}

function closeQuickActionDialog() {
    quickActionDialog.value.open = false;
    quickActionForm.reset();
}

function submitDialogAction() {
    quickActionForm.post(route('admin.tasks.quick-action'), {
        preserveScroll: true,
        onSuccess: closeQuickActionDialog,
    });
}
</script>

<template>
    <Head title="My Tasks" />

    <AdminLayout title="My Tasks">
        <div class="space-y-6">
            <section class="rounded-[28px] bg-[linear-gradient(135deg,#003e32,#0f5b46_58%,#b7e29a)] px-6 py-7 text-white shadow-[0_18px_60px_rgba(0,54,41,0.18)]">
                <p class="text-[0.8rem] font-black uppercase tracking-[0.18em] text-white/72">Staff Queue</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">My Tasks</h1>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article v-for="card in summaryCards" :key="card.label" class="rounded-[1.35rem] border border-[#dbe4de] bg-white p-5 shadow-[0_16px_34px_rgba(15,91,70,0.06)]">
                    <span :class="['inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]', card.tone]">{{ card.label }}</span>
                    <p class="mt-4 text-[2.2rem] font-black leading-none text-[#0f172a]">{{ card.value }}</p>
                </article>
            </section>

            <section class="rounded-[24px] border border-[#dbe2de] bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                <div class="flex flex-col gap-4 border-b border-[#e4ebe7] pb-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Queue Filters</p>
                        <h2 class="mt-1 text-lg font-bold text-[#1a2420]">Staff records and task urgency</h2>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#d6dfda] px-4 py-3 text-sm font-bold text-[#5c6b65] transition hover:bg-[#f3f6f4]" @click="resetFilters">
                        Reset Filters
                    </button>
                </div>

                <form class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3" @submit.prevent="applyFilters">
                    <label class="space-y-2">
                        <span class="ml-1 text-[0.64rem] font-black uppercase tracking-[0.22em] text-[#78857f]">Timing</span>
                        <select v-model="form.timing" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                            <option v-for="option in filterOptions.timings" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="ml-1 text-[0.64rem] font-black uppercase tracking-[0.22em] text-[#78857f]">Priority</span>
                        <select v-model="form.priority" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                            <option v-for="option in filterOptions.priorities" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="ml-1 text-[0.64rem] font-black uppercase tracking-[0.22em] text-[#78857f]">Module</span>
                        <select v-model="form.module" class="w-full rounded-2xl border border-[#d7e0db] bg-[#f8faf9] px-4 py-3 text-sm text-[#1a2420] outline-none">
                            <option v-for="option in filterOptions.modules" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <div class="xl:col-span-3 flex justify-end">
                        <button type="submit" class="inline-flex h-[48px] items-center justify-center rounded-2xl bg-[#003629] px-5 text-sm font-extrabold text-white transition hover:bg-[#0d4637]">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-[28px] border border-[#dbe2de] bg-white shadow-[0_10px_32px_rgba(15,23,42,0.05)]">
                <div class="flex flex-col gap-2 border-b border-[#e4ebe7] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Task Queue</p>
                        <h2 class="mt-1 text-lg font-bold text-[#1a2420]">Staff work items</h2>
                    </div>
                    <p class="text-sm text-[#697772]">Showing {{ tasks.from || 0 }} to {{ tasks.to || 0 }} of {{ tasks.total }} tasks</p>
                </div>

                <div class="divide-y divide-[#edf2ef]">
                    <article v-for="task in tasks.data" :key="task.key" class="px-6 py-5 transition hover:bg-[#fbfdfc]">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span :class="['inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]', moduleTone(task.module)]">{{ task.moduleLabel }}</span>
                                    <span :class="['inline-flex rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.08em]', priorityTone(task.priority)]">{{ task.priorityLabel }}</span>
                                    <span class="inline-flex rounded-full bg-[#f5f7f6] px-3 py-1 text-xs font-black uppercase tracking-[0.08em] text-[#5f6d67]">{{ task.timingLabel }}</span>
                                </div>
                                <h3 class="mt-3 text-lg font-bold text-[#14202c]">{{ task.title }}</h3>
                                <p class="mt-1 text-sm text-[#42515b]">{{ task.subject }}</p>
                                <div class="mt-3 flex flex-wrap gap-4 text-sm text-[#65736d]">
                                    <span>{{ task.meta || 'No reference' }}</span>
                                    <span>{{ task.status }}</span>
                                    <span>{{ task.submittedAt || 'No timestamp' }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    v-if="task.quickActions?.canMarkComplete"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-[#cfe0d6] bg-[#eff7e8] px-4 py-3 text-sm font-extrabold text-[#486814] transition hover:bg-[#e5f1da]"
                                    @click="submitQuickAction(task, 'mark_complete')"
                                >
                                    Mark Complete
                                </button>
                                <button
                                    v-if="task.quickActions?.canForwardToAdmin"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-[#d6dfda] bg-white px-4 py-3 text-sm font-bold text-[#36554a] transition hover:bg-[#f7faf8]"
                                    @click="openQuickActionDialog(task, 'forward_to_admin')"
                                >
                                    Forward to Admin
                                </button>
                                <button
                                    v-if="task.quickActions?.canRequestCorrection"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-[#f2d4c8] bg-[#fff6f1] px-4 py-3 text-sm font-bold text-[#b85b34] transition hover:bg-[#fff0e7]"
                                    @click="openQuickActionDialog(task, 'request_correction')"
                                >
                                    Request Correction
                                </button>
                                <Link :href="task.url" class="inline-flex items-center justify-center rounded-2xl bg-[#003629] px-4 py-3 text-sm font-extrabold text-white transition hover:bg-[#0d4637]">
                                    Review
                                </Link>
                            </div>
                        </div>
                    </article>

                    <div v-if="tasks.data.length === 0" class="px-6 py-16 text-center">
                        <div class="mx-auto max-w-md rounded-[24px] border border-dashed border-[#dbe2de] bg-[#f8faf9] px-6 py-8">
                            <p class="text-base font-semibold text-[#1a2420]">No tasks matched the current filters.</p>
                            <p class="mt-2 text-sm text-[#6a7872]">Try clearing the current filters.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4 border-t border-[#e4ebe7] bg-[#f4f7f5] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <span class="text-sm text-[#65736d]">Page {{ tasks.current_page }} of {{ tasks.last_page }}</span>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in tasks.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex items-center rounded-xl border border-[#dbe2de] px-3 py-2 text-sm text-[#9aa6a1]" v-html="link.label" />
                            <Link
                                v-else
                                :href="link.url"
                                class="inline-flex items-center rounded-xl border px-3 py-2 text-sm font-bold transition"
                                :class="link.active ? 'border-[#003629] bg-[#003629] text-white' : 'border-[#dbe2de] text-[#5f6b66] hover:bg-white'"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </div>

        <QuickActionDialog
            :open="quickActionDialog.open"
            :title="quickActionDialog.title"
            :note-label="quickActionDialog.noteLabel"
            :submit-label="quickActionDialog.submitLabel"
            :note="quickActionForm.note"
            :placeholder="quickActionDialog.action === 'request_correction' ? 'Explain what must be fixed before review can continue.' : 'Add context for the admin escalation.'"
            :processing="quickActionForm.processing"
            :error="quickActionForm.errors.note"
            @close="closeQuickActionDialog"
            @submit="submitDialogAction"
            @update:note="quickActionForm.note = $event"
        />
    </AdminLayout>
</template>
