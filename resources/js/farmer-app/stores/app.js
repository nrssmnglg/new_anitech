import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { readStorage, writeStorage } from '../utils/storage';

const SETTINGS_KEY = 'app-settings';
const INSTALL_PROMPT_KEY = 'install-prompt';
const DEFAULT_SETTINGS = {
    low_data_mode: false,
};
const DEFAULT_INSTALL_PROMPT = {
    first_seen_at: null,
    prompt_ready_at: null,
    dismissed_until: null,
};

export const useAppStore = defineStore('farmer-app', () => {
    const online = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
    const installPromptAvailable = ref(false);
    const installEvent = ref(null);
    const updateReady = ref(false);
    const updateServiceWorker = ref(null);
    const lastSyncLabel = ref('');
    const queueStatus = ref('');
    const settings = ref(readStorage(SETTINGS_KEY, DEFAULT_SETTINGS) ?? DEFAULT_SETTINGS);
    const installPromptState = ref(readStorage(INSTALL_PROMPT_KEY, DEFAULT_INSTALL_PROMPT) ?? DEFAULT_INSTALL_PROMPT);

    const lowDataMode = computed(() => Boolean(settings.value?.low_data_mode));
    const canShowInstallPrompt = computed(() => {
        if (!installPromptAvailable.value || !installEvent.value) {
            return false;
        }

        const now = Date.now();
        const dismissedUntil = installPromptState.value?.dismissed_until
            ? new Date(installPromptState.value.dismissed_until).getTime()
            : 0;
        const promptReadyAt = installPromptState.value?.prompt_ready_at
            ? new Date(installPromptState.value.prompt_ready_at).getTime()
            : 0;

        if (dismissedUntil && now < dismissedUntil) {
            return false;
        }

        return !promptReadyAt || now >= promptReadyAt;
    });

    const statusText = computed(() => {
        const mode = online.value ? 'Online' : 'Offline';
        const details = [lastSyncLabel.value, queueStatus.value].filter(Boolean).join(' • ');

        return details ? `${mode} • ${details}` : mode;
    });

    const persistSettings = () => {
        writeStorage(SETTINGS_KEY, settings.value);
    };

    const persistInstallPromptState = () => {
        writeStorage(INSTALL_PROMPT_KEY, installPromptState.value);
    };

    const setOnline = (value) => {
        online.value = Boolean(value);
    };

    const setInstallPrompt = (event) => {
        installEvent.value = event ?? null;
        installPromptAvailable.value = !!event;

        if (!event) {
            return;
        }

        if (!installPromptState.value?.first_seen_at) {
            const now = new Date();
            installPromptState.value = {
                ...DEFAULT_INSTALL_PROMPT,
                ...installPromptState.value,
                first_seen_at: now.toISOString(),
                prompt_ready_at: new Date(now.getTime() + (2 * 60 * 1000)).toISOString(),
            };
            persistInstallPromptState();
        }
    };

    const setUpdateReady = (value, callback = null) => {
        updateReady.value = Boolean(value);
        updateServiceWorker.value = callback ?? null;
    };

    const applyUpdate = async () => {
        if (!updateServiceWorker.value) {
            return false;
        }

        await updateServiceWorker.value(true);

        return true;
    };

    const setLastSyncLabel = (value = '') => {
        lastSyncLabel.value = value;
    };

    const setQueueStatus = (value = '') => {
        queueStatus.value = value;
    };

    const setLowDataMode = (value) => {
        settings.value = {
            ...settings.value,
            low_data_mode: Boolean(value),
        };
        persistSettings();
    };

    const consumeInstallPrompt = async () => {
        if (!installEvent.value) {
            return false;
        }

        installEvent.value.prompt();
        const result = await installEvent.value.userChoice;
        installEvent.value = null;
        installPromptAvailable.value = false;
        installPromptState.value = {
            ...installPromptState.value,
            dismissed_until: result?.outcome === 'accepted'
                ? new Date(Date.now() + (365 * 24 * 60 * 60 * 1000)).toISOString()
                : new Date(Date.now() + (3 * 24 * 60 * 60 * 1000)).toISOString(),
        };
        persistInstallPromptState();

        return result?.outcome === 'accepted';
    };

    const dismissInstallPrompt = () => {
        installPromptState.value = {
            ...installPromptState.value,
            dismissed_until: new Date(Date.now() + (24 * 60 * 60 * 1000)).toISOString(),
        };
        persistInstallPromptState();
    };

    return {
        applyUpdate,
        canShowInstallPrompt,
        consumeInstallPrompt,
        dismissInstallPrompt,
        installPromptAvailable,
        lastSyncLabel,
        lowDataMode,
        online,
        queueStatus,
        setInstallPrompt,
        setLastSyncLabel,
        setLowDataMode,
        setOnline,
        setQueueStatus,
        setUpdateReady,
        statusText,
        updateReady,
    };
});
