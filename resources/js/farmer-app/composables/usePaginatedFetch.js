import { ref } from 'vue';
import { extractApiMessage } from '../utils/api';
import { readStorage, writeStorage } from '../utils/storage';

export function usePaginatedFetch(loader, initialMeta = {}, options = {}) {
    const items = ref([]);
    const meta = ref(initialMeta);
    const loading = ref(false);
    const error = ref('');
    const fromCache = ref(false);
    const lastSyncedAt = ref(null);

    const readCachedPage = (params = {}) => {
        if (!options.cacheKey) {
            return null;
        }

        const cached = readStorage(`${options.cacheKey}:${params.page ?? 1}`);

        if (!cached) {
            return null;
        }

        items.value = cached.items ?? [];
        meta.value = cached.meta ?? initialMeta;
        fromCache.value = true;
        lastSyncedAt.value = cached.cached_at ?? null;

        return cached;
    };

    const fetchPage = async (params = {}) => {
        loading.value = true;
        error.value = '';

        try {
            const payload = await loader(params);
            items.value = payload?.data ?? [];
            meta.value = payload?.meta ?? initialMeta;
            fromCache.value = false;
            lastSyncedAt.value = new Date().toISOString();

            if (options.cacheKey) {
                writeStorage(`${options.cacheKey}:${params.page ?? 1}`, {
                    items: items.value,
                    meta: meta.value,
                    cached_at: lastSyncedAt.value,
                });
            }

            return payload;
        } catch (err) {
            const cached = readCachedPage(params);

            if (!cached) {
                error.value = extractApiMessage(err, 'Unable to load this list right now.');
                throw err;
            }

            error.value = '';

            return {
                data: items.value,
                meta: meta.value,
            };
        } finally {
            loading.value = false;
        }
    };

    return {
        error,
        fetchPage,
        fromCache,
        items,
        lastSyncedAt,
        loading,
        meta,
        readCachedPage,
    };
}
