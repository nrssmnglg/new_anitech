<script setup>
import { Head } from '@inertiajs/vue3';
import AdminAuthShell from '../../../Components/Admin/Auth/AdminAuthShell.vue';
import OfficeLoginForm from '../../../Components/Admin/Auth/OfficeLoginForm.vue';
import { useOfficeLogin } from '../../../Composables/Admin/Auth/useOfficeLogin';

const props = defineProps({
    forgotPasswordUrl: {
        type: String,
        required: true,
    },
    loginUrl: {
        type: String,
        required: true,
    },
    logoUrl: {
        type: String,
        required: true,
    },
});

const { errorList, flashStatus, form, showPassword, submit } = useOfficeLogin(props.loginUrl);
</script>

<template>
    <Head title="Office Portal Login" />

    <AdminAuthShell
        :hero-title="'Municipal Agriculture\nOffice Portal'"
        hero-copy="Log in to support agricultural services through efficient management of farmers, records, and staff operations."
        :logo-url="logoUrl"
    >
        <OfficeLoginForm
            :error-list="errorList"
            :flash-status="flashStatus"
            :forgot-password-url="forgotPasswordUrl"
            :form="form"
            :logo-url="logoUrl"
            :show-password="showPassword"
            @submit="submit"
            @toggle-password="showPassword = !showPassword"
        />
    </AdminAuthShell>
</template>
