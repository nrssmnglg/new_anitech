window.toggleMenu = function toggleMenu(id) {
    const container = document.getElementById(id);
    if (!container) return;

    const wasOpen = container.classList.contains('open');
    document.querySelectorAll('.menu-item-container').forEach((item) => item.classList.remove('open'));
    if (!wasOpen) container.classList.add('open');
};

window.addEventListener('DOMContentLoaded', () => {
    const normalizeUrl = (value) => {
        const url = new URL(value, window.location.origin);

        return `${url.pathname}${url.search}`;
    };

    const mobileBreakpoint = window.matchMedia('(max-width: 1024px)');
    const sidebarToggleButtons = document.querySelectorAll('[data-admin-sidebar-toggle]');
    const sidebarCloseButtons = document.querySelectorAll('[data-admin-sidebar-close]');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');

    const setSidebarState = (open) => {
        document.body.classList.toggle('admin-sidebar-open', open);
        sidebarToggleButtons.forEach((button) => {
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        if (sidebarOverlay) {
            sidebarOverlay.hidden = !open;
        }
    };

    const toggleSidebar = () => setSidebarState(!document.body.classList.contains('admin-sidebar-open'));
    const closeSidebar = () => setSidebarState(false);

    document.querySelectorAll('[data-menu-target]').forEach((item) => {
        item.addEventListener('click', () => window.toggleMenu(item.dataset.menuTarget));
    });

    sidebarToggleButtons.forEach((button) => {
        button.addEventListener('click', toggleSidebar);
    });

    sidebarCloseButtons.forEach((button) => {
        button.addEventListener('click', closeSidebar);
    });

    document.querySelectorAll('.sidebar .nav-menu a[href]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (normalizeUrl(link.href) === normalizeUrl(window.location.href)) {
                event.preventDefault();
            }

            if (mobileBreakpoint.matches) {
                closeSidebar();
            }
        });
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    mobileBreakpoint.addEventListener('change', (event) => {
        if (!event.matches) {
            closeSidebar();
        }
    });

    const opened = document.querySelector('.menu-item-container.open');
    if (!opened) {
        document.getElementById('farmerModule')?.classList.add('open');
    }

    bootAdminNotifications();
});

function bootAdminNotifications() {
    const feedUrl = document.body?.dataset.adminNotificationFeedUrl || '';
    if (!feedUrl) return;

    const badge = document.querySelector('[data-admin-notification-badge]');
    const bell = document.querySelector('[data-admin-notification-trigger]');
    const bellMeta = document.querySelector('[data-admin-notification-meta]');
    const bellPing = document.querySelector('[data-admin-notification-ping]');
    const totalCount = document.querySelector('[data-admin-notification-total]');
    const unreadCount = document.querySelector('[data-admin-notification-unread]');
    const readCount = document.querySelector('[data-admin-notification-read]');
    const list = document.querySelector('[data-admin-notification-list]');
    const panel = document.querySelector('[data-admin-notification-panel]');
    const markAll = document.querySelector('[data-admin-mark-all]');
    const shouldRefreshList = panel?.dataset.adminNotificationLiveList === 'true';
    const pollIntervalMs = shouldRefreshList ? 15000 : 60000;
    const formatter = new Intl.NumberFormat();
    let isFetching = false;

    const createRequestUrl = () => {
        const url = new URL(feedUrl, window.location.origin);
        const current = new URL(window.location.href);

        current.searchParams.forEach((value, key) => {
            url.searchParams.set(key, value);
        });

        return url.toString();
    };

    const updateBadge = (count) => {
        const hasUnread = count > 0;

        if (bell) {
            bell.classList.toggle('has-unread', hasUnread);
            bell.setAttribute('aria-label', hasUnread ? `${formatter.format(count)} unread notifications` : 'Open notifications');
        }

        if (bellMeta) {
            bellMeta.textContent = hasUnread ? `${formatter.format(count)} unread` : 'All caught up';
        }

        if (bellPing) {
            bellPing.hidden = !hasUnread;
        }

        if (!badge) return;

        if (hasUnread) {
            badge.hidden = false;
            badge.textContent = count > 99 ? '99+' : String(count);
            return;
        }

        badge.hidden = true;
        badge.textContent = '';
    };

    const updateSummary = (summary) => {
        if (!summary || typeof summary !== 'object') return;

        if (totalCount) totalCount.textContent = formatter.format(Number(summary.total || 0));
        if (unreadCount) unreadCount.textContent = formatter.format(Number(summary.unread || 0));
        if (readCount) readCount.textContent = formatter.format(Number(summary.read || 0));
        if (markAll) markAll.hidden = Number(summary.unread || 0) <= 0;
    };

    const refresh = async () => {
        if (isFetching || document.visibilityState === 'hidden') return;

        isFetching = true;

        try {
            const response = await window.axios.get(createRequestUrl(), {
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = response?.data || {};

            updateBadge(Number(payload.unread_count || 0));
            updateSummary(payload.summary || null);

            if (shouldRefreshList && list && typeof payload.notifications_html === 'string') {
                list.innerHTML = payload.notifications_html;
            }
        } catch (error) {
            // Ignore transient polling failures and retry on the next interval.
        } finally {
            isFetching = false;
        }
    };

    if (shouldRefreshList) {
        refresh();
    }

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            refresh();
        }
    });

    window.addEventListener('focus', refresh);
    window.setInterval(refresh, pollIntervalMs);
}

