<script setup>
import { computed, watch, ref, onMounted, onUnmounted } from 'vue';
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
const showTermsModal = ref(false);

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
    terms_accepted: false,
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

const requiredDocumentChecklist = ref(shell.publicData?.applicationDocumentChecklist ?? []);
const refreshRequirements = async () => {
    try {
        const response = await farmerApi.get(farmerPublicUrl('/farmer/application/requirements'));
        requiredDocumentChecklist.value = response.data.data;
    } catch {
        // Keep the last server-provided checklist when the connection is unavailable.
    }
};

onMounted(() => {
    refreshRequirements();
    window.addEventListener('focus', refreshRequirements);
});
onUnmounted(() => window.removeEventListener('focus', refreshRequirements));

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

const applicationFieldSteps = {
    first_name: 1,
    middle_name: 1,
    last_name: 1,
    suffix: 1,
    birth_date: 1,
    sex: 1,
    civil_status: 1,
    mobile_number: 1,
    email: 1,
    barangay_id: 2,
    association_id: 2,
    address: 2,
    remarks: 2,
    terms_accepted: 4,
};

const requiredFieldLabels = {
    first_name: 'first name',
    last_name: 'last name',
};

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

const openTermsModal = () => {
    showTermsModal.value = true;
};

const closeTermsModal = () => {
    showTermsModal.value = false;
};

const agreeToTerms = () => {
    form.terms_accepted = true;
    clearLocalError('terms_accepted');
    showTermsModal.value = false;
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
        setLocalError('birth_date', 'The birth date field is required.');
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
                setLocalError(field, `The ${requiredFieldLabels[field]} field is required.`);
                valid = false;
            } else {
                clearLocalError(field);
            }
        });

        if (!form.sex) {
            setLocalError('sex', 'The sex field is required.');
            valid = false;
        } else {
            clearLocalError('sex');
        }

        if (!form.civil_status) {
            setLocalError('civil_status', 'The civil status field is required.');
            valid = false;
        } else {
            clearLocalError('civil_status');
        }

        valid = validateBirthDate() && valid;
        valid = validateMobileNumber() && valid;
    }

    if (step === 2) {
        if (!form.barangay_id) {
            setLocalError('barangay_id', 'The barangay field is required.');
            valid = false;
        } else {
            clearLocalError('barangay_id');
        }

        if (!form.address) {
            setLocalError('address', 'The home address field is required.');
            valid = false;
        } else {
            clearLocalError('address');
        }
    }

    if (step === 4) {
        if (!form.terms_accepted) {
            setLocalError('terms_accepted', 'You must accept the Terms and Conditions before submitting.');
            valid = false;
        } else {
            clearLocalError('terms_accepted');
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

    if (!validateStep(4)) {
        currentStep.value = 4;
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
            validationErrors.value = extractedErrors;

            const firstInvalidField = Object.keys(extractedErrors).find((field) => applicationFieldSteps[field]);
            if (firstInvalidField) {
                currentStep.value = applicationFieldSteps[firstInvalidField];
                error.value = 'Please review the highlighted fields and correct the information below.';
            } else {
                error.value = extractApiMessage(err, t('apply.submit_failed'));
            }
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
                <AppState v-if="success" type="success" :message="`${success} Redirecting to upload.`" />

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

                        <label class="farmer-app__apply-terms">
                            <input v-model="form.terms_accepted" type="checkbox">
                            <span>
                                I have read and agree to the
                                <button type="button" class="farmer-app__terms-link" @click="openTermsModal">
                                    Terms and Conditions
                                </button>
                                and Privacy Notice.
                            </span>
                        </label>
                        <small v-if="localErrors.terms_accepted || validationErrors.terms_accepted" class="farmer-app__field-error">
                            {{ localErrors.terms_accepted || validationErrors.terms_accepted }}
                        </small>

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

        </main>

        <div v-if="showTermsModal" class="farmer-app__modal-backdrop" role="presentation" @click.self="closeTermsModal">
            <section class="farmer-app__terms-modal" role="dialog" aria-modal="true" aria-labelledby="terms-title">
                <div class="farmer-app__terms-header">
                    <span>ANITECH FARMER SERVICES - TERMS AND CONDITIONS</span>
                    <button type="button" aria-label="Close Terms and Conditions" @click="closeTermsModal">x</button>
                </div>

                <div class="farmer-app__terms-body">
                    <p id="terms-title">By using AniTech Farmer Services, you agree to the following:</p>

                    <ol>
                        <li><strong>Use of AniTech.</strong> AniTech provides farmers with access to membership services, transaction monitoring, advisories, notifications, inquiries, payments, and other Agriculture Office services.</li>
                        <li><strong>Account Responsibility.</strong> Users must provide accurate information, keep their login credentials secure, and report suspected unauthorized access.</li>
                        <li><strong>Information and Documents.</strong> All submitted information and documents must be accurate, complete, valid, and readable. Submissions remain subject to verification by the Agriculture Office.</li>
                        <li><strong>Transactions and Claims.</strong> Applications, renewals, reactivations, payments, and mortuary assistance claims are subject to applicable requirements and approval procedures. Submission through AniTech does not guarantee approval.</li>
                        <li><strong>Proper Use.</strong> Users must not submit false information, impersonate others, upload harmful files, attempt unauthorized access, or misuse the system.</li>
                        <li><strong>Privacy.</strong> Personal information and submitted records will be processed for Agriculture Office services and handled according to the system's Privacy Notice.</li>
                        <li><strong>System Availability.</strong> AniTech may occasionally be unavailable due to maintenance, internet connectivity, updates, or technical issues.</li>
                        <li><strong>Changes.</strong> These Terms and Conditions may be updated when necessary. Users may be required to review and accept updated terms.</li>
                        <li><strong>Acceptance.</strong> By selecting <strong>"I Agree,"</strong> you confirm that you have read, understood, and agreed to these Terms and Conditions and the Privacy Notice.</li>
                    </ol>
                </div>

                <div class="farmer-app__terms-actions">
                    <button type="button" class="farmer-app__btn farmer-app__apply-secondary" @click="closeTermsModal">
                        Cancel
                    </button>
                    <button type="button" class="farmer-app__btn farmer-app__apply-submit" @click="agreeToTerms">
                        I Agree
                    </button>
                </div>
            </section>
        </div>
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

.farmer-app__apply-screen { min-height: 100dvh; background: #f3f7f4; }
.farmer-app__apply-shell { width: min(100%, 760px); min-height: 100dvh; margin: 0 auto; padding: 16px; }
.farmer-app__apply-card { display: block; width: 100%; padding: 16px; border: 1px solid var(--pwa-border); border-radius: 12px; background: #fff; box-shadow: var(--pwa-shadow-soft); }
.farmer-app__apply-brand { display: grid; grid-template-columns: 42px minmax(0, 1fr); column-gap: 10px; align-items: center; justify-items: stretch; margin-bottom: 14px; text-align: left; }
.farmer-app__apply-mark { grid-row: 1 / span 2; width: 42px; height: 42px; padding: 0; margin: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
.farmer-app__apply-mark img { width: 42px; height: 42px; object-fit: contain; }
.farmer-app__apply-brand h1 { margin: 0; color: var(--pwa-ink); font-size: 1.15rem; line-height: 1.25; text-align: left; }
.farmer-app__apply-brand p { margin: 2px 0 0; color: var(--pwa-muted); font-size: .76rem; line-height: 1.4; }
.farmer-app__apply-steps { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; margin-bottom: 14px; }
.farmer-app__apply-step { min-width: 0; min-height: 58px; align-content: center; justify-items: center; gap: 3px; padding: 6px 3px; border: 0; border-bottom: 3px solid var(--pwa-border); border-radius: 8px 8px 0 0; background: transparent; text-align: center; cursor: pointer; }
.farmer-app__apply-step span { width: 25px; height: 25px; font-size: .7rem; }
.farmer-app__apply-step strong { max-width: 100%; color: var(--pwa-muted); font-size: .64rem; line-height: 1.2; }
.farmer-app__apply-step.is-active { border-color: var(--pwa-green-700); background: var(--pwa-green-100); }
.farmer-app__apply-step.is-active strong { color: var(--pwa-green-900); }
.farmer-app__apply-step.is-complete { border-color: var(--pwa-green-500); }
.farmer-app__apply-form { display: grid; gap: 12px; }
.farmer-app__apply-section-label { margin: 0; padding: 0 0 8px; border-bottom: 1px solid var(--pwa-border); color: var(--pwa-green-800); font-size: .7rem; letter-spacing: .1em; }
.farmer-app__apply-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.farmer-app__apply-field { display: grid; gap: 5px; }
.farmer-app__apply-field span { margin: 0; color: var(--pwa-ink); font-size: .75rem; font-weight: 700; }
.farmer-app__apply-field input,
.farmer-app__apply-field select,
.farmer-app__apply-field textarea { width: 100%; min-height: 44px; padding: 9px 11px !important; border: 1px solid var(--pwa-border); border-radius: 9px; background: #fff; color: var(--pwa-ink); font: inherit; font-size: .82rem; box-shadow: none; }
.farmer-app__apply-field textarea { min-height: 70px; resize: vertical; }
.farmer-app__field-error, .farmer-app__field-hint { margin: 0; font-size: .7rem; line-height: 1.35; }
.farmer-app__apply-review { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
.farmer-app__apply-review-card, .farmer-app__apply-documents { padding: 11px; border-radius: 9px; background: var(--pwa-surface-soft); }
.farmer-app__apply-review-card small { margin-bottom: 3px; font-size: .64rem; letter-spacing: .08em; }
.farmer-app__apply-review-card strong { font-size: .84rem; }
.farmer-app__apply-review-card p, .farmer-app__apply-documents, .farmer-app__apply-review-note { font-size: .74rem; line-height: 1.4; }
.farmer-app__apply-documents ul { margin-top: 7px; gap: 3px; }
.farmer-app__apply-terms { display: grid; grid-template-columns: 18px minmax(0, 1fr); gap: 8px; align-items: start; padding: 10px; border: 1px solid var(--pwa-border); border-radius: 9px; background: #fff; color: var(--pwa-ink); font-size: .74rem; line-height: 1.45; }
.farmer-app__apply-terms input { width: 16px; height: 16px; margin-top: 2px; accent-color: var(--pwa-green-800); }
.farmer-app__terms-link { display: inline; padding: 0; border: 0; background: transparent; color: var(--pwa-green-800); font: inherit; font-weight: 800; text-decoration: underline; cursor: pointer; }
.farmer-app__modal-backdrop { position: fixed; inset: 0; z-index: 50; display: grid; place-items: center; padding: 16px; background: rgba(0, 21, 16, .58); }
.farmer-app__terms-modal { display: grid; grid-template-rows: auto minmax(0, 1fr) auto; width: min(100%, 680px); max-height: min(86dvh, 720px); overflow: hidden; border-radius: 12px; background: #fff; box-shadow: 0 24px 80px rgba(0, 21, 16, .24); }
.farmer-app__terms-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 15px; border-bottom: 1px solid var(--pwa-border); color: var(--pwa-green-900); font-size: .72rem; font-weight: 800; letter-spacing: .08em; }
.farmer-app__terms-header button { flex: 0 0 auto; width: 32px; height: 32px; border: 1px solid var(--pwa-border); border-radius: 8px; background: #fff; color: var(--pwa-ink); font-size: .9rem; cursor: pointer; }
.farmer-app__terms-body { overflow: auto; padding: 15px; color: var(--pwa-ink); }
.farmer-app__terms-body h2 { margin: 0 0 8px; font-size: 1rem; line-height: 1.3; }
.farmer-app__terms-body p { margin: 0 0 12px; color: var(--pwa-muted); font-size: .82rem; line-height: 1.5; }
.farmer-app__terms-body ol { display: grid; gap: 10px; margin: 0; padding-left: 1.2rem; font-size: .78rem; line-height: 1.5; }
.farmer-app__terms-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; padding: 12px 15px; border-top: 1px solid var(--pwa-border); background: var(--pwa-surface-soft); }
.farmer-app__terms-actions .farmer-app__btn { min-height: 42px; border-radius: 9px; font-size: .76rem; }
.farmer-app__apply-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-top: 2px; }
.farmer-app__apply-actions .farmer-app__btn { width: 100%; min-height: 44px; padding: 9px 11px; border-radius: 9px; font-size: .76rem; box-shadow: none; }
.farmer-app__apply-actions > :last-child { grid-column: 1 / -1; min-height: 38px; border: 0; background: transparent; }
.farmer-app__apply-submit { background: var(--pwa-green-800); color: #fff; }
.farmer-app__apply-secondary { border: 1px solid var(--pwa-border); background: #fff; color: var(--pwa-green-800); }

@media (max-width: 520px) {
    .farmer-app__apply-shell { padding: 10px; }
    .farmer-app__apply-card { padding: 13px; }
    .farmer-app__apply-grid, .farmer-app__apply-review { grid-template-columns: 1fr; }
    .farmer-app__apply-step strong { display: none; }
    .farmer-app__apply-step { min-height: 42px; }
}
</style>
