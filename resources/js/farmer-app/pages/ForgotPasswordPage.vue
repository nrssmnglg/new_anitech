<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import AppState from '../components/ui/AppState.vue';
import { useLocale } from '../composables/useLocale';
import { apiPost } from '../services/api';
import { extractApiMessage, extractValidationErrors } from '../utils/api';

const router = useRouter();
const { t } = useLocale();
import { brandLogoUrl } from '../utils/asset';

const brandLogo = brandLogoUrl();
const loading = ref(false);
const error = ref('');
const success = ref('');
const validationErrors = ref({});
const otpMeta = ref(null);
const countdown = ref(0);
let countdownTimer = null;
const form = reactive({
    email: '',
});

const supportMessage = computed(() => otpMeta.value?.support_message ?? 'If the OTP still does not arrive, contact AniTech support or visit the office for account recovery help.');

const startCountdown = (seconds = 0) => {
    countdown.value = Number(seconds || 0);

    if (countdownTimer) {
        window.clearInterval(countdownTimer);
    }

    if (countdown.value <= 0) {
        return;
    }

    countdownTimer = window.setInterval(() => {
        countdown.value = Math.max(0, countdown.value - 1);

        if (countdown.value <= 0 && countdownTimer) {
            window.clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }, 1000);
};

const submit = async () => {
    loading.value = true;
    error.value = '';
    success.value = '';
    validationErrors.value = {};

    try {
        const response = await apiPost('/forgot-password', form);
        success.value = response?.message ?? 'OTP sent.';
        otpMeta.value = response?.meta ?? null;
        router.push({
            name: 'reset-verify',
            query: {
                email: response?.data?.email ?? form.email,
                cooldown: String(response?.meta?.retry_seconds_remaining ?? 0),
                attempts_used: String(response?.meta?.attempts_used ?? 0),
                attempts_remaining: String(response?.meta?.attempts_remaining ?? 0),
                expires_at: response?.meta?.expires_at ?? '',
            },
        });
    } catch (err) {
        error.value = extractApiMessage(err, t('forgotPassword.failed'));
        validationErrors.value = extractValidationErrors(err);
        otpMeta.value = err?.response?.data?.meta ?? null;
        startCountdown(otpMeta.value?.retry_seconds_remaining ?? 0);
    } finally {
        loading.value = false;
    }
};

onBeforeUnmount(() => {
    if (countdownTimer) {
        window.clearInterval(countdownTimer);
    }
});
</script>

<template>
    <div class="farmer-app__forgot-screen">
        <main class="farmer-app__forgot-shell">
            <header class="farmer-app__forgot-brand">
                <div class="farmer-app__forgot-mark"><img :src="brandLogo" alt="AniTech logo"></div>
                <div><strong>AniTech</strong><span>Farmer Portal</span></div>
            </header>
            <section class="farmer-app__forgot-card">
                <div class="farmer-app__forgot-heading">
                    <h1>{{ t('forgotPassword.title') }}</h1>
                    <p>{{ t('forgotPassword.intro') }}</p>
                </div>

                <form class="farmer-app__forgot-form" @submit.prevent="submit">
                    <label class="farmer-app__forgot-field" for="recovery_email">
                        <span>Farmer Email Address</span>
                        <div class="farmer-app__forgot-input-wrap">
                            <span class="farmer-app__forgot-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 7.5A1.5 1.5 0 0 1 5.5 6h13A1.5 1.5 0 0 1 20 7.5v9A1.5 1.5 0 0 1 18.5 18h-13A1.5 1.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/><path d="m5.5 7 6.5 5 6.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <input
                                id="recovery_email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                placeholder="Enter your registered farmer email"
                            >
                        </div>
                        <small v-if="validationErrors.email" class="farmer-app__field-error">{{ validationErrors.email }}</small>
                    </label>

                    <AppState v-if="error" type="error" :message="error" />
                    <AppState v-if="success" type="success" :message="success" />

                    <div v-if="otpMeta" class="farmer-app__forgot-help">
                        <p v-if="countdown > 0">You can request another OTP in {{ countdown }}s.</p>
                        <p>The OTP expires in 10 minutes.</p>
                        <p>{{ supportMessage }}</p>
                    </div>

                    <button type="submit" class="farmer-app__btn farmer-app__forgot-primary" :disabled="loading || countdown > 0">
                        {{ loading ? t('forgotPassword.sending') : countdown > 0 ? t('forgotPassword.wait_seconds', { seconds: countdown }) : t('forgotPassword.send_otp') }}
                    </button>
                </form>

                <div class="farmer-app__forgot-divider">
                    <span>OR</span>
                </div>

                <RouterLink :to="{ name: 'login' }" class="farmer-app__forgot-back">
                    {{ t('forgotPassword.back_to_login') }}
                </RouterLink>
            </section>

        </main>
    </div>
</template>

<style scoped>
.farmer-app__forgot-screen { min-height: 100dvh; background: #f3f7f4; }
.farmer-app__forgot-shell { width: min(100%, 390px); min-height: 100dvh; margin: 0 auto; padding: 24px 16px; display: flex; flex-direction: column; justify-content: center; }
.farmer-app__forgot-brand { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 14px; text-align: left; }
.farmer-app__forgot-mark { width: 44px; height: 44px; padding: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
.farmer-app__forgot-mark img { width: 44px; height: 44px; object-fit: contain; }
.farmer-app__forgot-brand strong, .farmer-app__forgot-brand span { display: block; }
.farmer-app__forgot-brand strong { color: var(--pwa-green-900); font-size: 1.15rem; }
.farmer-app__forgot-brand span { color: var(--pwa-muted); font-size: .7rem; }
.farmer-app__forgot-card { display: block; width: 100%; padding: 18px; border: 1px solid var(--pwa-border); border-radius: 12px; background: #fff; box-shadow: var(--pwa-shadow-soft); }
.farmer-app__forgot-heading { margin-bottom: 16px; }
.farmer-app__forgot-heading h1 { margin: 0; color: var(--pwa-ink); font-size: 1.2rem; line-height: 1.3; text-align: left; }
.farmer-app__forgot-heading p { margin: 4px 0 0; color: var(--pwa-muted); font-size: .8rem; line-height: 1.45; }
.farmer-app__forgot-form { display: grid; gap: 13px; }
.farmer-app__forgot-field { display: grid; gap: 6px; }
.farmer-app__forgot-field > span { color: var(--pwa-ink); font-size: .77rem; font-weight: 700; }
.farmer-app__forgot-input-wrap { position: relative; display: block; min-height: 46px; height: 46px; padding: 0 !important; border: 1px solid var(--pwa-border); border-radius: 10px; background: #fff; }
.farmer-app__forgot-input-wrap:focus-within { border-color: var(--pwa-green-700); box-shadow: 0 0 0 3px rgba(35, 117, 84, .12); }
.farmer-app__forgot-input-icon { position: absolute; z-index: 1; left: 13px; top: 50%; width: 18px; height: 18px; transform: translateY(-50%); color: var(--pwa-muted); pointer-events: none; }
.farmer-app__forgot-input-icon svg { display: block; width: 18px; height: 18px; }
.farmer-app__forgot-input-wrap input { width: 100%; height: 44px; min-height: 44px; padding: 10px 12px 10px 42px !important; border: 0; outline: 0; background: transparent; color: var(--pwa-ink); font: inherit; font-size: .84rem; }
.farmer-app__forgot-help { padding: 10px; border: 1px solid #ead49a; border-radius: 8px; background: #fff9e9; color: #735300; font-size: .74rem; }
.farmer-app__forgot-help p { margin: 3px 0; }
.farmer-app__forgot-primary { width: 100%; min-height: 46px; border-radius: 10px; background: var(--pwa-green-800); color: #fff; box-shadow: none; }
.farmer-app__forgot-divider { margin: 14px 0; gap: 10px; }
.farmer-app__forgot-back { min-height: 44px; display: grid; place-items: center; border: 1px solid var(--pwa-border); border-radius: 10px; color: var(--pwa-green-800); text-decoration: none; font-size: .8rem; font-weight: 700; }
</style>
