<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\AnonymousSubscriber;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

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

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rotas para envio manual de notificações
Route::post('/notifications/send', [\App\Http\Controllers\NotificationController::class, 'send'])->name('notifications.send');

// Rota para a página de teste manual
Route::get('/push-test', function () {
    return Inertia::render('PushTest');
})->name('push.test');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
