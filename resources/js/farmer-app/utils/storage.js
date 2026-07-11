const PREFIX = 'farmer-pwa:';

function resolveKey(key) {
    return `${PREFIX}${key}`;
}

export function readStorage(key, fallback = null) {
    if (typeof window === 'undefined') {
        return fallback;
    }

    try {
        const raw = window.localStorage.getItem(resolveKey(key));

        if (!raw) {
            return fallback;
        }

        return JSON.parse(raw);
    } catch {
        return fallback;
    }
}

export function writeStorage(key, value) {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem(resolveKey(key), JSON.stringify(value));
    } catch {
        // Ignore local cache write failures.
    }
}

export function removeStorage(key) {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.removeItem(resolveKey(key));
    } catch {
        // Ignore local cache removal failures.
    }
}
