import '../css/app.css';
import '../css/components.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

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

  console.log('Echo initialized successfully:', window.Echo);
  console.log('Pusher key:', import.meta.env.VITE_PUSHER_APP_KEY);
  console.log('Pusher cluster:', import.meta.env.VITE_PUSHER_APP_CLUSTER);
} catch (error) {
  console.error('Failed to initialize Echo:', error);
}

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
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});