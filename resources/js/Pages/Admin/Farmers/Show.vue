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
        <div class="space-y-5 bg-[radial-gradient(circle_at_top_left,_rgba(186,238,217,0.18),_transparent_24%),radial-gradient(circle_at_bottom_right,_rgba(165,213,119,0.12),_transparent_22%)]">
            <ProfileHero :farmer="farmer" />

            <ProfileSummaryGrid :farmer="farmer" :summary="summary" />

            <section class="rounded-[18px] border border-[#dbe2de] bg-white p-3.5 shadow-[0_8px_20px_rgba(15,23,42,0.05)]">
                <div class="grid gap-3 md:grid-cols-[minmax(0,1.3fr)_minmax(0,0.7fr)]">
                    <div>
                        <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Last Updated By</p>
                        <p class="mt-1.5 text-lg font-black text-[#14202c]">{{ farmer.accountability?.lastUpdatedBy || 'No staff update recorded' }}</p>
                        <p class="mt-1 text-xs text-[#6c7772]">{{ farmer.accountability?.lastUpdatedAt || 'Not recorded' }}</p>
                    </div>
                    <div class="grid gap-2.5 sm:grid-cols-2 md:grid-cols-1">
                        <div>
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Barangay</p>
                            <p class="mt-1 text-sm font-bold text-[#14202c]">{{ farmer.barangay?.name || 'Unassigned' }}</p>
                        </div>
                        <div>
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Association</p>
                            <p class="mt-1 text-sm font-bold text-[#14202c]">{{ farmer.association?.name || 'No association' }}</p>
                        </div>
                    </div>
                </div>
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

            <section v-if="recoverySnapshots.length" class="rounded-[22px] border border-[#dbe2de] bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)]">
                <div class="flex flex-col gap-1.5 border-b border-[#e4ebe7] pb-3">
                    <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-[#7b8782]">Recovery</p>
                    <h2 class="text-base font-bold text-[#1a2420]">Available restore points for this farmer</h2>
                </div>

                <div class="mt-4 space-y-2.5">
                    <div v-for="snapshot in recoverySnapshots" :key="snapshot.key" class="flex flex-col gap-2.5 rounded-[18px] border border-[#dbe2de] bg-[#f8faf9] p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-[#1b2320]">{{ snapshot.label }}</p>
                            <p class="mt-1 text-xs text-[#6a7872]">{{ snapshot.createdAt || 'Not recorded' }}</p>
                        </div>
                        <button type="button" class="inline-flex items-center justify-center rounded-[16px] border border-[#c8d8cf] bg-white px-3.5 py-2.5 text-xs font-extrabold text-[#003629] transition hover:bg-[#f6fbf8]" @click="recoverRecord(snapshot.key)">
                            Recover Record
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
