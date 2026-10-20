import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { appName, applyBranding, readBootBranding } from './lib/branding';

const bootBranding = readBootBranding();
applyBranding(bootBranding);

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName()}` : `${appName()} · ${window.__SIGAP_BRANDING__?.tagline || 'Pengasuhan Sekolah Rakyat'}`),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: bootBranding.primary_color || '#0d9488',
        showSpinner: false,
    },
});

if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
