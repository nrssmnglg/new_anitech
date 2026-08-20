<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppPagination from '../components/ui/AppPagination.vue';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { useDraft } from '../composables/useDraft';
import { useLocale } from '../composables/useLocale';
import { usePaginatedFetch } from '../composables/usePaginatedFetch';
import { apiGet, apiPost } from '../services/api';
import { useSyncQueueStore } from '../stores/syncQueue';
import { deleteDraftFile, getDraftFile, putDraftFile } from '../utils/draftFiles';
import { extractApiMessage, extractValidationErrors } from '../utils/api';
import { formatDateTime } from '../utils/navigation';

const MAX_FILE_SIZE = 5 * 1024 * 1024;
const { t } = useLocale();
const summary = ref({});
const categories = ref([]);
const templates = ref([]);
const selectedStatus = ref('all');
const submitError = ref('');
const submitValidationErrors = ref({});
const syncQueue = useSyncQueueStore();
const { draft: form, clearDraft } = useDraft('inquiries:create', {
    category_id: '',
    subject: '',
    message: '',
});
const attachments = ref([]);
const attachmentError = ref('');
const uploadProgress = ref(0);
const composing = ref(false);

const { items: inquiries, meta, error, loading, fetchPage, fromCache, lastSyncedAt } = usePaginatedFetch(async (params) => {
    const response = await apiGet('/inquiries', { params });
    summary.value = response?.meta?.summary ?? {};
    categories.value = response?.meta?.categories ?? [];
    templates.value = response?.meta?.templates ?? [];

    if (!form.category_id && categories.value.length) {
        form.category_id = String(categories.value[0].id);
    }

    return response;
});

const draftAttachmentKey = 'inquiries:create:attachments';

const statusClass = (status) => {
    const tone = String(status ?? '').toLowerCase();

    if (tone === 'resolved') {
        return 'farmer-app__inquiries-status--resolved';
    }

    if (tone === 'in progress' || tone === 'in_progress') {
        return 'farmer-app__inquiries-status--progress';
    }

    if (tone === 'escalated') {
        return 'farmer-app__inquiries-status--escalated';
    }

    return 'farmer-app__inquiries-status--new';
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

const refreshList = async (page = 1) => {
    await fetchPage({
        page,
        status: selectedStatus.value === 'all' ? undefined : selectedStatus.value,
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
            attachmentError.value = t('inquiries.attachment_too_large');
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

const applyTemplate = (template) => {
    if (template.category_id) {
        form.category_id = String(template.category_id);
    }

    if (!form.subject) {
        form.subject = template.subject ?? '';
    }

    if (!form.message) {
        form.message = template.message ?? '';
    } else {
        form.message = `${form.message.trim()}\n\n${template.message ?? ''}`.trim();
    }
};

const resetInquiryDraft = async () => {
    clearDraft();
    form.category_id = categories.value[0]?.id ? String(categories.value[0].id) : '';
    attachments.value = [];
    attachmentError.value = '';
    uploadProgress.value = 0;
    await deleteDraftFile(draftAttachmentKey);
};

const createInquiry = async () => {
    submitError.value = '';
    submitValidationErrors.value = {};
    attachmentError.value = '';

    if (!form.category_id) {
        submitValidationErrors.value = { category_id: t('inquiries.select_topic_error') };
        return;
    }

    if (typeof navigator !== 'undefined' && !navigator.onLine) {
        if (attachments.value.length) {
            submitError.value = t('inquiries.saved_attachments');
            return;
        }

        syncQueue.enqueue({
            type: 'inquiry.create',
            url: '/inquiries',
            payload: {
                category_id: Number(form.category_id),
                subject: form.subject,
                message: form.message,
            },
            meta: {
                subject: form.subject,
            },
        });
        await resetInquiryDraft();
        await refreshList().catch(() => {});
        return;
    }

    try {
        if (attachments.value.length) {
            const payload = new FormData();
            payload.append('category_id', form.category_id);
            payload.append('subject', form.subject);
            payload.append('message', form.message);
            attachments.value.forEach((file, index) => {
                payload.append(`images[${index}]`, file);
            });

            await apiPost('/inquiries', payload, {
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
            await apiPost('/inquiries', {
                category_id: Number(form.category_id),
                subject: form.subject,
                message: form.message,
            });
        }

        uploadProgress.value = 0;
        await resetInquiryDraft();
        composing.value = false;
        await refreshList();
    } catch (err) {
        submitError.value = extractApiMessage(err, t('inquiries.submit_failed'));
        submitValidationErrors.value = extractValidationErrors(err);
    }
};

onMounted(async () => {
    await restoreAttachments();
    await refreshList();
});
</script>

<template>
    <div class="farmer-app__inquiries-screen">
        <div class="farmer-app__inquiries-backdrop"></div>

        <main class="farmer-app__inquiries-shell">
            <div class="farmer-app__inquiries-stack">
                <header class="farmer-app__inquiries-header">
                    <h1>Inbox</h1>
                    <button type="button" class="farmer-app__inquiries-plus" @click="composing = !composing">
                        +
                    </button>
                </header>

                <section v-if="composing" class="farmer-app__inquiries-form-card">
                    <div class="farmer-app__inquiries-form-head">
                        <div class="farmer-app__inquiries-form-copy">
                            <h2>{{ t('inquiries.new_inquiry') }}</h2>
                        </div>
                    </div>

                    <div class="farmer-app__inquiries-form">
                        <label class="farmer-app__inquiries-field">
                            <span>{{ t('inquiries.topic') }}</span>
                            <select v-model="form.category_id">
                                <option disabled value="">{{ t('inquiries.select_topic') }}</option>
                                <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                                    {{ category.name }}
                                </option>
                            </select>
                            <p v-if="submitValidationErrors.category_id" class="farmer-app__inquiries-error">{{ submitValidationErrors.category_id }}</p>
                        </label>

                        <div v-if="templates.length" class="farmer-app__inquiries-templates">
                            <span>Quick questions</span>
                            <div class="farmer-app__inquiries-template-list">
                                <button
                                    v-for="template in templates"
                                    :key="template.key"
                                    type="button"
                                    class="farmer-app__inquiries-template-chip"
                                    @click="applyTemplate(template)"
                                >
                                    {{ template.label }}
                                </button>
                            </div>
                        </div>

                        <label class="farmer-app__inquiries-field">
                            <span>Subject</span>
                            <input v-model="form.subject" type="text" placeholder="e.g., My payment is not reflected" />
                            <p v-if="submitValidationErrors.subject" class="farmer-app__inquiries-error">{{ submitValidationErrors.subject }}</p>
                        </label>

                        <label class="farmer-app__inquiries-field">
                            <span>Message</span>
                            <textarea v-model="form.message" placeholder="Describe your concern in detail..." rows="5"></textarea>
                            <p v-if="submitValidationErrors.message" class="farmer-app__inquiries-error">{{ submitValidationErrors.message }}</p>
                        </label>

                        <label class="farmer-app__inquiries-field">
                            <span>Attachments</span>
                            <input type="file" accept="image/*,.pdf" multiple @change="setAttachments" />
                            <p class="farmer-app__inquiries-hint">Choose photos or files from your device, or use the camera if available. Maximum 5 MB each.</p>
                        </label>

                        <div v-if="attachments.length" class="farmer-app__inquiries-attachments">
                            <article v-for="(file, index) in attachments" :key="`${file.name}-${index}`" class="farmer-app__inquiries-attachment">
                                <div>
                                    <strong>{{ file.name }}</strong>
                                    <p>{{ (file.size / 1024 / 1024).toFixed(2) }} MB</p>
                                </div>
                                <button type="button" class="farmer-app__inquiries-secondary" @click="removeAttachment(index)">
                                    Remove
                                </button>
                            </article>
                        </div>

                        <div v-if="attachments.length && uploadProgress" class="farmer-app__upload-meter">
                            <span :style="{ width: `${uploadProgress}%` }"></span>
                        </div>

                        <AppState v-if="attachmentError" type="error" :message="attachmentError" />
                        <AppState v-if="submitError" type="error" :message="submitError" />
                        <AppState
                            v-if="syncQueue.pendingCount || syncQueue.failedCount"
                            :message="t('inquiries.queued_sync', { count: syncQueue.pendingCount })"
                            :action-label="t('common.retry')"
                            @action="syncQueue.syncPending"
                        />

                        <div class="farmer-app__inquiries-form-actions">
                            <button type="button" class="farmer-app__inquiries-primary" @click="createInquiry">
                                {{ attachments.length && uploadProgress ? `Uploading ${uploadProgress}%` : 'Submit Inquiry' }}
                            </button>
                            <button type="button" class="farmer-app__inquiries-secondary" @click="resetInquiryDraft(); composing = false">
                                Cancel
                            </button>
                        </div>
                    </div>
                </section>

                <AppLoader v-if="loading" />
                <AppState v-else-if="error" type="error" :message="error" :action-label="t('common.retry')" @action="refreshList()" />
                <AppState
                    v-else-if="fromCache"
                    :message="lastSyncedAt ? t('inquiries.saved_from', { time: formatDateTime(lastSyncedAt) }) : t('inquiries.saved')"
                    :action-label="t('common.refresh')"
                    @action="refreshList()"
                />

                <section v-if="inquiries.length" class="farmer-app__inquiries-list-wrap">
                    <div class="farmer-app__inquiries-list">
                        <RouterLink
                            v-for="inquiry in inquiries"
                            :key="inquiry.id"
                            :to="{ name: 'inquiry-detail', params: { inquiryId: inquiry.id } }"
                            class="farmer-app__inquiries-card"
                        >
                            <div class="farmer-app__inquiries-card-main">
                                <strong>{{ inquiry.subject }}</strong>
                                <div class="farmer-app__inquiries-card-meta">
                                    <span>{{ inquiry.status }}</span>
                                    <span>{{ formatDateTime(inquiry.created_at) }}</span>
                                </div>
                            </div>
                        </RouterLink>
                    </div>

                    <div class="farmer-app__inquiries-pagination-wrap">
                        <AppPagination :meta="meta" @change="(page) => refreshList(page)" />
                    </div>
                </section>

                <AppState v-else-if="!loading" :message="t('inquiries.no_inquiries')" :action-label="t('common.refresh')" @action="refreshList()" />
            </div>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__inquiries-form-copy,
.farmer-app__inquiries-history-head > div:first-child {
    display: grid;
    gap: 0.35rem;
}

.farmer-app__inquiries-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.farmer-app__inquiries-plus {
    width: 42px;
    height: 42px;
    border: 1px solid rgba(0, 54, 41, 0.12);
    border-radius: 999px;
    background: #ffffff;
    color: #0d4738;
    font-size: 1.5rem;
    line-height: 1;
    font-weight: 600;
}

.farmer-app__inquiries-templates,
.farmer-app__inquiries-template-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.farmer-app__inquiries-templates {
    flex-direction: column;
}

.farmer-app__inquiries-template-chip {
    border: 1px solid rgba(0, 54, 41, 0.12);
    background: #f7fbf8;
    color: #0d4738;
    border-radius: 999px;
    padding: 0.65rem 0.95rem;
    font-size: 0.86rem;
    font-weight: 700;
}

.farmer-app__inquiries-history-head {
    display: grid;
    gap: 1rem;
}

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

.farmer-app__inquiries-hint,
.farmer-app__inquiries-preview {
    margin: 0.35rem 0 0;
    color: #5e736a;
    font-size: 0.84rem;
}

.farmer-app__inquiries-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.85rem;
    align-items: center;
}

.farmer-app__inquiries-card-meta {
    margin-top: 0.45rem;
    font-size: 0.82rem;
    color: #64746c;
}

.farmer-app__inquiries-status--new {
    background: #ecfdf3;
    color: #166534;
}

.farmer-app__inquiries-status--progress {
    background: #eff6ff;
    color: #1d4ed8;
}

.farmer-app__inquiries-status--resolved {
    background: #f3f4f6;
    color: #374151;
}

.farmer-app__inquiries-status--escalated {
    background: #fff7ed;
    color: #c2410c;
}
</style>

