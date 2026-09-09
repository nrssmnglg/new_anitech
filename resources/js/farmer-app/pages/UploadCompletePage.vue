<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppLoader from '../components/ui/AppLoader.vue';
import { farmerApi } from '../services/api';

const route = useRoute();
const router = useRouter();
import { brandLogoUrl } from '../utils/asset';

const brandLogo = brandLogoUrl();
const applicationNumber = computed(() => String(route.query.application_no ?? '').trim());
const birthDate = computed(() => String(route.query.birth_date ?? '').trim());
const checkingSubmission = ref(true);
const canShowConfirmation = ref(false);

const trackTarget = computed(() => ({
    name: 'track-status',
    query: {
        application_no: String(route.query.application_no ?? ''),
        birth_date: String(route.query.birth_date ?? ''),
    },
}));

onMounted(async () => {
    if (!applicationNumber.value || !birthDate.value) {
        await router.replace({ name: 'upload' });
        return;
    }

    try {
        const response = await farmerApi.get('/application/track', {
            params: {
                application_no: applicationNumber.value,
                birth_date: birthDate.value,
            },
        });
        const documents = response?.data?.data?.documents ?? [];
        canShowConfirmation.value = documents.length > 0 && documents.every((document) => document.uploaded);

        if (!canShowConfirmation.value) {
            await router.replace({
                name: 'upload',
                query: {
                    application_no: applicationNumber.value,
                    birth_date: birthDate.value,
                },
            });
        }
    } catch {
        await router.replace({ name: 'upload' });
    } finally {
        checkingSubmission.value = false;
    }
});
</script>

<template>
    <div class="farmer-app__upload-complete-screen">
        <main class="farmer-app__upload-complete-shell">
            <AppLoader v-if="checkingSubmission" />
            <template v-else-if="canShowConfirmation">
            <header class="farmer-app__upload-complete-brand">
                <img :src="brandLogo" alt="AniTech logo">
                <div><strong>AniTech</strong><span>Farmer Portal</span></div>
            </header>

            <section class="farmer-app__upload-complete-card">
                <div class="farmer-app__upload-complete-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="m7.5 12 3 3 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>

                <div class="farmer-app__upload-complete-copy">
                    <h1>Documents Submitted</h1>
                    <strong v-if="applicationNumber" class="farmer-app__upload-complete-reference">{{ applicationNumber }}</strong>
                </div>

                <div class="farmer-app__upload-complete-status">
                    <div class="farmer-app__upload-complete-status-head">
                        <span class="farmer-app__upload-complete-pulse"></span>
                        <strong>Waiting For Office Approval</strong>
                    </div>
                    <p>The City Agriculture Office will review your application. Check the tracking page for updates.</p>
                </div>

                <RouterLink :to="trackTarget" class="farmer-app__btn farmer-app__upload-complete-primary">
                    View Application Status
                </RouterLink>
            </section>
            </template>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__upload-complete-screen { min-height: 100dvh; display: block; background: #f3f7f4; }
.farmer-app__upload-complete-shell { width: min(100%, 390px); min-height: 100dvh; margin: 0 auto; padding: 24px 16px; display: flex; flex-direction: column; justify-content: center; }
.farmer-app__upload-complete-brand { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 14px; color: inherit; }
.farmer-app__upload-complete-brand img { width: 44px; height: 44px; object-fit: contain; }
.farmer-app__upload-complete-brand strong, .farmer-app__upload-complete-brand span { display: block; }
.farmer-app__upload-complete-brand strong { color: var(--pwa-green-900); font-size: 1.15rem; }
.farmer-app__upload-complete-brand span { color: var(--pwa-muted); font-size: .7rem; }
.farmer-app__upload-complete-card { display: grid; justify-items: stretch; gap: 14px; width: 100%; padding: 18px; border: 1px solid var(--pwa-border); border-radius: 12px; background: #fff; box-shadow: var(--pwa-shadow-soft); text-align: left; }
.farmer-app__upload-complete-icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 10px; background: #e9f7ee; color: var(--pwa-green-700); box-shadow: none; animation: none; }
.farmer-app__upload-complete-icon svg { width: 24px; height: 24px; }
.farmer-app__upload-complete-copy h1 { margin: 0; color: var(--pwa-ink); font-size: 1.2rem; line-height: 1.3; }
.farmer-app__upload-complete-copy p { margin: 4px 0 0; color: var(--pwa-muted); font-size: .8rem; line-height: 1.45; }
.farmer-app__upload-complete-reference { display: inline-flex; margin-top: 9px; padding: 5px 8px; border-radius: 7px; background: var(--pwa-surface-soft); color: var(--pwa-green-800); font-size: .72rem; }
.farmer-app__upload-complete-status { display: grid; gap: 7px; padding: 11px; border: 1px solid var(--pwa-border); border-radius: 9px; background: var(--pwa-surface-soft); text-align: left; }
.farmer-app__upload-complete-status-head { justify-content: flex-start; gap: 7px; }
.farmer-app__upload-complete-status-head strong { color: var(--pwa-green-800); font-size: .76rem; }
.farmer-app__upload-complete-pulse { width: 7px; height: 7px; animation: none; }
.farmer-app__upload-complete-status p { margin: 0; color: var(--pwa-muted); font-size: .75rem; line-height: 1.45; }
.farmer-app__upload-complete-primary { width: 100%; min-height: 46px; display: grid; place-items: center; padding: 10px 14px; border-radius: 10px; background: var(--pwa-green-800); color: #fff; text-decoration: none; box-shadow: none; }
</style>
