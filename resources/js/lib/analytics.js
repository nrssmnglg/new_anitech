export function trackAnalyticsEvent(payload) {
    if (!window.axios || !payload?.event_name) {
        return;
    }

    window.axios.post('/analytics/events', payload).catch(() => {
        // Ignore analytics failures so UI behavior is never blocked.
    });
}
