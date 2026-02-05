<?php

return [
    'database' => [
        'driver' => 'sqlite',
        'path' => __DIR__ . '/../database/adms.db',
    ],
    
    'server' => [
        'host' => '0.0.0.0',
        'port' => 8080,
        'timeout' => 30,
    ],
    
    'adms' => [
        'protocol_version' => '1.0',
        'device_timeout' => 300, // seconds
        'sync_interval' => 60, // seconds
    ],
    
    'security' => [
        'api_key' => getenv('ADMS_API_KEY') ?: 'change_this_in_production',
        'enable_auth' => true,
    ],
    
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs',
        'level' => 'info', // debug, info, warning, error
    ],
];
