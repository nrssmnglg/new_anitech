<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import InternalNotesPanel from '../../../Components/Admin/InternalNotesPanel.vue';
import ProfileHero from '../../../Components/Admin/Farmers/ProfileHero.vue';
import ProfileHistoryColumns from '../../../Components/Admin/Farmers/ProfileHistoryColumns.vue';
import ProfileIdentityPanel from '../../../Components/Admin/Farmers/ProfileIdentityPanel.vue';
import ProfileSidebar from '../../../Components/Admin/Farmers/ProfileSidebar.vue';
import ProfileSummaryGrid from '../../../Components/Admin/Farmers/ProfileSummaryGrid.vue';
import ProfileTimeline from '../../../Components/Admin/Farmers/ProfileTimeline.vue';

const props = defineProps({
    farmer: { type: Object, required: true },
    summary: { type: Object, required: true },
    latestApplication: { type: Object, default: null },
    latestAssessment: { type: Object, default: null },
    latestPayment: { type: Object, default: null },
    latestLedger: { type: Object, default: null },
    internalNotes: { type: Array, required: true },
    recentApplications: { type: Array, required: true },
    recentAssessments: { type: Array, required: true },
    recentLedgers: { type: Array, required: true },
    timeline: { type: Array, required: true },
    urls: { type: Object, required: true },
    permissions: { type: Object, required: true },
    renewal: { type: Object, required: true },
    recoverySnapshots: { type: Array, required: true },
});

function recoverRecord(snapshotKey) {
    if (!snapshotKey || !props.urls.recover || !window.confirm('Restore this farmer record from the selected restore point?')) {
        return;
    }

    router.post(props.urls.recover, {
        snapshot_key: snapshotKey,
    });
}
</script>

<template>
    <Head :title="farmer.fullName" />

    <AdminLayout title="Farmer Record">
        <div class="space-y-7 bg-[radial-gradient(circle_at_top_left,_rgba(186,238,217,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(165,213,119,0.12),_transparent_22%)]">
            <ProfileHero :farmer="farmer" />

            <ProfileSummaryGrid :farmer="farmer" :summary="summary" />

            <section class="grid gap-4 md:grid-cols-2">
                <article class="rounded-[22px] border border-[#dbe2de] bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Last Updated By</p>
                    <p class="mt-2 text-lg font-bold text-[#14202c]">{{ farmer.accountability?.lastUpdatedBy || 'System' }}</p>
                    <p class="mt-1 text-sm text-[#6c7772]">{{ farmer.accountability?.lastUpdatedAt || 'Not recorded' }}</p>
                </article>
                <article class="rounded-[22px] border border-[#dbe2de] bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)]">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Reviewed By</p>
                    <p class="mt-2 text-lg font-bold text-[#14202c]">{{ farmer.accountability?.reviewedBy || 'No formal review' }}</p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
                <ProfileIdentityPanel :farmer="farmer" />

                <ProfileSidebar
                    :farmer="farmer"
                    :latest-application="latestApplication"
                    :latest-payment="latestPayment"
                    :latest-ledger="latestLedger"
                    :urls="urls"
                    :permissions="permissions"
                    :renewal="renewal"
                />
            </section>

            <InternalNotesPanel :notes="internalNotes" :submit-url="urls.storeInternalNote" title="Farmer Internal Notes" />

            <ProfileHistoryColumns
                :recent-applications="recentApplications"
                :recent-assessments="recentAssessments"
                :recent-ledgers="recentLedgers"
            />

            <ProfileTimeline :timeline="timeline" />

            <section v-if="recoverySnapshots.length" class="rounded-[28px] border border-[#dbe2de] bg-white p-6 shadow-[0_10px_32px_rgba(15,23,42,0.05)]">
                <div class="flex flex-col gap-2 border-b border-[#e4ebe7] pb-4">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Recovery</p>
                    <h2 class="text-lg font-bold text-[#1a2420]">Available restore points for this farmer</h2>
                </div>

                <div class="mt-5 space-y-3">
                    <div v-for="snapshot in recoverySnapshots" :key="snapshot.key" class="flex flex-col gap-3 rounded-[22px] border border-[#dbe2de] bg-[#f8faf9] p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-[#1b2320]">{{ snapshot.label }}</p>
                            <p class="mt-1 text-sm text-[#6a7872]">{{ snapshot.createdAt || 'Not recorded' }}</p>
                        </div>
                        <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-[#c8d8cf] bg-white px-4 py-3 text-sm font-extrabold text-[#003629] transition hover:bg-[#f6fbf8]" @click="recoverRecord(snapshot.key)">
                            Recover Record
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
