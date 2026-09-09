<script setup>
import { computed, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppState from '../components/ui/AppState.vue';
import { useLocale } from '../composables/useLocale';
import { apiPost } from '../services/api';
import { extractApiMessage, extractValidationErrors } from '../utils/api';
import { brandLogoUrl } from '../utils/asset';
import { resolveFarmerAppRedirect } from '../utils/paths';

const route = useRoute();
const router = useRouter();
const { t } = useLocale();

const brandLogo = brandLogoUrl();
const loading = ref(false);
const error = ref('');
const success = ref('');
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const validationErrors = ref({});
const form = reactive({
    email: String(route.query.email ?? ''),
    password: '',
    password_confirmation: '',
});

const passwordToggleLabel = computed(() => (showPassword.value ? t('newPassword.hide_password') : t('newPassword.show_password')));
const passwordConfirmationToggleLabel = computed(() => (showPasswordConfirmation.value ? t('newPassword.hide_password_confirmation') : t('newPassword.show_password_confirmation')));
const canSubmit = computed(() => form.email && form.password.length >= 8 && form.password === form.password_confirmation);

const submit = async () => {
    loading.value = true;
    error.value = '';
    success.value = '';
    validationErrors.value = {};

    try {
        const response = await apiPost('/reset-password', form);
        success.value = response?.message ?? t('newPassword.reset_success');
        router.push(resolveFarmerAppRedirect(response?.data?.redirect_url) ?? '/login');
    } catch (err) {
        error.value = extractApiMessage(err, t('newPassword.reset_failed'));
        validationErrors.value = extractValidationErrors(err);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="farmer-app__new-password-screen">
        <main class="farmer-app__new-password-shell">
            <header class="farmer-app__new-password-brand">
                <div class="farmer-app__new-password-mark"><img :src="brandLogo" alt="AniTech logo" /></div>
                <div><strong>AniTech</strong><span>Farmer Portal</span></div>
            </header>
            <section class="farmer-app__new-password-card">
                <div class="farmer-app__new-password-heading">
                    <h1>{{ t('newPassword.title') }}</h1>
                    <p>{{ t('newPassword.subtitle') }}</p>
                </div>

                <form class="farmer-app__new-password-form" @submit.prevent="submit">
                    <div class="farmer-app__new-password-field">
                        <span>{{ t('newPassword.email') }}</span>
                        <div class="farmer-app__new-password-input-wrap farmer-app__new-password-input-wrap--readonly">
                            <span class="farmer-app__new-password-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7.5A1.5 1.5 0 0 1 5.5 6h13A1.5 1.5 0 0 1 20 7.5v9A1.5 1.5 0 0 1 18.5 18h-13A1.5 1.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/><path d="m5.5 7 6.5 5 6.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <input v-model="form.email" type="email" readonly />
                        </div>
                        <p v-if="validationErrors.email" class="farmer-app__new-password-error">{{ validationErrors.email }}</p>
                    </div>

                    <div class="farmer-app__new-password-field">
                        <span>{{ t('newPassword.new_password') }}</span>
                        <div class="farmer-app__new-password-input-wrap">
                            <span class="farmer-app__new-password-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M7.5 10V8a4.5 4.5 0 1 1 9 0v2" stroke="currentColor" stroke-width="1.8"/><rect x="5" y="10" width="14" height="10" rx="2.5" stroke="currentColor" stroke-width="1.8"/></svg></span>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                :placeholder="t('newPassword.min_chars')"
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                class="farmer-app__new-password-toggle"
                                :aria-label="passwordToggleLabel"
                                @click="showPassword = !showPassword"
                            >
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.5 12c0-1.8 3.3-7 8.5-7s8.5 5.2 8.5 7-3.3 7-8.5 7-8.5-5.2-8.5-7Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                            </button>
                        </div>
                        <p v-if="validationErrors.password" class="farmer-app__new-password-error">{{ validationErrors.password }}</p>
                    </div>

                    <div class="farmer-app__new-password-field">
                        <span>{{ t('newPassword.confirm_password') }}</span>
                        <div class="farmer-app__new-password-input-wrap">
                            <span class="farmer-app__new-password-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M7.5 10V8a4.5 4.5 0 1 1 9 0v2" stroke="currentColor" stroke-width="1.8"/><rect x="5" y="10" width="14" height="10" rx="2.5" stroke="currentColor" stroke-width="1.8"/><path d="m9.5 15 1.7 1.7 3.3-3.7" stroke="currentColor" stroke-width="1.8"/></svg></span>
                            <input
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                :placeholder="t('newPassword.repeat_password')"
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                class="farmer-app__new-password-toggle"
                                :aria-label="passwordConfirmationToggleLabel"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                            >
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.5 12c0-1.8 3.3-7 8.5-7s8.5 5.2 8.5 7-3.3 7-8.5 7-8.5-5.2-8.5-7Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                            </button>
                        </div>
                        <p v-if="validationErrors.password_confirmation" class="farmer-app__new-password-error">{{ validationErrors.password_confirmation }}</p>
                    </div>

                    <AppState v-if="error" type="error" :message="error" />
                    <AppState v-if="success" type="success" :message="success" />

                    <button type="submit" class="farmer-app__btn farmer-app__new-password-primary" :disabled="loading || !canSubmit">
                        {{ loading ? t('newPassword.resetting') : t('newPassword.reset_password') }}
                    </button>

                    <RouterLink :to="{ name: 'login' }" class="farmer-app__new-password-back">
                        {{ t('newPassword.back_to_login') }}
                    </RouterLink>
                </form>
            </section>

        </main>
    </div>
</template>

<style scoped>
.farmer-app__new-password-screen { min-height: 100dvh; background: #f3f7f4; }
.farmer-app__new-password-shell { width: min(100%, 390px); min-height: 100dvh; margin: 0 auto; padding: 24px 16px; display: flex; flex-direction: column; justify-content: center; }
.farmer-app__new-password-brand { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 14px; text-align: left; }
.farmer-app__new-password-mark { width: 44px; height: 44px; padding: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
.farmer-app__new-password-mark img { width: 44px; height: 44px; object-fit: contain; }
.farmer-app__new-password-brand strong, .farmer-app__new-password-brand span { display: block; }
.farmer-app__new-password-brand strong { color: var(--pwa-green-900); font-size: 1.15rem; }
.farmer-app__new-password-brand span { color: var(--pwa-muted); font-size: .7rem; }
.farmer-app__new-password-card { display: block; width: 100%; padding: 18px; border: 1px solid var(--pwa-border); border-radius: 12px; background: #fff; box-shadow: var(--pwa-shadow-soft); }
.farmer-app__new-password-heading { margin-bottom: 16px; }
.farmer-app__new-password-heading h1 { margin: 0; color: var(--pwa-ink); font-size: 1.2rem; line-height: 1.3; text-align: left; }
.farmer-app__new-password-heading p { margin: 4px 0 0; color: var(--pwa-muted); font-size: .8rem; line-height: 1.45; }
.farmer-app__new-password-form { display: grid; gap: 13px; }
.farmer-app__new-password-field { display: grid; gap: 6px; }
.farmer-app__new-password-field > span { color: var(--pwa-ink); font-size: .77rem; font-weight: 700; }
.farmer-app__new-password-input-wrap { position: relative; display: block; height: 46px; min-height: 46px; padding: 0 !important; border: 1px solid var(--pwa-border); border-radius: 10px; background: #fff; }
.farmer-app__new-password-input-wrap:focus-within { border-color: var(--pwa-green-700); box-shadow: 0 0 0 3px rgba(35, 117, 84, .12); }
.farmer-app__new-password-input-wrap--readonly { background: var(--pwa-surface-soft); }
.farmer-app__new-password-input-icon { position: absolute; z-index: 1; left: 13px; top: 50%; width: 18px; height: 18px; transform: translateY(-50%); color: var(--pwa-muted); pointer-events: none; }
.farmer-app__new-password-input-icon svg { display: block; width: 18px; height: 18px; }
.farmer-app__new-password-input-wrap input { width: 100%; height: 44px; min-height: 44px; padding: 10px 44px 10px 42px !important; border: 0; outline: 0; background: transparent; color: var(--pwa-ink); font: inherit; font-size: .84rem; }
.farmer-app__new-password-toggle { position: absolute; z-index: 2; top: 50%; right: 1px; width: 44px; height: 44px; display: grid; place-items: center; padding: 12px; transform: translateY(-50%); border: 0; background: transparent; color: var(--pwa-muted); cursor: pointer; }
.farmer-app__new-password-toggle svg { display: block; width: 19px; height: 19px; }
.farmer-app__new-password-error { margin: 0; color: var(--pwa-danger); font-size: .74rem; }
.farmer-app__new-password-primary { width: 100%; min-height: 46px; border-radius: 10px; background: var(--pwa-green-800); color: #fff; box-shadow: none; }
.farmer-app__new-password-back { min-height: 44px; display: grid; place-items: center; border: 1px solid var(--pwa-border); border-radius: 10px; color: var(--pwa-green-800); text-decoration: none; font-size: .8rem; font-weight: 700; }
</style>
