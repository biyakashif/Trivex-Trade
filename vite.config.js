import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    // Load environment variables based on mode (development, production, etc.)
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        server: {
                        host: '0.0.0.0',
            port: 3000,
            // host: 'localhost',
            // port: 5173,
            hmr: {
                host: 'localhost',
                protocol: 'ws',
            },
        },
        define: {
            // Map environment variables to client-side
            'import.meta.env.VITE_PUSHER_APP_KEY': JSON.stringify(env.PUSHER_APP_KEY || '96321197b1081515311a'),
            'import.meta.env.VITE_PUSHER_APP_CLUSTER': JSON.stringify(env.PUSHER_APP_CLUSTER || 'mt1'),
        },
    };
});