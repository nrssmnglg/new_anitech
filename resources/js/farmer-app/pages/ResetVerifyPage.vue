<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
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
const validationErrors = ref({});
const otpDigits = ref(['', '', '', '', '', '']);
const otpRefs = ref([]);
const otpMeta = ref({
    retry_seconds_remaining: Number(route.query.cooldown ?? 0),
    attempts_used: Number(route.query.attempts_used ?? 0),
    attempts_remaining: Number(route.query.attempts_remaining ?? 0),
    expires_at: String(route.query.expires_at ?? ''),
    support_message: '',
});
const resendLoading = ref(false);
const supportVisible = computed(() => (otpMeta.value?.attempts_used ?? 0) >= 3 || otpMeta.value?.is_blocked);
let countdownTimer = null;
const form = reactive({
    email: String(route.query.email ?? ''),
    otp: '',
});

const otpValue = computed(() => otpDigits.value.join(''));
const canVerify = computed(() => otpValue.value.length === 6 && Boolean(form.email));

watch(
    otpValue,
    (value) => {
        form.otp = value;
    },
    { immediate: true }
);

const startCountdown = (seconds = 0) => {
    otpMeta.value = {
        ...otpMeta.value,
        retry_seconds_remaining: Number(seconds || 0),
    };

    if (countdownTimer) {
        window.clearInterval(countdownTimer);
    }

    if ((otpMeta.value.retry_seconds_remaining ?? 0) <= 0) {
        return;
    }

    countdownTimer = window.setInterval(() => {
        otpMeta.value = {
            ...otpMeta.value,
            retry_seconds_remaining: Math.max(0, Number(otpMeta.value.retry_seconds_remaining ?? 0) - 1),
        };

        if ((otpMeta.value.retry_seconds_remaining ?? 0) <= 0 && countdownTimer) {
            window.clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }, 1000);
};

const focusOtp = (index) => {
    nextTick(() => {
        otpRefs.value[index]?.focus();
        otpRefs.value[index]?.select?.();
    });
};

const assignOtp = (value) => {
    const digits = String(value).replace(/\D/g, '').slice(0, 6).split('');

    otpDigits.value = Array.from({ length: 6 }, (_, index) => digits[index] ?? '');

    const nextIndex = Math.min(digits.length, 5);
    focusOtp(nextIndex);
};

const handleOtpInput = (index, event) => {
    const cleanValue = event.target.value.replace(/\D/g, '').slice(-1);

    otpDigits.value[index] = cleanValue;

    if (cleanValue && index < 5) {
        focusOtp(index + 1);
    }
};

const handleOtpKeydown = (index, event) => {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        otpDigits.value[index - 1] = '';
        focusOtp(index - 1);
        return;
    }

    if (event.key === 'ArrowLeft' && index > 0) {
        event.preventDefault();
        focusOtp(index - 1);
    }

    if (event.key === 'ArrowRight' && index < 5) {
        event.preventDefault();
        focusOtp(index + 1);
    }
};

const handleOtpPaste = (event) => {
    event.preventDefault();
    assignOtp(event.clipboardData?.getData('text') ?? '');
};

const submit = async () => {
    loading.value = true;
    error.value = '';
    success.value = '';
    validationErrors.value = {};
    form.otp = otpValue.value;

    try {
        const response = await apiPost('/reset-password/verify', form);
        otpMeta.value = {
            ...otpMeta.value,
            ...(response?.meta ?? {}),
        };
        router.push(resolveFarmerAppRedirect(response?.data?.redirect_url) ?? `/reset-password/new?email=${encodeURIComponent(form.email)}`);
    } catch (err) {
        error.value = extractApiMessage(err, t('otp.verify_failed'));
        validationErrors.value = extractValidationErrors(err);
        otpMeta.value = {
            ...otpMeta.value,
            ...(err?.response?.data?.meta ?? {}),
        };
        startCountdown(otpMeta.value?.retry_seconds_remaining ?? 0);
    } finally {
        loading.value = false;
    }
};

const resendCode = async () => {
    if (!form.email || (otpMeta.value?.retry_seconds_remaining ?? 0) > 0) {
        return;
    }

    resendLoading.value = true;
    error.value = '';
    success.value = '';
    validationErrors.value = {};

    try {
        const response = await apiPost('/forgot-password', { email: form.email });
        otpMeta.value = {
            ...otpMeta.value,
            ...(response?.meta ?? {}),
        };
        success.value = response?.message ?? t('otp.resend_success');
        otpDigits.value = ['', '', '', '', '', ''];
        startCountdown(otpMeta.value?.retry_seconds_remaining ?? 0);
        focusOtp(0);
    } catch (err) {
        error.value = extractApiMessage(err, t('otp.resend_failed'));
        validationErrors.value = extractValidationErrors(err);
        otpMeta.value = {
            ...otpMeta.value,
            ...(err?.response?.data?.meta ?? {}),
        };
        startCountdown(otpMeta.value?.retry_seconds_remaining ?? 0);
    } finally {
        resendLoading.value = false;
    }
};

onMounted(() => {
    startCountdown(otpMeta.value?.retry_seconds_remaining ?? 0);
});

onBeforeUnmount(() => {
    if (countdownTimer) {
        window.clearInterval(countdownTimer);
    }
});
</script>

<template>
    <div class="farmer-app__verify-screen">
        <main class="farmer-app__verify-shell">
            <header class="farmer-app__verify-brand">
                <div class="farmer-app__verify-mark"><img :src="brandLogo" alt="AniTech logo" /></div>
                <div><strong>AniTech</strong><span>Farmer Portal</span></div>
            </header>
            <section class="farmer-app__verify-card">
                <div class="farmer-app__verify-heading">
                    <h1>{{ t('otp.verify_title') }}</h1>
                    <p>{{ t('otp.verify_intro') }}</p>
                </div>

                <div class="farmer-app__verify-email">
                    <span class="farmer-app__verify-email-icon" aria-hidden="true">&#9993;</span>
                    <span>{{ form.email || t('otp.no_email') }}</span>
                </div>

                <form class="farmer-app__verify-form" @submit.prevent="submit">
                    <div class="farmer-app__verify-otp-group">
                        <input
                            v-for="(digit, index) in otpDigits"
                            :key="index"
                            :ref="(el) => otpRefs[index] = el"
                            :value="digit"
                            class="farmer-app__verify-otp-input"
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            autocomplete="one-time-code"
                            @input="handleOtpInput(index, $event)"
                            @keydown="handleOtpKeydown(index, $event)"
                            @paste="handleOtpPaste"
                        />
                    </div>

                    <p v-if="validationErrors.otp" class="farmer-app__verify-error">
                        {{ validationErrors.otp }}
                    </p>
                    <p v-if="validationErrors.email" class="farmer-app__verify-error">
                        {{ validationErrors.email }}
                    </p>

                    <AppState v-if="error" type="error" :message="error" />
                    <AppState v-if="success" type="success" :message="success" />

                    <div class="farmer-app__verify-status">
                        <p v-if="otpMeta.retry_seconds_remaining > 0">{{ t('otp.resend_in', { seconds: otpMeta.retry_seconds_remaining }) }}</p>
                        <p v-else class="farmer-app__verify-hint">OTP codes expire after 10 minutes.</p>
                    </div>

                    <button type="submit" class="farmer-app__btn farmer-app__verify-primary" :disabled="loading || !canVerify">
                        {{ loading ? t('otp.verifying') : t('otp.verify') }}
                    </button>
                </form>

                <div class="farmer-app__verify-actions">
                    <p>
                        {{ t('otp.didnt_receive') }}
                        <button
                            type="button"
                            class="farmer-app__verify-text-button"
                            :disabled="resendLoading || otpMeta.retry_seconds_remaining > 0"
                            @click="resendCode"
                        >
                            {{ resendLoading ? t('otp.sending') : otpMeta.retry_seconds_remaining > 0 ? t('otp.resend_in_button', { seconds: otpMeta.retry_seconds_remaining }) : t('otp.resend_code') }}
                        </button>
                    </p>

                    <p v-if="supportVisible" class="farmer-app__verify-support">
                        {{ otpMeta.support_message || 'If OTP delivery keeps failing, contact AniTech support or visit the office for account recovery assistance.' }}
                    </p>

                    <RouterLink :to="{ name: 'login' }" class="farmer-app__verify-back">
                        Back to Login
                    </RouterLink>
                </div>
            </section>

        </main>
    </div>
</template>

<style scoped>
.farmer-app__verify-screen { min-height: 100dvh; background: #f3f7f4; }
.farmer-app__verify-shell { width: min(100%, 390px); min-height: 100dvh; margin: 0 auto; padding: 24px 16px; display: flex; flex-direction: column; justify-content: center; }
.farmer-app__verify-brand { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 14px; text-align: left; }
.farmer-app__verify-mark { width: 44px; height: 44px; padding: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
.farmer-app__verify-mark img { width: 44px; height: 44px; object-fit: contain; }
.farmer-app__verify-brand strong, .farmer-app__verify-brand span { display: block; }
.farmer-app__verify-brand strong { color: var(--pwa-green-900); font-size: 1.15rem; }
.farmer-app__verify-brand span { color: var(--pwa-muted); font-size: .7rem; }
.farmer-app__verify-card { display: block; width: 100%; padding: 18px; border: 1px solid var(--pwa-border); border-radius: 12px; background: #fff; box-shadow: var(--pwa-shadow-soft); }
.farmer-app__verify-heading { margin-bottom: 12px; }
.farmer-app__verify-heading h1 { margin: 0; color: var(--pwa-ink); font-size: 1.2rem; line-height: 1.3; text-align: left; }
.farmer-app__verify-heading p { margin: 4px 0 0; color: var(--pwa-muted); font-size: .8rem; line-height: 1.45; }
.farmer-app__verify-email { min-height: 38px; display: flex; align-items: center; gap: 8px; margin-bottom: 14px; padding: 8px 10px; border: 1px solid var(--pwa-border); border-radius: 8px; background: var(--pwa-surface-soft); color: var(--pwa-muted); font-size: .76rem; overflow-wrap: anywhere; }
.farmer-app__verify-email-icon { margin: 0; color: var(--pwa-green-700); }
.farmer-app__verify-form { display: grid; gap: 12px; }
.farmer-app__verify-otp-group { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 7px; }
.farmer-app__verify-otp-input { width: 100%; min-width: 0; height: 48px; padding: 0 !important; border: 1px solid var(--pwa-border); border-radius: 8px; background: #fff; color: var(--pwa-ink); font: inherit; font-size: 1.05rem; font-weight: 700; text-align: center; }
.farmer-app__verify-otp-input:focus { border-color: var(--pwa-green-700); outline: 3px solid rgba(35, 117, 84, .13); }
.farmer-app__verify-error { margin: 0; color: var(--pwa-danger); font-size: .74rem; }
.farmer-app__verify-status { min-height: 20px; color: var(--pwa-muted); font-size: .74rem; text-align: center; }
.farmer-app__verify-status p { margin: 0; }
.farmer-app__verify-primary { width: 100%; min-height: 46px; border-radius: 10px; background: var(--pwa-green-800); color: #fff; box-shadow: none; }
.farmer-app__verify-actions { display: grid; gap: 10px; margin-top: 14px; color: var(--pwa-muted); font-size: .76rem; text-align: center; }
.farmer-app__verify-actions p { margin: 0; }
.farmer-app__verify-text-button { min-height: 44px; padding: 8px; border: 0; background: transparent; color: var(--pwa-green-700); font: inherit; font-weight: 700; cursor: pointer; }
.farmer-app__verify-support { padding: 9px; border: 1px solid #ead49a; border-radius: 8px; background: #fff9e9; color: #735300; }
.farmer-app__verify-back { min-height: 44px; display: grid; place-items: center; border: 1px solid var(--pwa-border); border-radius: 10px; color: var(--pwa-green-800); text-decoration: none; font-weight: 700; }
@media (max-width: 350px) { .farmer-app__verify-otp-group { gap: 4px; } .farmer-app__verify-otp-input { height: 44px; font-size: .95rem; } }
</style>
