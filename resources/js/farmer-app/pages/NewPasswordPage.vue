<script setup>
import { computed, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppState from '../components/ui/AppState.vue';
import { useLocale } from '../composables/useLocale';
import { apiPost } from '../services/api';
import { extractApiMessage, extractValidationErrors } from '../utils/api';

const route = useRoute();
const router = useRouter();
const { t } = useLocale();
import { brandLogoUrl } from '../utils/asset';

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

const submit = async () => {
    loading.value = true;
    error.value = '';
    success.value = '';
    validationErrors.value = {};

    try {
        const response = await apiPost('/reset-password', form);
        success.value = response?.message ?? t('newPassword.reset_success');
        router.push(response?.data?.redirect_url ?? '/farmer/app/login');
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
        <div class="farmer-app__new-password-backdrop"></div>

        <main class="farmer-app__new-password-shell">
            <section class="farmer-app__new-password-card">
                <div class="farmer-app__new-password-brand">
                    <div class="farmer-app__new-password-mark">
                        <img :src="brandLogo" alt="AniTech logo" />
                    </div>
                    <h1>{{ t('newPassword.title') }}</h1>
                    <p>{{ t('newPassword.subtitle') }}</p>
                </div>

                <form class="farmer-app__new-password-form" @submit.prevent="submit">
                    <div class="farmer-app__new-password-field">
                        <span>{{ t('newPassword.email') }}</span>
                        <div class="farmer-app__new-password-input-wrap farmer-app__new-password-input-wrap--readonly">
                            <span class="farmer-app__new-password-input-icon" aria-hidden="true">✉</span>
                            <input v-model="form.email" type="email" readonly />
                        </div>
                        <p v-if="validationErrors.email" class="farmer-app__new-password-error">{{ validationErrors.email }}</p>
                    </div>

                    <div class="farmer-app__new-password-field">
                        <span>{{ t('newPassword.new_password') }}</span>
                        <div class="farmer-app__new-password-input-wrap">
                            <span class="farmer-app__new-password-input-icon" aria-hidden="true">🔒</span>
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
                                {{ showPassword ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <p v-if="validationErrors.password" class="farmer-app__new-password-error">{{ validationErrors.password }}</p>
                    </div>

                    <div class="farmer-app__new-password-field">
                        <span>{{ t('newPassword.confirm_password') }}</span>
                        <div class="farmer-app__new-password-input-wrap">
                            <span class="farmer-app__new-password-input-icon" aria-hidden="true">🛡</span>
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
                                {{ showPasswordConfirmation ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <p v-if="validationErrors.password_confirmation" class="farmer-app__new-password-error">{{ validationErrors.password_confirmation }}</p>
                    </div>

                    <AppState v-if="error" type="error" :message="error" />
                    <AppState v-if="success" :message="success" />
                    <div class="farmer-app__new-password-help">
                        <p>{{ t('newPassword.otp_help') }}</p>
                    </div>

                    <button type="submit" class="farmer-app__btn farmer-app__new-password-primary" :disabled="loading">
                        {{ loading ? t('newPassword.resetting') : t('newPassword.reset_password') }}
                    </button>

                    <RouterLink :to="{ name: 'login' }" class="farmer-app__new-password-back">
                        {{ t('newPassword.back_to_login') }}
                    </RouterLink>
                </form>
            </section>

            <footer class="farmer-app__new-password-footer">{{ t('newPassword.secure_footer') }}</footer>
        </main>
    </div>
</template>
