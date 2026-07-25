<script setup>
import { computed, watch, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import AppState from '../components/ui/AppState.vue';
import { useDraft } from '../composables/useDraft';
import { useLocale } from '../composables/useLocale';
import { extractApiMessage, extractValidationErrors } from '../utils/api';
import { farmerApi } from '../services/api';
import { useSyncQueueStore } from '../stores/syncQueue';
import { farmerPublicUrl } from '../utils/paths';
import { brandLogoUrl } from '../utils/asset';

const route = useRoute();
const shell = window.__FARMER_PWA__ ?? {};
const syncQueue = useSyncQueueStore();
const { t } = useLocale();
const loading = ref(false);
const error = ref('');
const success = ref('');
const validationErrors = ref({});
const localErrors = ref({});
const reapplyMessage = ref('');
const duplicateGuidance = ref('');
const brandLogo = brandLogoUrl();

const defaultFormState = {
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',
    birth_date: '',
    sex: '',
    civil_status: '',
    mobile_number: '',
    email: '',
    barangay_id: '',
    association_id: '',
    address: '',
    remarks: '',
    reapply_from_application_id: String(route.query.reapply_from_application_id ?? ''),
    step: 1,
};

const { draft: form, clearDraft } = useDraft('membership-application:create', defaultFormState, {
    onHydrate(currentDraft) {
        currentDraft.reapply_from_application_id = String(route.query.reapply_from_application_id ?? currentDraft.reapply_from_application_id ?? '');
    },
});

const barangays = computed(() => shell.publicData?.barangays ?? []);
const associations = computed(() => shell.publicData?.associations ?? []);
const filteredAssociations = computed(() => {
    if (!form.barangay_id) {
        return associations.value;
    }

    return associations.value.filter((association) => String(association.barangay_id) === String(form.barangay_id));
});

const requiredDocumentChecklist = computed(() => (
    shell.publicData?.applicationDocumentChecklist ?? ['2x2 Picture', 'Birth Certificate', 'Cedula']
));

const currentStep = computed({
    get: () => Number(form.step || 1),
    set: (value) => {
        form.step = Number(value);
    },
});

const stepItems = computed(() => [
    { id: 1, label: 'Personal Details' },
    { id: 2, label: 'Location' },
    { id: 3, label: 'Membership & Documents' },
    { id: 4, label: 'Review & Submit' },
]);

const applicantName = computed(() => [form.first_name, form.middle_name, form.last_name, form.suffix].filter(Boolean).join(' '));
const selectedBarangay = computed(() => barangays.value.find((item) => String(item.id) === String(form.barangay_id))?.name || '');
const selectedAssociation = computed(() => associations.value.find((item) => String(item.id) === String(form.association_id))?.name || '');
const submitLabel = computed(() => form.reapply_from_application_id ? 'Resubmit Application' : 'Submit Application');
const headingTitle = computed(() => form.reapply_from_application_id ? 'Correct and Resubmit' : 'Apply as New Farmer');
const headingCopy = computed(() => (
    form.reapply_from_application_id
        ? t('apply.copy_reapply')
        : t('apply.copy_new')
));
const autosaveMessage = computed(() => t('apply.draft_saved', { step: currentStep.value, total: stepItems.value.length }));
const translatedHeadingTitle = computed(() => form.reapply_from_application_id ? t('apply.title_reapply') : t('apply.title_new'));

const age = computed(() => {
    if (!form.birth_date) {
        return null;
    }

    const today = new Date();
    const birthDate = new Date(form.birth_date);

    if (Number.isNaN(birthDate.getTime())) {
        return null;
    }

    let years = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        years -= 1;
    }

    return years;
});

const estimatedMemberType = computed(() => {
    if (age.value === null) {
        return {
            code: 'Pending',
            label: 'Member type will be determined after you enter your birth date.',
        };
    }

    if (age.value >= 60) {
        return {
            code: 'NSC',
            label: 'New Senior Citizen',
        };
    }

    return {
        code: 'NM',
        label: 'New Member',
    };
});

const birthDateGuidance = computed(() => {
    if (age.value === null) {
        return '';
    }

    if (age.value < 18) {
        return 'Applicants must be at least 18 years old to continue.';
    }

    if (age.value >= 60) {
        return 'Senior age detected. Your member type will follow the senior-citizen fee schedule.';
    }

    return '';
});

const syncAssociation = () => {
    if (!form.barangay_id) {
        form.association_id = '';
        return;
    }

    const matches = filteredAssociations.value.some((association) => String(association.id) === String(form.association_id));

    if (!matches) {
        form.association_id = filteredAssociations.value[0] ? String(filteredAssociations.value[0].id) : '';
    }
};

watch(() => form.barangay_id, syncAssociation);

const setLocalError = (field, message) => {
    localErrors.value = {
        ...localErrors.value,
        [field]: message,
    };
};

const clearLocalError = (field) => {
    const nextErrors = { ...localErrors.value };
    delete nextErrors[field];
    localErrors.value = nextErrors;
};

const validateMobileNumber = () => {
    if (!form.mobile_number) {
        return true;
    }

    const normalized = String(form.mobile_number).replace(/[\s-]/g, '');
    const valid = /^(09|\+639)\d{9}$/.test(normalized);

    if (!valid) {
        setLocalError('mobile_number', 'Use a valid mobile number such as 09171234567 or +639171234567.');
        return false;
    }

    clearLocalError('mobile_number');
    return true;
};

const validateBirthDate = () => {
    if (!form.birth_date) {
        setLocalError('birth_date', 'Birth date is required.');
        return false;
    }

    if (age.value === null) {
        setLocalError('birth_date', 'Enter a valid birth date.');
        return false;
    }

    if (age.value < 18) {
        setLocalError('birth_date', 'Applicant must be at least 18 years old.');
        return false;
    }

    clearLocalError('birth_date');
    return true;
};

const validateStep = (step) => {
    let valid = true;

    if (step === 1) {
        ['first_name', 'last_name'].forEach((field) => {
            if (!form[field]) {
                setLocalError(field, 'This field is required.');
                valid = false;
            } else {
                clearLocalError(field);
            }
        });

        if (!form.sex) {
            setLocalError('sex', 'Please select a gender.');
            valid = false;
        } else {
            clearLocalError('sex');
        }

        if (!form.civil_status) {
            setLocalError('civil_status', 'Please select a civil status.');
            valid = false;
        } else {
            clearLocalError('civil_status');
        }

        valid = validateBirthDate() && valid;
        valid = validateMobileNumber() && valid;
    }

    if (step === 2) {
        if (!form.barangay_id) {
            setLocalError('barangay_id', 'Barangay is required.');
            valid = false;
        } else {
            clearLocalError('barangay_id');
        }

        if (!form.address) {
            setLocalError('address', 'Home address is required.');
            valid = false;
        } else {
            clearLocalError('address');
        }
    }

    return valid;
};

const goToStep = (step) => {
    if (step > currentStep.value && !validateStep(currentStep.value)) {
        return;
    }

    currentStep.value = step;
};

const nextStep = () => {
    if (!validateStep(currentStep.value)) {
        return;
    }

    currentStep.value = Math.min(stepItems.value.length, currentStep.value + 1);
};

const previousStep = () => {
    currentStep.value = Math.max(1, currentStep.value - 1);
};

const loadReapplyContext = async () => {
    const applicationNo = String(route.query.application_no ?? '');
    const birthDate = String(route.query.birth_date ?? '');

    if (!applicationNo || !birthDate) {
        return;
    }

    try {
        const response = await farmerApi.get('/application/track', {
            params: {
                application_no: applicationNo,
                birth_date: birthDate,
            },
        });

        const application = response?.data?.data;
        if (!application?.reapply?.can_reapply) {
            return;
        }

        form.first_name = application.farmer?.first_name ?? '';
        form.middle_name = application.farmer?.middle_name ?? '';
        form.last_name = application.farmer?.last_name ?? '';
        form.suffix = application.farmer?.suffix ?? '';
        form.birth_date = application.farmer?.birth_date ?? '';
        form.sex = application.farmer?.sex ?? '';
        form.civil_status = application.farmer?.civil_status ?? '';
        form.mobile_number = application.farmer?.mobile_number ?? '';
        form.email = application.farmer?.email ?? '';
        form.address = application.farmer?.address ?? '';
        form.barangay_id = String(application.farmer?.barangay_id ?? '');
        form.association_id = String(application.farmer?.association_id ?? '');
        form.remarks = application.remarks ?? '';
        form.reapply_from_application_id = String(application.reapply?.application_id ?? '');
        form.step = 1;

        const summary = [application.rejection?.reason_label, application.rejection?.details].filter(Boolean).join(': ');
        reapplyMessage.value = summary
            ? `Correction note: ${summary}`
            : 'The office returned your application. Update the requested details and resubmit.';
    } catch (err) {
        error.value = extractApiMessage(err, 'Unable to load the previous application for correction.');
    }
};

const submit = async () => {
    if (!validateStep(1) || !validateStep(2)) {
        currentStep.value = !validateStep(1) ? 1 : 2;
        return;
    }

    loading.value = true;
    error.value = '';
    success.value = '';
    duplicateGuidance.value = '';
    validationErrors.value = {};

    try {
        const payload = { ...form };
        delete payload.step;

        if (typeof navigator !== 'undefined' && !navigator.onLine) {
            syncQueue.enqueue({
                type: 'application.create',
                url: '/application',
                payload,
                meta: {
                    applicant_name: applicantName.value,
                    birth_date: form.birth_date,
                },
            });
            clearDraft();
            success.value = t('apply.offline_saved');
            return;
        }

        const response = await farmerApi.post('/application', payload);

        const data = response?.data?.data;
        const trackingPayload = {
            application_no: data?.application_no,
            birth_date: form.birth_date,
        };

        window.localStorage.setItem('anitech_mobile_application', JSON.stringify(trackingPayload));
        clearDraft();
        success.value = response?.data?.message ?? 'Application submitted successfully.';

        window.setTimeout(() => {
            window.location.href = farmerPublicUrl('/farmer/upload', trackingPayload);
        }, 700);
    } catch (err) {
        const extractedErrors = extractValidationErrors(err);
        if (!err?.response) {
            const payload = { ...form };
            delete payload.step;
            syncQueue.enqueue({
                type: 'application.create',
                url: '/application',
                payload,
                meta: {
                    applicant_name: applicantName.value,
                    birth_date: form.birth_date,
                },
            });
            clearDraft();
            success.value = t('apply.offline_saved');
        } else {
            error.value = extractApiMessage(err, t('apply.submit_failed'));
            validationErrors.value = extractedErrors;
        }

        if (extractedErrors.application) {
            duplicateGuidance.value = 'A possible duplicate farmer record may already exist in the system. If this is your record, do not create another application. Contact the office and provide your full name, birth date, and mobile number for verification.';
        }
    } finally {
        loading.value = false;
    }
};

loadReapplyContext();
</script>

<template>
    <div class="farmer-app__apply-screen">
        <div class="farmer-app__apply-backdrop" aria-hidden="true"></div>

        <main class="farmer-app__apply-shell">
            <section class="farmer-app__apply-card">
                <div class="farmer-app__apply-brand">
                    <div class="farmer-app__apply-mark">
                        <img :src="brandLogo" alt="AniTech mark">
                    </div>
                    <h1>{{ translatedHeadingTitle }}</h1>
                    <p>{{ headingCopy }}</p>
                </div>

                <div class="farmer-app__apply-steps">
                    <button
                        v-for="item in stepItems"
                        :key="item.id"
                        type="button"
                        class="farmer-app__apply-step"
                        :class="{ 'is-active': item.id === currentStep, 'is-complete': item.id < currentStep }"
                        @click="goToStep(item.id)"
                    >
                        <span>{{ item.id }}</span>
                        <strong>{{ item.label }}</strong>
                    </button>
                </div>

                <AppState v-if="reapplyMessage" :message="reapplyMessage" />
                <AppState v-if="duplicateGuidance" type="error" :message="duplicateGuidance" />
                <AppState v-if="error" type="error" :message="error" />
                <AppState v-if="success" :message="`${success} Redirecting to upload.`" />
                <AppState v-if="!error && !success" :message="autosaveMessage" />

                <form class="farmer-app__apply-form" @submit.prevent="submit">
                    <template v-if="currentStep === 1">
                        <div class="farmer-app__apply-section-label">Personal Information</div>

                        <div class="farmer-app__apply-grid">
                            <label class="farmer-app__apply-field">
                                <span>First Name</span>
                                <input v-model="form.first_name" type="text" placeholder="Juan">
                                <small v-if="localErrors.first_name || validationErrors.first_name" class="farmer-app__field-error">{{ localErrors.first_name || validationErrors.first_name }}</small>
                            </label>

                            <label class="farmer-app__apply-field">
                                <span>Middle Name</span>
                                <input v-model="form.middle_name" type="text" placeholder="Santos">
                                <small v-if="validationErrors.middle_name" class="farmer-app__field-error">{{ validationErrors.middle_name }}</small>
                            </label>
                        </div>

                        <div class="farmer-app__apply-grid">
                            <label class="farmer-app__apply-field">
                                <span>Last Name</span>
                                <input v-model="form.last_name" type="text" placeholder="Dela Cruz">
                                <small v-if="localErrors.last_name || validationErrors.last_name" class="farmer-app__field-error">{{ localErrors.last_name || validationErrors.last_name }}</small>
                            </label>

                            <label class="farmer-app__apply-field">
                                <span>Suffix</span>
                                <input v-model="form.suffix" type="text" placeholder="Jr.">
                                <small v-if="validationErrors.suffix" class="farmer-app__field-error">{{ validationErrors.suffix }}</small>
                            </label>
                        </div>

                        <div class="farmer-app__apply-grid">
                            <label class="farmer-app__apply-field">
                                <span>Birth Date</span>
                                <input v-model="form.birth_date" type="date">
                                <small v-if="localErrors.birth_date || validationErrors.birth_date" class="farmer-app__field-error">{{ localErrors.birth_date || validationErrors.birth_date }}</small>
                                <small v-if="birthDateGuidance" class="farmer-app__field-hint">{{ birthDateGuidance }}</small>
                            </label>

                            <label class="farmer-app__apply-field">
                                <span>Gender</span>
                                <select v-model="form.sex">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                <small v-if="localErrors.sex || validationErrors.sex" class="farmer-app__field-error">{{ localErrors.sex || validationErrors.sex }}</small>
                            </label>
                        </div>

                        <div class="farmer-app__apply-grid">
                            <label class="farmer-app__apply-field">
                                <span>Civil Status</span>
                                <select v-model="form.civil_status">
                                    <option value="">Select</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="widowed">Widowed</option>
                                    <option value="separated">Separated</option>
                                </select>
                                <small v-if="localErrors.civil_status || validationErrors.civil_status" class="farmer-app__field-error">{{ localErrors.civil_status || validationErrors.civil_status }}</small>
                            </label>

                            <label class="farmer-app__apply-field">
                                <span>Mobile Number</span>
                                <input v-model="form.mobile_number" type="tel" placeholder="09171234567">
                                <small v-if="localErrors.mobile_number || validationErrors.mobile_number" class="farmer-app__field-error">{{ localErrors.mobile_number || validationErrors.mobile_number }}</small>
                            </label>
                        </div>

                        <label class="farmer-app__apply-field">
                            <span>Email Address</span>
                            <input v-model="form.email" type="email" placeholder="farmer@anitech.com">
                            <small v-if="validationErrors.email" class="farmer-app__field-error">{{ validationErrors.email }}</small>
                        </label>
                    </template>

                    <template v-else-if="currentStep === 2">
                        <div class="farmer-app__apply-section-label">Location & Association</div>

                        <div class="farmer-app__apply-grid">
                            <label class="farmer-app__apply-field">
                                <span>Barangay</span>
                                <select v-model="form.barangay_id" @change="syncAssociation">
                                    <option value="">Select</option>
                                    <option v-for="barangay in barangays" :key="barangay.id" :value="String(barangay.id)">
                                        {{ barangay.name }}
                                    </option>
                                </select>
                                <small v-if="localErrors.barangay_id || validationErrors.barangay_id" class="farmer-app__field-error">{{ localErrors.barangay_id || validationErrors.barangay_id }}</small>
                            </label>

                            <label class="farmer-app__apply-field">
                                <span>Association</span>
                                <select v-model="form.association_id">
                                    <option value="">Select</option>
                                    <option v-for="association in filteredAssociations" :key="association.id" :value="String(association.id)">
                                        {{ association.name }}
                                    </option>
                                </select>
                                <small v-if="validationErrors.association_id" class="farmer-app__field-error">{{ validationErrors.association_id }}</small>
                            </label>
                        </div>

                        <label class="farmer-app__apply-field">
                            <span>Home Address</span>
                            <textarea v-model="form.address" rows="2" placeholder="Street, Building, House No."></textarea>
                            <small v-if="localErrors.address || validationErrors.address" class="farmer-app__field-error">{{ localErrors.address || validationErrors.address }}</small>
                        </label>

                        <label class="farmer-app__apply-field">
                            <span>Remarks (Optional)</span>
                            <textarea v-model="form.remarks" rows="3" placeholder="Any additional information..."></textarea>
                            <small v-if="validationErrors.remarks" class="farmer-app__field-error">{{ validationErrors.remarks }}</small>
                        </label>
                    </template>

                    <template v-else-if="currentStep === 3">
                        <div class="farmer-app__apply-section-label">Membership & Document Checklist</div>

                        <div class="farmer-app__apply-review">
                            <article class="farmer-app__apply-review-card">
                                <small>Estimated Member Type</small>
                                <strong>{{ estimatedMemberType.code }}</strong>
                                <p>{{ estimatedMemberType.label }}</p>
                            </article>
                            <article class="farmer-app__apply-review-card">
                                <small>Age Check</small>
                                <strong>{{ age ?? 'Pending' }}</strong>
                                <p v-if="birthDateGuidance">{{ birthDateGuidance }}</p>
                            </article>
                        </div>

                        <div class="farmer-app__apply-documents">
                            <strong>Required documents after submit</strong>
                            <ul>
                                <li v-for="item in requiredDocumentChecklist" :key="item">{{ item }}</li>
                            </ul>
                            <p class="farmer-app__apply-review-note">
                                You will upload these in the next step after the application is sent.
                            </p>
                        </div>
                    </template>

                    <template v-else>
                        <div class="farmer-app__apply-section-label">Review Before Submit</div>
                        <div class="farmer-app__apply-review">
                            <article class="farmer-app__apply-review-card">
                                <small>Applicant</small>
                                <strong>{{ applicantName || 'Pending details' }}</strong>
                                <p>{{ form.birth_date || 'Birth date not set' }}</p>
                            </article>
                            <article class="farmer-app__apply-review-card">
                                <small>Contact</small>
                                <strong>{{ form.mobile_number || 'Mobile number not set' }}</strong>
                                <p>{{ form.email || 'Email not set' }}</p>
                            </article>
                            <article class="farmer-app__apply-review-card">
                                <small>Location</small>
                                <strong>{{ selectedBarangay || 'Barangay not selected' }}</strong>
                                <p>{{ selectedAssociation || 'Association not selected' }}</p>
                            </article>
                            <article class="farmer-app__apply-review-card">
                                <small>Member Type</small>
                                <strong>{{ estimatedMemberType.code }}</strong>
                                <p>{{ estimatedMemberType.label }}</p>
                            </article>
                        </div>

                        <div class="farmer-app__apply-documents">
                            <strong>Checklist confirmation</strong>
                            <ul>
                                <li v-for="item in requiredDocumentChecklist" :key="item">{{ item }}</li>
                            </ul>
                        </div>

                    </template>

                    <div class="farmer-app__apply-actions">
                        <button v-if="currentStep > 1" type="button" class="farmer-app__btn farmer-app__apply-secondary" @click="previousStep">
                            Back
                        </button>

                        <button
                            v-if="currentStep < stepItems.length"
                            type="button"
                            class="farmer-app__btn farmer-app__apply-submit"
                            @click="nextStep"
                        >
                            Continue
                        </button>

                        <button v-else type="submit" class="farmer-app__btn farmer-app__apply-submit" :disabled="loading">
                            {{ loading ? 'Submitting...' : submitLabel }}
                        </button>

                        <RouterLink :to="{ name: 'login' }" class="farmer-app__btn farmer-app__apply-secondary">
                            Farmer Login
                        </RouterLink>
                    </div>
                </form>
            </section>

            <div class="farmer-app__apply-help">
                Need help? Contact <span>support@anitech.com</span>
            </div>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__apply-steps,
.farmer-app__apply-review,
.farmer-app__apply-documents {
    display: grid;
    gap: 0.85rem;
}

.farmer-app__apply-steps {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-bottom: 1rem;
}

.farmer-app__apply-step {
    border: 1px solid rgba(0, 54, 41, 0.14);
    border-radius: 18px;
    background: #f7fbf7;
    padding: 0.9rem;
    text-align: left;
    display: grid;
    gap: 0.35rem;
}

.farmer-app__apply-step span {
    width: 1.8rem;
    height: 1.8rem;
    border-radius: 999px;
    display: inline-grid;
    place-items: center;
    background: rgba(0, 54, 41, 0.09);
    color: #003629;
    font-weight: 700;
}

.farmer-app__apply-step.is-active {
    border-color: #0c6a52;
    background: #eff8f2;
}

.farmer-app__apply-step.is-complete {
    border-color: rgba(12, 106, 82, 0.24);
}

.farmer-app__apply-review-card,
.farmer-app__apply-documents {
    border: 1px solid rgba(0, 54, 41, 0.12);
    border-radius: 18px;
    padding: 1rem;
    background: #fbfdfb;
}

.farmer-app__apply-review-card small {
    display: block;
    margin-bottom: 0.35rem;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    font-size: 0.7rem;
    font-weight: 700;
    color: #537167;
}

.farmer-app__apply-documents ul {
    margin: 0;
    padding-left: 1.1rem;
    display: grid;
    gap: 0.4rem;
}

.farmer-app__apply-review-note,
.farmer-app__field-hint {
    margin: 0;
    color: #5d7169;
    font-size: 0.92rem;
}

@media (max-width: 720px) {
    .farmer-app__apply-steps {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
