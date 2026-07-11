<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { RouterView } from 'vue-router';
import { useRegisterSW } from 'virtual:pwa-register/vue';
import { useAuthStore } from './stores/auth';
import { useAppStore } from './stores/app';
import { useSyncQueueStore } from './stores/syncQueue';
import { useOfflineStatus } from './composables/useOfflineStatus';
import { publicAsset } from './utils/asset';

const auth = useAuthStore();
const app = useAppStore();
const syncQueue = useSyncQueueStore();
const shell = window.__FARMER_PWA__ ?? {};

const splashLogo = shell.logoUrl ?? publicAsset('/figures/anitech-mark-official.svg');
const ready = computed(() => auth.booted);
const { updateServiceWorker } = useRegisterSW({
    onNeedRefresh() {
        app.setUpdateReady(true, updateServiceWorker);
        updateServiceWorker(true);
    },
    onOfflineReady() {
        app.setLastSyncLabel('App ready offline');
    },
});

useOfflineStatus();

const syncWhenVisible = () => {
    if (document.visibilityState !== 'visible') {
        return;
    }

    syncQueue.syncPending();
};

onMounted(() => {
    auth.bootstrap(shell);
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        app.setInstallPrompt(event);
    });
    window.addEventListener('online', syncQueue.syncPending);
    window.addEventListener('focus', syncQueue.syncPending);
    document.addEventListener('visibilitychange', syncWhenVisible);
    syncQueue.syncPending();
});

onBeforeUnmount(() => {
    window.removeEventListener('online', syncQueue.syncPending);
    window.removeEventListener('focus', syncQueue.syncPending);
    document.removeEventListener('visibilitychange', syncWhenVisible);
});
</script>

<template>
    <div class="farmer-app">
        <div v-if="!app.online || app.updateReady" class="pwa-shell farmer-app__shell" style="padding-bottom: 0;">
            <div v-if="!app.online" class="farmer-app__error">You are offline. Some live data may be outdated.</div>
            <div v-if="app.updateReady" class="farmer-app__empty farmer-app__stack">
                <div>New app update available.</div>
                <div class="farmer-app__actions">
                    <button type="button" class="farmer-app__btn farmer-app__btn--primary farmer-app__btn--tiny" @click="app.applyUpdate">
                        Update now
                    </button>
                </div>
            </div>
        </div>
        <div v-if="syncQueue.pendingCount || syncQueue.failedCount" class="pwa-shell farmer-app__shell" style="padding-top: 0; padding-bottom: 0;">
            <div class="farmer-app__empty">
                {{ syncQueue.pendingCount }} queued
                <span v-if="syncQueue.failedCount"> • {{ syncQueue.failedCount }} waiting to retry</span>
            </div>
        </div>
        <RouterView v-if="ready" />
        <div v-else class="farmer-app__splash">
            <div class="farmer-app__splash-bg">
                <div class="farmer-app__splash-haze"></div>
                <div class="farmer-app__splash-grain"></div>
            </div>
            <main class="farmer-app__splash-main">
                <div class="farmer-app__splash-spacer" aria-hidden="true"></div>

                <div class="farmer-app__splash-brand">
                    <div class="farmer-app__splash-mark">
                        <img :src="splashLogo" alt="AniTech logo">
                    </div>
                    <div class="farmer-app__splash-copy">
                        <h1>AniTech</h1>
                        <p>Farmer Workspace</p>
                    </div>
                </div>

                <div class="farmer-app__splash-footer">
                    <div class="farmer-app__splash-progress" aria-hidden="true">
                        <span></span>
                    </div>
                    <p>From AniTech Solutions</p>
                </div>
            </main>
        </div>
    </div>
</template>
