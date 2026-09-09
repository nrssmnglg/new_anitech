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
        <main class="farmer-app__setup-shell">
            <header class="farmer-app__setup-brand">
                <div class="farmer-app__setup-mark"><img :src="brandLogo" alt="AniTech logo" /></div>
                <div><strong>AniTech</strong><span>Farmer Portal</span></div>
            </header>
            <section class="farmer-app__setup-card">
                <div class="farmer-app__setup-heading">
                    <h1>{{ t('accountSetup.title') }}</h1>
                    <p>{{ t('accountSetup.subtitle') }}</p>
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
                                <input id="birth_date" v-model="form.birth_date" type="date" class="farmer-app__date-input" :readonly="hasLockedLookup" autocomplete="off" />
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
                                    autocomplete="new-password"
                                />
                                <button type="button" class="farmer-app__setup-toggle" :aria-label="showPassword ? 'Hide password' : 'Show password'" @click="showPassword = !showPassword">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.5 12c0-1.8 3.3-7 8.5-7s8.5 5.2 8.5 7-3.3 7-8.5 7-8.5-5.2-8.5-7Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                                </button>
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
                                    autocomplete="new-password"
                                />
                                <button type="button" class="farmer-app__setup-toggle" :aria-label="showPasswordConfirmation ? 'Hide password' : 'Show password'" @click="showPasswordConfirmation = !showPasswordConfirmation">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3.5 12c0-1.8 3.3-7 8.5-7s8.5 5.2 8.5 7-3.3 7-8.5 7-8.5-5.2-8.5-7Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                                </button>
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

            </section>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__setup-screen {
    min-height: 100dvh;
    background: #f3f7f4;
}

.farmer-app__setup-shell {
    width: min(100%, 430px);
    min-height: 100dvh;
    margin: 0 auto;
    padding: 22px 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.farmer-app__setup-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 14px;
    text-align: left;
}

.farmer-app__setup-mark {
    width: 44px;
    height: 44px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}

.farmer-app__setup-mark img { width: 44px; height: 44px; object-fit: contain; }
.farmer-app__setup-brand strong, .farmer-app__setup-brand span { display: block; }
.farmer-app__setup-brand strong { color: var(--pwa-green-900); font-size: 1.15rem; }
.farmer-app__setup-brand span { color: var(--pwa-muted); font-size: .7rem; }

.farmer-app__setup-card {
    padding: 18px;
    border: 1px solid var(--pwa-border);
    border-radius: 12px;
    background: #fff;
    box-shadow: var(--pwa-shadow-soft);
}

.farmer-app__setup-heading { margin-bottom: 16px; }
.farmer-app__setup-heading h1 { margin: 0; color: var(--pwa-ink); font-size: 1.2rem; line-height: 1.3; }
.farmer-app__setup-heading p { margin: 4px 0 0; color: var(--pwa-muted); font-size: .8rem; line-height: 1.45; }
.farmer-app__setup-form { display: grid; gap: 13px; }
.farmer-app__setup-grid { display: grid; grid-template-columns: 1fr; gap: 13px; }
.farmer-app__setup-field { display: grid; gap: 6px; }
.farmer-app__setup-field label { color: var(--pwa-ink); font-size: .77rem; font-weight: 700; }

.farmer-app__setup-input-wrap {
    position: relative;
    min-height: 46px;
    border: 1px solid var(--pwa-border);
    border-radius: 10px;
    background: #fff;
    box-shadow: none;
}

.farmer-app__setup-input-wrap:focus-within { border-color: var(--pwa-green-700); box-shadow: 0 0 0 3px rgba(35, 117, 84, .12); }
.farmer-app__setup-input-icon { position: absolute; z-index: 1; left: 13px; top: 50%; width: 18px; height: 18px; transform: translateY(-50%); color: var(--pwa-muted); pointer-events: none; }
.farmer-app__setup-input-icon svg { display: block; width: 18px; height: 18px; }
.farmer-app__setup-input-wrap input { width: 100%; min-height: 44px; padding: 10px 42px !important; border: 0; outline: 0; background: transparent; color: var(--pwa-ink); font: inherit; font-size: .85rem; }
.farmer-app__setup-input-wrap input[readonly] { color: var(--pwa-muted); background: #f5f8f6; }

.farmer-app__setup-toggle {
    position: absolute;
    z-index: 2;
    top: 50%;
    right: 1px;
    width: 44px;
    height: 44px;
    padding: 12px;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: var(--pwa-muted);
    cursor: pointer;
}
.farmer-app__setup-toggle svg { display: block; width: 19px; height: 19px; }
.farmer-app__setup-error { margin: 0; color: var(--pwa-danger); font-size: .74rem; }
.farmer-app__setup-actions { display: grid; gap: 8px; margin-top: 2px; }
.farmer-app__setup-primary { width: 100%; min-height: 46px; border-radius: 10px; background: var(--pwa-green-800); color: #fff; box-shadow: none; }
.farmer-app__setup-secondary { min-height: 44px; display: grid; place-items: center; border: 1px solid var(--pwa-border); border-radius: 10px; color: var(--pwa-green-800); text-decoration: none; font-size: .8rem; font-weight: 700; }

@media (min-width: 700px) {
    .farmer-app__setup-shell { width: min(100%, 660px); }
    .farmer-app__setup-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
