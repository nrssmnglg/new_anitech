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

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);

        trackPageView(props.initialPage?.component ?? null);
        router.on('finish', (event) => {
            trackPageView(event.detail.page.component ?? null);
        });
    },
    progress: {
        color: '#1B4D3E',
    },
});
