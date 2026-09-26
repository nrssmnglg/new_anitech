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
const notificationTab = ref('all');
const search = ref('');
const notifications = ref([]);
const notificationSummary = ref({ total: 0, unread: 0, read: 0 });
const unreadCount = ref(0);
const loadingNotifications = ref(false);
const hasLoadedNotifications = ref(false);

const filteredNotifications = computed(() => {
    if (notificationTab.value === 'unread') {
        return notifications.value.filter((n) => n.hasRecipient && !n.isRead);
    }
    return notifications.value;
});
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

async function openNotification(notification) {
    if (!notification?.openUrl && !notification?.targetUrl) {
        return;
    }

    if (notification.openUrl) {
        await window.axios.post(notification.openUrl);
    }

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

        <div class="min-h-screen lg:pl-[256px]">
            <header class="sticky top-0 z-30 border-b border-[#dde4de] bg-white/95 backdrop-blur-md transition-shadow">
                <div class="flex min-h-[64px] items-center justify-between gap-3 px-4 py-2 sm:px-6">
                    <div class="min-w-0 flex-1 pl-14 lg:pl-0">
                        <form class="group relative flex max-w-[440px] items-center gap-2.5 rounded-xl border border-[#dde4de] bg-[#f8faf9] px-3.5 py-2 text-stone-500 transition-all duration-200 focus-within:border-[#014d3c] focus-within:bg-white focus-within:ring-2 focus-within:ring-[#014d3c]/10 focus-within:shadow-xs" @submit.prevent="submitSearch">
                            <svg viewBox="0 0 24 24" class="h-4 w-4 flex-none text-stone-400 transition-colors group-focus-within:text-[#014d3c]" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m20 20-3.5-3.5" />
                            </svg>
                            <input
                                v-model="search"
                                type="search"
                                :placeholder="shell?.searchPlaceholder || 'Search directory, farmers, requests...'"
                                class="w-full border-0 bg-transparent p-0 text-xs text-stone-800 outline-none ring-0 placeholder:text-stone-400 focus:ring-0"
                            >
                            <button
                                v-if="search"
                                type="button"
                                class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-stone-400 transition hover:bg-stone-200 hover:text-stone-700"
                                @click="search = ''; submitSearch()"
                            >
                                <span class="text-xs leading-none">&times;</span>
                            </button>
                            <kbd v-else class="hidden rounded border border-[#dde4de] bg-white px-1.5 py-0.5 text-[0.6rem] font-semibold text-stone-400 sm:inline-block">
                                /
                            </kbd>
                        </form>
                    </div>

                    <div v-if="shell && user" class="flex items-center gap-2 sm:gap-2.5">
                        <!-- Notifications Button -->
                        <button
                            type="button"
                            class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl text-stone-600 transition-all duration-200 hover:bg-[#f0faf5] hover:text-[#014d3c] active:scale-95"
                            title="Notifications"
                            @click="openNotifications"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 21a2 2 0 0 0 4 0" />
                            </svg>
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-center text-[0.55rem] font-bold leading-none text-white ring-2 ring-white"
                            >
                                {{ unreadCount > 99 ? '99+' : unreadCount }}
                            </span>
                        </button>

                        <!-- Farmer Inquiries Button -->
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-stone-600 transition-all duration-200 hover:bg-[#f0faf5] hover:text-[#014d3c] active:scale-95"
                            title="Farmer Inquiries"
                            @click="openQueries"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v14H4z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4 7 8 6 8-6" />
                            </svg>
                        </button>

                        <!-- Separator -->
                        <div class="hidden h-6 w-px bg-[#e4ebe6] sm:block mx-0.5"></div>

                        <!-- User Profile Chip -->
                        <div class="hidden items-center gap-2.5 rounded-xl py-1 pl-1.5 pr-2.5 transition-all duration-200 hover:bg-stone-50 sm:flex">
                            <div class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] text-xs font-bold text-white shadow-xs">
                                {{ initials }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-stone-900 leading-tight">{{ firstName }}</p>
                                <div class="flex items-center gap-1 text-[0.62rem] font-medium text-stone-500">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    <span class="truncate">{{ shell.topbar.roleLabel }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Logout Button -->
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl px-2.5 text-xs font-semibold text-stone-600 transition-all duration-200 hover:bg-rose-50 hover:text-rose-600 active:scale-95"
                            title="Sign out of AniTech"
                            @click="showLogoutConfirm = true"
                        >
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 5H5v14h5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 8l4 4-4 4" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h10" />
                            </svg>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="admin-compact px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>

        <!-- Confirm Logout Modal -->
        <div v-if="showLogoutConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-[#09110d]/50 p-4 backdrop-blur-sm" @click.self="showLogoutConfirm = false">
            <div class="w-full max-w-sm overflow-hidden rounded-2xl border border-[#dde4de] bg-white p-6 shadow-2xl transition-all">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-[#0f172a]">Confirm Logout</h2>
                        <p class="text-xs text-[#64748b]">Are you sure you want to end your session?</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-xl border border-[#dde4de] bg-white px-4 text-xs font-semibold text-stone-600 transition hover:bg-stone-50"
                        @click="showLogoutConfirm = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 px-4 text-xs font-bold text-white shadow-sm transition hover:from-rose-700 hover:to-rose-800 active:scale-95"
                        @click="showLogoutConfirm = false; logout()"
                    >
                        Log Out
                    </button>
                </div>
            </div>
        </div>

        <!-- Slide-over Notification Panel -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showNotifications"
                class="fixed inset-0 z-50 bg-[#09110d]/50 backdrop-blur-sm"
                @click="showNotifications = false"
            >
                <aside
                    class="admin-notification-panel absolute right-0 top-0 flex h-full w-full max-w-[440px] flex-col border-l border-[#dbe3dd] bg-[#f9fbfa] shadow-2xl transition-transform duration-300"
                    @click.stop
                >
                    <!-- Gradient Hero Header -->
                    <div class="relative overflow-hidden bg-gradient-to-br from-[#003629] via-[#00483a] to-[#005a45] px-5 py-4 text-white shadow-md">
                        <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-white/[0.05]"></div>
                        <div class="pointer-events-none absolute -bottom-8 -left-8 h-32 w-32 rounded-full bg-white/[0.04]"></div>

                        <div class="relative z-10 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/[0.12] backdrop-blur-sm">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#7ddfb8]" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
                                        <path d="M10 21a2 2 0 0 0 4 0" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-sm font-bold tracking-tight text-white">Notifications</h2>
                                        <span v-if="unreadCount > 0" class="rounded-full bg-[#7ddfb8]/20 px-2 py-0.5 text-[0.6rem] font-bold text-[#7ddfb8] border border-[#7ddfb8]/30">
                                            {{ unreadCount }} new
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-white/15 bg-white/10 text-white/80 transition hover:bg-white/20 hover:text-white"
                                aria-label="Close notification panel"
                                @click="showNotifications = false"
                            >
                                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor">
                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Panel Filter & Action Ribbon -->
                        <div class="relative z-10 mt-3.5 flex items-center justify-between border-t border-white/10 pt-3 text-xs">
                            <!-- Tab filters -->
                            <div class="inline-flex rounded-lg bg-black/20 p-0.5">
                                <button
                                    type="button"
                                    class="rounded-md px-2.5 py-1 text-[0.65rem] font-bold transition"
                                    :class="notificationTab === 'all' ? 'bg-white text-[#003629] shadow-xs' : 'text-white/70 hover:text-white'"
                                    @click="notificationTab = 'all'"
                                >
                                    All ({{ notifications.length }})
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md px-2.5 py-1 text-[0.65rem] font-bold transition"
                                    :class="notificationTab === 'unread' ? 'bg-white text-[#003629] shadow-xs' : 'text-white/70 hover:text-white'"
                                    @click="notificationTab = 'unread'"
                                >
                                    Unread ({{ unreadCount }})
                                </button>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="text-[0.65rem] font-semibold text-white/80 transition hover:text-[#7ddfb8] disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="notificationSummary.unread <= 0"
                                    @click="markAllNotificationsRead"
                                >
                                    Mark all read
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Items Container -->
                    <div class="flex-1 overflow-y-auto p-3.5 space-y-2.5">
                        <div v-if="loadingNotifications && !hasLoadedNotifications" class="flex flex-col items-center justify-center rounded-xl bg-white p-8 text-center border border-[#e4ebe7]">
                            <svg class="h-6 w-6 animate-spin text-[#014d3c]" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-xs font-semibold text-[#64748b]">Loading notification feed...</p>
                        </div>

                        <template v-else-if="filteredNotifications.length">
                            <article
                                v-for="notification in filteredNotifications"
                                :key="notification.recipientId || `history-${notification.notificationId}`"
                                role="button"
                                tabindex="0"
                                class="group relative cursor-pointer rounded-xl border p-3.5 transition-all duration-200 outline-none focus:ring-2 focus:ring-[#014d3c]/20"
                                :class="notification.hasRecipient && !notification.isRead ? 'border-[#c3dfce] bg-[#f2f8f4] hover:bg-[#eaf4ed] shadow-xs' : 'border-[#e3e9e5] bg-white hover:border-[#b8c9c0] hover:bg-[#fbfcfb]'"
                                @click="openNotification(notification)"
                                @keydown.enter.prevent="openNotification(notification)"
                                @keydown.space.prevent="openNotification(notification)"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#014d3c]/10 text-[#014d3c] transition-colors group-hover:bg-[#014d3c] group-hover:text-white">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="rounded bg-[#014d3c]/10 px-1.5 py-0.5 text-[0.58rem] font-bold uppercase tracking-wider text-[#014d3c]">
                                                {{ notification.moduleLabel }}
                                            </span>
                                            <span v-if="notification.sourceLabel" class="rounded bg-[#f1f5f3] px-1.5 py-0.5 text-[0.58rem] font-semibold text-[#64748b]">
                                                {{ notification.sourceLabel }}
                                            </span>
                                            <span
                                                v-if="notification.hasRecipient && !notification.isRead"
                                                class="ml-auto inline-flex items-center gap-1 text-[0.58rem] font-bold text-[#15803d]"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full bg-[#22c55e]"></span>
                                                New
                                            </span>
                                        </div>

                                        <h3 class="mt-1.5 text-xs font-bold leading-snug text-[#0f172a] group-hover:text-[#014d3c] transition-colors">
                                            {{ notification.subject }}
                                        </h3>
                                        <p class="mt-1 text-[0.68rem] leading-relaxed text-[#64748b] line-clamp-2">
                                            {{ notification.message }}
                                        </p>

                                        <div class="mt-2 flex items-center justify-between border-t border-[#edf2ee] pt-1.5">
                                            <span class="inline-flex items-center gap-1 text-[0.6rem] text-[#94a3b8]">
                                                <svg viewBox="0 0 20 20" class="h-3 w-3" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                                                </svg>
                                                {{ notification.createdAt }}
                                            </span>
                                            <span v-if="notification.targetUrl" class="inline-flex items-center gap-0.5 text-[0.62rem] font-bold text-[#014d3c] opacity-0 group-hover:opacity-100 transition-opacity">
                                                Open
                                                <svg viewBox="0 0 20 20" class="h-3 w-3" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </template>

                        <!-- Empty State -->
                        <div v-else class="flex flex-col items-center justify-center rounded-xl bg-white p-8 text-center border border-[#e4ebe7] shadow-xs">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#014d3c]/10 text-[#014d3c]">
                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <path d="M22 4L12 14.01l-3-3" />
                                </svg>
                            </div>
                            <h3 class="mt-3 text-xs font-bold text-[#0f172a]">
                                {{ notificationTab === 'unread' ? 'No unread notifications' : 'No notifications found' }}
                            </h3>
                            <p class="mt-1 text-[0.68rem] text-[#64748b]">
                                {{ notificationTab === 'unread' ? 'You are completely caught up on all alerts.' : 'New activity and updates will appear here.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Panel Footer -->
                    <div class="border-t border-[#e2e8e4] bg-white px-5 py-3 flex items-center justify-between">
                        <span class="text-[0.68rem] text-[#64748b]">
                            {{ notificationSummary.total }} total notifications
                        </span>
                        <a
                            v-if="shell?.topbar?.notificationsUrl"
                            :href="shell.topbar.notificationsUrl"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#014d3c] transition hover:text-[#002a20]"
                            @click="showNotifications = false"
                        >
                            Notification Center
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.25 5.5a.75.75 0 0 1 0 1.08l-5.25 5.5a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </aside>
            </div>
        </Transition>
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

.dashboard-compact [class*="rounded-[2"],
.dashboard-compact [class*="rounded-[1"] {
    border-radius: 0.75rem !important;
}

.dashboard-compact [class*="p-7"],
.dashboard-compact [class*="p-6"],
.dashboard-compact [class*="p-5"] {
    padding: 1rem !important;
}

.dashboard-compact [class*="px-5"] {
    padding-left: 0.875rem !important;
    padding-right: 0.875rem !important;
}

.dashboard-compact [class*="py-5"] {
    padding-top: 0.75rem !important;
    padding-bottom: 0.75rem !important;
}

.dashboard-compact [class*="mt-7"],
.dashboard-compact [class*="mt-6"],
.dashboard-compact [class*="mt-5"] {
    margin-top: 0.875rem !important;
}

.dashboard-compact [class*="gap-6"],
.dashboard-compact [class*="gap-5"],
.dashboard-compact [class*="gap-4"] {
    gap: 0.75rem !important;
}

.dashboard-compact [class*="h-52"] {
    height: 10rem !important;
}

.dashboard-compact [class*="h-44"][class*="w-44"] {
    height: 8rem !important;
    width: 8rem !important;
}

.dashboard-compact article,
.dashboard-compact section[class*="border"] {
    box-shadow: none !important;
}

.dashboard-compact article[class*="p-"],
.dashboard-compact section[class*="p-"] {
    padding: 0.875rem !important;
}

.dashboard-compact article [class*="px-5"][class*="py-5"],
.dashboard-compact article [class*="px-4"][class*="py-4"] {
    padding: 0.625rem 0.75rem !important;
}

.dashboard-compact [class*="text-[2.8rem]"],
.dashboard-compact [class*="text-[2.2rem]"],
.dashboard-compact [class*="text-[2rem]"] {
    font-size: 1.5rem !important;
    line-height: 1.8rem !important;
}

.dashboard-compact [class*="text-[1.7rem]"],
.dashboard-compact [class*="text-[1.5rem]"],
.dashboard-compact [class*="text-[1.35rem]"],
.dashboard-compact [class*="text-[1.2rem]"] {
    font-size: 1rem !important;
    line-height: 1.4rem !important;
}

.dashboard-compact [class*="h-14"][class*="w-14"],
.dashboard-compact [class*="h-11"][class*="w-11"],
.dashboard-compact [class*="h-10"][class*="w-10"] {
    height: 2rem !important;
    width: 2rem !important;
    border-radius: 0.375rem !important;
}

.dashboard-compact [class*="rounded-full"][class*="px-4"],
.dashboard-compact [class*="rounded-full"][class*="px-3"] {
    padding-left: 0.625rem !important;
    padding-right: 0.625rem !important;
}

.dashboard-compact table th,
.dashboard-compact table td {
    padding: 0.5rem 0.75rem !important;
    font-size: 0.75rem !important;
}

.dashboard-compact [class*="py-10"] {
    padding-top: 1.5rem !important;
    padding-bottom: 1.5rem !important;
}

.dashboard-compact h2,
.dashboard-compact h3 {
    letter-spacing: -0.01em !important;
}

.dashboard-compact h2:not([class*="text-lg"]),
.dashboard-compact h3:not([class*="text-sm"]) {
    font-size: 0.9375rem !important;
    line-height: 1.3rem !important;
}

.dashboard-compact [class*="uppercase"] {
    letter-spacing: 0.08em !important;
}

.dashboard-compact [class*="text-[1.05rem]"],
.dashboard-compact [class*="text-[1.1rem]"],
.dashboard-compact [class*="text-[1.15rem]"] {
    font-size: 0.875rem !important;
    line-height: 1.25rem !important;
}

.dashboard-compact [class*="leading-7"],
.dashboard-compact [class*="leading-6"] {
    line-height: 1.25rem !important;
}

.dashboard-compact [class*="grid"][class*="gap-6"] {
    gap: 0.75rem !important;
}

.dashboard-compact [class*="mb-5"],
.dashboard-compact [class*="mb-4"] {
    margin-bottom: 0.75rem !important;
}

@media (min-width: 1280px) {
    .dashboard-compact > section,
    .dashboard-compact > article,
    .dashboard-compact > div {
        min-width: 0;
    }
}

.analytics-compact article,
.analytics-compact section[class*="border"] {
    border-radius: 0.75rem !important;
    box-shadow: none !important;
}

.analytics-compact article[class*="p-"],
.analytics-compact section[class*="p-"] {
    padding: 0.875rem !important;
}

.analytics-compact [class*="gap-6"],
.analytics-compact [class*="gap-5"],
.analytics-compact [class*="gap-4"] {
    gap: 0.75rem !important;
}

.analytics-compact [class*="mt-6"],
.analytics-compact [class*="mt-5"],
.analytics-compact [class*="mt-4"] {
    margin-top: 0.75rem !important;
}

.analytics-compact h2,
.analytics-compact h3 {
    font-size: 0.9rem !important;
    line-height: 1.25rem !important;
    font-weight: 600 !important;
}

.analytics-compact [class*="text-5xl"],
.analytics-compact [class*="text-4xl"],
.analytics-compact [class*="text-3xl"] {
    font-size: 1.5rem !important;
    line-height: 1.8rem !important;
}

.analytics-compact [class*="text-2xl"] {
    font-size: 1.1rem !important;
    line-height: 1.45rem !important;
}

.analytics-compact [class*="rounded-[1"] {
    border-radius: 0.5rem !important;
}

.analytics-compact [class*="h-48"],
.analytics-compact [class*="h-40"] {
    height: 8rem !important;
}

.analytics-compact [class*="py-4"] {
    padding-top: 0.625rem !important;
    padding-bottom: 0.625rem !important;
}

.analytics-compact [class*="px-4"] {
    padding-left: 0.75rem !important;
    padding-right: 0.75rem !important;
}

.analytics-compact section.grid > article:only-child {
    grid-column: 1 / -1;
}

.analytics-compact [class*="space-y-4"] > :not([hidden]) ~ :not([hidden]),
.analytics-compact [class*="space-y-3"] > :not([hidden]) ~ :not([hidden]) {
    margin-top: 0.5rem !important;
}

.analytics-compact article [class*="mt-3"] {
    margin-top: 0.5rem !important;
}

.analytics-compact article [class*="h-3"] {
    height: 0.4rem !important;
}

.analytics-compact article [class*="h-2"] {
    height: 0.3rem !important;
}

.analytics-compact p[class*="text-sm"] {
    font-size: 0.75rem !important;
    line-height: 1.15rem !important;
}

.analytics-compact [class*="py-8"],
.analytics-compact [class*="py-10"] {
    padding-top: 1.25rem !important;
    padding-bottom: 1.25rem !important;
}

@media (max-width: 640px) {
    .admin-notification-panel {
        max-width: 100%;
    }
}
</style>
