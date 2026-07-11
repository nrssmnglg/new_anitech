<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue';
import { RouterLink, RouterView, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useAppStore } from '../stores/app';
import { useNotificationStore } from '../stores/notifications';
import { useSyncQueueStore } from '../stores/syncQueue';
import { useLocale } from '../composables/useLocale';
import { publicAsset } from '../utils/asset';

const route = useRoute();
const auth = useAuthStore();
const app = useAppStore();
const notifications = useNotificationStore();
const syncQueue = useSyncQueueStore();
const { t } = useLocale();

const shellConfig = window.__FARMER_PWA__ ?? {};
const logoUrl = shellConfig.logoUrl ?? publicAsset('/figures/anitech-mark-official.svg');

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
        <div v-if="app.statusText || notifications.unreadCount" class="farmer-app__shell-note farmer-app__shell-banner">
            <span>{{ app.statusText || 'All systems synced.' }}</span>
            <span v-if="notifications.unreadCount">{{ notifications.unreadCount }} unread alerts</span>
        </div>

        <div v-if="notifications.surfacedItems.length" class="farmer-app__shell-surfaces">
            <article v-for="item in notifications.surfacedItems" :key="item.notification_id" class="farmer-app__shell-surface-card">
                <div class="farmer-app__shell-surface-copy">
                    <strong>{{ item.subject }}</strong>
                    <p>{{ item.message }}</p>
                </div>
                <div class="farmer-app__shell-surface-actions">
                    <RouterLink :to="{ name: 'notifications' }" class="farmer-app__shell-surface-open">Open</RouterLink>
                    <button type="button" class="farmer-app__shell-surface-dismiss" @click="notifications.dismissSurfaced(item.notification_id)">
                        Dismiss
                    </button>
                </div>
            </article>
        </div>

        <header class="farmer-app__topbar">
            <RouterLink :to="{ name: 'dashboard' }" class="farmer-app__brand farmer-app__brand--shell">
                <img :src="logoUrl" alt="AniTech" />
                <strong>AniTech</strong>
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
                <RouterLink :to="{ name: 'notifications' }" class="farmer-app__shell-icon-btn">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5.5a4 4 0 0 0-4 4v2.2c0 .5-.17.98-.49 1.36L6 14.8h12l-1.51-1.74a2.06 2.06 0 0 1-.49-1.36V9.5a4 4 0 0 0-4-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M10.2 17.5a2 2 0 0 0 3.6 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <small v-if="notifications.unreadCount">{{ notifications.unreadCount }}</small>
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
