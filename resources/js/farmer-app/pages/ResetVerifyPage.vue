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
        <div class="farmer-app__verify-backdrop"></div>

        <main class="farmer-app__verify-shell">
            <section class="farmer-app__verify-card">
                <div class="farmer-app__verify-brand">
                    <div class="farmer-app__verify-mark">
                        <img :src="brandLogo" alt="AniTech logo" />
                    </div>
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
                    <AppState v-if="success" :message="success" />

                    <div class="farmer-app__verify-status">
                        <p v-if="otpMeta.retry_seconds_remaining > 0">{{ t('otp.resend_in', { seconds: otpMeta.retry_seconds_remaining }) }}</p>
                        <p v-else class="farmer-app__verify-hint">OTP codes expire after 10 minutes.</p>
                    </div>

                    <button type="submit" class="farmer-app__btn farmer-app__verify-primary" :disabled="loading">
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

            <footer class="farmer-app__verify-footer">Secure AniTech Authentication</footer>
        </main>
    </div>
</template>
