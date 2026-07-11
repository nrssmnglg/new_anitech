import { ref } from 'vue';
import { extractApiMessage } from '../utils/api';
import { readStorage, writeStorage } from '../utils/storage';

export function useCachedResource(cacheKey, loader, options = {}) {
    const loading = ref(false);
    const error = ref('');
    const data = ref(options.initialData ?? null);
    const fromCache = ref(false);
    const lastSyncedAt = ref(null);

    const hydrateFromCache = () => {
        const cached = readStorage(cacheKey);

        if (!cached) {
            return null;
        }

        data.value = cached.data ?? options.initialData ?? null;
        fromCache.value = true;
        lastSyncedAt.value = cached.cached_at ?? null;

        return data.value;
    };

    const fetchFresh = async (params = {}) => {
        loading.value = true;
        error.value = '';

        try {
            const payload = await loader(params);
            data.value = payload;
            fromCache.value = false;
            lastSyncedAt.value = new Date().toISOString();
            writeStorage(cacheKey, {
                data: payload,
                cached_at: lastSyncedAt.value,
            });

            return payload;
        } catch (err) {
            const cached = hydrateFromCache();

            if (!cached) {
                error.value = extractApiMessage(err, 'Unable to load this screen right now.');
                throw err;
            }

            error.value = '';

            return cached;
        } finally {
            loading.value = false;
        }
    };

    return {
        data,
        error,
        fetchFresh,
        fromCache,
        hydrateFromCache,
        lastSyncedAt,
        loading,
    };
}
