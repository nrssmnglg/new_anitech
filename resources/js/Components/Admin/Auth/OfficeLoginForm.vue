<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    errorList: {
        type: Array,
        required: true,
    },
    flashStatus: {
        type: String,
        default: null,
    },
    forgotPasswordUrl: {
        type: String,
        required: true,
    },
    logoUrl: {
        type: String,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    showPassword: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['submit', 'toggle-password']);
</script>

<template>
    <div class="relative w-full max-w-md rounded-2xl border border-black/5 bg-white p-8 shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
        <div class="mb-8 text-center">
            <div class="mx-auto flex justify-center">
                <img :src="logoUrl" alt="AniTech logo" class="h-20 w-auto object-contain">
            </div>
            <p class="mt-4 text-sm font-semibold uppercase tracking-[0.25em] text-stone-500">Rural-Base Organization Portal Login</p>
        </div>

        <div
            v-if="flashStatus"
            class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700"
        >
            {{ flashStatus }}
        </div>

        <div
            v-if="errorList.length"
            class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
            <ul class="list-disc space-y-1 pl-5">
                <li v-for="error in errorList" :key="error">{{ error }}</li>
            </ul>
        </div>

        <form class="space-y-5" @submit.prevent="emit('submit')">
            <div class="relative">
                <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-stone-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6h16v12H4V6Z" stroke="currentColor" stroke-width="2" />
                        <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </span>

                <input
                    v-model="form.email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    required
                    autofocus
                    placeholder="Email address"
                    class="w-full rounded-xl border border-stone-200 bg-stone-50 py-3.5 pr-4 pl-12 text-sm text-stone-900 placeholder:text-stone-400 outline-none transition focus:border-[#1B4D3E] focus:bg-white focus:ring-4 focus:ring-[#1B4D3E]/10"
                >
            </div>

            <div class="relative">
                <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-stone-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="2" />
                        <path d="M6 11h12v10H6V11Z" stroke="currentColor" stroke-width="2" />
                    </svg>
                </span>

                <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    autocomplete="current-password"
                    required
                    placeholder="Password"
                    class="w-full rounded-xl border border-stone-200 bg-stone-50 py-3.5 pr-12 pl-12 text-sm text-stone-900 placeholder:text-stone-400 outline-none transition focus:border-[#1B4D3E] focus:bg-white focus:ring-4 focus:ring-[#1B4D3E]/10"
                >

                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center px-4 text-stone-500 transition hover:text-[#1B4D3E]"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    :aria-pressed="showPassword ? 'true' : 'false'"
                    @click="emit('toggle-password')"
                >
                    <svg v-if="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="2" />
                        <path d="M12 15a3 3 0 1 0-3-3 3 3 0 0 0 3 3Z" stroke="currentColor" stroke-width="2" />
                    </svg>
                    <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="m3 3 18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c6.5 0 10 7 10 7a17.58 17.58 0 0 1-3.06 3.77" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <path d="M6.23 6.23C3.89 7.83 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 4.28-.84" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <div class="flex items-center justify-between text-sm text-stone-600">
                <label class="inline-flex items-center gap-2">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-stone-300 text-[#1B4D3E] focus:ring-[#1B4D3E]"
                    >
                    Remember me
                </label>

                <Link :href="forgotPasswordUrl" class="font-semibold text-[#1B4D3E] hover:text-[#2A6B54]">
                    Forgot password?
                </Link>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-[#1B4D3E] py-3.5 text-sm font-semibold text-white shadow-[0_4px_15px_rgba(27,77,62,0.30)] transition hover:-translate-y-[1px] hover:bg-[#2A6B54] disabled:cursor-not-allowed disabled:opacity-70"
            >
                {{ form.processing ? 'SIGNING IN...' : 'SIGN IN' }}
            </button>
        </form>
    </div>
</template>
