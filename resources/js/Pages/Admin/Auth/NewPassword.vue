<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminAuthShell from '../../../Components/Admin/Auth/AdminAuthShell.vue';

const props = defineProps({
    email: { type: String, required: true },
    submitUrl: { type: String, required: true },
    logoUrl: { type: String, required: true },
});

const page = usePage();
const form = useForm({
    email: props.email,
    password: '',
    password_confirmation: '',
});

const errorList = computed(() => Object.values(page.props.errors ?? {}).flat().filter(Boolean));

function submit() {
    form.post(props.submitUrl);
}
</script>

<template>
    <Head title="Set New Password" />

    <AdminAuthShell
        :hero-title="'Create\nNew Password'"
        hero-copy="Your OTP is already verified. Set a new password to regain access to the office portal."
        :logo-url="logoUrl"
    >
        <div class="relative w-full max-w-md rounded-2xl border border-black/5 bg-white p-8 shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
            <div class="mb-8 text-center">
                <div class="mx-auto flex justify-center"><img :src="logoUrl" alt="AniTech logo" class="h-20 w-auto object-contain"></div>
                <h1 class="mt-5 text-2xl font-extrabold text-stone-800">Choose New Password</h1>
                <p class="mt-2 text-sm text-stone-500">Your OTP is confirmed. Set a new password for your office account.</p>
            </div>

            <div v-if="errorList.length" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    <li v-for="error in errorList" :key="error">{{ error }}</li>
                </ul>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <input v-model="form.email" type="hidden">

                <div>
                    <label class="mb-2 block text-sm font-semibold text-stone-700" for="password">New password</label>
                    <input id="password" v-model="form.password" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 outline-none transition focus:border-[#1B4D3E] focus:bg-white focus:ring-4 focus:ring-[#1B4D3E]/10">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-stone-700" for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 outline-none transition focus:border-[#1B4D3E] focus:bg-white focus:ring-4 focus:ring-[#1B4D3E]/10">
                </div>

                <button type="submit" :disabled="form.processing" class="w-full rounded-xl bg-[#1B4D3E] py-3.5 text-sm font-semibold text-white shadow-[0_4px_15px_rgba(27,77,62,0.30)] transition hover:-translate-y-[1px] hover:bg-[#2A6B54] disabled:cursor-not-allowed disabled:opacity-70">
                    {{ form.processing ? 'RESETTING...' : 'RESET PASSWORD' }}
                </button>
            </form>
        </div>
    </AdminAuthShell>
</template>
