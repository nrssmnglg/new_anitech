<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { useCachedResource } from '../composables/useCachedResource';
import { useDraft } from '../composables/useDraft';
import { useLocale } from '../composables/useLocale';
import { apiGet, apiPost } from '../services/api';
import { useSyncQueueStore } from '../stores/syncQueue';
import { deleteDraftFile, getDraftFile, putDraftFile } from '../utils/draftFiles';
import { extractApiMessage, extractValidationErrors } from '../utils/api';
import { formatDateTime } from '../utils/navigation';

const MAX_FILE_SIZE = 5 * 1024 * 1024;
const props = defineProps({
    inquiryId: {
        type: String,
        required: true,
    },
});

const { t } = useLocale();
const inquiry = ref(null);
const replyError = ref('');
const replyValidationErrors = ref({});
const attachmentError = ref('');
const attachments = ref([]);
const uploadProgress = ref(0);
const showAttachmentPicker = ref(false);
const choosePhotoInput = ref(null);
const attachmentPickerRef = ref(null);
const syncQueue = useSyncQueueStore();
const { draft: form, clearDraft } = useDraft(`inquiries:${props.inquiryId}:reply`, {
    message: '',
});

const draftAttachmentKey = `inquiries:${props.inquiryId}:reply:attachments`;

const { data, error, loading, fetchFresh, fromCache, lastSyncedAt } = useCachedResource(`inquiry:${props.inquiryId}`, async () => {
    const response = await apiGet(`/inquiries/${props.inquiryId}`);
    return response?.data ?? null;
});

const detail = computed(() => data.value ?? inquiry.value);
const isResolved = computed(() => String(detail.value?.status ?? '').toLowerCase() === 'resolved');

const statusTone = computed(() => {
    const status = String(detail.value?.status ?? '').toLowerCase();

    if (status === 'resolved') {
        return 'resolved';
    }

    if (status === 'in progress' || status === 'in_progress') {
        return 'progress';
    }

    if (status === 'escalated') {
        return 'escalated';
    }

    return 'new';
});

const conversation = computed(() => {
    if (!detail.value) {
        return [];
    }

    const items = [
        {
            id: `origin-${detail.value.id}`,
            type: 'origin',
            isSupport: false,
            createdAt: detail.value.created_at,
            message: detail.value.message,
            attachments: detail.value.attachments ?? [],
        },
    ];

    for (const response of detail.value.responses ?? []) {
        items.push({
            id: `response-${response.id}`,
            type: 'response',
            isSupport: !!response.is_from_staff,
            createdAt: response.responded_at,
            message: response.message,
            attachments: response.attachments ?? [],
        });
    }

    return items.sort((left, right) => new Date(left.createdAt).getTime() - new Date(right.createdAt).getTime());
});

const isPreviewableImage = (attachment) => Boolean(attachment?.is_image && attachment?.url);
const canPreviewAttachment = (attachment) => Boolean(attachment?.can_preview && attachment?.url);
const hasMessage = (entry) => Boolean(String(entry?.message ?? '').trim());

const openAttachmentPicker = () => {
    showAttachmentPicker.value = !showAttachmentPicker.value;
};

const choosePhoto = () => {
    showAttachmentPicker.value = false;
    choosePhotoInput.value?.click();
};

const handleOutsideAttachmentPicker = (event) => {
    if (!showAttachmentPicker.value) {
        return;
    }

    if (attachmentPickerRef.value?.contains(event.target)) {
        return;
    }

    showAttachmentPicker.value = false;
};

const restoreAttachments = async () => {
    const payload = await getDraftFile(draftAttachmentKey);
    attachments.value = Array.isArray(payload?.files) ? payload.files : [];
};

const persistAttachments = async () => {
    if (!attachments.value.length) {
        await deleteDraftFile(draftAttachmentKey);
        return;
    }

    await putDraftFile(draftAttachmentKey, {
        files: attachments.value,
        saved_at: new Date().toISOString(),
    });
};

const setAttachments = async (event) => {
    attachmentError.value = '';
    const selected = Array.from(event.target.files ?? []);

    if (!selected.length) {
        return;
    }

    for (const file of selected) {
        if (file.size > MAX_FILE_SIZE) {
            attachmentError.value = t('inquiryDetail.reply_attachment_too_large');
            return;
        }
    }

    attachments.value = selected;
    await persistAttachments();
};

const removeAttachment = async (index) => {
    attachments.value.splice(index, 1);
    await persistAttachments();
};

const resetReplyDraft = async () => {
    clearDraft();
    attachments.value = [];
    attachmentError.value = '';
    uploadProgress.value = 0;
    await deleteDraftFile(draftAttachmentKey);
};

const submitReply = async () => {
    replyError.value = '';
    replyValidationErrors.value = {};
    attachmentError.value = '';

    if (typeof navigator !== 'undefined' && !navigator.onLine) {
        if (attachments.value.length) {
            replyError.value = t('inquiryDetail.saved_attachments');
            return;
        }

        syncQueue.enqueue({
            type: 'inquiry.reply',
            url: `/inquiries/${props.inquiryId}/reply`,
            payload: {
                message: form.message,
            },
            meta: {
                inquiry_id: props.inquiryId,
            },
        });
        await resetReplyDraft();
        return;
    }

    try {
        if (attachments.value.length) {
            const payload = new FormData();
            payload.append('message', form.message);
            attachments.value.forEach((file, index) => {
                payload.append(`attachments[${index}]`, file);
            });

            await apiPost(`/inquiries/${props.inquiryId}/reply`, payload, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (event) => {
                    if (event.total) {
                        uploadProgress.value = Math.round((event.loaded / event.total) * 100);
                    }
                },
            });
        } else {
            await apiPost(`/inquiries/${props.inquiryId}/reply`, form);
        }

        await resetReplyDraft();
        await fetchFresh();
    } catch (err) {
        replyError.value = extractApiMessage(err, t('inquiryDetail.reply_failed'));
        replyValidationErrors.value = extractValidationErrors(err);
    }
};

onMounted(async () => {
    document.addEventListener('click', handleOutsideAttachmentPicker);
    await restoreAttachments();
    const payload = await fetchFresh();
    inquiry.value = payload ?? null;
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideAttachmentPicker);
    showAttachmentPicker.value = false;
});
</script>

<template>
    <div class="farmer-app__inquiry-detail-screen">
        <div class="farmer-app__inquiry-detail-backdrop"></div>

        <main class="farmer-app__inquiry-detail-shell">
            <AppLoader v-if="loading" />
            <AppState v-else-if="error" type="error" :message="error" :action-label="t('common.retry')" @action="fetchFresh" />
            <AppState
                v-else-if="fromCache"
                :message="lastSyncedAt ? t('inquiryDetail.saved_from', { time: formatDateTime(lastSyncedAt) }) : t('inquiryDetail.saved')"
                :action-label="t('common.refresh')"
                @action="fetchFresh"
            />

            <div v-if="detail" class="farmer-app__inquiry-detail-stack">
                <section class="farmer-app__inquiry-detail-hero">
                    <div class="farmer-app__inquiry-detail-hero-head">
                        <div>
                            <span class="farmer-app__inquiry-detail-topic">{{ detail.category?.name ?? 'Other' }}</span>
                            <h1>{{ detail.subject }}</h1>
                        </div>
                        <span class="farmer-app__inquiry-detail-status" :class="`is-${statusTone}`">{{ detail.status }}</span>
                    </div>
                </section>

                <section class="farmer-app__inquiry-detail-thread">
                    <article
                        v-for="entry in conversation"
                        :key="entry.id"
                        class="farmer-app__inquiry-detail-bubble"
                        :class="{ 'is-support': entry.isSupport, 'is-farmer': !entry.isSupport }"
                    >
                        <div class="farmer-app__inquiry-detail-bubble-head">
                            <span class="farmer-app__inquiry-detail-bubble-time">{{ formatDateTime(entry.createdAt) }}</span>
                        </div>
                        <p v-if="hasMessage(entry)" class="farmer-app__inquiry-detail-bubble-message">{{ entry.message }}</p>

                        <div v-if="entry.attachments?.length" class="farmer-app__inquiry-detail-files">
                            <template
                                v-for="attachment in entry.attachments"
                                :key="attachment.id"
                            >
                                <img
                                    v-if="isPreviewableImage(attachment)"
                                    :src="attachment.url"
                                    :alt="attachment.name"
                                    class="farmer-app__inquiry-detail-image"
                                >
                                <a
                                    v-else-if="attachment.url"
                                    :href="attachment.url"
                                    :download="canPreviewAttachment(attachment) ? null : attachment.name"
                                    :target="canPreviewAttachment(attachment) ? '_blank' : null"
                                    :rel="canPreviewAttachment(attachment) ? 'noopener noreferrer' : null"
                                    class="farmer-app__inquiry-detail-file"
                                >
                                    {{ attachment.name }}
                                </a>
                                <span
                                    v-else
                                    class="farmer-app__inquiry-detail-file"
                                >
                                    {{ attachment.name }}
                                </span>
                            </template>
                        </div>
                    </article>
                </section>

                <section v-if="isResolved" class="farmer-app__inquiry-detail-reply-card">
                    <p>This inquiry is closed. Contact the office to reopen it if you need to add more information.</p>
                </section>

                <section v-else class="farmer-app__inquiry-detail-reply-card">
                    <div class="farmer-app__inquiry-detail-reply-compose">
                        <div ref="attachmentPickerRef" class="farmer-app__inquiry-detail-attach-wrap">
                            <button
                                type="button"
                                class="farmer-app__inquiry-detail-attach-icon"
                                aria-label="Attach files"
                                @click="openAttachmentPicker"
                            >
                                <svg viewBox="0 0 24 24" class="farmer-app__inquiry-detail-icon" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 17.5 17.5 9a3.5 3.5 0 1 0-5-5L4 12.5a5 5 0 0 0 7.07 7.07l8.13-8.13" />
                                </svg>
                            </button>

                            <div v-if="showAttachmentPicker" class="farmer-app__inquiry-detail-attach-menu">
                                <button type="button" class="farmer-app__inquiry-detail-attach-option" @click="choosePhoto">
                                    Choose photo
                                </button>
                            </div>

                            <input
                                ref="choosePhotoInput"
                                type="file"
                                accept="image/*,.pdf"
                                multiple
                                class="farmer-app__inquiry-detail-hidden-input"
                                @change="setAttachments"
                            >
                        </div>

                        <label class="farmer-app__inquiry-detail-reply-field">
                            <textarea v-model="form.message" rows="2" placeholder="Type here..."></textarea>
                        </label>

                        <button type="button" class="farmer-app__inquiry-detail-send-icon" aria-label="Send reply" @click="submitReply">
                            <svg viewBox="0 0 24 24" class="farmer-app__inquiry-detail-icon" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 2 11 13" />
                                <path d="M22 2 15 22l-4-9-9-4Z" />
                            </svg>
                        </button>
                    </div>
                    <div v-if="attachments.length" class="farmer-app__inquiries-attachments">
                        <article v-for="(file, index) in attachments" :key="`${file.name}-${index}`" class="farmer-app__inquiries-attachment">
                            <div class="farmer-app__inquiries-attachment-name">
                                <strong>{{ file.name }}</strong>
                            </div>
                            <button type="button" class="farmer-app__inquiry-detail-secondary" @click="removeAttachment(index)">
                                Remove
                            </button>
                        </article>
                    </div>

                    <div v-if="attachments.length && uploadProgress" class="farmer-app__upload-meter">
                        <span :style="{ width: `${uploadProgress}%` }"></span>
                    </div>

                    <AppState v-if="attachmentError" type="error" :message="attachmentError" />
                    <AppState v-if="replyError" type="error" :message="replyError" />
                    <AppState
                        v-if="syncQueue.pendingCount || syncQueue.failedCount"
                        :message="`${syncQueue.pendingCount} reply action(s) queued for sync.`"
                        action-label="Retry Sync"
                        @action="syncQueue.syncPending"
                    />

                    <p v-if="attachments.length && uploadProgress" class="farmer-app__inquiry-detail-uploading">
                        Uploading {{ uploadProgress }}%
                    </p>
                </section>
            </div>

        </main>
    </div>
</template>

<style scoped>
.farmer-app__inquiry-detail-hero {
    display: grid;
    gap: 0.55rem;
}

.farmer-app__inquiry-detail-hero-head,
.farmer-app__inquiry-detail-hero-meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: center;
}

.farmer-app__inquiry-detail-topic,
.farmer-app__inquiry-detail-unread {
    font-size: 0.76rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.farmer-app__inquiry-detail-topic {
    color: #4b675d;
}

.farmer-app__inquiry-detail-hero h1 {
    margin: 0.2rem 0 0;
    font-size: 1.35rem;
    line-height: 1.2;
    color: #17352d;
}

.farmer-app__inquiry-detail-unread {
    color: #b42318;
}

.farmer-app__inquiry-detail-hero-meta {
    font-size: 0.84rem;
    color: #64746c;
}

.farmer-app__inquiry-detail-thread {
    display: grid;
    gap: 1rem;
}

.farmer-app__inquiry-detail-bubble {
    max-width: min(78%, 34rem);
    border: 1px solid rgba(0, 54, 41, 0.06);
    background: #ffffff;
    border-radius: 22px;
    padding: 0.9rem 1rem;
    display: grid;
    gap: 0.55rem;
    box-shadow: 0 10px 24px rgba(15, 91, 70, 0.05);
}

.farmer-app__inquiry-detail-bubble.is-support {
    justify-self: start;
    border-top-left-radius: 8px;
}

.farmer-app__inquiry-detail-bubble.is-farmer {
    justify-self: end;
    background: linear-gradient(135deg, #0f5b46 0%, #1f8a63 100%);
    color: #fff;
    border-top-right-radius: 8px;
}

.farmer-app__inquiry-detail-bubble-head {
    display: flex;
    align-items: baseline;
    justify-content: flex-end;
    gap: 0.75rem;
}

.farmer-app__inquiry-detail-bubble-time {
    font-size: 0.72rem;
    opacity: 0.72;
    white-space: nowrap;
}


.farmer-app__inquiry-detail-bubble-message {
    margin: 0;
    line-height: 1.6;
}

.farmer-app__inquiry-detail-bubble.is-farmer .farmer-app__inquiry-detail-file {
    background: rgba(255, 255, 255, 0.14);
    border-color: rgba(255, 255, 255, 0.22);
    color: #fff;
}

.farmer-app__inquiry-detail-image {
    width: 100%;
    max-width: 15rem;
    border-radius: 18px;
    display: block;
    object-fit: cover;
    border: 1px solid rgba(0, 54, 41, 0.08);
}

.farmer-app__inquiry-detail-files,
.farmer-app__inquiries-attachments {
    display: grid;
    gap: 0.75rem;
}

.farmer-app__inquiries-attachment {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    border: 1px solid rgba(0, 54, 41, 0.12);
    border-radius: 14px;
    padding: 0.8rem 0.95rem;
    background: #fbfdfb;
}

.farmer-app__inquiries-attachment-name strong {
    display: block;
    font-size: 0.9rem;
    line-height: 1.35;
    word-break: break-word;
}

.farmer-app__inquiry-detail-reply-card {
    display: grid;
    gap: 0.8rem;
}

.farmer-app__inquiry-detail-reply-compose {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 0.55rem;
    align-items: center;
}

.farmer-app__inquiry-detail-attach-wrap {
    position: relative;
}

.farmer-app__inquiry-detail-attach-icon,
.farmer-app__inquiry-detail-send-icon {
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 999px;
    border: 1px solid rgba(15, 91, 70, 0.18);
    background: linear-gradient(135deg, #0f5b46 0%, #1f8a63 100%);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 10px 20px rgba(15, 91, 70, 0.18);
}

.farmer-app__inquiry-detail-hidden-input {
    display: none;
}

.farmer-app__inquiry-detail-icon {
    width: 1.15rem;
    height: 1.15rem;
    display: block;
    flex: 0 0 auto;
}

.farmer-app__inquiry-detail-attach-menu {
    position: absolute;
    left: 0;
    bottom: calc(100% + 0.5rem);
    min-width: 10.5rem;
    display: grid;
    gap: 0.35rem;
    padding: 0.45rem;
    border: 1px solid rgba(15, 91, 70, 0.12);
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 16px 34px rgba(15, 91, 70, 0.12);
}

.farmer-app__inquiry-detail-attach-option {
    border: 0;
    border-radius: 12px;
    background: #f4faf4;
    color: #0f5b46;
    padding: 0.75rem 0.85rem;
    text-align: left;
    font-weight: 700;
    cursor: pointer;
}

.farmer-app__inquiry-detail-reply-field textarea {
    min-height: 2.8rem;
    border-radius: 22px;
    padding: 0.8rem 0.95rem;
    font-size: 0.95rem;
}

.farmer-app__inquiry-detail-uploading {
    margin: 0;
    font-size: 0.82rem;
    color: #64746c;
}

</style>

