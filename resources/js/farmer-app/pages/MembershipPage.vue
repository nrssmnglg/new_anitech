<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { useLocale } from '../composables/useLocale';
import { apiGet } from '../services/api';
import { useApiPage } from '../composables/useApiPage';
import { formatDateTime } from '../utils/navigation';

const { t } = useLocale();
const membership = ref(null);
const { error, loading, run } = useApiPage(async () => {
    const response = await apiGet('/membership');
    membership.value = response?.data ?? null;
});
const membershipBadge = computed(() => membership.value?.membership_status ?? t('membership.unknown'));
const memberType = computed(() => membership.value?.member_type?.name ?? t('membership.not_available'));
const memberCode = computed(() => membership.value?.member_type?.code ?? t('membership.not_available'));
const latestRenewalTitle = computed(() => membership.value?.latest_renewal ? `Year ${membership.value.latest_renewal.year}` : t('membership.no_renewal_record'));
const latestRenewalStatus = computed(() => membership.value?.latest_renewal?.status ?? t('membership.no_renewal_submitted'));
const detailItems = computed(() => membership.value ? [
    { label: t('membership.registered_at'), value: membership.value.registered_at ? formatDateTime(membership.value.registered_at) : t('membership.not_available') },
    { label: t('membership.activated_at'), value: membership.value.activated_at ? formatDateTime(membership.value.activated_at) : t('membership.not_available') },
] : []);
onMounted(() => run());
</script>

<template>
    <div class="farmer-app__membership-screen">
        <main class="farmer-app__membership-shell">
            <AppLoader v-if="loading && !membership" />
            <AppState v-else-if="error && !membership" type="error" :message="error" />
            <div v-else-if="membership" class="farmer-app__membership-stack">
                <AppState v-if="error" type="error" :message="error" />
                <header class="farmer-app__membership-header">
                    <h1>{{ t('membership.title') }}</h1>
                    <span class="farmer-app__membership-badge">{{ membershipBadge }}</span>
                </header>
                <section class="farmer-app__membership-grid">
                    <article class="farmer-app__membership-hero-card">
                        <div class="farmer-app__membership-hero-main">
                            <div class="farmer-app__membership-hero-icon">ID</div>
                            <div><span>{{ t('membership.member_identity') }}</span><h2>{{ memberType }}</h2></div>
                        </div>
                        <div class="farmer-app__membership-hero-code">
                            <span>{{ t('membership.registration_code') }}</span>
                            <strong>{{ membership.farmer_code }}</strong>
                            <small>{{ memberCode }}</small>
                        </div>
                    </article>
                    <article class="farmer-app__membership-info-card">
                        <span>{{ t('membership.registry_status') }}</span>
                        <strong>{{ membership.status }}</strong>
                        <p>{{ membership.membership_status }}</p>
                    </article>
                    <article class="farmer-app__membership-info-card">
                        <span>{{ t('membership.latest_renewal') }}</span>
                        <strong>{{ latestRenewalTitle }}</strong>
                        <p>{{ latestRenewalStatus }}</p>
                    </article>
                </section>
                <section class="farmer-app__membership-details-card">
                    <h2>{{ t('membership.registration_details') }}</h2>
                    <div class="farmer-app__membership-detail-grid">
                        <div v-for="item in detailItems" :key="item.label" class="farmer-app__membership-detail-item">
                            <span>{{ item.label }}</span><strong>{{ item.value }}</strong>
                        </div>
                    </div>
                </section>
                <section class="farmer-app__membership-actions">
                    <RouterLink :to="{ name: 'renewals' }" class="farmer-app__membership-primary-action">{{ t('membership.renewal_history') }}</RouterLink>
                </section>
            </div>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__membership-screen { min-height: 100%; background: transparent; }
.farmer-app__membership-shell { position: relative; width: 100%; max-width: 760px; margin: 0 auto; padding: 12px 12px 28px; }
.farmer-app__membership-stack { display: grid; gap: 8px; }
.farmer-app__membership-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 2px 4px; }
.farmer-app__membership-header h1 { margin: 0; color: var(--pwa-ink); font-size: 1.35rem; line-height: 1.2; letter-spacing: -.025em; }
.farmer-app__membership-badge { display: inline-flex; align-items: center; min-height: 25px; padding: 0 9px; border-radius: 999px; background: #eaf7f1; color: var(--pwa-green-800); font-size: .65rem; font-weight: 800; text-transform: capitalize; }
.farmer-app__membership-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 8px; }
.farmer-app__membership-hero-card { grid-column: 1/-1; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px; background: var(--pwa-green-900); border: 0; border-radius: 12px; color: #fff; box-shadow: none; }
.farmer-app__membership-hero-main { display: flex; align-items: center; gap: 9px; min-width: 0; }
.farmer-app__membership-hero-icon { width: 36px; height: 36px; display: grid; place-items: center; flex: 0 0 auto; border-radius: 9px; background: rgba(255,255,255,.14); font-size: .7rem; font-weight: 900; }
.farmer-app__membership-hero-main span,.farmer-app__membership-hero-code span,.farmer-app__membership-hero-code small { display: block; color: rgba(255,255,255,.72); font-size: .62rem; }
.farmer-app__membership-hero-main h2 { margin: 2px 0 0; color: #fff; font-size: .9rem; line-height: 1.3; }
.farmer-app__membership-hero-code { min-width: 0; text-align: right; }
.farmer-app__membership-hero-code strong { display: block; margin: 2px 0; color: #fff; font-size: .78rem; overflow-wrap: anywhere; }
.farmer-app__membership-info-card { display: grid; gap: 3px; min-width: 0; padding: 10px; background: #fff; border: 1px solid var(--pwa-border); border-radius: 10px; box-shadow: none; }
.farmer-app__membership-info-card>span,.farmer-app__membership-detail-item span { color: var(--pwa-muted); font-size: .65rem; }
.farmer-app__membership-info-card>strong,.farmer-app__membership-detail-item strong { color: var(--pwa-ink); font-size: .78rem; line-height: 1.35; overflow-wrap: anywhere; }
.farmer-app__membership-info-card>p { margin: 0; color: var(--pwa-muted); font-size: .67rem; line-height: 1.35; }
.farmer-app__membership-details-card { padding: 12px; background: #fff; border: 1px solid var(--pwa-border); border-radius: 12px; box-shadow: none; }
.farmer-app__membership-details-card h2 { margin: 0 0 8px; color: var(--pwa-ink); font-size: .86rem; }
.farmer-app__membership-detail-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); border-top: 1px solid var(--pwa-border); }
.farmer-app__membership-detail-item { display: grid; gap: 2px; padding: 9px 6px; border-bottom: 1px solid var(--pwa-border); }
.farmer-app__membership-actions { display: flex; justify-content: flex-end; }
.farmer-app__membership-primary-action { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 14px; border-radius: 9px; background: var(--pwa-green-800); color: #fff; font-size: .74rem; font-weight: 800; text-decoration: none; }
@media (max-width:420px) { .farmer-app__membership-hero-card { align-items: flex-start; } .farmer-app__membership-hero-code { max-width: 48%; } }
</style>
