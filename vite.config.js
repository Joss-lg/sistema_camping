import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const vitePort = Number(process.env.VITE_PORT || 5173);
const hmrHost = process.env.VITE_HMR_HOST;
const hmrPort = process.env.VITE_HMR_PORT || process.env.VITE_PORT;

const hmr = hmrHost
    ? {
          host: hmrHost,
          port: Number(hmrPort || 5173),
      }
    : undefined;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: vitePort,
        hmr,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
