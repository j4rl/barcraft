<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'barcraft',
        'user' => 'root',
        'pass' => '',
        'port' => 3306,
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Barcraft',
        'base_url' => '',
        'default_lang' => 'en',
    ],
    'openai' => [
        'api_key' => '',
        'model' => 'gpt-4o-mini',
        'base_url' => 'https://api.openai.com/v1',
        'timeout' => 25,
    ],
];
