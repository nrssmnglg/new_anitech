import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { trackAnalyticsEvent } from './lib/analytics';

function trackPageView(componentName = null) {
    trackAnalyticsEvent({
        event_name: 'page_view',
        module: window.location.pathname.startsWith('/admin') ? 'admin' : 'web',
        page: document.title,
        route_name: componentName,
        url: `${window.location.pathname}${window.location.search}`,
        properties: {
            component: componentName,
        },
    });
}

// Global unhandled promise rejection listener for DevTools
window.addEventListener('unhandledrejection', (event) => {
    console.error('[AniTech Unhandled Promise Rejection]', event.reason);
});

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Global Vue error handler
        app.config.errorHandler = (err, instance, info) => {
            console.error('[AniTech Vue Error]', {
                message: err?.message || err,
                component: instance?.$options?.name || instance?.$options?.__name || 'AnonymousComponent',
                lifecycleHook: info,
                stack: err?.stack,
            });
        };

        app.use(plugin).mount(el);

        trackPageView(props.initialPage?.component ?? null);
        router.on('navigate', (event) => {
            const component = event?.detail?.page?.component ?? router.page?.component ?? null;
            if (component) {
                trackPageView(component);
            }
        });

        router.on('invalid', (event) => {
            console.warn('[AniTech Inertia Invalid Response]', event.detail.response);
        });

        router.on('error', (errors) => {
            console.warn('[AniTech Inertia Validation Errors]', errors);
        });
    },
    progress: {
        color: '#1B4D3E',
    },
});
