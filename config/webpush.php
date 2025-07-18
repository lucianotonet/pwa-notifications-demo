<?php

return [

    /*
     * You can turn off payload encryption.
     */
    'payload_encryption' => false,

    /*
     * VAPID keys.
     */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /**
     * This is model that will be used to for push subscriptions.
     */
    'model' => \NotificationChannels\WebPush\PushSubscription::class,

    /**
     * This is the name of the table that will be created by the migration and
     * used by the PushSubscription model shipped with this package.
     */
    'table_name' => env('WEBPUSH_DB_TABLE', 'push_subscriptions'),

    /**
     * This is the database connection that will be used by the migration and
     * the PushSubscription model shipped with this package.
     */
    'database_connection' => env('WEBPUSH_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),

    /**
     * The Guzzle client options used by Minishlink\WebPush.
     */
    'client_options' => [],

    /**
     * The automatic padding in bytes used by Minishlink\WebPush.
     * Set to false to support Firefox Android with v1 endpoint.
     */
    'automatic_padding' => env('WEBPUSH_AUTOMATIC_PADDING', true),

];
