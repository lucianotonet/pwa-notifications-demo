// resources/js/sw.js
import { precacheAndRoute } from 'workbox-precaching';

// O Workbox injetará o manifesto de precache aqui.
precacheAndRoute(self.__WB_MANIFEST);

self.addEventListener('push', (event) => {
    const data = event.data.json();
    self.registration.showNotification(data.title, {
        body: data.body,
        icon: '/pwa-192x192.png', // Caminho para o ícone
        badge: '/pwa-192x192.png',
        actions: [
            { action: 'explore', title: 'Explorar' },
            { action: 'close', title: 'Fechar' },
        ],
        data: {
            url: data.url, // URL para abrir ao clicar
        }
    });
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const urlToOpen = event.notification.data.url || '/';

    event.waitUntil(
        clients.openWindow(urlToOpen)
    );
}); 