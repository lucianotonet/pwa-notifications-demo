<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const notificationStatus = ref('');
const isSupported = ref(true);
const isPWAInstalled = ref(false);
const showInstallPrompt = ref(false);
let deferredPrompt = null;

onMounted(() => {
    // Verificar se estamos no cliente antes de acessar navigator/window
    if (typeof navigator === 'undefined' || typeof window === 'undefined') {
        isSupported.value = false;
        return;
    }

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        isSupported.value = false;
        return;
    }

    // Verificar se o PWA já está instalado
    checkPWAInstallation();

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallPrompt.value = true;
        console.log('PWA disponível para instalação');
    });

    // Detectar quando o PWA é instalado
    window.addEventListener('appinstalled', () => {
        console.log('PWA instalado!');
        isPWAInstalled.value = true;
        showInstallPrompt.value = false;
        notificationStatus.value = 'PWA instalado com sucesso!';
    });
});

function checkPWAInstallation() {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
    const isInWebAppiOS = (window.navigator.standalone === true);
    const isInstalledChrome = window.matchMedia('(display-mode: standalone)').matches;
    
    isPWAInstalled.value = isStandalone || isInWebAppiOS || isInstalledChrome;
    
    // Debug log
    console.log('PWA Status:', {
        standalone: isStandalone,
        webAppiOS: isInWebAppiOS,
        installed: isPWAInstalled.value
    });
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function installPWA() {
    if (!deferredPrompt) {
        notificationStatus.value = 'PWA não disponível para instalação neste momento';
        return;
    }
    
    try {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            console.log('Usuário aceitou instalar o PWA');
            showInstallPrompt.value = false;
            // A detecção da instalação será feita pelo evento 'appinstalled'
            notificationStatus.value = 'Instalando PWA...';
        } else {
            console.log('Usuário recusou instalar o PWA');
            notificationStatus.value = 'Instalação do PWA cancelada';
        }
    } catch (error) {
        console.error('Erro durante instalação:', error);
        notificationStatus.value = 'Erro durante a instalação do PWA';
    }
    
    deferredPrompt = null;
}

async function subscribe() {
    if (!isSupported.value || typeof navigator === 'undefined' || typeof window === 'undefined') {
        notificationStatus.value = 'Navegador não suporta notificações push';
        return;
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        notificationStatus.value = 'Permissão negada';
        return;
    }

    try {
        const keyResponse = await fetch('/vapid-public-key');
        const { key } = await keyResponse.json();
        const applicationServerKey = urlBase64ToUint8Array(key);

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey,
        });

        await fetch('/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(subscription),
        });

        notificationStatus.value = '✅ Inscrito! Notificações a cada minuto.';
    } catch (error) {
        console.error('Erro:', error);
        notificationStatus.value = '❌ Erro na inscrição';
    }
}
</script>

<template>
    <Head title="PWA Push Notifications Demo" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center px-4">
            <div class="max-w-md w-full text-center space-y-8">
                <!-- Título -->
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        PWA Push Demo
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        Para receber notificações, você precisa instalar este PWA na tela inicial.
                    </p>
                </div>

                <!-- Status de Instalação -->
                <div v-if="isPWAInstalled" class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-200 dark:border-green-800">
                    <p class="text-green-800 dark:text-green-200 text-sm">✅ PWA instalado com sucesso!</p>
                </div>

                <!-- Botões de Ação -->
                <div class="space-y-4">
                    <!-- Botão Instalar PWA -->
                    <button
                        @click="installPWA"
                        :disabled="!showInstallPrompt || isPWAInstalled"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-4 px-8 rounded-lg text-lg transition-all"
                    >
                        <span v-if="isPWAInstalled">✅ PWA já instalado</span>
                        <span v-else-if="showInstallPrompt">📲 Instalar PWA</span>
                        <span v-else>📲 PWA não disponível para instalação</span>
                    </button>

                    <!-- Botão Ativar Notificações -->
                    <button
                        @click="subscribe"
                        :disabled="!isPWAInstalled || !isSupported"
                        class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-semibold py-4 px-8 rounded-lg text-lg transition-all"
                    >
                        🔔 Ativar Notificações Push
                    </button>

                    <!-- Mensagem explicativa -->
                    <div v-if="!isPWAInstalled" class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 border border-yellow-200 dark:border-yellow-800">
                        <p class="text-yellow-800 dark:text-yellow-200 text-sm">
                            ⚠️ Instale o PWA primeiro para ativar as notificações
                        </p>
                    </div>

                    <!-- Status -->
                    <div v-if="notificationStatus" class="bg-gray-100 dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-gray-800 dark:text-gray-200 text-sm font-medium">
                            {{ notificationStatus }}
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer de Debug -->
        <footer class="bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-3">
            <div class="max-w-4xl mx-auto px-4">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <div>
                        <span class="font-mono">SW:</span> 
                        <span :class="(typeof navigator !== 'undefined' && 'serviceWorker' in navigator) ? 'text-green-600' : 'text-red-600'">
                            {{ (typeof navigator !== 'undefined' && 'serviceWorker' in navigator) ? 'OK' : 'NO' }}
                        </span>
                    </div>
                    <div>
                        <span class="font-mono">Push:</span>
                        <span :class="(typeof window !== 'undefined' && 'PushManager' in window) ? 'text-green-600' : 'text-red-600'">
                            {{ (typeof window !== 'undefined' && 'PushManager' in window) ? 'OK' : 'NO' }}
                        </span>
                    </div>
                    <div>
                        <span class="font-mono">PWA:</span>
                        <span :class="isPWAInstalled ? 'text-green-600' : 'text-yellow-600'">
                            {{ isPWAInstalled ? 'YES' : 'NO' }}
                        </span>
                    </div>
                    <div>
                        <span class="font-mono">Install:</span>
                        <span :class="showInstallPrompt ? 'text-green-600' : 'text-red-600'">
                            {{ showInstallPrompt ? 'YES' : 'NO' }}
                        </span>
                    </div>
                    <div>
                        <span class="font-mono">Perm:</span>
                        <span :class="(typeof Notification !== 'undefined' && Notification.permission === 'granted') ? 'text-green-600' : 'text-red-600'">
                            {{ typeof Notification !== 'undefined' ? Notification.permission.toUpperCase() : 'UNKNOWN' }}
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
