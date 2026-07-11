<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    advisory: { type: Object, required: true },
    urls: { type: Object, required: true },
});

function publish() {
    if (!props.advisory.actions.publish) {
        return;
    }

    if (!window.confirm(`Publish "${props.advisory.title}" now?`)) {
        return;
    }

    router.post(props.advisory.actions.publish);
}

function unpublish() {
    if (!props.advisory.actions.unpublish) {
        return;
    }

    if (!window.confirm(`Move "${props.advisory.title}" back to draft?`)) {
        return;
    }

    router.post(props.advisory.actions.unpublish);
}

function removeAttachment(attachment) {
    if (!window.confirm(`Remove ${attachment.name}?`)) {
        return;
    }

    router.delete(attachment.deleteUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="advisory.title" />

    <AdminLayout title="Advisory Record">
        <div class="space-y-8">
            <section class="space-y-4">
                <nav class="flex flex-wrap items-center gap-2 text-[0.72rem] font-bold uppercase tracking-[0.14em] text-on-surface-variant">
                    <Link :href="urls.index" class="transition hover:text-primary">Advisories</Link>
                    <span>/</span>
                    <span class="text-primary">{{ advisory.title }}</span>
                </nav>

                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="text-4xl font-black tracking-[-0.04em] text-primary sm:text-5xl">{{ advisory.title }}</h1>
                        <p class="mt-2 text-lg text-on-surface-variant">Advisory Record</p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link :href="urls.index" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#0f5b46]/15 bg-white px-5 py-3 text-sm font-bold text-[#0f5b46] transition hover:bg-[#eef5f1]">
                            Back to Advisories
                        </Link>
                        <Link :href="urls.edit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#0f5b46] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-[#0f5b46]/20 transition hover:bg-[#0b4938]">
                            Edit Advisory
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 md:grid-cols-3">
                <article class="rounded-3xl border border-[#0f5b46]/12 bg-white p-6 shadow-[0_18px_40px_rgba(0,54,41,0.08)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Status</p>
                    <h2 class="mt-3 text-2xl font-black text-[#1b4337]">{{ advisory.status }}</h2>
                    <p class="mt-3 text-sm text-on-surface-variant">{{ advisory.publishedAt ? `Published ${advisory.publishedAt}` : 'Not yet published' }}</p>
                </article>
                <article class="rounded-3xl border border-[#0f5b46]/12 bg-white p-6 shadow-[0_18px_40px_rgba(0,54,41,0.08)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Audience</p>
                    <h2 class="mt-3 text-2xl font-black text-[#1b4337]">{{ advisory.audienceLabel }}</h2>
                    <p class="mt-3 text-sm text-on-surface-variant">Created {{ advisory.createdAt }}</p>
                </article>
                <article class="rounded-3xl border border-[#0f5b46]/12 bg-white p-6 shadow-[0_18px_40px_rgba(0,54,41,0.08)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-on-surface-variant">Publisher</p>
                    <h2 class="mt-3 text-2xl font-black text-[#1b4337]">{{ advisory.publisher || 'Not assigned' }}</h2>
                    <p class="mt-3 text-sm text-on-surface-variant">{{ advisory.attachments.length }} attachment{{ advisory.attachments.length === 1 ? '' : 's' }}</p>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.35fr_0.85fr]">
                <section class="overflow-hidden rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_20px_45px_rgba(0,54,41,0.08)]">
                    <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-4">
                        <h2 class="text-xl font-black text-primary">Message</h2>
                    </div>
                    <div class="p-6">
                        <div class="whitespace-pre-wrap text-sm leading-8 text-on-surface">{{ advisory.content }}</div>
                    </div>
                </section>

                <section class="space-y-6">
                    <section class="overflow-hidden rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_20px_45px_rgba(0,54,41,0.08)]">
                        <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-4">
                            <h2 class="text-xl font-black text-primary">Publishing Actions</h2>
                        </div>
                        <div class="space-y-3 p-6">
                            <button v-if="advisory.actions.publish" type="button" class="w-full rounded-2xl bg-[#0f5b46] px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#0f5b46]/20 transition hover:bg-[#0b4938]" @click="publish">
                                Publish Advisory
                            </button>
                            <button v-if="advisory.actions.unpublish" type="button" class="w-full rounded-2xl border border-[#a83d2a]/20 bg-[#fff7f5] px-5 py-3 text-sm font-extrabold text-[#a83d2a] transition hover:bg-[#ffefe9]" @click="unpublish">
                                Move Back to Draft
                            </button>
                            <p class="text-sm text-on-surface-variant">Only published advisories become visible in the farmer alerts page.</p>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-3xl border border-[#0f5b46]/12 bg-white shadow-[0_20px_45px_rgba(0,54,41,0.08)]">
                        <div class="border-b border-[#0f5b46]/10 bg-[#f5faf7] px-6 py-4">
                            <h2 class="text-xl font-black text-primary">Attachments</h2>
                        </div>
                        <div class="space-y-3 p-6">
                            <article v-for="attachment in advisory.attachments" :key="attachment.id" class="rounded-2xl border border-[#0f5b46]/10 bg-[#f8fbf9] p-4">
                                <p class="font-bold text-primary">{{ attachment.name }}</p>
                                <p class="mt-1 text-xs text-on-surface-variant">{{ attachment.uploadedAt || 'No upload timestamp' }}</p>
                                <div class="mt-3 flex items-center gap-4">
                                    <a :href="attachment.downloadUrl" target="_blank" rel="noopener" class="font-semibold text-[#2f7d5e] transition hover:underline">Open</a>
                                    <button type="button" class="font-semibold text-[#a83d2a] transition hover:underline" @click="removeAttachment(attachment)">
                                        Remove
                                    </button>
                                </div>
                            </article>
                            <p v-if="advisory.attachments.length === 0" class="text-sm text-on-surface-variant">No files attached to this advisory.</p>
                        </div>
                    </section>
                </section>
            </section>
        </div>
    </AdminLayout>
</template>
