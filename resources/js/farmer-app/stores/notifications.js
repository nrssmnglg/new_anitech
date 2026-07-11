import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { apiGet, apiPost } from '../services/api';
import { readStorage, writeStorage } from '../utils/storage';

const DEFAULT_PREFERENCES = {
    status_changes: true,
    renewals: true,
    inquiry_replies: true,
    payment_confirmations: true,
};

export const useNotificationStore = defineStore('farmer-notifications', () => {
    const items = ref([]);
    const summary = ref({
        total: 0,
        unread: 0,
        read: 0,
        modules: {},
    });
    const loading = ref(false);
    const fromCache = ref(false);
    const lastSyncedAt = ref(null);
    const activeLimit = ref(20);
    const filters = ref({
        module: 'all',
        unreadOnly: false,
    });
    const preferences = ref(readStorage('notification-preferences', DEFAULT_PREFERENCES) ?? DEFAULT_PREFERENCES);
    const surfacedItems = ref([]);
    let pollTimer = null;

    const unreadCount = computed(() => Number(summary.value?.unread ?? 0));

    const preferenceOptions = computed(() => ([
        {
            key: 'status_changes',
            label: 'Status changes',
            description: 'Applications and account status updates',
        },
        {
            key: 'renewals',
            label: 'Renewals',
            description: 'Renewal reminders and renewal decisions',
        },
        {
            key: 'inquiry_replies',
            label: 'Inquiry replies',
            description: 'Replies from staff on your inquiries',
        },
        {
            key: 'payment_confirmations',
            label: 'Payment confirmations',
            description: 'Assessment and payment verification notices',
        },
    ]));

    const filteredItems = computed(() => items.value.filter((item) => matchesPreferences(item)));

    const persistPreferences = () => {
        writeStorage('notification-preferences', preferences.value);
    };

    const setPreference = (key, value) => {
        preferences.value = {
            ...preferences.value,
            [key]: value,
        };
        persistPreferences();
    };

    const setFilters = (nextFilters = {}) => {
        filters.value = {
            ...filters.value,
            ...nextFilters,
        };
    };

    const matchesPreferences = (item) => {
        if (item.type === 'query_responded') {
            return preferences.value.inquiry_replies;
        }

        if (item.module === 'payments') {
            return preferences.value.payment_confirmations;
        }

        if (item.module === 'renewals') {
            return preferences.value.renewals;
        }

        return preferences.value.status_changes;
    };

    const registerSurfaceCandidates = (notificationItems = []) => {
        const seenIds = readStorage('notification-surfaced-ids', []);
        const nextSeenIds = new Set(seenIds);
        const fresh = notificationItems.filter((item) => !nextSeenIds.has(item.notification_id) && !item.is_read && matchesPreferences(item));

        fresh.forEach((item) => nextSeenIds.add(item.notification_id));
        surfacedItems.value = fresh.slice(0, 3);
        writeStorage('notification-surfaced-ids', Array.from(nextSeenIds).slice(-200));
    };

    const dismissSurfaced = (notificationId) => {
        surfacedItems.value = surfacedItems.value.filter((item) => item.notification_id !== notificationId);
    };

    const clearSurfaced = () => {
        surfacedItems.value = [];
    };

    const fetchNotifications = async (limit = 20, overrideFilters = null) => {
        activeLimit.value = limit;
        loading.value = true;

        if (overrideFilters) {
            setFilters(overrideFilters);
        }

        try {
            const payload = await apiGet('/notifications', {
                params: {
                    limit,
                    module: filters.value.module !== 'all' ? filters.value.module : undefined,
                    unread_only: filters.value.unreadOnly ? 1 : undefined,
                },
            });
            items.value = payload?.data ?? [];
            summary.value = payload?.meta?.summary ?? summary.value;
            fromCache.value = false;
            lastSyncedAt.value = new Date().toISOString();
            registerSurfaceCandidates(items.value);
            writeStorage(`notifications:${limit}:${filters.value.module}:${filters.value.unreadOnly ? 'unread' : 'all'}`, {
                items: items.value,
                summary: summary.value,
                cached_at: lastSyncedAt.value,
            });
        } catch (error) {
            const cached = readStorage(`notifications:${limit}:${filters.value.module}:${filters.value.unreadOnly ? 'unread' : 'all'}`);

            if (cached) {
                items.value = cached.items ?? [];
                summary.value = cached.summary ?? summary.value;
                fromCache.value = true;
                lastSyncedAt.value = cached.cached_at ?? null;
                return;
            }

            throw error;
        } finally {
            loading.value = false;
        }
    };

    const markAsRead = async (recipientId) => {
        const payload = await apiPost(`/notifications/${recipientId}/read`);
        summary.value = payload?.meta?.summary ?? summary.value;
        items.value = items.value.map((item) =>
            item.recipient_id === recipientId
                ? { ...item, is_read: true, read_at: new Date().toISOString() }
                : item,
        );
        surfacedItems.value = surfacedItems.value.filter((item) => item.recipient_id !== recipientId);
    };

    const hydrateSummary = (nextSummary = {}) => {
        summary.value = {
            ...summary.value,
            ...nextSummary,
        };
    };

    const stopLiveUpdates = () => {
        if (pollTimer) {
            window.clearInterval(pollTimer);
            pollTimer = null;
        }
    };

    const startLiveUpdates = (limit = activeLimit.value || 20, intervalMs = 15000) => {
        activeLimit.value = limit;
        stopLiveUpdates();

        pollTimer = window.setInterval(() => {
            if (document.visibilityState !== 'visible') {
                return;
            }

            fetchNotifications(activeLimit.value).catch(() => {});
        }, intervalMs);
    };

    return {
        clearSurfaced,
        dismissSurfaced,
        fetchNotifications,
        filteredItems,
        filters,
        fromCache,
        hydrateSummary,
        items,
        lastSyncedAt,
        loading,
        markAsRead,
        preferenceOptions,
        preferences,
        setFilters,
        setPreference,
        startLiveUpdates,
        stopLiveUpdates,
        summary,
        surfacedItems,
        unreadCount,
    };
});
