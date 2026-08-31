<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { apiGet, apiPut } from '../services/api';
import { useApiPage } from '../composables/useApiPage';
import { useAuthStore } from '../stores/auth';
import { formatDateTime } from '../utils/navigation';

const auth = useAuthStore();
const router = useRouter();
const profile = ref(null);
const options = ref({
    civil_statuses: [],
    barangays: [],
    associations: [],
});
const editing = ref(false);
const saving = ref(false);
const formError = ref('');
const formSuccess = ref('');
const validationErrors = ref({});
const form = reactive({
    civil_status: '',
    mobile_number: '',
    address: '',
});

const { error, loading, run } = useApiPage(async () => {
    const response = await apiGet('/profile');
    profile.value = response?.data ?? null;
    options.value = response?.meta?.options ?? options.value;
    hydrateForm();
});

const hydrateForm = () => {
    form.civil_status = profile.value?.profile?.civil_status ?? '';
    form.mobile_number = profile.value?.profile?.mobile_number ?? '';
    form.address = profile.value?.profile?.address ?? '';
};

const confirmLogout = async () => {
    if (typeof window !== 'undefined' && !window.confirm('Are you sure you want to log out?')) {
        return;
    }

    await auth.logout();
};

const openPaymentHistory = () => {
    router.push({ name: 'payments' });
};

const initials = computed(() => {
    const name = profile.value?.full_name || 'Farmer';

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
});

const memberBadge = computed(() => profile.value?.membership_status || 'member');
const completion = computed(() => profile.value?.profile_completion ?? { score: 0, missing_fields: [] });
const dataWarnings = computed(() => profile.value?.data_quality?.warnings ?? []);

const registryCards = computed(() => {
    if (!profile.value) {
        return [];
    }

    return [
        {
            label: 'Member Type',
            value: `${profile.value.member_type?.code ?? 'N/A'} - ${profile.value.member_type?.name ?? 'No member type'}`,
        },
        {
            label: 'Registry Status',
            value: profile.value.status ?? 'Unknown',
        },
        {
            label: 'Barangay',
            value: profile.value.barangay?.name ?? 'No barangay',
        },
        {
            label: 'Association',
            value: profile.value.association?.name ?? 'No association',
        },
    ];
});

const details = computed(() => {
    const data = profile.value?.profile;

    if (!data) {
        return [];
    }

    return [
        { label: 'Birth Date', value: data.birth_date || 'Not provided' },
        { label: 'Sex', value: data.sex || 'Not provided' },
        { label: 'Civil Status', value: data.civil_status || 'Not provided' },
        { label: 'Mobile Number', value: data.mobile_number || 'Not provided' },
        { label: 'Address', value: data.address || 'Not provided' },
        { label: 'Registered At', value: profile.value?.registered_at ? formatDateTime(profile.value.registered_at) : 'Not available' },
    ];
});

const localWarnings = computed(() => {
    const warnings = [];
    const normalizedMobile = String(form.mobile_number || '').replace(/[\s-]/g, '');

    if (!form.address || form.address.trim().split(/\s+/).length < 2) {
        warnings.push('Address is incomplete.');
    }

    if (!form.mobile_number || /^(09|\+639)\d{9}$/.test(normalizedMobile) !== true) {
        warnings.push('Mobile number format is invalid.');
    }

    if (!profile.value?.profile?.birth_date) {
        warnings.push('Birth date is missing.');
    }

    return warnings;
});

const submit = async () => {
    saving.value = true;
    formError.value = '';
    formSuccess.value = '';
    validationErrors.value = {};

    try {
        const response = await apiPut('/profile', {
            civil_status: form.civil_status,
            mobile_number: form.mobile_number,
            address: form.address,
        });
        profile.value = response?.data ?? profile.value;
        formSuccess.value = response?.message ?? 'Profile updated successfully.';
        editing.value = false;
        hydrateForm();
    } catch (err) {
        const errors = err?.response?.data?.errors ?? {};
        validationErrors.value = errors;
        // Field-level errors are rendered beside their inputs. Avoid repeating
        // the same validation problem again in the form-level AppState.
        formError.value = Object.keys(errors).length
            ? ''
            : (err?.response?.data?.message ?? 'Unable to update profile.');
    } finally {
        saving.value = false;
    }
};

const startEditing = () => {
    editing.value = true;
    formError.value = '';
    formSuccess.value = '';
    validationErrors.value = {};
    hydrateForm();
};

const cancelEditing = () => {
    editing.value = false;
    formError.value = '';
    formSuccess.value = '';
    validationErrors.value = {};
    hydrateForm();
};

onMounted(() => run());
</script>

<template>
    <div class="farmer-app__profile-screen">
        <div class="farmer-app__profile-backdrop"></div>

        <main class="farmer-app__profile-shell">
            <AppLoader v-if="loading && !profile" />
            <AppState v-else-if="error && !profile" type="error" :message="error" />

            <div v-else-if="profile" class="farmer-app__profile-stack">
                <AppState v-if="error" type="error" :message="error" />

                <section class="farmer-app__profile-hero">
                    <div class="farmer-app__profile-avatar-wrap">
                        <div class="farmer-app__profile-avatar">{{ initials }}</div>
                        <div class="farmer-app__profile-avatar-badge">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7.5 12.5 10.5 15.5 16.5 9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>

                    <div class="farmer-app__profile-heading">
                        <h1>{{ profile.full_name }}</h1>
                        <p>{{ profile.farmer_code }}</p>
                        <div class="farmer-app__profile-badge">{{ memberBadge }}</div>
                    </div>
                </section>

                <section class="farmer-app__profile-completion">
                    <div>
                        <span class="farmer-app__profile-kicker">Profile Completion</span>
                        <h2>{{ completion.score }}%</h2>
                        <p>Complete your basic details before starting renewal so staff can review your record faster.</p>
                    </div>
                    <div class="farmer-app__profile-progress">
                        <span :style="{ width: `${completion.score}%` }"></span>
                    </div>
                    <p v-if="completion.missing_fields?.length">Missing: {{ completion.missing_fields.join(', ') }}</p>
                </section>

                <AppState
                    v-if="dataWarnings.length"
                    type="error"
                    :message="`Profile warnings: ${dataWarnings.join(' ')}`"
                />

                <section class="farmer-app__profile-card-grid">
                    <article v-for="card in registryCards" :key="card.label" class="farmer-app__profile-info-card">
                        <span>{{ card.label }}</span>
                        <strong>{{ card.value }}</strong>
                    </article>
                </section>

                <section class="farmer-app__profile-details-card">
                    <div class="farmer-app__profile-section-head">
                        <h2>Registry Details</h2>
                        <p>Check your barangay, association, and personal details so mistakes are caught early.</p>
                    </div>

                    <div class="farmer-app__profile-detail-grid">
                        <div v-for="item in details" :key="item.label" class="farmer-app__profile-detail-item">
                            <span>{{ item.label }}</span>
                            <strong>{{ item.value }}</strong>
                        </div>
                    </div>
                </section>

                <section class="farmer-app__profile-edit-card">
                    <div class="farmer-app__profile-section-head">
                        <h2>Update Profile</h2>
                    </div>

                    <AppState v-if="formError" type="error" :message="formError" />
                    <AppState v-if="formSuccess" :message="formSuccess" />

                    <div v-if="!editing" class="farmer-app__profile-edit-preview">
                        <div class="farmer-app__profile-edit-item">
                            <span>Civil Status</span>
                            <strong>{{ profile.profile?.civil_status || 'Not provided' }}</strong>
                        </div>
                        <div class="farmer-app__profile-edit-item">
                            <span>Mobile Number</span>
                            <strong>{{ profile.profile?.mobile_number || 'Not provided' }}</strong>
                        </div>
                        <div class="farmer-app__profile-edit-item farmer-app__profile-edit-item--full">
                            <span>Address</span>
                            <strong>{{ profile.profile?.address || 'Not provided' }}</strong>
                        </div>
                        <div class="farmer-app__profile-edit-actions">
                            <button type="button" class="farmer-app__profile-primary-action" @click="startEditing">
                                Edit Profile
                            </button>
                        </div>
                    </div>

                    <form v-else class="farmer-app__profile-edit-form" @submit.prevent="submit">
                        <label class="farmer-app__profile-field">
                            <span>Civil Status</span>
                            <select v-model="form.civil_status">
                                <option value="">Select civil status</option>
                                <option v-for="status in options.civil_statuses" :key="status" :value="status">{{ status }}</option>
                            </select>
                            <small v-if="validationErrors.civil_status" class="farmer-app__field-error">{{ validationErrors.civil_status[0] || validationErrors.civil_status }}</small>
                        </label>

                        <label class="farmer-app__profile-field">
                            <span>Mobile Number</span>
                            <input v-model="form.mobile_number" type="tel" placeholder="09171234567">
                            <small v-if="validationErrors.mobile_number" class="farmer-app__field-error">{{ validationErrors.mobile_number[0] || validationErrors.mobile_number }}</small>
                        </label>

                        <label class="farmer-app__profile-field">
                            <span>Address</span>
                            <textarea v-model="form.address" rows="3" placeholder="House number / sitio / purok / street"></textarea>
                            <small v-if="validationErrors.address" class="farmer-app__field-error">{{ validationErrors.address[0] || validationErrors.address }}</small>
                        </label>

                        <div v-if="localWarnings.length" class="farmer-app__profile-local-warning">
                            <strong>Validation before submission</strong>
                            <ul>
                                <li v-for="warning in localWarnings" :key="warning">{{ warning }}</li>
                            </ul>
                        </div>

                        <div class="farmer-app__profile-edit-actions">
                            <button type="submit" class="farmer-app__profile-primary-action" :disabled="saving">
                                {{ saving ? 'Saving...' : 'Save Changes' }}
                            </button>
                            <button type="button" class="farmer-app__profile-secondary-action" @click="cancelEditing">
                                Cancel
                            </button>
                        </div>
                    </form>
                </section>

                <section class="farmer-app__profile-actions">
                    <button type="button" class="farmer-app__profile-primary-action" @click="openPaymentHistory">
                        <span>Payment History</span>
                    </button>
                    <button type="button" class="farmer-app__profile-secondary-action farmer-app__profile-danger-action" @click="confirmLogout">
                        <span>Log Out</span>
                    </button>
                </section>
            </div>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__profile-completion,
.farmer-app__profile-edit-card,
.farmer-app__profile-edit-form,
.farmer-app__profile-edit-preview,
.farmer-app__profile-edit-actions {
    display: grid;
    gap: 0.9rem;
}

.farmer-app__profile-completion,
.farmer-app__profile-edit-card {
    border: 1px solid rgba(0, 54, 41, 0.1);
    border-radius: 22px;
    background: #fbfdfb;
    padding: 1rem;
}

.farmer-app__profile-kicker {
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 0.72rem;
    color: #5e736a;
    font-weight: 700;
}

.farmer-app__profile-progress {
    width: 100%;
    height: 10px;
    border-radius: 999px;
    background: #edf3ef;
    overflow: hidden;
}

.farmer-app__profile-progress span {
    display: block;
    height: 100%;
    background: linear-gradient(90deg, #0c6a52, #59b27d);
}

.farmer-app__profile-field {
    display: grid;
    gap: 0.45rem;
}

.farmer-app__profile-field span {
    font-size: 0.85rem;
    font-weight: 700;
    color: #365247;
}

.farmer-app__profile-field select,
.farmer-app__profile-field input,
.farmer-app__profile-field textarea {
    width: 100%;
    border: 1px solid rgba(0, 54, 41, 0.12);
    border-radius: 16px;
    background: #fff;
    padding: 0.85rem 0.95rem;
    color: #10231b;
}

.farmer-app__profile-field textarea {
    resize: vertical;
    min-height: 110px;
}

.farmer-app__field-error {
    display: block;
    margin: 2px 2px 0;
    color: #f10d3f;
    font-size: 0.92rem;
    font-weight: 500;
    line-height: 1.4;
}

.farmer-app__profile-local-warning {
    border: 1px solid rgba(180, 83, 9, 0.18);
    background: #fff7ed;
    border-radius: 16px;
    padding: 0.9rem 1rem;
}

.farmer-app__profile-local-warning ul {
    margin: 0.5rem 0 0;
    padding-left: 1rem;
    display: grid;
    gap: 0.35rem;
}

.farmer-app__profile-edit-preview {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    align-items: end;
}

.farmer-app__profile-edit-item {
    display: grid;
    gap: 0.35rem;
    padding: 0.9rem 1rem;
    border: 1px solid rgba(0, 54, 41, 0.08);
    border-radius: 18px;
    background: #fff;
}

.farmer-app__profile-edit-item span {
    font-size: 0.78rem;
    color: #5e736a;
}

.farmer-app__profile-edit-item strong {
    color: #10231b;
    overflow-wrap: anywhere;
}

.farmer-app__profile-edit-item--full,
.farmer-app__profile-edit-actions {
    grid-column: 1 / -1;
}

.farmer-app__profile-edit-form {
    gap: 1rem;
}

.farmer-app__profile-edit-actions {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
</style>
