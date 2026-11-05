<?php

return [
    // The database table name
    'table_name'    => 'authentication_log',

    // The database connection where the authentication_log table resides. Leave empty to use the default
    'db_connection' => null,

    // The events the package listens for to log
    'events'        => [
        'login'                => \Illuminate\Auth\Events\Login::class,
        'failed'               => \Illuminate\Auth\Events\Failed::class,
        'logout'               => \Illuminate\Auth\Events\Logout::class,
        'logout-other-devices' => \Illuminate\Auth\Events\OtherDeviceLogout::class,
    ],

    'notifications' => [
        'new-device'   => [
            // Désactive la notification de nouvel appareil
            'enabled'  => false,
            'location' => false,
            'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\NewDevice::class,
        ],
        'failed-login' => [
            // Désactive la notification d'échec de connexion
            'enabled'  => false,
            'location' => false,
            'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\FailedLogin::class,
        ],
    ],

    // When the clean-up command is run, delete old logs greater than `purge` days
    'purge'         => 365,
];
