<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    summary: { type: Object, required: true },
    snapshots: { type: Array, required: true },
    urls: { type: Object, required: true },
});

function createDatabaseBackup() {
    router.post(props.urls.databaseBackup, {}, {
        preserveScroll: true,
    });
}

function createFarmerSnapshot() {
    router.post(props.urls.farmerSnapshot, {}, {
        preserveScroll: true,
    });
}

function formatBytes(value) {
    const size = Number(value || 0);

    if (size <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let index = 0;
    let current = size;

    while (current >= 1024 && index < units.length - 1) {
        current /= 1024;
        index += 1;
    }

    return `${current.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}

function typeLabel(type) {
    if (type === 'database') return 'Database Backup';
    if (type === 'export-snapshots') return 'Export Snapshot';
    if (type === 'restore-points') return 'Restore Point';

    return type;
}
</script>

<template>
    <Head title="Backup & Recovery" />

    <AdminLayout title="Backup & Recovery">
        <div class="space-y-7">
            <section class="rounded-[28px] border border-[#d8e2dc] bg-[radial-gradient(circle_at_top_right,_rgba(186,238,217,0.95),_rgba(248,250,249,0.95)_48%,_#f8faf9_100%)] px-6 py-7 shadow-[0_18px_45px_rgba(15,23,42,0.07)]">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[0.74rem] font-black uppercase tracking-[0.3em] text-[#5a7168]">Operational Safety</p>
                        <h1 class="mt-3 text-4xl font-black tracking-[-0.04em] text-[#003629]">Backup & Recovery</h1>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="inline-flex items-center justify-center rounded-2xl bg-[#003629] px-6 py-4 text-sm font-extrabold text-white shadow-[0_18px_35px_rgba(0,54,41,0.18)] transition hover:bg-[#0d4637]" @click="createDatabaseBackup">
                            Create Database Backup
                        </button>
                        <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white/85 px-6 py-4 text-sm font-extrabold text-[#003629] transition hover:bg-white" @click="createFarmerSnapshot">
                            Create Farmer Snapshot
                        </button>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[24px] border border-[#dbe2de] bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#76847d]">Total Snapshots</p>
                    <p class="mt-2 text-4xl font-black text-[#003629]">{{ summary.total }}</p>
                </article>
                <article class="rounded-[24px] border border-[#dbe2de] bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#76847d]">Database Backups</p>
                    <p class="mt-2 text-4xl font-black text-[#003629]">{{ summary.databaseBackups }}</p>
                </article>
                <article class="rounded-[24px] border border-[#dbe2de] bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#76847d]">Export Snapshots</p>
                    <p class="mt-2 text-4xl font-black text-[#003629]">{{ summary.exportSnapshots }}</p>
                </article>
                <article class="rounded-[24px] border border-[#dbe2de] bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#76847d]">Restore Points</p>
                    <p class="mt-2 text-4xl font-black text-[#003629]">{{ summary.restorePoints }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-[28px] border border-[#dbe2de] bg-white shadow-[0_10px_32px_rgba(15,23,42,0.05)]">
                <div class="border-b border-[#e4ebe7] px-6 py-5">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Snapshot Log</p>
                    <h2 class="mt-1 text-lg font-bold text-[#1a2420]">Stored backup files and restore points</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#e5ece8] text-sm">
                        <thead class="bg-[#f4f7f5]">
                            <tr class="text-left text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#7a8781]">
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4">Created</th>
                                <th class="px-6 py-4">Requested By</th>
                                <th class="px-6 py-4">File</th>
                                <th class="px-6 py-4">Size</th>
                                <th class="px-6 py-4">Checksum</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ef] bg-white">
                            <tr v-for="snapshot in snapshots" :key="snapshot.key">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-[#1b2320]">{{ typeLabel(snapshot.type) }}</p>
                                    <p class="mt-1 text-xs text-[#7b8782]">{{ snapshot.key }}</p>
                                </td>
                                <td class="px-6 py-4 text-[#5f6b66]">{{ snapshot.createdAt || 'Not recorded' }}</td>
                                <td class="px-6 py-4 text-[#5f6b66]">{{ snapshot.requestedBy }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-[#1b2320]">{{ snapshot.fileName }}</p>
                                    <div v-if="Object.keys(snapshot.metadata || {}).length" class="mt-2 space-y-1 text-xs text-[#7b8782]">
                                        <p v-for="(value, key) in snapshot.metadata" :key="`${snapshot.key}-${key}`">{{ key }}: {{ Array.isArray(value) ? value.join(', ') : value }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-[#5f6b66]">{{ formatBytes(snapshot.fileSize) }}</td>
                                <td class="px-6 py-4 text-xs text-[#5f6b66]">{{ snapshot.checksum }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a v-if="snapshot.exists" :href="snapshot.downloadUrl" class="inline-flex rounded-xl border border-[#d8e2dc] px-4 py-2 font-bold text-[#003629] transition hover:bg-[#f6fbf8]">
                                        Download
                                    </a>
                                    <span v-else class="text-xs font-bold uppercase tracking-[0.16em] text-[#b45309]">Missing file</span>
                                </td>
                            </tr>
                            <tr v-if="snapshots.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <p class="text-base font-semibold text-[#1a2420]">No backup files created yet.</p>
                                    <p class="mt-2 text-sm text-[#6a7872]">Create your first database backup to start the recovery log.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
