<?php

namespace App\Console\Commands;

use App\Actions\SendPushNotificationToAllAction;
use Illuminate\Console\Command;

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
    public function handle(SendPushNotificationToAllAction $sendAction)
    {
        $this->info('Enviando notificações de teste automáticas...');

        $title = 'Notificação push de demonstração';
        $body = 'Esta é uma mensagem de teste enviada pelo sistema. Horário: ' . now()->toDateTimeString();

        $sendAction->execute($title, $body);

        $this->info('Notificações enviadas com sucesso.');
    }
}
