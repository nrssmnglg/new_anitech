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
    },
    onOfflineReady() {
        app.setLastSyncLabel('App ready offline');
    },
});

useOfflineStatus();

const syncWhenVisible = () => {
    if (document.visibilityState === 'visible') syncQueue.syncPending();
};
const captureInstallPrompt = (event) => {
    event.preventDefault();
    app.setInstallPrompt(event);
};

onMounted(() => {
    auth.bootstrap(shell);
    window.addEventListener('beforeinstallprompt', captureInstallPrompt);
    window.addEventListener('online', syncQueue.syncPending);
    window.addEventListener('focus', syncQueue.syncPending);
    document.addEventListener('visibilitychange', syncWhenVisible);
    syncQueue.syncPending();
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeinstallprompt', captureInstallPrompt);
    window.removeEventListener('online', syncQueue.syncPending);
    window.removeEventListener('focus', syncQueue.syncPending);
    document.removeEventListener('visibilitychange', syncWhenVisible);
});
</script>

<template>
    <div class="farmer-app">
        <div v-if="!app.online || app.updateReady || syncQueue.pendingCount || syncQueue.failedCount" class="farmer-app__global-notices">
            <div v-if="!app.online" class="farmer-app__global-notice is-offline">Offline · Some data may be outdated</div>
            <div v-if="app.updateReady" class="farmer-app__global-notice is-update">
                <span>App update available</span>
                <button type="button" @click="app.applyUpdate">Update</button>
            </div>
            <div v-if="syncQueue.pendingCount || syncQueue.failedCount" class="farmer-app__global-notice is-sync">
                <span>{{ syncQueue.pendingCount }} queued<span v-if="syncQueue.failedCount"> · {{ syncQueue.failedCount }} retrying</span></span>
            </div>
        </div>

        <RouterView v-if="ready" />
        <div v-else class="farmer-app__splash">
            <div class="farmer-app__splash-bg"><div class="farmer-app__splash-haze"></div><div class="farmer-app__splash-grain"></div></div>
            <main class="farmer-app__splash-main">
                <div class="farmer-app__splash-spacer" aria-hidden="true"></div>
                <div class="farmer-app__splash-brand">
                    <div class="farmer-app__splash-mark"><img :src="splashLogo" alt="AniTech logo"></div>
                    <div class="farmer-app__splash-copy"><h1>AniTech</h1><p>Farmer Workspace</p></div>
                </div>
                <div class="farmer-app__splash-footer">
                    <div class="farmer-app__splash-progress" aria-hidden="true"><span></span></div>
                    <p>From AniTech Solutions</p>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.farmer-app__global-notices { position: fixed; z-index: 80; top: calc(62px + env(safe-area-inset-top)); right: 10px; left: 10px; display: grid; gap: 5px; max-width: 420px; margin-left: auto; pointer-events: none; }
.farmer-app__global-notice { display: flex; align-items: center; justify-content: space-between; gap: 10px; min-height: 38px; padding: 7px 9px 7px 11px; border: 1px solid var(--pwa-border); border-left: 3px solid var(--pwa-green-800); border-radius: 9px; background: #fff; box-shadow: var(--pwa-shadow-soft); color: #405149; font-size: .7rem; font-weight: 700; pointer-events: auto; }
.farmer-app__global-notice.is-offline { border-left-color: #b54708; background: #fffaf0; }
.farmer-app__global-notice.is-sync { border-left-color: #667085; }
.farmer-app__global-notice button { min-height: 30px; padding: 0 9px; border: 0; border-radius: 7px; background: var(--pwa-green-800); color: #fff; font-size: .68rem; font-weight: 800; }
</style>
