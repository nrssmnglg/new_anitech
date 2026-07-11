<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { useCachedResource } from '../composables/useCachedResource';
import { useLocale } from '../composables/useLocale';
import { useAppStore } from '../stores/app';
import { apiGet, apiPost } from '../services/api';
import { formatDateTime } from '../utils/navigation';

const app = useAppStore();
const { t } = useLocale();
const router = useRouter();
const reactingId = ref(null);
let dashboardPollTimer = null;

const { data, error, loading, fetchFresh, hydrateFromCache } = useCachedResource('dashboard-advisories', async () => {
    const response = await apiGet('/advisories', { params: { page: 1 } });
    return {
        advisories: response?.data ?? [],
        meta: response?.meta ?? null,
    };
});

const payload = computed(() => data.value ?? { advisories: [], meta: null });
const advisories = computed(() => payload.value.advisories ?? []);
const logoUrl = '/figures/anitech-mark-official.svg';

const advisoryTone = (advisory) => {
    const audience = String(advisory.audience_type ?? '').toLowerCase();
    const title = String(advisory.title ?? '').toLowerCase();

    if (title.includes('warning') || title.includes('urgent') || title.includes('alert')) {
        return 'urgent';
    }

    if (audience === 'group' || audience === 'barangay') {
        return 'warning';
    }

    return 'info';
};

const advisoryTag = (advisory) => {
    const tone = advisoryTone(advisory);

    if (tone === 'urgent') {
        return 'Urgent';
    }

    if (tone === 'warning') {
        return 'Notice';
    }

    return 'Info';
};

const advisoryMeta = (advisory) => {
    return advisory.member_type?.name || advisory.barangay?.name || advisory.audience_type || 'General advisory';
};

const advisoryExcerpt = (advisory) => {
    const text = String(advisory.content ?? '').replace(/\s+/g, ' ').trim();

    if (text.length <= 180) {
        return text;
    }

    return `${text.slice(0, 177)}...`;
};

const advisoryReaction = (advisory) => advisory.farmer_reaction ?? null;

const setReaction = async (advisory, reaction) => {
    reactingId.value = advisory.id;

    try {
        const response = await apiPost(`/advisories/${advisory.id}/reaction`, { reaction });
        const updated = response?.data;

        if (!updated) {
            return;
        }

        data.value = {
            ...payload.value,
            advisories: advisories.value.map((item) => (
                item.id === advisory.id
                    ? { ...item, ...updated }
                    : item
            )),
        };
    } finally {
        reactingId.value = null;
    }
};

const openAdvisory = (advisoryId) => {
    router.push({ name: 'advisory-detail', params: { advisoryId } });
};

onMounted(() => {
    hydrateFromCache();
    fetchFresh();
    startDashboardPolling();
    document.addEventListener('visibilitychange', refreshDashboard);
    window.addEventListener('focus', refreshDashboard);
});

function refreshDashboard() {
    if (document.visibilityState !== 'visible') {
        return;
    }

    fetchFresh().catch(() => {});
}

function startDashboardPolling() {
    stopDashboardPolling();

    dashboardPollTimer = window.setInterval(() => {
        refreshDashboard();
    }, app.lowDataMode ? 90000 : 60000);
}

function stopDashboardPolling() {
    if (dashboardPollTimer) {
        window.clearInterval(dashboardPollTimer);
        dashboardPollTimer = null;
    }
}

onBeforeUnmount(() => {
    stopDashboardPolling();
    document.removeEventListener('visibilitychange', refreshDashboard);
    window.removeEventListener('focus', refreshDashboard);
});
</script>

<template>
    <div class="farmer-app__dashboard-screen">
        <div class="farmer-app__dashboard-backdrop"></div>

        <main class="farmer-app__dashboard-shell">
            <AppLoader v-if="loading && !advisories.length" />
            <AppState v-else-if="error && !advisories.length" type="error" :message="error" :action-label="t('common.retry')" @action="fetchFresh" />

            <div class="farmer-app__dashboard-stack">
                <AppState v-if="error && advisories.length" type="error" :message="error" :action-label="t('common.retry')" @action="fetchFresh" />

                <section v-if="advisories.length" class="farmer-app__dashboard-feed-list">
                    <article
                        v-for="advisory in advisories"
                        :key="advisory.id"
                        class="farmer-app__dashboard-feed-card"
                        @click="openAdvisory(advisory.id)"
                    >
                        <div class="farmer-app__dashboard-feed-publisher">
                            <img :src="logoUrl" alt="AniTech" class="farmer-app__dashboard-feed-logo">
                            <div class="farmer-app__dashboard-feed-publisher-copy">
                                <strong>AniTech</strong>
                                <small>{{ formatDateTime(advisory.published_at) }}</small>
                            </div>
                        </div>

                        <div class="farmer-app__dashboard-feed-card-body">
                            <span class="farmer-app__dashboard-feed-pill" :class="`is-${advisoryTone(advisory)}`">
                                {{ advisoryTag(advisory) }}
                            </span>
                            <strong>{{ advisory.title }}</strong>
                            <p>{{ advisoryExcerpt(advisory) || advisoryMeta(advisory) }}</p>
                        </div>

                        <div class="farmer-app__dashboard-feed-actions">
                            <button
                                type="button"
                                class="farmer-app__dashboard-feed-action"
                                :class="{ 'is-active': advisoryReaction(advisory) === 'like' }"
                                :disabled="reactingId === advisory.id"
                                @click.stop="setReaction(advisory, 'like')"
                            >
                                <span aria-hidden="true">👍</span>
                                <span>{{ advisory.like_count ?? 0 }}</span>
                            </button>
                            <button
                                type="button"
                                class="farmer-app__dashboard-feed-action"
                                :class="{ 'is-active': advisoryReaction(advisory) === 'dislike' }"
                                :disabled="reactingId === advisory.id"
                                @click.stop="setReaction(advisory, 'dislike')"
                            >
                                <span aria-hidden="true">👎</span>
                                <span>{{ advisory.dislike_count ?? 0 }}</span>
                            </button>
                        </div>
                    </article>
                </section>

                <AppState v-else-if="!loading" message="No advisories available." :action-label="t('common.refresh')" @action="fetchFresh" />
            </div>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__dashboard-feed-card {
    display: grid;
    gap: 14px;
    padding: 18px;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid rgba(22, 63, 49, 0.08);
    box-shadow: 0 14px 30px rgba(20, 48, 37, 0.06);
    backdrop-filter: blur(10px);
    cursor: pointer;
}

.farmer-app__dashboard-feed-list {
    display: grid;
    gap: 14px;
}

.farmer-app__dashboard-feed-publisher {
    display: flex;
    align-items: center;
    gap: 12px;
}

.farmer-app__dashboard-feed-logo {
    width: 42px;
    height: 42px;
    border-radius: 999px;
    padding: 7px;
    background: #eff7f1;
    border: 1px solid rgba(22, 63, 49, 0.08);
}

.farmer-app__dashboard-feed-publisher-copy {
    display: grid;
    gap: 2px;
}

.farmer-app__dashboard-feed-publisher-copy strong {
    color: #10231b;
    font-size: 0.98rem;
}

.farmer-app__dashboard-feed-publisher-copy small {
    color: #737874;
    font-size: 0.8rem;
}

.farmer-app__dashboard-feed-pill {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
}

.farmer-app__dashboard-feed-pill.is-info {
    background: #edf7f1;
    color: #1a6b4a;
}

.farmer-app__dashboard-feed-pill.is-warning {
    background: #fff8e6;
    color: #8a6500;
}

.farmer-app__dashboard-feed-pill.is-urgent {
    background: #fff0ef;
    color: #b33d34;
}

.farmer-app__dashboard-feed-card-body {
    display: grid;
    gap: 10px;
}

.farmer-app__dashboard-feed-card-body strong {
    color: #10231b;
    font-size: 1.05rem;
    line-height: 1.35;
}

.farmer-app__dashboard-feed-card-body p {
    margin: 0;
    color: #737874;
    line-height: 1.5;
}

.farmer-app__dashboard-feed-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    padding-top: 6px;
    border-top: 1px solid rgba(22, 63, 49, 0.08);
}

.farmer-app__dashboard-feed-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    border: 1px solid rgba(22, 63, 49, 0.08);
    border-radius: 14px;
    background: #fff;
    color: #365247;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

.farmer-app__dashboard-feed-action.is-active {
    background: #edf7f1;
    color: #1a6b4a;
    border-color: rgba(26, 107, 74, 0.18);
}
</style>
