<?php
declare(strict_types=1);

$local_config_file = __DIR__ . '/config.local.php';
$local_config = [];

if (is_file($local_config_file)) {
    $loaded_local_config = require $local_config_file;
    if (is_array($loaded_local_config)) {
        $local_config = $loaded_local_config;
    }
}

$base_config = [
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

return array_replace_recursive($base_config, $local_config);
