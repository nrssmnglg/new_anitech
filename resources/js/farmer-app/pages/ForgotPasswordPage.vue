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
        <div class="farmer-app__forgot-backdrop"></div>

        <main class="farmer-app__forgot-shell">
            <section class="farmer-app__forgot-card">
                <div class="farmer-app__forgot-brand">
                    <div class="farmer-app__forgot-mark">
                        <img :src="brandLogo" alt="AniTech mark">
                    </div>
                    <h1>{{ t('forgotPassword.title') }}</h1>
                    <p>{{ t('forgotPassword.intro') }}</p>
                </div>

                <form class="farmer-app__forgot-form" @submit.prevent="submit">
                    <label class="farmer-app__forgot-field">
                        <span>Farmer Email Address</span>
                        <div class="farmer-app__forgot-input-wrap">
                            <span class="farmer-app__forgot-input-icon">@</span>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="e.g. j.appleton@farm.com"
                            >
                        </div>
                        <small v-if="validationErrors.email" class="farmer-app__field-error">{{ validationErrors.email }}</small>
                    </label>

                    <AppState v-if="error" type="error" :message="error" />
                    <AppState v-if="success" :message="success" />

                    <div v-if="otpMeta" class="farmer-app__forgot-help">
                        <p v-if="countdown > 0">You can request another OTP in {{ countdown }}s.</p>
                        <p>{{ supportMessage }}</p>
                    </div>

                    <button type="submit" class="farmer-app__btn farmer-app__forgot-primary" :disabled="loading">
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

            <div class="farmer-app__forgot-footer">
                Powered by <strong>AniTech</strong> Smart Agriculture System
            </div>
        </main>
    </div>
</template>
