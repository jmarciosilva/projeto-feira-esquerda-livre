import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Bind mounts do Docker Desktop (Windows -> WSL2) nao propagam eventos
// inotify; o servico "node" do compose define VITE_USE_POLLING=true.
const usePolling = process.env.VITE_USE_POLLING === 'true';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // O navegador acessa o dev server pela porta publicada no host.
        hmr: {
            host: 'localhost',
            protocol: 'ws',
        },
        watch: {
            usePolling,
            interval: usePolling ? 500 : undefined,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
