import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { apiGet, apiPost } from '../services/api';
import { extractApiMessage, extractValidationErrors } from '../utils/api';
import { farmerAppPath } from '../utils/paths';

export const useAuthStore = defineStore('farmer-auth', () => {
    const booted = ref(false);
    const initialized = ref(false);
    const loading = ref(false);
    const account = ref(null);
    const user = ref(null);
    const shell = ref({});
    const error = ref('');
    const validationErrors = ref({});

    const isAuthenticated = computed(() => !!user.value);

    const bootstrap = (payload) => {
        shell.value = payload ?? {};
        booted.value = true;
    };

    const restore = async () => {
        if (initialized.value) {
            return;
        }

        if (!shell.value.authenticated) {
            initialized.value = true;
            return;
        }

        try {
            const response = await apiGet('/me');
            account.value = response?.data?.account ?? null;
            user.value = account.value;
        } catch {
            account.value = null;
            user.value = null;
        } finally {
            initialized.value = true;
        }
    };

    const login = async (payload) => {
        loading.value = true;
        error.value = '';
        validationErrors.value = {};

        try {
            const response = await apiPost('/login', payload);
            account.value = response?.data?.account ?? null;
            user.value = account.value;
            shell.value.authenticated = true;
            initialized.value = true;

            return response;
        } catch (err) {
            const errors = extractValidationErrors(err);

            validationErrors.value = errors;
            error.value = Object.keys(errors).length
                ? ''
                : extractApiMessage(err, 'Unable to sign in.');
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const setup = async (payload) => {
        loading.value = true;
        error.value = '';
        validationErrors.value = {};

        try {
            return await apiPost('/setup', payload);
        } catch (err) {
            error.value = extractApiMessage(err, 'Unable to create farmer account.');
            validationErrors.value = extractValidationErrors(err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const logout = async () => {
        await apiPost('/logout');
        account.value = null;
        user.value = null;
        shell.value.authenticated = false;
        window.location.href = farmerAppPath('/login');
    };

    return {
        account,
        booted,
        bootstrap,
        error,
        initialized,
        isAuthenticated,
        loading,
        login,
        logout,
        restore,
        shell,
        setup,
        user,
        validationErrors,
    };
});
