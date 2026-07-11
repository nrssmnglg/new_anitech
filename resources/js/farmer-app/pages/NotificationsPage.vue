<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppState from '../components/ui/AppState.vue';
import { useLocale } from '../composables/useLocale';
import { useNotificationStore } from '../stores/notifications';
import { resolveFarmerTarget } from '../utils/navigation';

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
        <div class="farmer-app__notifications-backdrop"></div>

        <main class="farmer-app__notifications-shell">
            <div class="farmer-app__notifications-stack">
                <section v-if="notifications.filteredItems.length" class="farmer-app__notifications-list">
                    <button
                        v-for="notification in notifications.filteredItems"
                        :key="notification.recipient_id"
                        type="button"
                        class="farmer-app__notifications-card"
                        :class="{ 'is-unread': !notification.is_read }"
                        @click="openTarget(notification)"
                    >
                        <div class="farmer-app__notifications-card-head">
                            <div class="farmer-app__notifications-card-meta">
                                <h2>{{ notificationHeadline(notification) }}</h2>
                            </div>
                        </div>

                        <p v-if="notificationCopy(notification)" class="farmer-app__notifications-copy">
                            {{ notificationCopy(notification) }}
                        </p>
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
.farmer-app__notifications-card {
    width: 100%;
    text-align: left;
}
</style>
