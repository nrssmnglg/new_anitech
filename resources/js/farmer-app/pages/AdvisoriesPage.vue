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
const latestPublishedLabel = computed(() => {
    if (!advisories.value.length) {
        return 'Waiting for the next field bulletin.';
    }

    return formatDateTime(advisories.value[0]?.published_at);
});

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
    return `${target} | ${formatDateTime(advisory.published_at)}`;
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
        <div class="farmer-app__advisories-backdrop"></div>

        <main class="farmer-app__advisories-shell">
            <AppLoader v-if="loading" />
            <AppState v-else-if="error" type="error" :message="error" action-label="Retry" @action="fetchPage()" />
            <AppState
                v-else-if="fromCache"
                :message="`Showing saved advisories${lastSyncedAt ? ` from ${formatDateTime(lastSyncedAt)}` : ''}.`"
                action-label="Refresh"
                @action="fetchPage()"
            />

            <div v-if="advisories.length" class="farmer-app__advisories-stack">
                <header class="farmer-app__advisories-header">
                    <span class="farmer-app__advisories-eyebrow">Field Broadcasts</span>
                    <h1>Advisories</h1>
                    <p>Published advisories targeted for this farmer, refreshed while the app stays open.</p>
                </header>

                <section class="farmer-app__advisories-summary">
                    <article class="farmer-app__advisories-summary-card">
                        <span>Total Bulletins</span>
                        <strong>{{ totalAdvisories }}</strong>
                        <small>Loaded in this feed</small>
                    </article>
                    <article class="farmer-app__advisories-summary-card farmer-app__advisories-summary-card--accent">
                        <span>Latest Release</span>
                        <strong>{{ latestPublishedLabel }}</strong>
                        <small>Most recent advisory publish time</small>
                    </article>
                </section>

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
                            <span class="farmer-app__advisories-link">Read full report</span>
                            <span class="farmer-app__advisories-icon" :class="`is-${advisoryTone(advisory)}`">&gt;</span>
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
