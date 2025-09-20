import '../css/app.css';
import '../css/components.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

const pinia = createPinia();
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Initialize Laravel Echo
window.Pusher = Pusher;

try {
  window.Echo = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_APP_KEY,
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
      forceTLS: true,
      auth: {
          headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
      },
  });

} catch (error) {
  // Echo initialization failed - silently handle
}

// Add global axios interceptor for authentication errors
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Redirect to login page for authentication failures
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(pinia);
        app.use(plugin);
        app.use(ZiggyVue);

        // Add global error handling for authentication failures
        app.mixin({
            mounted() {
                // Listen for Inertia.js errors
                this.$inertia.on('error', (event) => {
                    if (event.detail.error.status === 401) {
                        // Redirect to login page for authentication failures
                        this.$inertia.visit('/login');
                    }
                });
            }
        });

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});