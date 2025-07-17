import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa'; // Importe o plugin

export default defineConfig({
    server: {
        host: true,
        cors: {
            origin: ['https://pwa-demo.local', 'http://localhost:8000', 'http://127.0.0.1:8000'],
            credentials: true
        },
        hmr: {
            host: 'localhost'
        }
    },
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // Adicione a configuração do PWA
        VitePWA({
            base: '/',
            outDir: './public',
            registerType: 'autoUpdate',
            strategies: 'injectManifest',
            srcDir: 'resources/js', // Diretório do Service Worker
            filename: 'sw.js',       // Nome do arquivo do Service Worker
            manifest: {
                name: 'PWA Push Notifications Demo',
                short_name: 'PWA Demo',
                description: 'Prova de Conceito para PWA com notificações push',
                start_url: '/',
                display: 'standalone',
                background_color: '#ffffff',
                theme_color: '#3b82f6',
                icons: [
                    {
                        src: 'pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                    {
                        src: 'pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                ],
            },
        }),
    ],
});
