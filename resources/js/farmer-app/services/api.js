import axios from 'axios';
import { extractApiMessage, extractValidationErrors } from '../utils/api';
import { useAppStore } from '../stores/app';
import { farmerAppPath } from '../utils/paths';

const shell = window.__FARMER_PWA__ ?? {};

export const farmerApi = axios.create({
    baseURL: shell.apiBase ?? '/api/farmer',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

export async function apiRequest(config) {
    const response = await farmerApi.request(config);
    const app = useAppStore();
    app.setLastSyncLabel('Synced just now');

    return response.data;
}

export async function apiGet(url, config = {}) {
    return apiRequest({
        method: 'get',
        url,
        ...config,
    });
}

export async function apiPost(url, data = {}, config = {}) {
    return apiRequest({
        method: 'post',
        url,
        data,
        ...config,
    });
}

export async function apiPut(url, data = {}, config = {}) {
    return apiRequest({
        method: 'put',
        url,
        data,
        ...config,
    });
}

farmerApi.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error?.response?.status === 401) {
            window.location.href = farmerAppPath('/login');
        }

        error.apiMessage = extractApiMessage(error);
        error.validationErrors = extractValidationErrors(error);

        return Promise.reject(error);
    },
);
