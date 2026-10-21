import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Intercept responses for clear developer mode debugging and CSRF handling
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const url = error.config?.url || 'unknown';

        if (status === 419) {
            console.warn(`[AniTech Security] 419 CSRF token mismatch or session expired at ${url}.`);
        } else if (status === 403) {
            console.warn(`[AniTech Security] 403 Forbidden access at ${url}. Check user roles/permissions.`);
        } else if (status === 422) {
            console.warn(`[AniTech Validation] 422 Unprocessable Content at ${url}:`, error.response?.data?.errors);
        } else if (status >= 500) {
            console.error(`[AniTech Server Error] ${status} at ${url}:`, error.response?.data);
        }

        return Promise.reject(error);
    }
);
