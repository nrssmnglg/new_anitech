<script setup>
import { computed, reactive, ref, onMounted } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppState from '../components/ui/AppState.vue';
import { useDraft } from '../composables/useDraft';
import { useLocale } from '../composables/useLocale';
import { useAppStore } from '../stores/app';
import { deleteDraftFile, getDraftFile, putDraftFile } from '../utils/draftFiles';
import { extractApiMessage, extractValidationErrors } from '../utils/api';
import { farmerApi } from '../services/api';
import { farmerPublicUrl } from '../utils/paths';
import { brandLogoUrl } from '../utils/asset';
import { prepareUploadFile } from '../utils/imageUpload';

const MAX_FILE_SIZE = 10 * 1024 * 1024;
const route = useRoute();
const router = useRouter();
const app = useAppStore();
const { t } = useLocale();
const brandLogo = brandLogoUrl();
const loading = ref(false);
const uploading = ref(false);
const restoringDraft = ref(false);
const error = ref('');
const success = ref('');
const validationErrors = ref({});
const application = ref(null);
const uploadProgress = ref(0);
const lookupDefaults = {
    application_no: String(route.query.application_no ?? ''),
    birth_date: String(route.query.birth_date ?? ''),
    current_step: 'lookup',
};

const { draft: lookup, clearDraft } = useDraft('membership-upload:lookup', lookupDefaults);
const files = reactive({});
const previewUrls = reactive({});
const fileErrors = reactive({});
const fileMeta = reactive({});
const cameraInputs = reactive({});
const fileInputs = reactive({});

const documentSummary = computed(() => {
    const list = application.value?.documents ?? [];
    const uploaded = list.filter((document) => document.uploaded).length;
    const missing = list.filter((document) => !document.uploaded).length;
    const flagged = list.filter((document) => document.needs_correction).length;

    return {
        total: list.length,
        uploaded,
        missing,
        flagged,
    };
});

const uploadChecklist = computed(() => (application.value?.documents ?? []).map((document) => ({
    ...document,
    isSelected: Boolean(files[document.type]),
    statusKey: documentStatusKey(document),
    statusLabel: documentStatusLabel(document),
})));

const blockingDocuments = computed(() => (application.value?.documents ?? [])
    .filter((document) => {
        const statusKey = documentStatusKey(document);
        return statusKey === 'missing' || statusKey === 'needs_resubmission';
    })
    .map((document) => ({
        type: document.type,
        label: document.label,
        statusLabel: documentStatusLabel(document),
        remarks: document.remarks,
    })));

const uploadHelpText = computed(() => {
    if (!application.value) {
        return t('upload.open_saved_draft');
    }

    if (documentSummary.value.flagged > 0) {
        return t('upload.replace_flagged');
    }

    if (documentSummary.value.missing > 0) {
        return t('upload.attach_missing');
    }

    return t('upload.all_on_record');
});

const qualityHints = [
    t('upload.hint_readable'),
    t('upload.hint_glare'),
    t('upload.hint_corners'),
    t('upload.hint_lighting'),
];

const draftAttachmentKey = (documentType) => `membership-upload:${lookup.application_no || 'pending'}:${documentType}`;

const createPreviewUrl = (file) => {
    if (!file || file.type === 'application/pdf') {
        return '';
    }

    return URL.createObjectURL(file);
};

const documentStatusKey = (document) => {
    if (document.needs_correction) {
        return 'needs_resubmission';
    }

    if ((document.verification_status ?? '').toLowerCase() === 'verified') {
        return 'verified';
    }

    if (files[document.type]) {
        return 'uploaded';
    }

    if (document.uploaded) {
        return 'under_review';
    }

    return 'missing';
};

const documentStatusLabel = (document) => {
    return {
        missing: 'Missing',
        uploaded: 'Uploaded',
        under_review: 'Under Review',
        verified: 'Verified',
        needs_resubmission: 'Needs Resubmission',
    }[documentStatusKey(document)].replace('Missing', t('upload.missing'))
        .replace('Uploaded', t('upload.uploaded'))
        .replace('Under Review', t('upload.under_review'))
        .replace('Verified', t('upload.verified'))
        .replace('Needs Resubmission', t('upload.needs_resubmission'));
};

const statusBadgeClass = (document) => {
    return {
        missing: 'is-pending',
        uploaded: 'is-uploaded',
        under_review: 'is-review',
        verified: 'is-verified',
        needs_resubmission: 'is-flagged',
    }[documentStatusKey(document)];
};

const revokePreview = (documentType) => {
    if (previewUrls[documentType]) {
        URL.revokeObjectURL(previewUrls[documentType]);
        delete previewUrls[documentType];
    }
};

const restoreDraftFiles = async () => {
    if (!application.value?.documents?.length) {
        return;
    }

    restoringDraft.value = true;

    try {
        for (const document of application.value.documents) {
            const payload = await getDraftFile(draftAttachmentKey(document.type));
            if (!payload?.file) {
                continue;
            }

            files[document.type] = payload.file;
            revokePreview(document.type);
            previewUrls[document.type] = createPreviewUrl(payload.file);
        }
    } finally {
        restoringDraft.value = false;
    }
};

const persistSelectedFile = async (documentType, file) => {
    if (!file) {
        await deleteDraftFile(draftAttachmentKey(documentType));
        return;
    }

    await putDraftFile(draftAttachmentKey(documentType), {
        file,
        name: file.name,
        size: file.size,
        type: file.type,
        saved_at: new Date().toISOString(),
    });
};

const trackApplication = async () => {
    loading.value = true;
    error.value = '';
    success.value = '';
    validationErrors.value = {};

    try {
        const response = await farmerApi.get('/application/track', {
            params: {
                application_no: lookup.application_no,
                birth_date: lookup.birth_date,
            },
        });

        application.value = response?.data?.data ?? null;
        lookup.current_step = 'upload';
        window.localStorage.setItem('anitech_mobile_application', JSON.stringify({
            application_no: lookup.application_no,
            birth_date: lookup.birth_date,
        }));
        success.value = t('upload.loaded_continue', { applicationNo: lookup.application_no });
        await restoreDraftFiles();
    } catch (err) {
        application.value = null;
        error.value = extractApiMessage(err, t('upload.load_failed'));
        validationErrors.value = extractValidationErrors(err);
    } finally {
        loading.value = false;
    }
};

const setFile = async (type, event, source = 'file') => {
    const file = event.target.files?.[0] ?? null;
    delete fileErrors[type];

    if (!file) {
        return;
    }

    const prepared = await prepareUploadFile(file, app.lowDataMode
        ? {
            maxWidth: 1400,
            maxHeight: 1400,
            quality: 0.72,
            minBytesToCompress: 600 * 1024,
        }
        : {});

    if (prepared.file.size > MAX_FILE_SIZE) {
        fileErrors[type] = t('upload.file_too_large');
        return;
    }

    files[type] = prepared.file;
    fileMeta[type] = {
        source,
        compressed: prepared.compressed,
        originalSize: prepared.originalSize ?? prepared.file.size,
        finalSize: prepared.finalSize ?? prepared.file.size,
    };
    revokePreview(type);
    previewUrls[type] = createPreviewUrl(prepared.file);
    await persistSelectedFile(type, prepared.file);
};

const removeFile = async (type) => {
    delete files[type];
    delete fileErrors[type];
    delete fileMeta[type];
    revokePreview(type);
    await persistSelectedFile(type, null);
};

const selectedFileName = (type) => files[type]?.name ?? '';
const previewUrl = (type) => previewUrls[type] ?? '';

const openCamera = (type) => {
    cameraInputs[type]?.click?.();
};

const openFilePicker = (type) => {
    fileInputs[type]?.click?.();
};

const submitDocuments = async () => {
    if (!application.value) {
        error.value = 'Load your application first.';
        return;
    }

    const selectedFiles = Object.entries(files).filter(([, file]) => file);
    if (!selectedFiles.length) {
        error.value = 'Choose at least one file before submitting.';
        return;
    }

    uploading.value = true;
    uploadProgress.value = 0;
    error.value = '';
    success.value = '';

    try {
        const formData = new FormData();
        formData.append('birth_date', lookup.birth_date);

        selectedFiles.forEach(([type, file]) => {
            formData.append(`documents[${type}]`, file);
        });

        const response = await farmerApi.post(`/application/${encodeURIComponent(lookup.application_no)}/documents`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (event) => {
                if (event.total) {
                    uploadProgress.value = Math.round((event.loaded / event.total) * 100);
                }
            },
        });

        application.value = response?.data?.data?.application ?? null;
        success.value = 'Documents uploaded successfully. Redirecting to tracking...';
        lookup.current_step = 'complete';

        await Promise.all(
            selectedFiles.map(([type]) => removeFile(type)),
        );

        window.setTimeout(() => {
            window.location.href = farmerPublicUrl('/farmer/upload-complete', {
                application_no: lookup.application_no,
                birth_date: lookup.birth_date,
            });
        }, 700);
    } catch (err) {
        error.value = extractApiMessage(err, t('upload.upload_failed'));
        validationErrors.value = extractValidationErrors(err);
    } finally {
        uploading.value = false;
    }
};

onMounted(() => {
    if (lookup.current_step === 'upload' && lookup.application_no && lookup.birth_date) {
        trackApplication();
    }
});

const resetWorkflow = () => {
    application.value = null;
    clearDraft();
};

onMounted(() => {
    if (lookup.application_no && lookup.birth_date) {
        trackApplication();
    }
});
</script>

<template>
    <div class="farmer-app__upload-screen">
        <div class="farmer-app__upload-backdrop"></div>

        <main class="farmer-app__upload-shell">
            <section class="farmer-app__upload-hero">
                <h1>{{ t('upload.title') }}</h1>
                <p>{{ t('upload.subtitle') }}</p>
            </section>

            <section class="farmer-app__upload-card">
                <div v-if="!application" class="farmer-app__upload-lookup">
                    <div class="farmer-app__upload-grid">
                        <label class="farmer-app__upload-field">
                            <span>Application Number</span>
                            <input
                                v-model="lookup.application_no"
                                type="text"
                                placeholder="e.g. AT-2023-8842"
                            >
                            <small v-if="validationErrors.application_no" class="farmer-app__field-error">{{ validationErrors.application_no }}</small>
                        </label>

                        <label class="farmer-app__upload-field">
                            <span>Birth Date</span>
                            <input
                                v-model="lookup.birth_date"
                                type="date"
                            >
                            <small v-if="validationErrors.birth_date" class="farmer-app__field-error">{{ validationErrors.birth_date }}</small>
                        </label>
                    </div>

                    <button type="button" class="farmer-app__btn farmer-app__upload-primary" :disabled="loading" @click="trackApplication">
                        {{ loading ? 'Loading...' : 'Load Application' }}
                    </button>
                </div>

                <AppState v-if="error" type="error" :message="error" />
                <AppState v-if="success" :message="success" />

                <div v-if="application" class="farmer-app__upload-workflow">
                    <article class="farmer-app__upload-progress">
                        <div class="farmer-app__upload-progress-main">
                            <div class="farmer-app__upload-progress-count">
                                {{ documentSummary.uploaded }}/{{ documentSummary.total }}
                            </div>
                            <div>
                                <strong>Progress: {{ documentSummary.total ? Math.round((documentSummary.uploaded / documentSummary.total) * 100) : 0 }}%</strong>
                                <p>Application Status: <span>{{ application.status_label }}</span></p>
                            </div>
                        </div>
                        <div class="farmer-app__upload-progress-meta">
                            <p>Reference: #{{ application.application_no }}</p>
                            <small>{{ application.farmer?.farmer_code ? `Farmer ID: ${application.farmer.farmer_code}` : 'Farmer ID pending' }}</small>
                        </div>
                    </article>

                    <AppState
                        :message="uploadHelpText"
                        :action-label="documentSummary.flagged > 0 ? 'Open Tracking' : undefined"
                        @action="router.push({ name: 'track-status', query: { application_no: lookup.application_no, birth_date: lookup.birth_date } })"
                    />

                    <div class="farmer-app__upload-checklist">
                        <article
                            v-for="document in uploadChecklist"
                            :key="document.type"
                            class="farmer-app__upload-check"
                            :class="{ 'is-flagged': document.needs_correction }"
                        >
                            <strong>{{ document.label }}</strong>
                            <span>{{ document.statusLabel }}</span>
                        </article>
                    </div>

                    <div v-if="blockingDocuments.length" class="farmer-app__upload-blockers">
                        <strong>{{ t('upload.missing_summary') }}</strong>
                        <ul>
                            <li v-for="document in blockingDocuments" :key="document.type">
                                {{ document.label }} - {{ document.statusLabel }}<span v-if="document.remarks">: {{ document.remarks }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="farmer-app__upload-quality">
                        <ul>
                            <li v-for="hint in qualityHints" :key="hint">{{ hint }}</li>
                        </ul>
                    </div>

                    <div class="farmer-app__upload-list">
                        <article
                            v-for="document in uploadChecklist"
                            :key="document.type"
                            class="farmer-app__upload-item"
                            :class="{ 'is-flagged': document.needs_correction }"
                        >
                            <div class="farmer-app__upload-item-head">
                                <div class="farmer-app__upload-item-icon">
                                    <img :src="brandLogo" alt="AniTech mark">
                                </div>
                                <div>
                                    <h3>{{ document.label }}</h3>
                                    <p>{{ document.verification_status_label }}</p>
                                    <small v-if="document.remarks" class="farmer-app__upload-remark">{{ document.remarks }}</small>
                                </div>
                            </div>

                            <div class="farmer-app__upload-item-actions">
                                <span class="farmer-app__upload-badge" :class="statusBadgeClass(document)">
                                    {{ documentStatusLabel(document) }}
                                </span>

                                <input
                                    :ref="(el) => cameraInputs[document.type] = el"
                                    type="file"
                                    accept="image/*"
                                    capture="environment"
                                    class="farmer-app__upload-hidden-input"
                                    @change="setFile(document.type, $event, 'camera')"
                                >
                                <input
                                    :ref="(el) => fileInputs[document.type] = el"
                                    type="file"
                                    accept="image/*,.pdf"
                                    class="farmer-app__upload-hidden-input"
                                    @change="setFile(document.type, $event, 'library')"
                                >

                                <button type="button" class="farmer-app__upload-picker farmer-app__upload-picker--camera" @click="openCamera(document.type)">
                                    Capture Photo
                                </button>
                                <button type="button" class="farmer-app__upload-picker" @click="openFilePicker(document.type)">
                                    Choose File
                                </button>
                                <button
                                    v-if="selectedFileName(document.type)"
                                    type="button"
                                    class="farmer-app__upload-link"
                                    @click="openCamera(document.type)"
                                >
                                    Retake
                                </button>

                                <button v-if="selectedFileName(document.type)" type="button" class="farmer-app__upload-link" @click="removeFile(document.type)">
                                    Remove
                                </button>
                            </div>

                            <div v-if="selectedFileName(document.type)" class="farmer-app__upload-selected">
                                <div>
                                    <strong>{{ selectedFileName(document.type) }}</strong>
                                </div>
                                <img v-if="previewUrl(document.type)" :src="previewUrl(document.type)" :alt="`${document.label} preview`" class="farmer-app__upload-preview">
                                <div v-else class="farmer-app__upload-preview farmer-app__upload-preview--file">PDF ready</div>
                            </div>

                            <p v-if="fileErrors[document.type]" class="farmer-app__field-error">{{ fileErrors[document.type] }}</p>
                        </article>
                    </div>

                    <div v-if="uploading" class="farmer-app__upload-meter">
                        <span :style="{ width: `${uploadProgress}%` }"></span>
                    </div>

                    <div class="farmer-app__upload-actions">
                        <button type="button" class="farmer-app__btn farmer-app__upload-primary" :disabled="uploading || restoringDraft" @click="submitDocuments">
                            {{ uploading ? `Uploading ${uploadProgress}%` : 'Submit Selected Documents' }}
                        </button>
                        <button type="button" class="farmer-app__upload-link" @click="resetWorkflow">
                            Back to Search
                        </button>
                    </div>
                </div>
            </section>

            <div class="farmer-app__upload-help">
                <RouterLink :to="{ name: 'track' }">Need help with your application? Track your request</RouterLink>
            </div>

            <footer class="farmer-app__upload-footer">
                <p>&copy; 2023 AniTech Solutions. All rights reserved.</p>
                <small>Powered by Agri-Data Hub.</small>
            </footer>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__upload-checklist,
.farmer-app__upload-selected {
    display: grid;
    gap: 0.8rem;
}

.farmer-app__upload-hidden-input {
    display: none;
}

.farmer-app__upload-checklist {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
}

.farmer-app__upload-check {
    border: 1px solid rgba(0, 54, 41, 0.11);
    border-radius: 16px;
    padding: 0.8rem 0.95rem;
    background: #fbfdfb;
    display: grid;
    gap: 0.25rem;
}

.farmer-app__upload-check.is-flagged,
.farmer-app__upload-item.is-flagged {
    border-color: rgba(179, 84, 58, 0.3);
    background: #fff7f3;
}

.farmer-app__upload-blockers,
.farmer-app__upload-quality {
    border: 1px solid rgba(0, 54, 41, 0.1);
    border-radius: 18px;
    background: #fff;
    padding: 1rem;
    display: grid;
    gap: 0.55rem;
}

.farmer-app__upload-blockers ul,
.farmer-app__upload-quality ul {
    margin: 0;
    padding-left: 1rem;
    display: grid;
    gap: 0.35rem;
}

.farmer-app__upload-remark {
    color: #9b553f;
    display: block;
    margin-top: 0.35rem;
}

.farmer-app__upload-selected {
    border-top: 1px dashed rgba(0, 54, 41, 0.14);
    padding-top: 0.8rem;
    align-items: center;
}

.farmer-app__upload-preview {
    width: 100%;
    max-width: 160px;
    border-radius: 16px;
    object-fit: cover;
    border: 1px solid rgba(0, 54, 41, 0.1);
}

.farmer-app__upload-preview--file {
    display: grid;
    place-items: center;
    min-height: 120px;
    background: #eff5f1;
    color: #33574c;
    font-weight: 700;
}

.farmer-app__upload-picker {
    border: 1px solid rgba(0, 54, 41, 0.12);
    background: #f7fbf8;
    color: #0c5f49;
    border-radius: 999px;
    padding: 0.72rem 1rem;
    font-weight: 700;
}

.farmer-app__upload-picker--camera {
    background: #0c6a52;
    border-color: #0c6a52;
    color: #fff;
}

.farmer-app__upload-badge.is-review {
    background: #edf4ff;
    color: #2956a3;
}

.farmer-app__upload-badge.is-verified {
    background: #ebfaf1;
    color: #1f7a47;
}

.farmer-app__upload-meter {
    width: 100%;
    height: 10px;
    border-radius: 999px;
    background: #edf3ef;
    overflow: hidden;
}

.farmer-app__upload-meter span {
    display: block;
    height: 100%;
    background: linear-gradient(90deg, #0c6a52, #59b27d);
}
</style>
