<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useAppStore } from '../stores/app';
import { useNotificationStore } from '../stores/notifications';
import { useSyncQueueStore } from '../stores/syncQueue';
import { useLocale } from '../composables/useLocale';
import { publicAsset } from '../utils/asset';
import { resolveFarmerTarget } from '../utils/navigation';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const app = useAppStore();
const notifications = useNotificationStore();
const syncQueue = useSyncQueueStore();
const { t } = useLocale();

const shellConfig = window.__FARMER_PWA__ ?? {};
const logoUrl = shellConfig.logoUrl ?? publicAsset('/figures/anitech-mark-official.svg');
const notificationCount = computed(() => notifications.unreadCount > 99 ? '99+' : notifications.unreadCount);

const navItems = [
    { name: 'dashboard', key: 'nav.home', icon: 'home' },
    { name: 'renewals', key: 'nav.renew', icon: 'renewals' },
    { name: 'inquiries', key: 'nav.help', icon: 'inquiries' },
    { name: 'profile', key: 'common.profile', icon: 'profile' },
];

const installApp = async () => {
    await app.consumeInstallPrompt();
};

const dismissInstall = () => {
    app.dismissInstallPrompt();
};

const openSurfacedNotification = async (item) => {
    if (!item.is_read) {
        await notifications.markAsRead(item.recipient_id).catch(() => {});
    }

    notifications.dismissSurfaced(item.notification_id);
    const target = resolveFarmerTarget(item.action?.target_url ?? item.target_url);
    await router.push(target ?? { name: 'notifications' });
};

watch(
    () => [syncQueue.pendingCount, syncQueue.failedCount],
    ([pendingCount, failedCount]) => {
        if (pendingCount || failedCount) {
            app.setQueueStatus(`${pendingCount} queued`);
            return;
        }

        app.setQueueStatus('');
    },
    { immediate: true },
);

onMounted(() => {
    notifications.fetchNotifications(8).catch(() => {});
    notifications.startLiveUpdates(8);
    document.addEventListener('visibilitychange', handleVisibilityRefresh);
    window.addEventListener('focus', handleVisibilityRefresh);
});

function handleVisibilityRefresh() {
    if (document.visibilityState !== 'visible') {
        return;
    }

    notifications.fetchNotifications(8).catch(() => {});
}

onBeforeUnmount(() => {
    notifications.stopLiveUpdates();
    document.removeEventListener('visibilitychange', handleVisibilityRefresh);
    window.removeEventListener('focus', handleVisibilityRefresh);
});
</script>

<template>
    <div class="pwa-shell farmer-app__shell farmer-app__shell--app">
        <div v-if="notifications.surfacedItems.length" class="farmer-app__shell-surfaces">
            <article
                v-for="item in notifications.surfacedItems"
                :key="item.notification_id"
                class="farmer-app__shell-surface-card"
                role="button"
                tabindex="0"
                @click="openSurfacedNotification(item)"
                @keydown.enter="openSurfacedNotification(item)"
            >
                <div class="farmer-app__shell-surface-copy">
                    <strong>{{ item.subject }}</strong>
                    <p>{{ item.message }}</p>
                </div>
                <button
                    type="button"
                    class="farmer-app__shell-surface-dismiss"
                    aria-label="Dismiss notification"
                    @click.stop="notifications.dismissSurfaced(item.notification_id)"
                >×</button>
            </article>
        </div>

        <header class="farmer-app__topbar">
            <RouterLink :to="{ name: 'dashboard' }" class="farmer-app__brand farmer-app__brand--shell" aria-label="AniTech home">
                <img :src="logoUrl" alt="AniTech" />
            </RouterLink>

            <div class="farmer-app__shell-controls">
                <button
                    v-if="app.updateReady"
                    type="button"
                    class="farmer-app__shell-chip farmer-app__shell-chip--primary"
                    @click="app.applyUpdate"
                >
                    Update
                </button>
                <div v-if="app.canShowInstallPrompt" class="farmer-app__shell-install-actions">
                    <button
                        type="button"
                        class="farmer-app__shell-chip"
                        @click="installApp"
                    >
                        Install
                    </button>
                    <button
                        type="button"
                        class="farmer-app__shell-chip farmer-app__shell-chip--ghost"
                        @click="dismissInstall"
                    >
                        Later
                    </button>
                </div>
                <RouterLink
                    :to="{ name: 'notifications' }"
                    class="farmer-app__shell-icon-btn"
                    :class="{ 'is-active': route.name === 'notifications' }"
                    :aria-label="notifications.unreadCount ? `${notifications.unreadCount} unread notifications` : 'Notifications'"
                >
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5.5a4 4 0 0 0-4 4v2.2c0 .5-.17.98-.49 1.36L6 14.8h12l-1.51-1.74a2.06 2.06 0 0 1-.49-1.36V9.5a4 4 0 0 0-4-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M10.2 17.5a2 2 0 0 0 3.6 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <small v-if="notifications.unreadCount">{{ notificationCount }}</small>
                </RouterLink>
            </div>
        </header>

        <RouterView />

        <nav class="farmer-app__nav">
            <RouterLink
                v-for="item in navItems"
                :key="item.name"
                :to="{ name: item.name }"
                :class="{ 'is-active': route.name === item.name }"
            >
                <span class="farmer-app__nav-icon" aria-hidden="true">
                    <svg v-if="item.icon === 'home'" viewBox="0 0 24 24" fill="none">
                        <path d="M5 10.5 12 5l7 5.5V18a1 1 0 0 1-1 1h-4.5v-5h-3v5H6a1 1 0 0 1-1-1v-7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                    <svg v-else-if="item.icon === 'renewals'" viewBox="0 0 24 24" fill="none">
                        <path d="M7 7h4V3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7.8 17A6 6 0 1 0 7 7h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <svg v-else-if="item.icon === 'inquiries'" viewBox="0 0 24 24" fill="none">
                        <path d="M4 7.5A1.5 1.5 0 0 1 5.5 6h13A1.5 1.5 0 0 1 20 7.5v9A1.5 1.5 0 0 1 18.5 18h-13A1.5 1.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="m5.5 7 6.5 5 6.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <svg v-else-if="item.icon === 'profile'" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="8.5" r="3.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M5.5 19a6.5 6.5 0 0 1 13 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="8.5" r="3.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M5.5 19a6.5 6.5 0 0 1 13 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>{{ t(item.key) }}</span>
            </RouterLink>
        </nav>
    </div>
</template>

<style scoped>
.farmer-app__shell-surfaces {
    position: fixed;
    z-index: 60;
    top: 62px;
    right: 10px;
    left: 10px;
    display: grid;
    gap: 6px;
    max-width: 420px;
    margin-left: auto;
    pointer-events: none;
}

.farmer-app__shell-surface-card {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 38px 10px 11px;
    border: 1px solid var(--pwa-border);
    border-left: 3px solid var(--pwa-green-800);
    border-radius: 10px;
    background: #fff;
    box-shadow: var(--pwa-shadow-soft);
    cursor: pointer;
    pointer-events: auto;
}

.farmer-app__shell-surface-card:focus-visible {
    outline: 2px solid var(--pwa-green-800);
    outline-offset: 2px;
}

.farmer-app__shell-surface-copy {
    min-width: 0;
}

.farmer-app__shell-surface-copy strong {
    display: block;
    color: var(--pwa-ink);
    font-size: 0.78rem;
    line-height: 1.3;
}

.farmer-app__shell-surface-copy p {
    display: -webkit-box;
    margin: 3px 0 0;
    overflow: hidden;
    color: var(--pwa-muted);
    font-size: 0.7rem;
    line-height: 1.4;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.farmer-app__shell-surface-dismiss {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    padding: 0;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: var(--pwa-muted);
    font-size: 1rem;
}
</style>
