<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $title = 'Notificação Agendada PoC', protected string $body = '')
    {
        $this->body = $body ?: 'Esta é uma notificação de teste agendada. Horário: ' . now()->toDateTimeString();
    }

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon('/pwa-192x192.png')
            ->body($this->body)
            ->action('Explorar', 'explore')
            ->data(['url' => url('/')]);
    }
}