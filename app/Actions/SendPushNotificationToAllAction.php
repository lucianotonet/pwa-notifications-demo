<?php

namespace App\Actions;

use App\Models\AnonymousSubscriber;
use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Notification;

class SendPushNotificationToAllAction
{
    public function execute(string $title, string $body): void
    {
        $subscribers = AnonymousSubscriber::whereHas('pushSubscriptions')->get();

        if ($subscribers->isEmpty()) {
            return;
        }

        // Envia a notificação usando o canal WebPush do wrapper
        Notification::send($subscribers, new TestNotification($title, $body));

        // Logging simples para depuração
        logger()->info('Notificações enviadas para ' . $subscribers->count() . ' inscritos.');
    }
} 