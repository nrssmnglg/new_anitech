<script setup>
import { computed, reactive, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
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

const uploadStatusLabel = computed(() => {
    if (!application.value) {
        return '';
    }

    if (documentSummary.value.flagged > 0) {
        return 'Resubmission';
    }

    return application.value.status_label || 'Submitted';
});

const formattedValidationErrors = computed(() => Object.entries(validationErrors.value)
    .filter(([key]) => !['application_no', 'birth_date'].includes(key))
    .map(([key, message]) => {
    const normalizedKey = String(key ?? '').replace(/^documents\./, '').replace(/^document$/, 'uploaded file');
    const label = normalizedKey
        .split('.')
        .pop()
        .split('_')
        .filter(Boolean)
        .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
        .join(' ');

    return {
        key,
        label: label || 'Error',
        message: String(message ?? ''),
    };
}));

const draftAttachmentKey = (documentType) => `membership-upload:${lookup.application_no || 'pending'}:${documentType}`;

const coerceUploadFile = (value, fallbackName = 'document') => {
    if (value instanceof File) {
        return value;
    }

    if (value instanceof Blob) {
        const extension = value.type === 'application/pdf' ? 'pdf' : 'jpg';
        const safeName = String(fallbackName || 'document').includes('.')
            ? String(fallbackName)
            : `${fallbackName}.${extension}`;

        return new File([value], safeName, {
            type: value.type || 'application/octet-stream',
            lastModified: Date.now(),
        });
    }

    return null;
};

const createPreviewUrl = (file) => {
    if (!file || file.type === 'application/pdf') {
        return '';
    }

    return URL.createObjectURL(file);
};

const normalizeBirthDate = (value) => {
    const raw = String(value ?? '').trim();
    if (!raw) {
        return '';
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return raw;
    }

    const parsed = new Date(raw);
    if (Number.isNaN(parsed.getTime())) {
        return raw;
    }

    const year = parsed.getFullYear();
    const month = String(parsed.getMonth() + 1).padStart(2, '0');
    const day = String(parsed.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
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
            const restoredFile = coerceUploadFile(payload?.file, payload?.name || document.type);

            if (!restoredFile) {
                continue;
            }

            files[document.type] = restoredFile;
            revokePreview(document.type);
            previewUrls[document.type] = createPreviewUrl(restoredFile);
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
        validationErrors.value = extractValidationErrors(err);
        error.value = Object.keys(validationErrors.value).length ? '' : extractApiMessage(err, t('upload.load_failed'));
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
        const birthDate = normalizeBirthDate(lookup.birth_date);
        let latestApplication = application.value;

        for (const [index, [type, file]] of selectedFiles.entries()) {
            const uploadFile = coerceUploadFile(file, file?.name || type);

            if (!uploadFile) {
                throw new Error(`Selected file for ${type} is invalid.`);
            }

            const formData = new FormData();
            formData.append('birth_date', birthDate);
            formData.append('document_type', type);
            formData.append('document', uploadFile, uploadFile.name);

            const response = await farmerApi.post(`/application/${encodeURIComponent(lookup.application_no)}/documents`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                params: {
                    birth_date: birthDate,
                    document_type: type,
                },
                onUploadProgress: (event) => {
                    if (!event.total) {
                        return;
                    }

                    const filePortion = event.loaded / event.total;
                    const completedPortion = index / selectedFiles.length;
                    const totalProgress = ((completedPortion + (filePortion / selectedFiles.length)) * 100);

                    uploadProgress.value = Math.max(uploadProgress.value, Math.round(totalProgress));
                },
            });

            latestApplication = response?.data?.data?.application ?? latestApplication;
        }

        uploadProgress.value = 100;
        application.value = latestApplication;

        await Promise.all(
            selectedFiles.map(([type]) => removeFile(type)),
        );

        const remainingDocuments = (latestApplication?.documents ?? []).filter((document) => !document.uploaded);
        if (remainingDocuments.length > 0) {
            success.value = `${selectedFiles.length} document${selectedFiles.length === 1 ? '' : 's'} uploaded. Add the remaining required documents to continue.`;
            lookup.current_step = 'upload';
        } else {
            success.value = 'Documents uploaded successfully. Redirecting to tracking...';
            lookup.current_step = 'complete';
            window.setTimeout(() => {
                window.location.href = farmerPublicUrl('/farmer/upload-complete', {
                    application_no: lookup.application_no,
                    birth_date: lookup.birth_date,
                });
            }, 700);
        }
    } catch (err) {
        validationErrors.value = extractValidationErrors(err);
        error.value = Object.keys(validationErrors.value).length ? '' : extractApiMessage(err, t('upload.upload_failed'));
    } finally {
        uploading.value = false;
    }
};

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
                                class="farmer-app__date-input"
                            >
                            <small v-if="validationErrors.birth_date" class="farmer-app__field-error">{{ validationErrors.birth_date }}</small>
                        </label>
                    </div>

                    <button type="button" class="farmer-app__btn farmer-app__upload-primary" :disabled="loading" @click="trackApplication">
                        {{ loading ? 'Loading...' : 'Load Application' }}
                    </button>
                </div>

                <AppState v-if="error" type="error" :message="error" />
                <AppState v-if="success" type="success" :message="success" />
                <div v-if="formattedValidationErrors.length" class="farmer-app__upload-error-list">
                    <ul>
                        <li v-for="item in formattedValidationErrors" :key="item.key">
                            {{ item.label }}: {{ item.message }}
                        </li>
                    </ul>
                </div>

                <div v-if="application" class="farmer-app__upload-workflow">
                    <article class="farmer-app__upload-progress">
                        <div class="farmer-app__upload-progress-main">
                            <div class="farmer-app__upload-progress-count">
                                {{ documentSummary.uploaded }}/{{ documentSummary.total }}
                            </div>
                            <div>
                                <strong>Progress: {{ documentSummary.total ? Math.round((documentSummary.uploaded / documentSummary.total) * 100) : 0 }}%</strong>
                                <p>Application Status: <span>{{ uploadStatusLabel }}</span></p>
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
                                <span class="farmer-app__upload-badge" :class="statusBadgeClass(document)">
                                    {{ documentStatusLabel(document) }}
                                </span>
                            </div>

                            <div class="farmer-app__upload-item-actions">
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

                                <div class="farmer-app__upload-action-row">
                                    <button type="button" class="farmer-app__upload-picker farmer-app__upload-picker--camera" @click="openCamera(document.type)">
                                        Camera
                                    </button>
                                    <button type="button" class="farmer-app__upload-picker" @click="openFilePicker(document.type)">
                                        File
                                    </button>
                                </div>
                            </div>

                            <div v-if="selectedFileName(document.type)" class="farmer-app__upload-selected">
                                <div class="farmer-app__upload-selected-head">
                                    <strong>{{ selectedFileName(document.type) }}</strong>
                                    <div class="farmer-app__upload-selected-links">
                                        <button
                                            type="button"
                                            class="farmer-app__upload-link"
                                            @click="openCamera(document.type)"
                                        >
                                            Retake
                                        </button>
                                        <button type="button" class="farmer-app__upload-link" @click="removeFile(document.type)">
                                            Remove
                                        </button>
                                    </div>
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

        </main>
    </div>
</template>

<style scoped>
.farmer-app__upload-screen {
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    background:
        linear-gradient(180deg, rgba(139, 199, 213, 0.78), rgba(243, 235, 190, 0.7)),
        url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
}

.farmer-app__upload-backdrop {
    position: absolute;
    inset: 0;
    backdrop-filter: blur(2px);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0.1));
}

.farmer-app__upload-shell {
    position: relative;
    z-index: 1;
    width: min(1120px, calc(100% - 2rem));
    margin: 0 auto;
    padding: 2.5rem 0 3.5rem;
}

.farmer-app__upload-hero {
    text-align: center;
    margin-bottom: 1.75rem;
}

.farmer-app__upload-hero h1 {
    margin: 0;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    color: #10261f;
}

.farmer-app__upload-hero p {
    margin: 0.75rem auto 0;
    max-width: 40rem;
    color: rgba(16, 38, 31, 0.78);
    font-size: 1rem;
}

.farmer-app__upload-card {
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.75);
    border-radius: 30px;
    box-shadow: 0 24px 70px rgba(20, 54, 44, 0.12);
    padding: 1.35rem;
}

.farmer-app__upload-workflow,
.farmer-app__upload-checklist,
.farmer-app__upload-selected {
    display: grid;
    gap: 0.8rem;
}

.farmer-app__upload-workflow {
    gap: 1.15rem;
}

.farmer-app__upload-lookup,
.farmer-app__upload-grid {
    display: grid;
    gap: 1rem;
}

.farmer-app__upload-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.farmer-app__upload-field {
    display: grid;
    gap: 0.45rem;
}

.farmer-app__upload-field span {
    font-size: 0.9rem;
    font-weight: 700;
    color: #19372e;
}

.farmer-app__upload-field input {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    border: 1px solid rgba(12, 106, 82, 0.15);
    background: #f7fbf8;
    border-radius: 16px;
    padding: 0.95rem 1rem;
    color: #163229;
    outline: none;
    box-sizing: border-box;
}

.farmer-app__upload-field input:focus {
    border-color: #0c6a52;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(12, 106, 82, 0.1);
}

.farmer-app__date-input {
    appearance: none;
    -webkit-appearance: none;
    display: block;
    min-height: 3.5rem;
    line-height: 1.2;
    text-align: left;
}

.farmer-app__date-input::-webkit-date-and-time-value,
.farmer-app__date-input::-webkit-datetime-edit,
.farmer-app__date-input::-webkit-datetime-edit-fields-wrapper {
    text-align: left;
    padding: 0;
}

.farmer-app__date-input::-webkit-calendar-picker-indicator {
    margin: 0;
}

.farmer-app__upload-hidden-input {
    display: none;
}

.farmer-app__upload-progress {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.2rem;
    border-radius: 20px;
    background: #102b23;
    color: #f4fbf6;
}

.farmer-app__upload-progress-main {
    display: flex;
    align-items: center;
    gap: 1.1rem;
    min-width: 0;
}

.farmer-app__upload-progress-count {
    width: 2.8rem;
    height: 2.8rem;
    border-radius: 999px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border: 3px solid rgba(136, 212, 171, 0.9);
    font-weight: 800;
    font-size: 1rem;
}

.farmer-app__upload-progress-main strong,
.farmer-app__upload-progress-meta p,
.farmer-app__upload-progress-meta small {
    display: block;
}

.farmer-app__upload-progress-main p,
.farmer-app__upload-progress-meta p,
.farmer-app__upload-progress-meta small {
    margin: 0.2rem 0 0;
}

.farmer-app__upload-progress-main p {
    color: rgba(238, 247, 241, 0.75);
}

.farmer-app__upload-progress-main p span {
    color: #8fe2b8;
    font-weight: 700;
}

.farmer-app__upload-progress-meta {
    text-align: right;
    color: rgba(244, 251, 246, 0.9);
}

.farmer-app__upload-progress-meta small {
    color: rgba(244, 251, 246, 0.7);
}

.farmer-app__upload-checklist {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
}

.farmer-app__upload-check {
    border: 1px solid rgba(0, 54, 41, 0.11);
    border-radius: 16px;
    padding: 0.9rem 1rem;
    background: #fbfdfb;
    display: grid;
    gap: 0.3rem;
}

.farmer-app__upload-check.is-flagged,
.farmer-app__upload-item.is-flagged {
    border-color: rgba(179, 84, 58, 0.3);
    background: #fff7f3;
}

.farmer-app__upload-quality,
.farmer-app__upload-error-list {
    border: 1px solid rgba(0, 54, 41, 0.1);
    border-radius: 18px;
    background: #fff;
    padding: 1rem 1.1rem;
    display: grid;
    gap: 0.65rem;
}

.farmer-app__upload-quality ul,
.farmer-app__upload-error-list ul {
    margin: 0;
    padding-left: 1rem;
    display: grid;
    gap: 0.35rem;
}

.farmer-app__upload-error-list {
    border-color: rgba(179, 84, 58, 0.28);
    background: #fff5f2;
    color: #8d3f2a;
}

.farmer-app__upload-remark {
    color: #9b553f;
    display: block;
    margin-top: 0.35rem;
}

.farmer-app__upload-list {
    display: grid;
    gap: 1.1rem;
}

.farmer-app__upload-item {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(0, 0.95fr) minmax(168px, 208px);
    gap: 1.15rem;
    align-items: start;
    border: 1px solid rgba(0, 54, 41, 0.11);
    border-radius: 22px;
    background: #fff;
    padding: 1.1rem;
}

.farmer-app__upload-item-head {
    display: flex;
    align-items: flex-start;
    gap: 0.9rem;
    min-width: 0;
}

.farmer-app__upload-item-head h3,
.farmer-app__upload-item-head p {
    margin: 0;
}

.farmer-app__upload-item-head h3 {
    color: #17352c;
    font-size: 1.12rem;
    line-height: 1.2;
}

.farmer-app__upload-item-head p {
    color: #65776f;
    margin-top: 0.28rem;
    line-height: 1.35;
    font-size: 0.94rem;
}

.farmer-app__upload-item-icon {
    width: 2.5rem;
    height: 2.5rem;
    flex: 0 0 auto;
    border-radius: 12px;
    background: #f5f8f5;
    border: 1px solid rgba(0, 54, 41, 0.08);
    display: grid;
    place-items: center;
    overflow: hidden;
}

.farmer-app__upload-item-icon img {
    width: 1.6rem;
    height: 1.6rem;
    object-fit: contain;
}

.farmer-app__upload-item-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    align-items: center;
    align-content: flex-start;
    min-width: 0;
}

.farmer-app__upload-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2rem;
    padding: 0.35rem 0.8rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    background: #eef3f0;
    color: #35564b;
    margin-right: 0.1rem;
}

.farmer-app__upload-badge.is-pending {
    background: #f1f3f1;
    color: #5f7269;
}

.farmer-app__upload-badge.is-uploaded {
    background: #e4f5ea;
    color: #196840;
}

.farmer-app__upload-badge.is-flagged {
    background: #fff0ea;
    color: #a14d34;
}

.farmer-app__upload-selected {
    border-left: 1px dashed rgba(0, 54, 41, 0.14);
    padding-left: 1.15rem;
    align-items: start;
    justify-items: start;
    min-width: 0;
    gap: 0.55rem;
}

.farmer-app__upload-selected-head {
    width: 100%;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.farmer-app__upload-selected-head strong {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.farmer-app__upload-selected-links {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex: 0 0 auto;
}

.farmer-app__upload-preview {
    width: 100%;
    max-width: 112px;
    aspect-ratio: 4 / 5;
    border-radius: 14px;
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
    padding: 0.55rem 0.9rem;
    font-weight: 700;
    min-height: 2.25rem;
    min-width: 0;
    font-size: 0.85rem;
}

.farmer-app__upload-picker--camera {
    background: #0c6a52;
    border-color: #0c6a52;
    color: #fff;
}

.farmer-app__upload-action-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.farmer-app__upload-link {
    border: none;
    background: transparent;
    color: #17704f;
    font-weight: 700;
    padding: 0;
    cursor: pointer;
    text-decoration: none;
}

.farmer-app__upload-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem;
    justify-content: space-between;
    padding-top: 0.25rem;
}

.farmer-app__upload-primary {
    min-height: 3rem;
}

.farmer-app__upload-help,
.farmer-app__upload-footer {
    text-align: center;
    margin-top: 1.15rem;
    color: rgba(16, 38, 31, 0.8);
}

.farmer-app__upload-help a {
    color: #0c6a52;
    font-weight: 700;
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

@media (max-width: 920px) {
    .farmer-app__upload-item {
        grid-template-columns: 1fr;
        gap: 0.95rem;
    }

    .farmer-app__upload-selected {
        border-left: none;
        border-top: 1px dashed rgba(0, 54, 41, 0.14);
        padding-left: 0;
        padding-top: 0.95rem;
        justify-items: start;
    }

    .farmer-app__upload-progress {
        flex-direction: column;
        align-items: stretch;
    }

    .farmer-app__upload-progress-meta {
        text-align: left;
    }
}

@media (max-width: 720px) {
    .farmer-app__upload-shell {
        width: min(100% - 1rem, 100%);
        padding-top: 1rem;
        padding-bottom: 2rem;
    }

    .farmer-app__upload-card {
        border-radius: 24px;
        padding: 0.95rem;
    }

    .farmer-app__upload-grid {
        grid-template-columns: 1fr;
    }

    .farmer-app__upload-checklist {
        grid-template-columns: 1fr;
    }

    .farmer-app__upload-item-actions {
        align-items: flex-start;
        gap: 0.6rem;
    }

    .farmer-app__upload-picker {
        flex: 1 1 0;
        justify-content: center;
        min-width: 5.5rem;
    }

    .farmer-app__upload-item-head h3 {
        font-size: 1.02rem;
    }

    .farmer-app__upload-action-row {
        width: 100%;
    }

    .farmer-app__upload-selected-head {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.35rem;
    }

    .farmer-app__upload-selected-links {
        gap: 0.75rem;
    }

    .farmer-app__upload-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 0.8rem;
    }
}

/* Compact upload workflow */
.farmer-app__upload-screen { min-height: 100dvh; overflow: visible; background: #f3f7f4; }
.farmer-app__upload-shell { width: min(100%, 760px); min-height: 100dvh; margin: 0 auto; padding: 16px; }
.farmer-app__upload-hero { margin-bottom: 12px; padding: 0; text-align: left; }
.farmer-app__upload-hero h1 { margin: 0; color: var(--pwa-ink); font-size: 1.2rem; line-height: 1.3; }
.farmer-app__upload-hero p { min-height: 0; margin: 3px 0 0; color: var(--pwa-muted); font-size: .78rem; line-height: 1.4; position: static; overflow: visible; background: none; }
.farmer-app__upload-card { width: 100%; padding: 14px; border: 1px solid var(--pwa-border); border-radius: 12px; background: #fff; box-shadow: var(--pwa-shadow-soft); }
.farmer-app__upload-lookup, .farmer-app__upload-workflow { gap: 12px; }
.farmer-app__upload-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.farmer-app__upload-field { gap: 5px; }
.farmer-app__upload-field span { margin: 0; color: var(--pwa-ink); font-size: .75rem; }
.farmer-app__upload-field input { min-height: 44px; padding: 9px 11px !important; border: 1px solid var(--pwa-border); border-radius: 9px; background: #fff; font-size: .82rem; box-shadow: none; }
.farmer-app__upload-primary { min-height: 44px; padding: 9px 12px; border-radius: 9px; background: var(--pwa-green-800); box-shadow: none; font-size: .78rem; }
.farmer-app__upload-progress { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 10px; padding: 11px; border: 1px solid var(--pwa-border); border-radius: 9px; background: var(--pwa-surface-soft); color: var(--pwa-ink); }
.farmer-app__upload-progress-main { gap: 9px; }
.farmer-app__upload-progress-count { width: 42px; height: 42px; border: 2px solid #79cda5; border-radius: 9px; color: var(--pwa-green-800); font-size: .8rem; }
.farmer-app__upload-progress-main strong { font-size: .8rem; }
.farmer-app__upload-progress-main p, .farmer-app__upload-progress-meta p, .farmer-app__upload-progress-meta small { margin: 2px 0 0; font-size: .7rem; }
.farmer-app__upload-progress-main p, .farmer-app__upload-progress-meta, .farmer-app__upload-progress-meta p, .farmer-app__upload-progress-meta small { color: var(--pwa-muted); }
.farmer-app__upload-progress-main p span { color: var(--pwa-green-800); }
.farmer-app__upload-checklist { grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 7px; }
.farmer-app__upload-check { padding: 8px 9px; border-radius: 8px; font-size: .7rem; }
.farmer-app__upload-list { gap: 9px; }
.farmer-app__upload-item { grid-template-columns: 1fr; gap: 8px; padding: 10px; border-radius: 10px; box-shadow: none; }
.farmer-app__upload-item-head { align-items: center; gap: 8px; }
.farmer-app__upload-item-head > div:nth-child(2) { min-width: 0; flex: 1; }
.farmer-app__upload-item-icon { width: 34px; height: 34px; border-radius: 8px; }
.farmer-app__upload-item-icon img { width: 22px; height: 22px; }
.farmer-app__upload-item-head h3 { margin: 0; font-size: .85rem; }
.farmer-app__upload-item-head p, .farmer-app__upload-remark { margin: 2px 0 0; font-size: .68rem; }
.farmer-app__upload-item-actions { display: block; }
.farmer-app__upload-badge { min-height: 24px; padding: 4px 7px; border-radius: 999px; font-size: .62rem; }
.farmer-app__upload-action-row { justify-content: flex-end; gap: 6px; width: 100%; }
.farmer-app__upload-picker { flex: 0 0 auto; min-width: 76px; min-height: 36px; padding: 6px 10px; border-radius: 8px; font-size: .7rem; }
.farmer-app__upload-selected { grid-column: 1 / -1; gap: 8px; padding: 9px; border-radius: 8px; }
.farmer-app__upload-selected-head strong { font-size: .72rem; }
.farmer-app__upload-link { min-height: 40px; padding: 7px 9px; font-size: .7rem; }
.farmer-app__upload-preview { max-height: 150px; border-radius: 8px; object-fit: contain; }
.farmer-app__upload-actions { gap: 8px; }
.farmer-app__upload-actions .farmer-app__upload-primary { flex: 1; }
.farmer-app__upload-meter { height: 7px; border-radius: 999px; }
.farmer-app__upload-error-list { padding: 10px; border-radius: 8px; font-size: .72rem; }

@media (max-width: 560px) {
    .farmer-app__upload-shell { width: 100%; padding: 10px; }
    .farmer-app__upload-grid { grid-template-columns: 1fr; }
    .farmer-app__upload-progress { grid-template-columns: 1fr; }
    .farmer-app__upload-progress-meta { padding-top: 7px; border-top: 1px solid var(--pwa-border); text-align: left; }
    .farmer-app__upload-item { grid-template-columns: 1fr; }
    .farmer-app__upload-item-icon { display: none; }
    .farmer-app__upload-actions { align-items: stretch; flex-direction: column; }
}
</style>
