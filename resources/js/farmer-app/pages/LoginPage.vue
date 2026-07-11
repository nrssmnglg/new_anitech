<script setup>
import { computed, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const router = useRouter();
const route = useRoute();
const auth = useAuth();
import { brandLogoUrl } from '../utils/asset';

const brandLogo = brandLogoUrl();
const showPassword = ref(false);
const sessionHint = computed(() => auth.shell.value?.authenticated ? 'A farmer session was already detected on this device. If sign-in fails, your previous session may have expired and you can sign in again below.' : '');
const form = reactive({
    email: '',
    password: '',
});

const passwordFieldType = computed(() => showPassword.value ? 'text' : 'password');

const submit = async () => {
    try {
        await auth.login(form);
        router.push(route.query.redirect || { name: 'dashboard' });
    } catch {}
};
</script>

<template>
    <div class="farmer-app__login-screen">
        <div class="farmer-app__login-decor" aria-hidden="true">
            <div class="farmer-app__login-orb farmer-app__login-orb--top"></div>
            <div class="farmer-app__login-orb farmer-app__login-orb--bottom"></div>
        </div>

        <div class="pwa-shell farmer-app__shell farmer-app__shell--login">
            <header class="farmer-app__login-brand">
                <img :src="brandLogo" alt="AniTech logo">
                <h1>AniTech</h1>
            </header>

            <main class="farmer-app__login-card">
                <div v-if="sessionHint" class="farmer-app__login-session-note">{{ sessionHint }}</div>

                <div v-if="auth.error.value" class="farmer-app__error">{{ auth.error.value }}</div>

                <form class="farmer-app__auth-form farmer-app__auth-form--polished" @submit.prevent="submit">
                    <label class="farmer-app__login-field">
                        <span>Email Address</span>
                        <div class="farmer-app__input-wrap">
                            <span class="farmer-app__field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 7.5A1.5 1.5 0 0 1 5.5 6h13A1.5 1.5 0 0 1 20 7.5v9A1.5 1.5 0 0 1 18.5 18h-13A1.5 1.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="m5.5 7 6.5 5 6.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                placeholder="farmer@example.com"
                            >
                        </div>
                        <small v-if="auth.validationErrors.value.email" class="farmer-app__field-error">
                            {{ auth.validationErrors.value.email }}
                        </small>
                    </label>

                    <label class="farmer-app__login-field">
                        <span class="farmer-app__login-field-head">
                            <span>Password</span>
                            <RouterLink :to="{ name: 'forgot-password' }">Forgot Password?</RouterLink>
                        </span>
                        <div class="farmer-app__input-wrap">
                            <span class="farmer-app__field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M7.5 10V8a4.5 4.5 0 1 1 9 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <rect x="5" y="10" width="14" height="10" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="passwordFieldType"
                                autocomplete="current-password"
                                placeholder="********"
                            >
                            <button
                                type="button"
                                class="farmer-app__input-toggle"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            >

                            </button>
                        </div>
                        <small v-if="auth.validationErrors.value.password" class="farmer-app__field-error">
                            {{ auth.validationErrors.value.password }}
                        </small>
                    </label>

                    <button type="submit" class="farmer-app__btn farmer-app__btn--primary farmer-app__btn--hero" :disabled="auth.loading.value">
                        {{ auth.loading.value ? 'SIGNING IN...' : 'SIGN IN' }}
                    </button>
                </form>

                <div class="farmer-app__login-divider">
                    <span>OR</span>
                </div>

                <div class="farmer-app__login-actions">
                    <RouterLink :to="{ name: 'track' }" class="farmer-app__btn farmer-app__btn--outline">
                        Track Application
                    </RouterLink>
                    <RouterLink :to="{ name: 'apply' }" class="farmer-app__btn farmer-app__btn--ghostline">
                        Apply as New Farmer
                    </RouterLink>
                </div>

                <div class="farmer-app__login-security">
                    <span class="farmer-app__login-security-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3.5 18.5 6v5.7c0 4.1-2.7 7.8-6.5 8.8-3.8-1-6.5-4.7-6.5-8.8V6L12 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="m9.5 12 1.7 1.7 3.3-3.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Your farm data is protected and your session stays active until you sign out or it expires.</strong>
                        <p>Use the same trusted device when possible for faster access.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
