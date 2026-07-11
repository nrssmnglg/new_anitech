<script setup>
import { Head } from '@inertiajs/vue3';
import StaffDashboardApp from '../../../Components/Admin/Dashboard/StaffDashboardApp.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Staff Dashboard" />

    <AdminLayout title="Dashboard">
        <StaffDashboardApp :dashboard="dashboard" />
    </AdminLayout>
</template>
