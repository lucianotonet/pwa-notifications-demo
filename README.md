# PWA Push Notifications Demo com Laravel e Vue

Este projeto é uma Prova de Conceito (PoC) para demonstrar a implementação de um Progressive Web App (PWA) instalável com notificações push, utilizando **Laravel 12** para o backend e **Vue 3** (com Vite) para o frontend.

O objetivo principal é permitir que qualquer visitante se inscreva para receber notificações push, que são enviadas automaticamente a cada minuto pelo servidor, sem a necessidade de autenticação de usuário.

## Visão Geral da Arquitetura

A aplicação é dividida em duas áreas principais:

1.  **Backend (Laravel):** O "cérebro" da operação, responsável por:
    *   Gerar as chaves de segurança (VAPID) para as notificações.
    *   Armazenar as inscrições de cada navegador que permite receber notificações.
    *   Possuir uma tarefa agendada (`schedule`) para enviar as mensagens periodicamente.

2.  **Frontend (Vue 3):** A interface com o usuário, responsável por:
    *   Tornar o site um PWA instalável, com um Service Worker e um Manifesto.
    *   Exibir uma interface clara para o usuário instalar o PWA e, subsequentemente, ativar as notificações.
    *   Comunicar-se com o navegador para obter a permissão e a inscrição de notificação.
    *   Enviar os dados da inscrição para o backend para armazenamento.

---

## Detalhamento da Implementação

### Backend: A Lógica no Laravel

1.  **Base de Notificações (`laravel-notification-channels/webpush`):** Utilizamos este pacote padrão da comunidade para lidar com a complexidade do protocolo Web Push, permitindo-nos enviar notificações para os servidores da Google, Apple e Mozilla.
2.  **'Assinante Anônimo' (`AnonymousSubscriber` Model):** Como não há autenticação, criamos um modelo simples para atuar como o "dono" de cada inscrição de notificação. Este modelo utiliza os traits `Notifiable` e `HasPushSubscriptions` para se integrar ao sistema de notificações do Laravel e do pacote web-push.
3.  **Rotas de Comunicação:**
    *   `GET /vapid-public-key`: Fornece a chave de segurança pública VAPID para o frontend iniciar o processo de inscrição.
    *   `POST /subscribe`: Recebe os dados da inscrição gerados pelo navegador e os armazena no banco de dados, associando-os a um novo `AnonymousSubscriber`.
4.  **Conteúdo da Notificação (`TestNotification` Class):** Uma classe de notificação define o conteúdo de cada push (título, corpo, ícone, ações) através do método `toWebPush`.
5.  **Envio Automático (Comando Agendado):**
    *   Um comando do Artisan (`send:test-notifications`) foi criado para buscar todos os assinantes ativos e disparar a `TestNotification` para eles.
    *   Este comando foi registrado no Kernel do Console para ser executado `everyMinute()`.

### Frontend: A Interação com o Usuário (Vue 3)

1.  **Configuração PWA (`vite-plugin-pwa`):** Utilizamos este plugin para automatizar a criação do `manifest.webmanifest` e do `sw.js` (Service Worker), garantindo que a aplicação atenda aos critérios de um PWA.
2.  **Service Worker (`sw.js`):** Este script roda em segundo plano no navegador. Ele tem dois papéis principais:
    *   Ouvir o evento `push` e exibir a notificação recebida.
    *   Ouvir o evento `notificationclick` para abrir a aplicação quando o usuário interage com a notificação.
3.  **Interface de Inscrição (`Welcome.vue`):** O componente principal da aplicação orquestra todo o fluxo do usuário com uma lógica clara:
    *   **Instalação Primeiro:** A interface apresenta um botão "📲 Instalar PWA". Este botão só se torna funcional quando o navegador emite o evento `beforeinstallprompt`, indicando que o PWA está pronto para ser instalado.
    *   **Ativação Depois:** Um segundo botão, "🔔 Ativar Notificações", permanece desabilitado até que o PWA seja efetivamente instalado.
    *   **Fluxo de Permissão:** Após a instalação, o usuário pode clicar para ativar as notificações. Isso dispara a função `subscribe()`, que pede a permissão ao usuário, obtém a inscrição do navegador e a envia para o backend.

---

## 🚀 Próximos Passos: Testando a Funcionalidade

Com o PWA devidamente configurado e instalável, o próximo passo é testar o fluxo completo de notificações.

### Passo 1: Iniciar os Servidores
Para um ambiente de produção (build), certifique-se de que seu servidor web (Nginx, Apache com Laragon, etc.) esteja servindo a pasta `public` do projeto via HTTPS.

Para testar o agendador localmente, execute:
```bash
php artisan schedule:work
```
Em produção, configure um cron job para executar `php artisan schedule:run` a cada minuto.

### Passo 2: Testar o Fluxo no Navegador
1.  **Limpe o Cache:** Acesse `https://pwa-demo.local` (ou sua URL local) em uma **aba anônima** ou limpe o cache e os dados do site para garantir que não há Service Workers antigos registrados.
2.  **Instale o PWA:** O botão "📲 Instalar PWA" deve estar visível e funcional. Clique nele e aceite a instalação.
3.  **Ative as Notificações:** Após a instalação, a página deve recarregar ou o estado da aplicação deve mudar, habilitando o botão "🔔 Ativar Notificações Push". Clique nele e aceite a permissão de notificações.
4.  **Verifique o Banco de Dados (Opcional):** Após aceitar, uma nova entrada deve aparecer nas tabelas `anonymous_subscribers` and `push_subscriptions` do seu banco de dados.
5.  **Aguarde a Notificação:** Mantenha o navegador aberto (pode ser em segundo plano) ou feche a janela do PWA. Dentro de um minuto, você deve receber a primeira notificação push.

### Debug
- **Console do Navegador:** Verifique se há erros durante o processo de inscrição.
- **Aba "Application" (DevTools):** Inspecione o Manifesto, o Service Worker e o armazenamento para garantir que tudo está registrado corretamente.
- **Logs do Laravel:** Verifique `storage/logs/laravel.log` para quaisquer erros do lado do servidor durante a chamada a `/subscribe` ou a execução do comando agendado.
- **Comando Manual:** Para forçar o envio de uma notificação sem esperar o agendador, execute:
  ```bash
  php artisan send:test-notifications
  ``` 

## Fase 2: Envio e Teste de Notificações

Com a inscrição funcional, o foco agora é o envio das notificações, tanto de forma automática (agendada) quanto manual (para testes).

### Parte 1: Envio Automático (Agendado)

#### Objetivo
Confirmar que o sistema envia automaticamente uma notificação para todos os inscritos a cada minuto.

#### Plano de Implementação
1.  **Refatorar a Lógica de Envio:** Para evitar duplicação de código entre o envio agendado e o envio manual, a lógica de buscar todos os inscritos e disparar a notificação será extraída do comando `SendTestNotifications` para uma classe de Ação dedicada: `app/Actions/SendPushNotificationToAllAction.php`.
2.  **Atualizar o Comando:** O comando `SendTestNotifications` será simplificado para apenas invocar a nova Action com uma mensagem de teste padrão.
3.  **Testar o Agendador:** O teste será feito executando o worker do agendador do Laravel localmente.

#### Passos para Teste
1.  **Iniciar o Worker do Agendador:** No seu terminal, na pasta do projeto, execute o comando:
    ```bash
    php artisan schedule:work
    ```
2.  **Observar o Terminal:** O terminal deverá mostrar a execução do comando `send:test-notifications` a cada minuto.
3.  **Receber a Notificação:** Com o PWA instalado (e o navegador aberto, mesmo que em segundo plano), você receberá uma notificação a cada minuto.

### Parte 2: Página de Envio Manual para Testes

#### Objetivo
Criar uma página simples e sem restrições que permita enviar uma notificação push com título e mensagem customizados para todos os inscritos.

#### Plano de Implementação

**Backend:**
1.  **Controller:** Criar `app/Http/Controllers/NotificationController.php`.
2.  **Método `send`:** Este método receberá `title` e `body` da requisição, fará uma validação básica e usará a `SendPushNotificationToAllAction` para disparar o envio.
3.  **Rota:** Adicionar a rota `POST /notifications/send` em `routes/web.php` apontando para o novo controller.

**Frontend:**
1.  **Página de Teste:** Criar o componente `resources/js/pages/PushTest.vue`.
2.  **Formulário:** A página terá um formulário simples com `v-model` para o título e a mensagem.
3.  **Lógica de Envio:** Uma função `async` fará a chamada `fetch` para a rota `POST /notifications/send`, incluindo o token CSRF no cabeçalho.
4.  **Rota:** Adicionar a rota `GET /push-test` em `routes/web.php` para renderizar a página de teste. 