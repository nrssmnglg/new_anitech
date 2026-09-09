<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { apiGet, apiPost } from '../services/api';
import { useApiPage } from '../composables/useApiPage';
import { formatDateTime } from '../utils/navigation';

const props = defineProps({
    advisoryId: {
        type: String,
        required: true,
    },
});

const advisory = ref(null);
const reacting = ref(false);
const { error, loading, run } = useApiPage(async () => {
    const response = await apiGet(`/advisories/${props.advisoryId}`);
    advisory.value = response?.data ?? null;
});

const advisoryTone = computed(() => {
    const title = String(advisory.value?.title ?? '').toLowerCase();
    const audience = String(advisory.value?.audience_type ?? '').toLowerCase();

    if (title.includes('warning') || title.includes('urgent') || title.includes('alert')) {
        return 'urgent';
    }

    if (audience === 'group' || audience === 'barangay') {
        return 'warning';
    }

    return 'info';
});

const advisoryTag = computed(() => {
    if (advisoryTone.value === 'urgent') {
        return 'Urgent';
    }

    if (advisoryTone.value === 'warning') {
        return 'Warning';
    }

    return 'Info';
});

const advisorySummary = computed(() => {
    if (!advisory.value) {
        return '';
    }

    return advisory.value.member_type?.name
        || advisory.value.barangay?.name
        || advisory.value.audience_type
        || 'General advisory';
});

const contentParagraphs = computed(() => {
    const raw = String(advisory.value?.content ?? '');

    return raw
        .split(/\n\s*\n/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean);
});

const hasAttachments = computed(() => Array.isArray(advisory.value?.attachments) && advisory.value.attachments.length > 0);

const setReaction = async (reaction) => {
    if (!advisory.value || reacting.value) {
        return;
    }

    reacting.value = true;

    try {
        const response = await apiPost(`/advisories/${advisory.value.id}/reaction`, { reaction });

        if (response?.data) {
            advisory.value = {
                ...advisory.value,
                ...response.data,
            };
        }
    } finally {
        reacting.value = false;
    }
};

onMounted(() => run());
</script>

<template>
    <div class="farmer-app__advisory-detail-screen">
        <main class="farmer-app__advisory-detail-shell">
            <AppLoader v-if="loading" />
            <AppState v-else-if="error" type="error" :message="error" />

            <div v-else-if="advisory" class="farmer-app__advisory-detail-stack">
                <header class="farmer-app__advisory-detail-header">
                    <RouterLink :to="{ name: 'advisories' }" class="farmer-app__advisory-detail-back">
                        <span aria-hidden="true">‹</span> Advisories
                    </RouterLink>
                </header>

                <article class="farmer-app__advisory-detail-card">
                    <div class="farmer-app__advisory-detail-meta">
                        <span class="farmer-app__advisory-detail-pill" :class="`is-${advisoryTone}`">
                            {{ advisoryTag }}
                        </span>
                        <span class="farmer-app__advisory-detail-date">{{ formatDateTime(advisory.published_at) }}</span>
                    </div>

                    <h1>{{ advisory.title }}</h1>
                    <p class="farmer-app__advisory-detail-summary">{{ advisorySummary }}</p>

                    <div class="farmer-app__advisory-detail-copy">
                        <p v-for="(paragraph, index) in contentParagraphs" :key="index">
                            {{ paragraph }}
                        </p>
                    </div>

                    <div class="farmer-app__advisory-detail-actions">
                        <button
                            type="button"
                            class="farmer-app__advisory-detail-action"
                            :class="{ 'is-active': advisory.farmer_reaction === 'like' }"
                            :disabled="reacting"
                            @click="setReaction('like')"
                        >
                            Like · {{ advisory.like_count ?? 0 }}
                        </button>
                        <button
                            type="button"
                            class="farmer-app__advisory-detail-action"
                            :class="{ 'is-active': advisory.farmer_reaction === 'dislike' }"
                            :disabled="reacting"
                            @click="setReaction('dislike')"
                        >
                            Dislike · {{ advisory.dislike_count ?? 0 }}
                        </button>
                    </div>
                </article>

                <section v-if="hasAttachments" class="farmer-app__advisory-detail-attachments">
                    <div class="farmer-app__advisory-detail-attachment-list">
                        <template
                            v-for="attachment in advisory.attachments"
                            :key="attachment.id"
                        >
                            <img
                                v-if="attachment.is_image"
                                :src="attachment.url"
                                :alt="attachment.name"
                                class="farmer-app__advisory-detail-image"
                            >
                            <a
                                v-else
                                :href="attachment.url"
                                :download="attachment.name"
                                class="farmer-app__advisory-detail-file-link"
                            >
                                {{ attachment.name }}
                            </a>
                        </template>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__advisory-detail-screen {
    min-height: 100%;
    background: transparent;
}

.farmer-app__advisory-detail-shell {
    position: relative;
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    padding: 10px 12px 28px;
}

.farmer-app__advisory-detail-stack {
    display: grid;
    gap: 8px;
}

.farmer-app__advisory-detail-header {
    padding: 0 2px 2px;
}

.farmer-app__advisory-detail-back {
    display: inline-flex;
    align-items: center;
    min-height: 36px;
    gap: 4px;
    color: var(--pwa-green-800);
    font-size: 0.78rem;
    font-weight: 800;
    text-decoration: none;
}

.farmer-app__advisory-detail-back span {
    font-size: 1.25rem;
    line-height: 1;
}

.farmer-app__advisory-detail-card,
.farmer-app__advisory-detail-attachments {
    padding: 14px;
    background: #fff;
    border: 1px solid var(--pwa-border);
    border-radius: 12px;
    box-shadow: none;
}

.farmer-app__advisory-detail-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.farmer-app__advisory-detail-pill {
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

.farmer-app__advisory-detail-pill.is-urgent {
    background: #fff0f0;
    color: #b42318;
}

.farmer-app__advisory-detail-pill.is-warning {
    background: #fff6df;
    color: #9a6200;
}

.farmer-app__advisory-detail-pill.is-info {
    background: #eaf7f1;
    color: var(--pwa-green-800);
}

.farmer-app__advisory-detail-date {
    color: var(--pwa-muted);
    font-size: 0.68rem;
}

.farmer-app__advisory-detail-card h1 {
    margin: 12px 0 0;
    color: var(--pwa-ink);
    font-size: 1.18rem;
    line-height: 1.3;
    letter-spacing: -0.02em;
}

.farmer-app__advisory-detail-summary {
    margin: 3px 0 0;
    color: var(--pwa-muted);
    font-size: 0.74rem;
    text-transform: capitalize;
}

.farmer-app__advisory-detail-copy {
    display: grid;
    gap: 10px;
    margin-top: 14px;
    padding-top: 13px;
    border-top: 1px solid var(--pwa-border);
}

.farmer-app__advisory-detail-copy p {
    margin: 0;
    color: #34453e;
    font-size: 0.86rem;
    line-height: 1.62;
    white-space: pre-line;
}

.farmer-app__advisory-detail-actions {
    display: flex;
    gap: 8px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--pwa-border);
}

.farmer-app__advisory-detail-action {
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid var(--pwa-border);
    border-radius: 9px;
    background: #fff;
    color: #53635c;
    font-size: 0.75rem;
    font-weight: 800;
}

.farmer-app__advisory-detail-action.is-active {
    border-color: var(--pwa-green-800);
    background: #eaf7f1;
    color: var(--pwa-green-800);
}

.farmer-app__advisory-detail-action:disabled {
    opacity: 0.6;
}

.farmer-app__advisory-detail-attachment-list {
    display: grid;
    gap: 8px;
}

.farmer-app__advisory-detail-image {
    display: block;
    width: 100%;
    max-height: 360px;
    object-fit: contain;
    border-radius: 8px;
    background: var(--pwa-surface-soft);
}

.farmer-app__advisory-detail-file-link {
    display: flex;
    align-items: center;
    min-height: 44px;
    padding: 0 12px;
    border: 1px solid var(--pwa-border);
    border-radius: 9px;
    color: var(--pwa-green-800);
    font-size: 0.78rem;
    font-weight: 800;
    text-decoration: none;
    overflow-wrap: anywhere;
}
</style>
