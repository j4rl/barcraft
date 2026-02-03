<?php
declare(strict_types=1);

function supported_languages(): array
{
    return [
        'en' => 'English',
        'sv' => 'Svenska',
        'no' => 'Norsk',
        'fi' => 'Suomi',
        'da' => 'Dansk',
        'de' => 'Deutsch',
        'pl' => 'Polski',
    ];
}

function i18n_messages(string $lang): array
{
    $base = __DIR__ . '/../i18n/';
    $path = $base . $lang . '.php';
    if (!is_file($path)) {
        $path = $base . 'en.php';
    }

    $messages = require $path;
    if (!is_array($messages)) {
        return [];
    }

    return $messages;
}

function i18n_lang(array $config, ?array $user): string
{
    $default = $config['app']['default_lang'] ?? 'en';
    $lang = $default;
    if ($user && !empty($user['language'])) {
        $lang = (string) $user['language'];
    }

    $languages = supported_languages();
    if (!isset($languages[$lang])) {
        $lang = $default;
    }

    return $lang;
}

function t(string $key, array $vars = []): string
{
    $config = $GLOBALS['config'] ?? ['app' => ['default_lang' => 'en']];
    $user = $GLOBALS['current_user'] ?? null;
    $lang = i18n_lang($config, is_array($user) ? $user : null);

    $catalog = i18n_messages($lang);
    $fallback = i18n_messages('en');
    $text = $catalog[$key] ?? $fallback[$key] ?? $key;

    foreach ($vars as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }

    return $text;
}
