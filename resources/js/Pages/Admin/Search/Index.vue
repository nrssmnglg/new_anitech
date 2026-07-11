<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({
    search: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Search" />

    <AdminLayout title="Search">
        <div class="space-y-6">
            <section class="rounded-[28px] border border-[#dbe3de] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                <p class="text-[0.72rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">Admin Search</p>
                <h1 class="mt-3 text-3xl font-black tracking-[-0.04em] text-[#143c32]">
                    <template v-if="search.hasQuery">Results for "{{ search.term }}"</template>
                    <template v-else>Search the admin modules</template>
                </h1>
                <p class="mt-3 text-sm text-[#5f6f67]">
                    <template v-if="search.hasQuery">{{ search.totalResults }} result{{ search.totalResults === 1 ? '' : 's' }} across the available modules.</template>
                    <template v-else>Use the navbar search to look up farmers, applications, renewals, inquiries, advisories, and more.</template>
                </p>
            </section>

            <section v-if="search.hasQuery && search.totalResults === 0" class="rounded-[28px] border border-dashed border-[#d7dfda] bg-[#f8faf9] p-8 text-center">
                <h2 class="text-xl font-bold text-[#143c32]">No matches found</h2>
                <p class="mt-2 text-sm text-[#5f6f67]">Try a farmer code, name, email address, application number, or advisory title.</p>
            </section>

            <div v-for="group in search.groups" :key="group.title" class="rounded-[28px] border border-[#dbe3de] bg-white p-6 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-bold text-[#143c32]">{{ group.title }}</h2>
                            <span class="inline-flex rounded-full bg-[#eef4f1] px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-[#446258]">
                                {{ group.count }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-[#5f6f67]">{{ group.description }}</p>
                    </div>
                    <Link :href="group.viewAllUrl" class="text-sm font-bold text-[#0f5b46] transition hover:text-[#0b4636]">
                        View module
                    </Link>
                </div>

                <div v-if="group.items.length" class="mt-5 grid gap-3">
                    <Link
                        v-for="item in group.items"
                        :key="`${group.title}-${item.id}`"
                        :href="item.url"
                        class="block rounded-[20px] border border-[#e4ebe7] bg-[#fbfcfb] px-5 py-4 transition hover:border-[#cdd8d3] hover:bg-white hover:shadow-[0_10px_26px_rgba(15,23,42,0.06)]"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-[#12202b]">{{ item.title }}</p>
                                <p class="mt-1 text-sm text-[#5f6f67]">{{ item.subtitle || '-' }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-[#0f5b46]">Open</span>
                        </div>
                    </Link>
                </div>

                <div v-else-if="search.hasQuery" class="mt-5 rounded-[20px] border border-dashed border-[#d7dfda] bg-[#f8faf9] px-4 py-6 text-sm text-[#6d7b74]">
                    No {{ group.title.toLowerCase() }} matched this search.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
