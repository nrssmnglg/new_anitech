<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import ProfileHero from '../../../Components/Admin/Farmers/ProfileHero.vue';
import ProfileIdentityPanel from '../../../Components/Admin/Farmers/ProfileIdentityPanel.vue';
import ProfileSidebar from '../../../Components/Admin/Farmers/ProfileSidebar.vue';
import ProfileTimeline from '../../../Components/Admin/Farmers/ProfileTimeline.vue';

defineProps({
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
</script>

<template>
    <Head :title="`${farmer.fullName} - Farmer Record`" />

    <AdminLayout title="Farmer Record">
        <div class="space-y-4">
            <ProfileHero :farmer="farmer" :urls="urls" :renewal="renewal" />

            <section class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
                <ProfileIdentityPanel :farmer="farmer" :summary="summary" />

                <ProfileSidebar
                    :farmer="farmer"
                    :activities="timeline.slice(0, 4)"
                    :latest-application="latestApplication"
                    :latest-payment="latestPayment"
                    :latest-ledger="latestLedger"
                    :urls="urls"
                    :permissions="permissions"
                    :renewal="renewal"
                />
            </section>

            <ProfileTimeline :timeline="timeline" />
        </div>
    </AdminLayout>
</template>
