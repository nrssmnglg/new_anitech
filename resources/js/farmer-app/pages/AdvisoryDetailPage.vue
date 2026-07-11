<script setup>
import { computed, onMounted, ref } from 'vue';
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
        <div class="farmer-app__advisory-detail-backdrop"></div>

        <main class="farmer-app__advisory-detail-shell">
            <AppLoader v-if="loading" />
            <AppState v-else-if="error" type="error" :message="error" />

            <div v-else-if="advisory" class="farmer-app__advisory-detail-stack">
                <article class="farmer-app__advisory-detail-hero">
                    <div class="farmer-app__advisory-detail-meta">
                        <span class="farmer-app__advisory-detail-pill" :class="`is-${advisoryTone}`">
                            {{ advisoryTag }}
                        </span>
                        <span class="farmer-app__advisory-detail-date">{{ formatDateTime(advisory.published_at) }}</span>
                    </div>

                    <h1>{{ advisory.title }}</h1>
                    <p class="farmer-app__advisory-detail-summary">{{ advisorySummary }}</p>
                </article>

                <section class="farmer-app__advisory-detail-body">
                    <div class="farmer-app__advisory-detail-section-head">
                        <div>
                            <span class="farmer-app__advisory-detail-kicker">Field Brief</span>
                            <h2>Advisory Message</h2>
                        </div>
                    </div>

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
                            Like {{ advisory.like_count ?? 0 }}
                        </button>
                        <button
                            type="button"
                            class="farmer-app__advisory-detail-action"
                            :class="{ 'is-active': advisory.farmer_reaction === 'dislike' }"
                            :disabled="reacting"
                            @click="setReaction('dislike')"
                        >
                            Dislike {{ advisory.dislike_count ?? 0 }}
                        </button>
                    </div>
                </section>

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
