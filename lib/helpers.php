<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function str_lower(string $value): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }
    return strtolower($value);
}

function normalize_ingredient(string $value): string
{
    $value = str_lower(trim($value));
    $value = preg_replace('/\s+/', ' ', $value);
    return $value ?? '';
}

function parse_ingredient_lines(string $input): array
{
    $lines = preg_split('/[\r\n,;]+/', $input);
    $items = [];
    $seen = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $name = $line;
        $amount = '';
        if (preg_match('/\s+(?:-|\||:)\s+/', $line)) {
            $parts = preg_split('/\s+(?:-|\||:)\s+/', $line, 2);
            $name = trim($parts[0] ?? '');
            $amount = trim($parts[1] ?? '');
        }

        $norm = normalize_ingredient($name);
        if ($norm === '' || isset($seen[$norm])) {
            continue;
        }

        $seen[$norm] = true;
        $items[] = ['name' => $name, 'amount' => $amount];
    }

    return $items;
}

function parse_list(string $input): array
{
    $items = parse_ingredient_lines($input);
    $list = [];
    foreach ($items as $item) {
        $list[] = normalize_ingredient($item['name']);
    }
    return array_values(array_unique($list));
}

function display_ingredient(string $name): string
{
    if (function_exists('mb_convert_case')) {
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }
    return ucwords($name);
}

function filter_search(array $drinks, string $query): array
{
    $needle = str_lower(trim($query));
    if ($needle === '') {
        return $drinks;
    }

    $results = [];
    foreach ($drinks as $drink) {
        $haystack = str_lower(
            ($drink['name'] ?? '') . ' ' .
            ($drink['description'] ?? '') . ' ' .
            ($drink['instructions'] ?? '') . ' ' .
            ($drink['quote'] ?? '')
        );
        if (strpos($haystack, $needle) !== false) {
            $results[] = $drink;
        }
    }

    return $results;
}

function filter_possible(array $drinks, array $pantry_norm): array
{
    $possible = [];
    if ($pantry_norm === []) {
        return $possible;
    }

    $pantry_map = array_fill_keys($pantry_norm, true);
    foreach ($drinks as $drink) {
        $required = $drink['ingredient_norms'] ?? [];
        if ($required === []) {
            continue;
        }

        $missing = [];
        foreach ($required as $req) {
            if (!isset($pantry_map[$req])) {
                $missing[] = $req;
            }
        }

        if ($missing === []) {
            $possible[] = $drink;
        }
    }

    return $possible;
}
