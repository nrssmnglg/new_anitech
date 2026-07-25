<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AdminSidebar from '../Components/Admin/Layout/AdminSidebar.vue';
import { trackAnalyticsEvent } from '../lib/analytics';

const props = defineProps({
    title: {
        type: String,
        default: 'Admin',
    },
});

const page = usePage();

const shell = computed(() => page.props.adminShell || null);
const user = computed(() => page.props.auth?.user || null);
const initials = computed(() => String(user.value?.name || 'A').trim().charAt(0).toUpperCase());
const firstName = computed(() => String(user.value?.name || '').trim().split(/\s+/)[0] || 'Admin');
const showLogoutConfirm = ref(false);
const showNotifications = ref(false);
const search = ref('');
const notifications = ref([]);
const notificationSummary = ref({ total: 0, unread: 0, read: 0 });
const unreadCount = ref(0);
const loadingNotifications = ref(false);
const hasLoadedNotifications = ref(false);
let notificationsPollTimer = null;
let searchDebounceTimer = null;
let syncingSearchFromPage = false;

function syncSearchFromPageUrl() {
    try {
        syncingSearchFromPage = true;
        const currentUrl = new URL(page.url, window.location.origin);
        search.value = currentUrl.searchParams.get('search') || '';
    } catch {
        syncingSearchFromPage = true;
        search.value = '';
    } finally {
        window.setTimeout(() => {
            syncingSearchFromPage = false;
        }, 0);
    }
}

function openNotifications() {
    if (!shell.value?.topbar?.notificationsFeedUrl) {
        return;
    }

    showNotifications.value = true;

    if (!notifications.value.length) {
        refreshNotifications();
    }
}

function openQueries() {
    if (shell.value?.topbar?.queriesUrl) {
        router.get(shell.value.topbar.queriesUrl);
    }
}

function submitSearch() {
    if (!shell.value?.topbar?.searchUrl) {
        return;
    }

    clearSearchDebounce();

    const value = search.value.trim();

    if (value !== '') {
        trackAnalyticsEvent({
            event_name: 'admin_search',
            module: 'admin_search',
            page: document.title,
            route_name: shell.value?.currentRouteName || null,
            url: `${window.location.pathname}${window.location.search}`,
            properties: {
                query_length: value.length,
            },
        });
    }

    router.get(shell.value.topbar.searchUrl, value ? { search: value } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearSearchDebounce() {
    if (searchDebounceTimer) {
        window.clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
}

function scheduleSearch() {
    if (!shell.value?.topbar?.searchUrl || syncingSearchFromPage) {
        return;
    }

    clearSearchDebounce();

    searchDebounceTimer = window.setTimeout(() => {
        submitSearch();
    }, 300);
}

async function refreshNotifications() {
    if (!shell.value?.topbar?.notificationsFeedUrl || loadingNotifications.value) {
        return;
    }

    loadingNotifications.value = true;

    try {
        const response = await window.axios.get(shell.value.topbar.notificationsFeedUrl, {
            headers: { Accept: 'application/json' },
        });
        const payload = response?.data || {};
        notifications.value = Array.isArray(payload.notifications) ? payload.notifications : [];
        notificationSummary.value = payload.summary || { total: 0, unread: 0, read: 0 };
        unreadCount.value = Number(payload.unread_count || 0);
        hasLoadedNotifications.value = true;
    } finally {
        loadingNotifications.value = false;
    }
}

function clearNotificationsPolling() {
    if (notificationsPollTimer) {
        window.clearInterval(notificationsPollTimer);
        notificationsPollTimer = null;
    }
}

function startNotificationsPolling() {
    clearNotificationsPolling();

    if (!shell.value?.topbar?.notificationsFeedUrl) {
        return;
    }

    notificationsPollTimer = window.setInterval(() => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        refreshNotifications();
    }, 15000);
}

async function markAllNotificationsRead() {
    if (!shell.value?.topbar?.notificationsReadAllUrl) {
        return;
    }

    await window.axios.post(shell.value.topbar.notificationsReadAllUrl);
    await refreshNotifications();
}

async function markNotificationRead(notification) {
    if (!notification?.readUrl) {
        return;
    }

    await window.axios.post(notification.readUrl);
    await refreshNotifications();
}

async function openNotification(notification) {
    if (!notification?.openUrl) {
        return;
    }

    await window.axios.post(notification.openUrl);

    if (notification.targetUrl) {
        window.location.assign(notification.targetUrl);
        return;
    }

    await refreshNotifications();
}

function logout() {
    if (shell.value?.logoutUrl) {
        router.post(shell.value.logoutUrl);
    }
}

onMounted(() => {
    syncSearchFromPageUrl();
    refreshNotifications();
    startNotificationsPolling();

    document.addEventListener('visibilitychange', refreshNotifications);
    window.addEventListener('focus', refreshNotifications);
});

watch(() => shell.value?.topbar?.notificationsFeedUrl, () => {
    startNotificationsPolling();
});

watch(() => page.url, () => {
    syncSearchFromPageUrl();
});

watch(search, () => {
    scheduleSearch();
});

onBeforeUnmount(() => {
    clearSearchDebounce();
    clearNotificationsPolling();
    document.removeEventListener('visibilitychange', refreshNotifications);
    window.removeEventListener('focus', refreshNotifications);
});
</script>

<template>
    <div class="min-h-screen bg-[#f6f8f7] text-stone-900">
        <AdminSidebar
            v-if="shell && user"
            :brand="{ ...shell.brand, homeUrl: shell.homeUrl }"
            :current-route-name="shell.currentRouteName"
            :navigation="shell.navigation"
        />

        <div class="min-h-screen lg:pl-[292px]">
            <header class="sticky top-0 z-30 border-b border-[#dde6e1] bg-white/95 backdrop-blur">
                <div class="flex min-h-[78px] items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div class="min-w-0 flex-1 pl-14 lg:pl-0">
                        <form class="flex max-w-[540px] items-center gap-3 rounded-lg bg-[#eef1ef] px-5 py-3 text-stone-500" @submit.prevent="submitSearch">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 flex-none" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                            <input
                                v-model="search"
                                type="search"
                                :placeholder="shell?.searchPlaceholder || 'Search...'"
                                class="w-full border-0 bg-transparent p-0 text-[0.95rem] text-stone-700 outline-none ring-0 placeholder:text-stone-400 focus:ring-0"
                            >
                        </form>
                    </div>

                    <div v-if="shell && user" class="flex items-center gap-3 sm:gap-5">
                        <button type="button" class="relative inline-flex h-11 w-11 items-center justify-center rounded-md text-stone-700 transition hover:bg-stone-100" @click="openNotifications">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
                                <path d="M10 21a2 2 0 0 0 4 0" />
                            </svg>
                            <span v-if="unreadCount > 0" class="absolute right-1.5 top-1.5 min-w-[18px] rounded-full bg-red-600 px-1.5 text-center text-[0.65rem] font-bold leading-5 text-white">
                                {{ unreadCount > 99 ? '99+' : unreadCount }}
                            </span>
                        </button>

                        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-stone-700 transition hover:bg-stone-100" @click="openQueries">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 5h16v14H4z" />
                                <path d="m4 7 8 6 8-6" />
                            </svg>
                        </button>

                        <div class="hidden h-9 w-px bg-[#d9dfdc] sm:block"></div>

                        <div class="hidden items-center gap-3 sm:flex">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-[linear-gradient(135deg,#0f3f39_0%,#35675a_100%)] text-base font-black text-white">
                                {{ initials }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-[0.95rem] font-bold text-stone-900">{{ firstName }}</p>
                                <p class="truncate text-sm text-stone-500">{{ shell.topbar.roleLabel }}</p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-11 items-center justify-center rounded-md px-3 text-sm font-bold text-[#177136] transition hover:bg-[#f3f7f4] hover:text-[#0f5a2b]"
                            @click="showLogoutConfirm = true"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </header>

            <main class="admin-compact px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>

        <div v-if="showLogoutConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/35 px-4">
            <div class="w-full max-w-md rounded-[1.6rem] bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.22)]">
                <h2 class="text-xl font-bold text-stone-900">Confirm Logout</h2>
                <p class="mt-3 text-sm leading-6 text-stone-600">
                    Are you sure you want to log out?
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-[#d7e0db] px-4 text-sm font-bold text-stone-600 transition hover:bg-[#f4f7f5]"
                        @click="showLogoutConfirm = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-[#0f3f39] px-4 text-sm font-bold text-white transition hover:bg-[#174f47]"
                        @click="showLogoutConfirm = false; logout()"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showNotifications" class="fixed inset-0 z-50 bg-[#08130f]/32 backdrop-blur-[2px]" @click="showNotifications = false">
            <aside class="admin-notification-panel absolute right-0 top-0 flex h-full w-full max-w-[460px] flex-col border-l border-[#dfe7e2] bg-[#fcfdfc] shadow-[0_24px_80px_rgba(15,23,42,0.18)]" @click.stop>
                <div class="border-b border-[#e4ebe7] bg-white px-4 py-4 sm:px-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="inline-flex items-center gap-2 rounded-full border border-[#dce7e1] bg-[#f5f8f6] px-2.5 py-1">
                                <span class="h-2 w-2 rounded-full bg-[#0f5b46]"></span>
                                <span class="text-[0.62rem] font-black uppercase tracking-[0.2em] text-[#61756b]">Admin Inbox</span>
                            </div>
                            <h2 class="mt-2.5 text-[1.2rem] font-black tracking-[-0.03em] text-stone-900">Notifications</h2>
                            <p class="mt-1 text-[0.78rem] text-stone-500">{{ unreadCount }} unread</p>
                        </div>
                        <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-stone-500 transition hover:bg-stone-100" @click="showNotifications = false">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m6 6 12 12" />
                            <path d="M18 6 6 18" />
                        </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between border-b border-[#e8edea] bg-[#fcfdfc] px-4 py-3 sm:px-5">
                    <div class="flex items-center gap-3">
                        <button type="button" class="text-[0.82rem] font-bold text-[#0f5b46] transition hover:text-[#0b4636]" @click="refreshNotifications">
                            Refresh
                        </button>
                        <span v-if="loadingNotifications && hasLoadedNotifications" class="text-[0.72rem] font-semibold text-stone-400">
                            Syncing...
                        </span>
                    </div>
                    <button type="button" class="text-[0.82rem] font-bold text-[#0f5b46] transition hover:text-[#0b4636] disabled:cursor-not-allowed disabled:text-stone-300" :disabled="notificationSummary.unread <= 0" @click="markAllNotificationsRead">
                        Mark all read
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-3 py-3 sm:px-4 sm:py-4">
                    <div v-if="loadingNotifications && !hasLoadedNotifications" class="rounded-2xl bg-[#f8faf9] px-4 py-8 text-center text-[0.82rem] text-stone-500">
                        Loading notifications...
                    </div>

                    <div v-else-if="notifications.length" class="space-y-2.5">
                        <article v-for="notification in notifications" :key="notification.recipientId" class="rounded-[1.3rem] border border-[#e4ebe7] bg-white p-3.5 shadow-[0_8px_24px_rgba(15,23,42,0.04)] transition hover:border-[#d5e2db] hover:shadow-[0_14px_34px_rgba(15,23,42,0.07)] sm:p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="rounded-full bg-[#edf3ef] px-2.5 py-1 text-[0.62rem] font-black uppercase tracking-[0.14em] text-[#4e655a]">
                                            {{ notification.moduleLabel }}
                                        </span>
                                        <span v-if="notification.sourceLabel" class="rounded-full bg-[#f4f6f5] px-2.5 py-1 text-[0.62rem] font-black uppercase tracking-[0.14em] text-stone-500">
                                            {{ notification.sourceLabel }}
                                        </span>
                                        <span :class="notification.isRead ? 'bg-stone-200 text-stone-600' : 'bg-[#ccefe1] text-[#0f5b46]'" class="rounded-full px-2.5 py-1 text-[0.62rem] font-black uppercase tracking-[0.14em]">
                                            {{ notification.isRead ? 'Read' : 'Unread' }}
                                        </span>
                                    </div>
                                    <h3 class="mt-2.5 text-[0.88rem] font-black leading-5 tracking-[-0.01em] text-stone-900">{{ notification.subject }}</h3>
                                    <p class="mt-1 text-[0.78rem] leading-5 text-stone-600">{{ notification.message }}</p>
                                    <p class="mt-2.5 text-[0.66rem] font-bold uppercase tracking-[0.08em] text-stone-400">{{ notification.createdAt }}</p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-if="!notification.isRead"
                                    type="button"
                                    class="inline-flex h-8 items-center justify-center rounded-lg border border-[#d7e0db] px-3 text-[0.76rem] font-bold text-stone-600 transition hover:bg-[#f4f7f5]"
                                    @click="markNotificationRead(notification)"
                                >
                                    Mark Read
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex h-9 items-center justify-center rounded-xl bg-[#0f5b46] px-4 text-[0.76rem] font-black text-white transition hover:bg-[#0b4636]"
                                    @click="openNotification(notification)"
                                >
                                    {{ notification.targetUrl ? 'Open' : 'Acknowledge' }}
                                </button>
                            </div>
                        </article>
                    </div>

                    <div v-else class="rounded-2xl bg-[#f8faf9] px-4 py-8 text-center text-[0.82rem] text-stone-500">
                        No notifications found for this account.
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>

<style>
.admin-compact {
    --compact-card-radius-lg: 20px;
    --compact-card-radius-md: 16px;
    --compact-card-radius-sm: 14px;
}

.admin-compact [class*="rounded-[28px]"] {
    border-radius: var(--compact-card-radius-lg) !important;
}

.admin-compact [class*="rounded-[26px]"],
.admin-compact [class*="rounded-[24px]"],
.admin-compact [class*="rounded-[22px]"],
.admin-compact [class*="rounded-[20px]"] {
    border-radius: var(--compact-card-radius-md) !important;
}

.admin-compact [class*="rounded-[18px]"],
.admin-compact [class*="rounded-[16px]"],
.admin-compact [class*="rounded-[14px]"] {
    border-radius: var(--compact-card-radius-sm) !important;
}

.admin-compact [class*="p-8"] {
    padding: 1.5rem !important;
}

.admin-compact [class*="p-7"],
.admin-compact [class*="p-6"] {
    padding: 1.25rem !important;
}

.admin-compact [class*="p-5"] {
    padding: 1rem !important;
}

.admin-compact [class*="px-6"] {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
}

.admin-compact [class*="py-6"] {
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}

.admin-compact [class*="py-5"] {
    padding-top: 0.875rem !important;
    padding-bottom: 0.875rem !important;
}

.admin-compact [class*="gap-6"] {
    gap: 1rem !important;
}

.admin-compact [class*="gap-5"] {
    gap: 0.875rem !important;
}

.admin-compact [class*="gap-4"] {
    gap: 0.75rem !important;
}

.admin-compact [class*="space-y-7"] > :not([hidden]) ~ :not([hidden]),
.admin-compact [class*="space-y-6"] > :not([hidden]) ~ :not([hidden]) {
    margin-top: 1rem !important;
}

.admin-compact [class*="space-y-5"] > :not([hidden]) ~ :not([hidden]),
.admin-compact [class*="space-y-4"] > :not([hidden]) ~ :not([hidden]) {
    margin-top: 0.875rem !important;
}

.admin-compact [class*="text-2xl"] {
    font-size: 1.375rem !important;
    line-height: 1.85rem !important;
}

.admin-compact [class*="text-xl"] {
    font-size: 1.125rem !important;
    line-height: 1.6rem !important;
}

.admin-compact [class*="text-lg"] {
    font-size: 1rem !important;
    line-height: 1.5rem !important;
}

.admin-compact [class*="text-base"] {
    font-size: 0.9375rem !important;
    line-height: 1.4rem !important;
}

.admin-compact [class*="text-sm"] {
    font-size: 0.8125rem !important;
    line-height: 1.3rem !important;
}

.admin-compact [class*="text-[0.72rem]"],
.admin-compact [class*="text-[0.7rem]"],
.admin-compact [class*="text-[0.68rem]"] {
    font-size: 0.64rem !important;
}

.admin-compact [class*="shadow-[0_24px"],
.admin-compact [class*="shadow-[0_22px"],
.admin-compact [class*="shadow-[0_18px"],
.admin-compact [class*="shadow-[0_16px"],
.admin-compact [class*="shadow-[0_14px"],
.admin-compact [class*="shadow-[0_12px"],
.admin-compact [class*="shadow-[0_10px"] {
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05) !important;
}

@media (max-width: 640px) {
    .admin-notification-panel {
        max-width: 100%;
    }
}
</style>
