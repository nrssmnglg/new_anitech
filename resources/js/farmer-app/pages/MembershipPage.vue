<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { useLocale } from '../composables/useLocale';
import { apiGet } from '../services/api';
import { useApiPage } from '../composables/useApiPage';
import { formatDateTime } from '../utils/navigation';

import { brandLogoUrl } from '../utils/asset';

const brandLogo = brandLogoUrl();
const { t } = useLocale();
const membership = ref(null);
const { error, loading, run } = useApiPage(async () => {
    const response = await apiGet('/membership');
    membership.value = response?.data ?? null;
});

const membershipBadge = computed(() => membership.value?.membership_status ?? t('membership.unknown'));
const memberType = computed(() => membership.value?.member_type?.name ?? t('membership.not_available'));
const memberCode = computed(() => membership.value?.member_type?.code ?? t('membership.not_available'));
const latestRenewalTitle = computed(() => {
    if (!membership.value?.latest_renewal) {
        return t('membership.no_renewal_record');
    }

    return `Year ${membership.value.latest_renewal.year}`;
});
const latestRenewalStatus = computed(() => membership.value?.latest_renewal?.status ?? t('membership.no_renewal_submitted'));
const detailItems = computed(() => {
    if (!membership.value) {
        return [];
    }

    return [
        { label: t('membership.farmer_code'), value: membership.value.farmer_code || t('membership.not_available') },
        { label: t('membership.registry_status'), value: membership.value.status || t('membership.unknown') },
        { label: t('membership.registered_at'), value: membership.value.registered_at ? formatDateTime(membership.value.registered_at) : t('membership.not_available') },
        { label: t('membership.activated_at'), value: membership.value.activated_at ? formatDateTime(membership.value.activated_at) : t('membership.not_available') },
    ];
});

onMounted(() => run());
</script>

<template>
    <div class="farmer-app__membership-screen">
        <div class="farmer-app__membership-backdrop"></div>

        <main class="farmer-app__membership-shell">
            <section class="farmer-app__membership-topbar">
                <div class="farmer-app__membership-brand">
                    <div class="farmer-app__membership-brand-mark">
                        <img :src="brandLogo" alt="AniTech logo" />
                    </div>
                    <span>AniTech</span>
                </div>
                <button type="button" class="farmer-app__membership-bell">◌</button>
            </section>

            <AppLoader v-if="loading" />
            <AppState v-else-if="error" type="error" :message="error" />

            <div v-else-if="membership" class="farmer-app__membership-stack">
                <header class="farmer-app__membership-header">
                    <div>
                        <div class="farmer-app__membership-title-row">
                            <h1>{{ t('membership.title') }}</h1>
                            <span class="farmer-app__membership-badge">{{ membershipBadge }}</span>
                        </div>
                        <p>{{ t('membership.subtitle') }}</p>
                    </div>
                </header>

                <section class="farmer-app__membership-grid">
                    <article class="farmer-app__membership-hero-card">
                        <div class="farmer-app__membership-hero-main">
                            <div class="farmer-app__membership-hero-icon">ID</div>
                            <div>
                                <span>{{ t('membership.member_identity') }}</span>
                                <h2>{{ memberType }}</h2>
                            </div>
                        </div>

                        <div class="farmer-app__membership-hero-code">
                            <span>{{ t('membership.registration_code') }}</span>
                            <strong>{{ membership.farmer_code }}</strong>
                            <small>{{ memberCode }}</small>
                        </div>
                    </article>

                    <article class="farmer-app__membership-info-card">
                        <div class="farmer-app__membership-info-head">
                            <div class="farmer-app__membership-info-icon farmer-app__membership-info-icon--success">✓</div>
                            <span>{{ t('membership.registry_status') }}</span>
                        </div>
                        <strong>{{ membership.status }}</strong>
                        <p>{{ membership.membership_status }}</p>
                    </article>

                    <article class="farmer-app__membership-info-card">
                        <div class="farmer-app__membership-info-head">
                            <div class="farmer-app__membership-info-icon">⟳</div>
                            <span>{{ t('membership.latest_renewal') }}</span>
                        </div>
                        <strong>{{ latestRenewalTitle }}</strong>
                        <p>{{ latestRenewalStatus }}</p>
                    </article>
                </section>

                <section class="farmer-app__membership-details-card">
                    <div class="farmer-app__membership-section-head">
                        <h2>{{ t('membership.registration_details') }}</h2>
                        <p>{{ t('membership.registration_copy') }}</p>
                    </div>

                    <div class="farmer-app__membership-detail-grid">
                        <div v-for="item in detailItems" :key="item.label" class="farmer-app__membership-detail-item">
                            <span>{{ item.label }}</span>
                            <strong>{{ item.value }}</strong>
                        </div>
                    </div>
                </section>

                <section class="farmer-app__membership-actions">
                    <button type="button" class="farmer-app__membership-primary-action">
                        {{ t('membership.download_certificate') }}
                    </button>
                    <button type="button" class="farmer-app__membership-secondary-action">
                        {{ t('membership.renewal_history') }}
                    </button>
                </section>
            </div>
        </main>
    </div>
</template>
