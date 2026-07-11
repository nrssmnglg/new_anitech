import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { apiPost } from '../services/api';
import { readStorage, writeStorage } from '../utils/storage';

const STORAGE_KEY = 'sync-queue';

function makeId() {
    return `sync-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

export const useSyncQueueStore = defineStore('farmer-sync-queue', () => {
    const items = ref(readStorage(STORAGE_KEY, []));
    const syncing = ref(false);
    const lastResult = ref('');

    const pendingCount = computed(() => items.value.filter((item) => item.status === 'pending').length);
    const failedCount = computed(() => items.value.filter((item) => item.status === 'failed').length);

    const persist = () => {
        writeStorage(STORAGE_KEY, items.value);
    };

    const enqueue = ({ type, url, payload, meta = {} }) => {
        items.value.unshift({
            id: makeId(),
            type,
            url,
            payload,
            meta,
            attempts: 0,
            status: 'pending',
            created_at: new Date().toISOString(),
            next_retry_at: new Date().toISOString(),
            last_error: '',
        });
        persist();
    };

    const clearItem = (id) => {
        items.value = items.value.filter((item) => item.id !== id);
        persist();
    };

    const syncPending = async () => {
        if (syncing.value || (typeof navigator !== 'undefined' && !navigator.onLine)) {
            return;
        }

        if (!items.value.length) {
            return;
        }

        syncing.value = true;
        lastResult.value = '';

        try {
            for (const item of [...items.value]) {
                if (!['pending', 'failed'].includes(item.status)) {
                    continue;
                }

                const nextRetryAt = item.next_retry_at ? new Date(item.next_retry_at).getTime() : 0;

                if (nextRetryAt && nextRetryAt > Date.now()) {
                    continue;
                }

                try {
                    await apiPost(item.url, item.payload);
                    clearItem(item.id);
                } catch (error) {
                    const attempts = Number(item.attempts ?? 0) + 1;
                    const retryDelayMinutes = Math.min(5, attempts);

                    items.value = items.value.map((current) =>
                        current.id === item.id
                            ? {
                                ...current,
                                attempts,
                                status: 'failed',
                                next_retry_at: new Date(Date.now() + (retryDelayMinutes * 60 * 1000)).toISOString(),
                                last_error: error?.apiMessage ?? error?.message ?? 'Sync failed.',
                            }
                            : current,
                    );
                    persist();
                }
            }

            lastResult.value = items.value.length ? 'Some queued actions still need sync.' : 'Queued actions synced.';
        } finally {
            syncing.value = false;
        }
    };

    return {
        clearItem,
        enqueue,
        failedCount,
        items,
        lastResult,
        pendingCount,
        syncPending,
        syncing,
    };
});
