# Guia para Criar um PoC de Notificações Push com Laravel 12 e Vue 3 (Sem Autenticação)

Este guia detalha como configurar um Proof of Concept (PoC) para um Progressive Web App (PWA) com notificações push, utilizando Laravel 12 no backend e Vue 3 com Vite no frontend. O objetivo é criar um PWA instalável que envie notificações a cada minuto para **qualquer visitante inscrito**, funcionando mesmo com o aplicativo fechado.

## Pré-requisitos
- **PHP**: >= 8.2
- **Composer**
- **Node.js e npm**
- **HTTPS**: Essencial para PWAs e notificações push (exceto em `localhost`).

## Passo 1: Configurar o Projeto Laravel 12
Se você já tem um projeto, pode pular esta etapa.

```bash
composer create-project laravel/laravel pwa-demo
cd pwa-demo
```

Como este PoC não requer autenticação, não é necessário instalar o Laravel Breeze ou qualquer outro starter kit de autenticação.

## Passo 2: Instalar e Configurar o Pacote de Web Push
Instale o pacote `laravel-notification-channels/webpush` para gerenciar as notificações.

```bash
composer require laravel-notification-channels/webpush
```

Publique a migração para a tabela de inscrições:

```bash
php artisan vendor:publish --provider="NotificationChannels\WebPush\WebPushServiceProvider" --tag="migrations"
php artisan migrate
```

Gere as chaves VAPID, que são essenciais para a segurança do serviço de push.

```bash
php artisan webpush:vapid
```

Este comando adicionará `VAPID_PUBLIC_KEY` e `VAPID_PRIVATE_KEY` ao seu arquivo `.env`. Adicione também o `VAPID_SUBJECT` para compatibilidade com Safari/iOS, apontando para o seu domínio.

```env
VAPID_SUBJECT=https://seu-dominio.com
```

## Passo 3: Criar um Modelo para Inscritos Anônimos
Como não vamos atrelar as inscrições a usuários, criaremos um modelo simples para representar cada navegador inscrito.

Execute o comando para criar o modelo e sua migração:
```bash
php artisan make:model AnonymousSubscriber -m
```

Abra a migração recém-criada em `database/migrations/` e defina seu schema para ter apenas o ID e os timestamps. O arquivo deve ficar assim:

```php
// database/migrations/xxxx_xx_xx_xxxxxx_create_anonymous_subscribers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anonymous_subscribers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymous_subscribers');
    }
};
```

Execute a migração:
```bash
php artisan migrate
```

Agora, atualize o modelo `app/Models/AnonymousSubscriber.php` para que ele possa receber notificações e ter inscrições de push.

```php
// app/Models/AnonymousSubscriber.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class AnonymousSubscriber extends Model
{
    use HasFactory, Notifiable, HasPushSubscriptions;
}
```

## Passo 4: Configurar o Frontend com Vue 3 e Vite
O projeto já está configurado com Vue e Vite. Precisamos instalar e configurar o plugin PWA.

```bash
npm install vite-plugin-pwa --save-dev
```

### Configurar o Vite
Atualize o arquivo `vite.config.ts` para incluir o plugin `VitePWA`.

```javascript
// vite.config.ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            ssr: 'resources/js/ssr.ts',
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
        VitePWA({
            base: '/',
            outDir: './public',
            registerType: 'autoUpdate',
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
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
```

### Criar Ícones, Service Worker e Registrar no Template
1.  **Ícones**: Crie dois ícones (`pwa-192x192.png` e `pwa-512x512.png`) e coloque-os no diretório `public/`.
2.  **Service Worker**: Crie o arquivo `resources/js/sw.js` para gerenciar os eventos de push.

```javascript
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
    const data = event.data.json();
    self.registration.showNotification(data.title, {
        body: data.body,
        icon: '/pwa-192x192.png',
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
    event.waitUntil(clients.openWindow(urlToOpen));
});
```

3. **Registrar no Template Principal**: Atualize o `resources/views/app.blade.php` para incluir o manifesto do PWA e o script que registra o Service Worker. Isso garante que o PWA seja reconhecido em toda a aplicação.

```php
// resources/views/app.blade.php
<head>
    // ... outras tags head
    <link rel="manifest" href="/manifest.webmanifest">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' });
            });
        }
    </script>
    // ... @routes, @vite, etc.
</head>
```

### Configurar o Componente Vue para Inscrição
Vamos usar a página principal, `Welcome.vue`, para exibir os botões de instalação e inscrição. A lógica separa a instalação do PWA da ativação das notificações.

```vue
// resources/js/pages/Welcome.vue
<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const notificationStatus = ref('');
const isSupported = ref(true);
const isPWAInstalled = ref(false);
const showInstallPrompt = ref(false);
let deferredPrompt = null;

onMounted(() => {
    if (typeof window === 'undefined') return;

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        isSupported.value = false;
        return;
    }

    checkPWAInstallation();

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallPrompt.value = true;
    });

    window.addEventListener('appinstalled', () => {
        isPWAInstalled.value = true;
        showInstallPrompt.value = false;
        notificationStatus.value = 'PWA instalado com sucesso!';
    });
});

function checkPWAInstallation() {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
    isPWAInstalled.value = isStandalone;
}

async function installPWA() {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    if (outcome === 'accepted') {
        // O evento 'appinstalled' cuidará de atualizar o estado
    }
    deferredPrompt = null;
}

// Função para converter a VAPID key para o formato correto
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function subscribe() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        notificationStatus.value = 'Notificações push não são suportadas neste navegador.';
        return;
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        notificationStatus.value = 'Permissão para notificações foi negada.';
        return;
    }

    try {
        // 1. Obter a chave VAPID pública do backend
        const keyResponse = await fetch('/vapid-public-key');
        const { key } = await keyResponse.json();
        const applicationServerKey = urlBase64ToUint8Array(key);

        // 2. Obter o registro do Service Worker e fazer a inscrição
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey,
        });

        // 3. Enviar a inscrição para o backend
        await fetch('/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(subscription),
        });

        notificationStatus.value = 'Inscrito com sucesso!';
    } catch (error) {
        console.error('Falha na inscrição:', error);
        notificationStatus.value = 'Falha ao se inscrever. Verifique o console.';
    }
}
</script>

<template>
    <!-- O template contém os botões separados: -->
    <!-- 1. Botão para chamar installPWA(), habilitado por showInstallPrompt -->
    <!-- 2. Botão para chamar subscribe(), habilitado por isPWAInstalled -->
</template>
```

## Passo 5: Configurar Rotas no Backend
Adicione as rotas necessárias em `routes/web.php` para fornecer a chave VAPID e salvar as inscrições.

```php
// routes/web.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\AnonymousSubscriber;

// Rota para fornecer a chave pública VAPID ao frontend
Route::get('/vapid-public-key', function () {
    return response()->json(['key' => config('webpush.vapid.public_key')]);
});

// Rota para receber e salvar a inscrição do navegador
Route::post('/subscribe', function (Request $request) {
    // Cria um novo "assinante anônimo" a cada inscrição bem-sucedida
    $subscriber = AnonymousSubscriber::create();

    // Salva os detalhes da inscrição associados a este novo assinante
    $subscriber->updatePushSubscription(
        $request->input('endpoint'),
        $request->input('keys.p256dh'),
        $request->input('keys.auth')
    );

    return response()->json(['success' => true]);
});

// ... suas outras rotas ...
```

## Passo 6: Criar a Classe de Notificação
Crie a classe que definirá o conteúdo da notificação push.

```bash
php artisan make:notification TestNotification
```

Atualize o arquivo `app/Notifications/TestNotification.php`.

```php
// app/Notifications/TestNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TestNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $messageBody = 'Esta é uma notificação de teste agendada. Horário: ' . now()->toDateTimeString();

        return (new WebPushMessage)
            ->title('Notificação Agendada PoC')
            ->icon('/pwa-192x192.png')
            ->body($messageBody)
            ->action('Explorar', 'explore') // Ação 1
            ->data(['url' => '/settings/profile']); // URL para abrir
    }
}
```

## Passo 7: Agendar o Envio das Notificações
Crie um comando para disparar as notificações.

```bash
php artisan make:command SendTestNotifications
```

Atualize o arquivo `app/Console/Commands/SendTestNotifications.php` para buscar todos os inscritos anônimos e enviar a notificação.

```php
// app/Console/Commands/SendTestNotifications.php
namespace App\Console\Commands;

use App\Models\AnonymousSubscriber;
use App\Notifications\TestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendTestNotifications extends Command
{
    protected $signature = 'send:test-notifications';
    protected $description = 'Envia notificações de teste para todos os inscritos.';

    public function handle()
    {
        // Busca todos os inscritos que possuem uma assinatura de push ativa
        $subscribers = AnonymousSubscriber::whereHas('pushSubscriptions')->get();

        if ($subscribers->isEmpty()) {
            $this->info('Nenhum inscrito encontrado.');
            return;
        }

        // Envia a notificação para a coleção de inscritos
        Notification::send($subscribers, new TestNotification());

        $this->info('Notificações enviadas para ' . $subscribers->count() . ' inscritos.');
    }
}
```

Configure o agendador em `app/Console/Kernel.php` para executar o comando a cada minuto.

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('send:test-notifications')->everyMinute();
}
```

Para testar em desenvolvimento, execute o agendador:
```bash
php artisan schedule:work
```
Em produção, configure um cron job para executar `php artisan schedule:run` a cada minuto.

## Passo 8: Testar o PoC
1.  **Inicie os servidores**:
    ```bash
    # Em um terminal
    php artisan serve

    # Em outro terminal
    npm run dev
    ```

    ou

    ```bash
    composer run dev
    ```

2.  **Acesse o App**: Abra `http://localhost:8000` (ou a URL do seu `APP_URL`).
3.  **Inscreva-se**: Vá para o Dashboard e clique no botão "Inscrever-se". Permita as notificações no prompt do navegador.
4.  **Aguarde**: Feche a aba, mas mantenha o navegador aberto. Em um minuto, você deve receber a notificação.
5.  **Teste as Ações**: Clique na notificação ou em uma das ações para ver se o navegador abre a URL correta.

## Considerações Finais
- **HTTPS é Obrigatório**: Em produção, seu site **deve** ser servido via HTTPS para que o PWA e as notificações funcionem.
- **Depuração**: Use a aba "Application" nas ferramentas de desenvolvedor do seu navegador para inspecionar o Service Worker, o manifesto e o armazenamento.
- **Limitações de Plataforma**: O comportamento pode variar. Em desktops, o navegador precisa estar aberto (mesmo que em segundo plano). Em mobile, o PWA geralmente precisa estar instalado. O suporte no iOS é mais recente e restrito ao Safari.
- **Limpeza**: Este PoC cria um novo `AnonymousSubscriber` para cada inscrição. Em um sistema de produção, você pode querer adicionar uma lógica para limpar inscrições antigas ou inválidas, usando o `vapid:check` ou manipulando os resultados de envio.