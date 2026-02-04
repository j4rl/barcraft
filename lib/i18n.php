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

function supported_languages_with_flags(): array
{
    $languages = supported_languages();
    $flags = language_flag_map();
    $fallback = $flags['en'] ?? 'assets/en.png';
    $result = [];

    foreach ($languages as $code => $label) {
        $result[$code] = [
            'label' => $label,
            'flag' => $flags[$code] ?? $fallback,
        ];
    }

    return $result;
}

function language_flag_map(): array
{
    return [
        'en' => 'assets/en.png',
        'sv' => 'assets/se.png',
        'no' => 'assets/no.png',
        'fi' => 'assets/fi.png',
        'da' => 'assets/dk.png',
        'de' => 'assets/de.png',
        'pl' => 'assets/pl.png',
    ];
}

function language_flag_path(string $lang): string
{
    $flags = language_flag_map();
    return $flags[$lang] ?? ($flags['en'] ?? 'assets/en.png');
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
    } elseif (isset($_SESSION) && is_array($_SESSION) && !empty($_SESSION['lang'])) {
        $lang = (string) $_SESSION['lang'];
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
