<?php

namespace App\Console\Commands;

use App\Models\AnonymousSubscriber;
use App\Notifications\TestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendTestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:test-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia notificações de teste para todos os inscritos.';

    /**
     * Execute the console command.
     */
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
