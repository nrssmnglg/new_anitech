<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppState from '../components/ui/AppState.vue';
import { useLocale } from '../composables/useLocale';
import { useAuth } from '../composables/useAuth';

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const { t } = useLocale();
import { brandLogoUrl } from '../utils/asset';

const brandLogo = brandLogoUrl();
const createdEmail = ref('');
const successMessage = ref('');
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const form = reactive({
    application_no: String(route.query.application_no ?? ''),
    birth_date: String(route.query.birth_date ?? ''),
    email: '',
    password: '',
    password_confirmation: '',
});
const hasLockedLookup = computed(() => Boolean(String(route.query.application_no ?? '') && String(route.query.birth_date ?? '')));

const canSubmit = computed(() => {
    return form.application_no && form.birth_date && form.email && form.password && form.password_confirmation;
});

watch(
    () => route.query,
    (query) => {
        if (!hasLockedLookup.value) {
            return;
        }

        form.application_no = String(query.application_no ?? form.application_no ?? '');
        form.birth_date = String(query.birth_date ?? form.birth_date ?? '');
    },
    { deep: true },
);

const submit = async () => {
    if (!canSubmit.value || auth.loading.value) {
        return;
    }

    successMessage.value = '';
    createdEmail.value = '';

    try {
        const response = await auth.setup(form);
        createdEmail.value = response?.data?.account?.email ?? form.email;
        successMessage.value = response?.message ?? t('accountSetup.success');
        setTimeout(() => {
            router.push({ name: 'login' });
        }, 1200);
    } catch {}
};
</script>

<template>
    <div class="farmer-app__setup-screen">
        <div class="farmer-app__setup-backdrop"></div>

        <main class="farmer-app__setup-shell">
            <section class="farmer-app__setup-card">
                <div class="farmer-app__setup-brand">
                    <div class="farmer-app__setup-mark">
                        <img :src="brandLogo" alt="AniTech logo" />
                    </div>
                    <h1>{{ t('accountSetup.title') }}</h1>
                    <p>{{ t('accountSetup.subtitle') }}</p>
                </div>

                <div class="farmer-app__setup-progress" aria-hidden="true">
                    <span class="is-active"></span>
                    <span></span>
                    <span></span>
                </div>

                <form class="farmer-app__setup-form" @submit.prevent="submit">
                    <div class="farmer-app__setup-grid">
                        <div class="farmer-app__setup-field">
                            <label for="application_no">Application Number</label>
                            <div class="farmer-app__setup-input-wrap">
                                <span class="farmer-app__setup-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M7 6h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="M9 10h6M9 14h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <input id="application_no" v-model="form.application_no" type="text" placeholder="APP-82910" :readonly="hasLockedLookup" autocomplete="off" />
                            </div>
                            <p v-if="auth.validationErrors.value.application_no" class="farmer-app__setup-error">
                                {{ auth.validationErrors.value.application_no }}
                            </p>
                        </div>

                        <div class="farmer-app__setup-field">
                            <label for="birth_date">Birth Date</label>
                            <div class="farmer-app__setup-input-wrap">
                                <span class="farmer-app__setup-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <rect x="5" y="6" width="14" height="13" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="M8 4.5v3M16 4.5v3M5 10h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <input id="birth_date" v-model="form.birth_date" type="date" :readonly="hasLockedLookup" autocomplete="off" />
                            </div>
                            <p v-if="auth.validationErrors.value.birth_date" class="farmer-app__setup-error">
                                {{ auth.validationErrors.value.birth_date }}
                            </p>
                        </div>
                    </div>

                    <div class="farmer-app__setup-field">
                        <label for="setup_email">Email Address</label>
                        <div class="farmer-app__setup-input-wrap">
                            <span class="farmer-app__setup-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 7.5A1.5 1.5 0 0 1 5.5 6h13A1.5 1.5 0 0 1 20 7.5v9A1.5 1.5 0 0 1 18.5 18h-13A1.5 1.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="m5.5 7 6.5 5 6.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <input
                                id="setup_email"
                                v-model="form.email"
                                type="email"
                                placeholder="farmer@example.com"
                                autocomplete="email"
                                autocapitalize="off"
                                autocorrect="off"
                                spellcheck="false"
                                @keydown.enter.prevent.stop
                            />
                        </div>
                        <p v-if="auth.validationErrors.value.email" class="farmer-app__setup-error">
                            {{ auth.validationErrors.value.email }}
                        </p>
                    </div>

                    <div class="farmer-app__setup-grid">
                        <div class="farmer-app__setup-field">
                            <label for="setup_password">Password</label>
                            <div class="farmer-app__setup-input-wrap">
                                <span class="farmer-app__setup-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M7.5 10V8a4.5 4.5 0 1 1 9 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <rect x="5" y="10" width="14" height="10" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </span>
                                <input
                                    id="setup_password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Minimum 8 characters"
                                    @keydown.enter.prevent
                                    autocomplete="new-password"
                                />
                            </div>
                            <p v-if="auth.validationErrors.value.password" class="farmer-app__setup-error">
                                {{ auth.validationErrors.value.password }}
                            </p>
                        </div>

                        <div class="farmer-app__setup-field">
                            <label for="setup_password_confirmation">Confirm Password</label>
                            <div class="farmer-app__setup-input-wrap">
                                <span class="farmer-app__setup-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M7.5 10V8a4.5 4.5 0 1 1 9 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <rect x="5" y="10" width="14" height="10" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="m9.5 15 1.7 1.7 3.3-3.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <input
                                    id="setup_password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    placeholder="Repeat password"
                                    @keydown.enter.prevent
                                    autocomplete="new-password"
                                />
                            </div>
                            <p v-if="auth.validationErrors.value.password_confirmation" class="farmer-app__setup-error">
                                {{ auth.validationErrors.value.password_confirmation }}
                            </p>
                        </div>
                    </div>

                    <AppState v-if="auth.error.value" type="error" :message="auth.error.value" />
                    <AppState v-if="successMessage" :message="t('accountSetup.redirecting', { message: successMessage, email: createdEmail })" />

                    <div class="farmer-app__setup-actions">
                        <button type="submit" class="farmer-app__btn farmer-app__setup-primary" :disabled="auth.loading.value || !canSubmit">
                            {{ auth.loading.value ? t('accountSetup.creating') : t('accountSetup.create_account') }}
                        </button>
                        <RouterLink :to="{ name: 'login' }" class="farmer-app__setup-secondary">{{ t('accountSetup.back_to_login') }}</RouterLink>
                    </div>
                </form>

                <p class="farmer-app__setup-help">
                    {{ t('accountSetup.help') }}
                    <a href="#">{{ t('accountSetup.contact_support') }}</a>
                </p>
            </section>
        </main>
    </div>
</template>
