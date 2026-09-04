import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig(({ mode }) => {
    // Empty prefix so non-VITE_-prefixed vars load too; VITE_PORT must match the
    // port published in docker-compose.yml, or HMR cannot reach the container.
    const env = loadEnv(mode, process.cwd(), '');
    const port = Number(env.VITE_PORT || 5173);

    return {
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js'),
            },
        },
        plugins: [
            laravel({
                input: ["resources/css/app.css", "resources/js/app.tsx"],
                refresh: true,
                // Bunny splits each family into ~9 unicode-range subsets, and
                // `preload` defaults to "all WOFF2 variants" — so preload is
                // pinned to the one weight that blocks first paint.
                fonts: [
                    bunny("Inter", {
                        weights: [400, 500, 600, 700],
                        preload: [{ weight: 400 }],
                    }),
                    bunny("Hind Siliguri", {
                        weights: [400, 600],
                        subsets: ["bengali", "latin"],
                        display: "swap",
                        preload: [{ weight: 400 }],
                        fallbacks: ["Nirmala UI", "Noto Sans Bengali", "sans-serif"],
                    }),
                ],
            }),
            react(),
            tailwindcss(),
        ],
        server: {
            host: true,
            port,
            strictPort: true,
            hmr: { host: 'localhost', clientPort: port },
            watch: {
                usePolling: true,
                ignored: [
                    '**/vendor/**',
                    '**/storage/**',
                    '**/public/build/**',
                    '**/.git/**',
                ],
            },
        },
    }
});
