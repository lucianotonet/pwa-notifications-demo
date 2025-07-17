// resources/js/sw.js
import { precacheAndRoute } from 'workbox-precaching';

// O Workbox injetará o manifesto de precache aqui.
precacheAndRoute(self.__WB_MANIFEST);

self.addEventListener('install', function (event) {
    // Força o novo Service Worker a se tornar ativo imediatamente.
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function (event) {
    // Permite que o Service Worker ativo assuma o controle de todos os clientes (abas) imediatamente.
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};

    const showNotificationPromise = self.registration.showNotification(data.title || 'Nova notificação', {
        body: data.body || '',
        icon: '/pwa-192x192.png', // Caminho para o ícone
        badge: '/pwa-192x192.png',
        actions: [
            { action: 'explore', title: 'Explorar' },
            { action: 'close', title: 'Fechar' },
        ],
        data: {
            url: data.url || '/', // URL para abrir ao clicar
        }
    });

    // Garante que o navegador espere a exibição da notificação
    event.waitUntil(showNotificationPromise);
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const urlToOpen = event.notification.data.url || '/';

    event.waitUntil(
        clients.openWindow(urlToOpen)
    );
}); 