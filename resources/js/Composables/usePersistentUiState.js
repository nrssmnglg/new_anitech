import { watch } from 'vue';

function storageAvailable() {
    return typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';
}

export function readStoredValue(key, fallback) {
    if (!storageAvailable()) {
        return fallback;
    }

    try {
        const raw = window.localStorage.getItem(key);

        if (!raw) {
            return fallback;
        }

        return JSON.parse(raw);
    } catch {
        return fallback;
    }
}

export function usePersistentObject(key, target) {
    const stored = readStoredValue(key, null);

    if (stored && typeof stored === 'object' && !Array.isArray(stored)) {
        Object.assign(target, stored);
    }

    watch(target, (value) => {
        if (!storageAvailable()) {
            return;
        }

        window.localStorage.setItem(key, JSON.stringify(value));
    }, { deep: true });
}

export function persistValue(key, source) {
    watch(source, (value) => {
        if (!storageAvailable()) {
            return;
        }

        window.localStorage.setItem(key, JSON.stringify(value));
    }, { immediate: true });
}
