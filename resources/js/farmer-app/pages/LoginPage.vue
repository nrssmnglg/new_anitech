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
        <div class="pwa-shell farmer-app__shell farmer-app__shell--login">
            <header class="farmer-app__login-brand">
                <img :src="brandLogo" alt="AniTech logo">
                <div><h1>AniTech</h1><p>Farmer Portal</p></div>
            </header>

            <main class="farmer-app__login-card">
                <div class="farmer-app__login-heading">
                    <h2>Sign in</h2>
                    <p>Access your farmer account.</p>
                </div>
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
                                <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 3l18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 5.2A9.8 9.8 0 0 1 12 5c5.2 0 8.5 5.2 8.5 7a7.6 7.6 0 0 1-1.5 2.7M6.2 6.2C4.5 7.6 3.5 10 3.5 12c0 1.8 3.3 7 8.5 7 1.3 0 2.5-.3 3.5-.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <svg v-else viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3.5 12c0-1.8 3.3-7 8.5-7s8.5 5.2 8.5 7-3.3 7-8.5 7-8.5-5.2-8.5-7Z" stroke="currentColor" stroke-width="1.8" />
                                    <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8" />
                                </svg>
                            </button>
                        </div>
                        <small v-if="auth.validationErrors.value.password" class="farmer-app__field-error">
                            {{ auth.validationErrors.value.password }}
                        </small>
                    </label>

                    <button type="submit" class="farmer-app__btn farmer-app__btn--primary farmer-app__btn--hero" :disabled="auth.loading.value">
                        {{ auth.loading.value ? 'Signing in...' : 'Sign in' }}
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

            </main>
        </div>
    </div>
</template>

<style scoped>
.farmer-app__login-screen {
    min-height: 100dvh;
    background: #f3f7f4;
}

.farmer-app__shell--login {
    width: min(100%, 390px);
    min-height: 100dvh;
    justify-content: center;
    padding: 24px 16px;
}

.farmer-app__login-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin: 0 0 16px;
    text-align: left;
}

.farmer-app__login-brand img {
    width: 58px !important;
    height: 58px !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 50%;
    background: transparent !important;
    box-shadow: none !important;
    object-fit: cover;
}

.farmer-app__login-brand h1,
.farmer-app__login-brand p {
    margin: 0;
}

.farmer-app__login-brand h1 {
    color: var(--pwa-green-900);
    font-size: 1.2rem;
    line-height: 1.2;
}

.farmer-app__login-brand p {
    color: var(--pwa-muted);
    font-size: .72rem;
}

.farmer-app__login-card {
    padding: 18px;
    border: 1px solid var(--pwa-border);
    border-radius: 12px;
    background: #fff;
    box-shadow: var(--pwa-shadow-soft);
}

.farmer-app__login-heading {
    margin: 0 0 16px;
    text-align: left;
}

.farmer-app__login-heading h2 {
    margin: 0;
    color: var(--pwa-ink);
    font-size: 1.2rem !important;
    text-align: left !important;
}

.farmer-app__login-heading p {
    margin: 3px 0 0;
    color: var(--pwa-muted);
    font-size: .82rem;
}

.farmer-app__auth-form--polished {
    gap: 14px;
}

.farmer-app__login-field {
    gap: 6px;
}

.farmer-app__login-field > span,
.farmer-app__login-field-head > span {
    margin: 0;
    color: var(--pwa-ink);
    font-size: .78rem;
}

.farmer-app__input-wrap input {
    min-height: 46px;
    padding: 10px 44px 10px 42px !important;
    border-radius: 10px;
}

.farmer-app__field-icon {
    position: absolute;
    z-index: 1;
    left: 13px;
    top: 50%;
    width: 18px;
    height: 18px;
    transform: translateY(-50%);
    pointer-events: none;
}

.farmer-app__field-icon svg {
    display: block;
    width: 18px;
    height: 18px;
}

.farmer-app__input-toggle {
    right: 2px;
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
}

.farmer-app__input-toggle svg {
    width: 19px;
    height: 19px;
}

.farmer-app__btn--hero {
    width: 100%;
    min-height: 46px;
    padding: 10px 14px;
    border-radius: 10px;
    text-transform: none;
    letter-spacing: 0;
    box-shadow: none;
}

.farmer-app__login-divider {
    margin: 14px 0;
}

.farmer-app__login-actions {
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.farmer-app__login-actions .farmer-app__btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 56px;
    min-height: 56px;
    padding: 6px 8px;
    box-sizing: border-box;
    border-radius: 10px;
    font-size: .76rem;
    line-height: 1.25;
    text-align: center;
    border: 1px solid var(--pwa-border);
    background: #fff;
    color: var(--pwa-green-800);
    box-shadow: none;
}

.farmer-app__login-actions .farmer-app__btn:last-child {
    border-color: transparent;
    background: var(--pwa-green-100);
}

.farmer-app__login-session-note,
.farmer-app__error {
    margin-bottom: 12px;
    padding: 10px;
    border-radius: 8px;
    font-size: .78rem;
}

@media (max-width: 340px) {
    .farmer-app__login-actions {
        grid-template-columns: 1fr;
    }

    .farmer-app__login-actions .farmer-app__btn {
        height: 46px;
        min-height: 46px;
    }
}
</style>
