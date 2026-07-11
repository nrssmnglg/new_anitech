<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
    activeFilterCount: { type: Number, required: true },
    moduleOptions: { type: Array, required: true },
    eventOptions: { type: Array, required: true },
    actorOptions: { type: Array, required: true },
    summary: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const form = reactive({
    module: props.filters.module ?? '',
    event: props.filters.event ?? '',
    actor_user_id: props.filters.actor_user_id ? String(props.filters.actor_user_id) : '',
    date: props.filters.date ?? '',
    search: props.filters.search ?? '',
});

const applying = ref(false);

function applyFilters() {
    if (applying.value) {
        return;
    }

    applying.value = true;

    router.get(props.urls.index, {
        module: form.module || undefined,
        event: form.event || undefined,
        actor_user_id: form.actor_user_id || undefined,
        date: form.date || undefined,
        search: form.search || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            applying.value = false;
        },
    });
}

function resetFilters() {
    if (applying.value) {
        return;
    }

    form.module = '';
    form.event = '';
    form.actor_user_id = '';
    form.date = '';
    form.search = '';
    applyFilters();
}
</script>

<template>
    <Head title="Audit Trail" />

    <AdminLayout title="Audit Trail">
        <div class="space-y-6">
            <section class="rounded-3xl border border-[#0f5b46]/12 bg-white p-6 shadow-[0_18px_40px_rgba(0,54,41,0.06)]">
                <div class="mb-5 flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black text-primary">Filter Activity</h2>
                        </div>
                    <span class="rounded-full bg-[#f1f5f3] px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-on-surface-variant">
                        {{ activeFilterCount }} Active Filters
                    </span>
                </div>

                <div class="grid gap-4 xl:grid-cols-5">
                    <label class="space-y-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Module</span>
                        <select v-model="form.module" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15">
                            <option value="">All modules</option>
                            <option v-for="option in moduleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Event</span>
                        <select v-model="form.event" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15">
                            <option value="">All events</option>
                            <option v-for="option in eventOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">User</span>
                        <select v-model="form.actor_user_id" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15">
                            <option value="">All staff and admins</option>
                            <option v-for="option in actorOptions" :key="option.value" :value="String(option.value)">{{ option.label }}</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Date</span>
                        <input v-model="form.date" type="date" class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15">
                    </label>

                    <label class="space-y-2">
                        <span class="ml-1 text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Search</span>
                        <input v-model="form.search" type="text" placeholder="Description, actor, module..." class="w-full rounded-2xl border border-[#0f5b46]/15 bg-white px-4 py-3 text-sm text-on-surface outline-none transition focus:border-[#0f5b46] focus:ring-2 focus:ring-[#0f5b46]/15">
                    </label>
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl bg-[#0f5b46] px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#0f5b46]/20 transition hover:bg-[#0b4938] disabled:cursor-not-allowed disabled:opacity-60" :disabled="applying" @click="applyFilters">
                        {{ applying ? 'Applying...' : 'Apply Filters' }}
                    </button>
                    <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#0f5b46]/15 bg-white px-5 py-3 text-sm font-semibold text-[#0f5b46] transition hover:bg-[#eef5f1] disabled:cursor-not-allowed disabled:opacity-60" :disabled="applying" @click="resetFilters">
                        Reset
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_20px_45px_rgba(0,54,41,0.08)]">
                <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-4">
                    <h2 class="text-xl font-black text-primary">Activity Feed</h2>
                </div>

                <div class="divide-y divide-[#0f5b46]/8">
                    <component
                        :is="log.recordUrl ? Link : 'article'"
                        v-for="log in logs.data"
                        :key="log.id"
                        :href="log.recordUrl || undefined"
                        class="block space-y-4 px-6 py-5 transition"
                        :class="log.recordUrl ? 'cursor-pointer hover:bg-[#f8fbf9]' : ''"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-2">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-secondary">{{ log.event.label }}</p>
                                <h3 class="text-lg font-black text-[#163a31]">{{ log.description }}</h3>
                                <div class="flex flex-wrap gap-3 text-sm text-on-surface-variant">
                                    <span><strong class="text-[#1b4337]">{{ log.actorName }}</strong></span>
                                    <span>{{ log.createdAt }}</span>
                                    <span v-if="log.actorRole">{{ log.actorRole }}</span>
                                </div>
                                <p v-if="log.activity.label" class="text-sm font-semibold text-[#0f5b46]">
                                    {{ log.activity.label }}
                                    <span v-if="log.actorName">by {{ log.actorName }}</span>
                                </p>
                                <p v-if="log.recordUrl" class="text-xs font-bold uppercase tracking-[0.18em] text-[#0f5b46]">
                                    View record
                                </p>
                            </div>
                            <span class="rounded-full bg-[#eaf4ee] px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-[#0f5b46]">
                                {{ log.module.label }}
                            </span>
                        </div>

                        <p v-if="log.subjectLabel" class="text-sm text-on-surface-variant">
                            Record: <span class="font-semibold text-[#1b4337]">{{ log.subjectLabel }}</span>
                        </p>

                        <div v-if="log.changeSummary.length" class="grid gap-3 md:grid-cols-3">
                            <div v-for="change in log.changeSummary" :key="`${log.id}-${change.field}`" class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">{{ change.field }}</p>
                                <p class="mt-2 text-sm text-[#7b8a83]">{{ change.from }}</p>
                                <p class="mt-1 text-sm font-semibold text-[#0f5b46]">to {{ change.to }}</p>
                            </div>
                        </div>

                        <div v-if="log.metadata.length" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div v-for="item in log.metadata" :key="`${log.id}-${item.key}`" class="rounded-2xl border border-[#0f5b46]/10 bg-white p-4">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">{{ item.key }}</p>
                                <p class="mt-2 text-sm font-semibold text-[#1b4337]">{{ item.value }}</p>
                            </div>
                        </div>
                    </component>

                    <div v-if="logs.data.length === 0" class="px-6 py-16 text-center">
                        <h3 class="text-lg font-black text-primary">No activity matched your filters.</h3>
                        <p class="mt-2 text-sm text-on-surface-variant">Try clearing a filter or using a broader search term.</p>
                    </div>
                </div>

                <div class="flex flex-col gap-4 border-t border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-on-surface-variant">Showing {{ logs.from || 0 }}-{{ logs.to || 0 }} of {{ logs.total }} entries</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in logs.links" :key="link.label">
                            <span v-if="!link.url" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-[#0f5b46]/10 px-3 text-sm text-[#9aa6a1]" v-html="link.label" />
                            <Link
                                v-else
                                :href="link.url"
                                class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-bold transition"
                                :class="link.active ? 'border-[#0f5b46] bg-[#0f5b46] text-white shadow-lg shadow-[#0f5b46]/20' : 'border-[#0f5b46]/10 bg-white text-on-surface-variant hover:bg-[#eef5f1]'"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
