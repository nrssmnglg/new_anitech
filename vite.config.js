import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

const FARMER_PWA_CACHE_VERSION = 'v2';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/inertia.js',
                'resources/css/app.css',
                'resources/css/guest.css',
                'resources/js/app.js',
                'resources/css/pwa/shared.css',
                'resources/css/farmer-app.css',
                'resources/js/farmer-app/app.js',
                'resources/css/admin-farmers.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['figures/anitech-mark-official.svg', 'figures/anitech-logo-official.svg'],
            manifest: {
                name: 'AniTech Farmer PWA',
                short_name: 'AniTech',
                description: 'Farmer member portal for AniTech registry services.',
                theme_color: '#163f31',
                background_color: '#f7fbf8',
                display: 'standalone',
                start_url: '/farmer/app/',
                scope: '/farmer/app/',
                icons: [
                    {
                        src: '/figures/anitech-logo.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/figures/anitech-mark-official.svg',
                        sizes: 'any',
                        type: 'image/svg+xml',
                        purpose: 'any maskable',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,png,svg}'],
                navigateFallbackDenylist: [/^\/api\/farmer\//],
                runtimeCaching: [
                    {
                        urlPattern: ({ url }) => url.pathname.startsWith('/api/farmer/') && url.protocol.startsWith('http'),
                        handler: 'NetworkFirst',
                        method: 'GET',
                        options: {
                            cacheName: `farmer-api-cache-${FARMER_PWA_CACHE_VERSION}`,
                            networkTimeoutSeconds: 5,
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 10,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: ({ request, url }) =>
                            request.destination === 'image' ||
                            url.pathname.startsWith('/figures/'),
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: `farmer-media-cache-${FARMER_PWA_CACHE_VERSION}`,
                            expiration: {
                                maxEntries: 60,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: ({ request, url }) =>
                            request.destination === 'style' ||
                            request.destination === 'script' ||
                            url.pathname.startsWith('/build/'),
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: `farmer-static-cache-${FARMER_PWA_CACHE_VERSION}`,
                            expiration: {
                                maxEntries: 80,
                                maxAgeSeconds: 60 * 60 * 24 * 14,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                ],
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
