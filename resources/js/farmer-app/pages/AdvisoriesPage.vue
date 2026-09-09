<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import AppLoader from '../components/ui/AppLoader.vue';
import AppPagination from '../components/ui/AppPagination.vue';
import AppState from '../components/ui/AppState.vue';
import { usePaginatedFetch } from '../composables/usePaginatedFetch';
import { apiGet } from '../services/api';
import { formatDateTime } from '../utils/navigation';

const { items: advisories, meta, error, loading, fetchPage, fromCache, lastSyncedAt } = usePaginatedFetch(async (params) => {
    return apiGet('/advisories', { params });
}, {}, { cacheKey: 'advisories' });
let advisoriesPollTimer = null;

const totalAdvisories = computed(() => advisories.value.length);
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
        return 'Warning';
    }

    return 'Info';
};

const advisoryMeta = (advisory) => {
    const target = advisory.member_type?.name || advisory.barangay?.name || advisory.audience_type || 'General';
    return target;
};

function refreshAdvisories() {
    if (document.visibilityState !== 'visible') {
        return;
    }

    fetchPage().catch(() => {});
}

function startAdvisoriesPolling() {
    stopAdvisoriesPolling();

    advisoriesPollTimer = window.setInterval(() => {
        refreshAdvisories();
    }, 15000);
}

function stopAdvisoriesPolling() {
    if (advisoriesPollTimer) {
        window.clearInterval(advisoriesPollTimer);
        advisoriesPollTimer = null;
    }
}

onMounted(() => {
    fetchPage();
    startAdvisoriesPolling();
    document.addEventListener('visibilitychange', refreshAdvisories);
    window.addEventListener('focus', refreshAdvisories);
});

onBeforeUnmount(() => {
    stopAdvisoriesPolling();
    document.removeEventListener('visibilitychange', refreshAdvisories);
    window.removeEventListener('focus', refreshAdvisories);
});
</script>

<template>
    <div class="farmer-app__advisories-screen">
        <main class="farmer-app__advisories-shell">
            <header class="farmer-app__advisories-header">
                <div>
                    <span>Updates</span>
                    <h1>Advisories</h1>
                </div>
                <strong v-if="advisories.length">{{ totalAdvisories }} available</strong>
            </header>

            <AppLoader v-if="loading && !advisories.length" />
            <AppState v-else-if="error && !advisories.length" type="error" :message="error" action-label="Retry" @action="fetchPage()" />
            <AppState
                v-else-if="fromCache && advisories.length"
                :message="`Showing saved advisories${lastSyncedAt ? ` from ${formatDateTime(lastSyncedAt)}` : ''}.`"
                action-label="Refresh"
                @action="fetchPage()"
            />

            <div v-if="advisories.length" class="farmer-app__advisories-stack">
                <section class="farmer-app__advisories-list">
                    <RouterLink
                        v-for="advisory in advisories"
                        :key="advisory.id"
                        :to="{ name: 'advisory-detail', params: { advisoryId: advisory.id } }"
                        class="farmer-app__advisories-card"
                    >
                        <div class="farmer-app__advisories-card-head">
                            <span class="farmer-app__advisories-pill" :class="`is-${advisoryTone(advisory)}`">
                                {{ advisoryTag(advisory) }}
                            </span>
                            <span class="farmer-app__advisories-date">{{ formatDateTime(advisory.published_at) }}</span>
                        </div>

                        <div class="farmer-app__advisories-card-body">
                            <h2>{{ advisory.title }}</h2>
                            <p>{{ advisoryMeta(advisory) }}</p>
                        </div>

                        <div class="farmer-app__advisories-card-footer">
                            <span class="farmer-app__advisories-link">Read advisory</span>
                            <span class="farmer-app__advisories-icon" aria-hidden="true">›</span>
                        </div>
                    </RouterLink>
                </section>

                <div v-if="totalAdvisories" class="farmer-app__advisories-pagination-wrap">
                    <AppPagination :meta="meta" @change="(page) => fetchPage({ page })" />
                </div>
            </div>

            <AppState v-else-if="!loading" message="No advisories available." action-label="Refresh" @action="fetchPage()" />
        </main>
    </div>
</template>

<style scoped>
.farmer-app__advisories-screen {
    min-height: 100%;
    background: transparent;
}

.farmer-app__advisories-shell {
    position: relative;
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    padding: 12px 12px 28px;
    display: grid;
    gap: 10px;
}

.farmer-app__advisories-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    padding: 0 2px 2px;
}

.farmer-app__advisories-header div > span {
    display: block;
    margin-bottom: 2px;
    color: var(--pwa-muted);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.farmer-app__advisories-header h1 {
    margin: 0;
    color: var(--pwa-ink);
    font-size: 1.35rem;
    line-height: 1.2;
    letter-spacing: -0.025em;
}

.farmer-app__advisories-header > strong {
    color: var(--pwa-muted);
    font-size: 0.72rem;
    font-weight: 700;
}

.farmer-app__advisories-stack,
.farmer-app__advisories-list {
    display: grid;
    gap: 8px;
}

.farmer-app__advisories-card {
    display: grid;
    gap: 8px;
    padding: 12px;
    color: inherit;
    text-decoration: none;
    background: #fff;
    border: 1px solid var(--pwa-border);
    border-radius: 12px;
    box-shadow: none;
}

.farmer-app__advisories-card:active {
    background: var(--pwa-surface-soft);
}

.farmer-app__advisories-card-head,
.farmer-app__advisories-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.farmer-app__advisories-pill {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    background: #eef3f0;
    color: #52645d;
    font-size: 0.64rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.farmer-app__advisories-pill.is-urgent {
    background: #fff0f0;
    color: #b42318;
}

.farmer-app__advisories-pill.is-warning {
    background: #fff6df;
    color: #9a6200;
}

.farmer-app__advisories-pill.is-info {
    background: #eaf7f1;
    color: var(--pwa-green-800);
}

.farmer-app__advisories-date {
    color: var(--pwa-muted);
    font-size: 0.68rem;
}

.farmer-app__advisories-card-body h2 {
    margin: 0;
    color: var(--pwa-ink);
    font-size: 0.92rem;
    line-height: 1.35;
}

.farmer-app__advisories-card-body p {
    margin: 3px 0 0;
    color: var(--pwa-muted);
    font-size: 0.72rem;
    text-transform: capitalize;
}

.farmer-app__advisories-card-footer {
    padding-top: 8px;
    border-top: 1px solid var(--pwa-border);
}

.farmer-app__advisories-link {
    color: var(--pwa-green-800);
    font-size: 0.72rem;
    font-weight: 800;
}

.farmer-app__advisories-icon {
    color: var(--pwa-green-800);
    font-size: 1.15rem;
    line-height: 1;
}

.farmer-app__advisories-pagination-wrap {
    padding-top: 2px;
}
</style>
