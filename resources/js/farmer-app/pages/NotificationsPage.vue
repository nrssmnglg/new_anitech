<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLoader from '../components/ui/AppLoader.vue';
import AppState from '../components/ui/AppState.vue';
import { useLocale } from '../composables/useLocale';
import { useNotificationStore } from '../stores/notifications';
import { formatDateTime, resolveFarmerTarget } from '../utils/navigation';

const router = useRouter();
const notifications = useNotificationStore();
const { t } = useLocale();

const markAsRead = async (recipientId) => {
    await notifications.markAsRead(recipientId);
};

const openTarget = async (notification) => {
    if (!notification.is_read) {
        await markAsRead(notification.recipient_id);
    }

    const targetUrl = notification.action?.target_url ?? notification.target_url;
    const target = resolveFarmerTarget(targetUrl);

    if (target) {
        router.push(target);
    }
};

const notificationHeadline = (notification) => {
    if (notification.module === 'advisories' && notification.message) {
        return notification.message;
    }

    return notification.subject;
};

const notificationCopy = (notification) => {
    if (notification.module === 'advisories') {
        return '';
    }

    return notification.message;
};

const notificationTone = (notification) => {
    if (notification.is_priority) {
        return 'urgent';
    }

    if (notification.module === 'payments') {
        return 'neutral';
    }

    return 'info';
};

onMounted(() => notifications.fetchNotifications(20));
</script>

<template>
    <div class="farmer-app__notifications-screen">
        <main class="farmer-app__notifications-shell">
            <div class="farmer-app__notifications-stack">
                <header class="farmer-app__notifications-header">
                    <div>
                        <span>Updates</span>
                        <h1>Notifications</h1>
                    </div>
                    <strong v-if="notifications.unreadCount">{{ notifications.unreadCount }} unread</strong>
                </header>

                <AppLoader v-if="notifications.loading && !notifications.filteredItems.length" />

                <section v-if="notifications.filteredItems.length" class="farmer-app__notifications-list">
                    <button
                        v-for="notification in notifications.filteredItems"
                        :key="notification.recipient_id"
                        type="button"
                        class="farmer-app__notifications-card"
                        :class="[{ 'is-unread': !notification.is_read }, `is-${notificationTone(notification)}`]"
                        @click="openTarget(notification)"
                    >
                        <div class="farmer-app__notifications-card-head">
                            <div class="farmer-app__notifications-card-meta">
                                <div class="farmer-app__notifications-label-row">
                                    <span>{{ notification.module_label || 'Account update' }}</span>
                                    <i v-if="!notification.is_read" aria-label="Unread"></i>
                                </div>
                                <h2>{{ notificationHeadline(notification) }}</h2>
                            </div>
                            <span class="farmer-app__notifications-chevron" aria-hidden="true">›</span>
                        </div>

                        <p v-if="notificationCopy(notification)" class="farmer-app__notifications-copy">
                            {{ notificationCopy(notification) }}
                        </p>
                        <time>{{ formatDateTime(notification.created_at) }}</time>
                    </button>
                </section>

                <AppState
                    v-else-if="!notifications.loading"
                    :message="t('notifications.no_notifications')"
                    :action-label="t('common.refresh')"
                    @action="notifications.fetchNotifications(20)"
                />
            </div>
        </main>
    </div>
</template>

<style scoped>
.farmer-app__notifications-screen {
    min-height: 100%;
    background: transparent;
}

.farmer-app__notifications-shell {
    position: relative;
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
    padding: 12px 12px 28px;
}

.farmer-app__notifications-stack,
.farmer-app__notifications-list {
    display: grid;
    gap: 8px;
}

.farmer-app__notifications-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    padding: 0 2px 4px;
}

.farmer-app__notifications-header div > span {
    display: block;
    margin-bottom: 2px;
    color: var(--pwa-muted);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.farmer-app__notifications-header h1 {
    margin: 0;
    color: var(--pwa-ink);
    font-size: 1.35rem;
    line-height: 1.2;
    letter-spacing: -0.025em;
}

.farmer-app__notifications-header > strong {
    color: var(--pwa-green-800);
    font-size: 0.72rem;
}

.farmer-app__notifications-card {
    width: 100%;
    display: grid;
    gap: 6px;
    padding: 11px 12px;
    text-align: left;
    border: 1px solid var(--pwa-border);
    border-left: 3px solid transparent;
    border-radius: 12px;
    background: #fff;
    color: inherit;
    box-shadow: none;
}

.farmer-app__notifications-card.is-unread {
    border-left-color: var(--pwa-green-800);
    background: #fbfefc;
}

.farmer-app__notifications-card.is-urgent {
    border-left-color: #c2413d;
}

.farmer-app__notifications-card:active {
    background: var(--pwa-surface-soft);
}

.farmer-app__notifications-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.farmer-app__notifications-card-meta {
    min-width: 0;
}

.farmer-app__notifications-label-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 3px;
}

.farmer-app__notifications-label-row span {
    color: var(--pwa-muted);
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.farmer-app__notifications-label-row i {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--pwa-green-800);
}

.farmer-app__notifications-card-meta h2 {
    margin: 0;
    color: var(--pwa-ink);
    font-size: 0.86rem;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.farmer-app__notifications-chevron {
    flex: 0 0 auto;
    color: var(--pwa-green-800);
    font-size: 1.2rem;
    line-height: 1;
}

.farmer-app__notifications-copy {
    display: -webkit-box;
    margin: 0;
    overflow: hidden;
    color: #53635c;
    font-size: 0.76rem;
    line-height: 1.45;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.farmer-app__notifications-card time {
    color: var(--pwa-muted);
    font-size: 0.65rem;
}
</style>
