import { createApp } from 'vue';
import { createPinia } from 'pinia';
import '../bootstrap';
import App from './App.vue';
import router from './router';

const app = createApp(App);

app.config.errorHandler = (err, instance, info) => {
    console.error('[AniTech Farmer PWA Error]', {
        message: err?.message || err,
        component: instance?.$options?.name || instance?.$options?.__name || 'AnonymousComponent',
        lifecycleHook: info,
        stack: err?.stack,
    });
};

app.use(createPinia());
app.use(router);
app.mount('#farmer-pwa-app');
