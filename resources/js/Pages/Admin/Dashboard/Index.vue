<script setup>
import { Head } from '@inertiajs/vue3';
import AdminDashboardApp from '../../../Components/Admin/Dashboard/AdminDashboardApp.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout title="Dashboard">
        <AdminDashboardApp :dashboard="dashboard" />
    </AdminLayout>
</template>
