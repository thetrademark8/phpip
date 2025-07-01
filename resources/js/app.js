import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createI18nInstance, updateI18nLocale } from './plugins/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'phpIP';

// Ensure Ziggy is available
if (typeof window !== 'undefined') {
    // Clear any potentially corrupted localStorage/sessionStorage
    try {
        // Check for corrupted data in localStorage
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            try {
                const value = localStorage.getItem(key);
                if (value === 'undefined' || value === null) {
                    localStorage.removeItem(key);
                }
            } catch (e) {
                console.warn(`Removing corrupted localStorage item: ${key}`);
                localStorage.removeItem(key);
            }
        }
    } catch (e) {
        console.warn('Unable to access localStorage:', e);
    }
    
    // Wait for DOM to be ready
    const checkZiggy = () => {
        if (!window.Ziggy) {
            console.warn('Ziggy routes not found. Make sure @routes directive is included in your Blade template.');
            window.Ziggy = { 
                url: window.location.origin,
                port: null,
                defaults: {},
                routes: {} 
            };
        }
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkZiggy);
    } else {
        checkZiggy();
    }
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Create i18n instance with initial page data
        const i18n = createI18nInstance(props.initialPage);
        
        // Create and mount app
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n);
            
        // Listen for Inertia navigation to update locale
        if (typeof window !== 'undefined') {
            document.addEventListener('inertia:success', (event) => {
                const newPage = event.detail.page;
                const newLocale = newPage?.props?.locale;
                const newTranslations = newPage?.props?.translations;
                
                if (newLocale && newTranslations) {
                    updateI18nLocale(i18n, newLocale, newTranslations);
                }
            });
        }
        
        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});