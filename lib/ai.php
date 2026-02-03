<?php
declare(strict_types=1);

function ai_generate_drink(array $config, string $name, string $notes, string $lang): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'curl_missing'];
    }

    $api_key = trim((string) ($config['openai']['api_key'] ?? ''));
    if ($api_key === '') {
        return ['ok' => false, 'error' => 'missing_api_key'];
    }

    $base_url = rtrim((string) ($config['openai']['base_url'] ?? 'https://api.openai.com/v1'), '/');
    $model = (string) ($config['openai']['model'] ?? 'gpt-4o-mini');
    $timeout = (int) ($config['openai']['timeout'] ?? 25);

    $languages = supported_languages();
    $language = $languages[$lang] ?? 'English';

    $schema = [
        'type' => 'object',
        'additionalProperties' => false,
        'properties' => [
            'name' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'instructions' => ['type' => 'string'],
            'quote' => ['type' => 'string'],
            'ingredients' => [
                'type' => 'array',
                'minItems' => 3,
                'maxItems' => 8,
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'amount' => ['type' => 'string'],
                    ],
                    'required' => ['name', 'amount'],
                ],
            ],
        ],
        'required' => ['name', 'description', 'instructions', 'quote', 'ingredients'],
    ];

    $instructions = "You are a creative bartender. Respond in {$language}. " .
        "Return ONLY JSON that matches the schema. Use metric units (cl or ml). " .
        "Keep it realistic and concise.";

    $input = "Drink name: {$name}\n";
    if ($notes !== '') {
        $input .= "Notes: {$notes}\n";
    }

    $payload = [
        'model' => $model,
        'instructions' => $instructions,
        'input' => $input,
        'text' => [
            'format' => [
                'type' => 'json_schema',
                'name' => 'drink',
                'schema' => $schema,
                'strict' => true,
            ],
        ],
    ];

    $ch = curl_init($base_url . '/responses');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => $timeout,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => $curl_error ?: 'request_failed'];
    }

    $decoded = json_decode($response, true);
    if ($status >= 400) {
        $message = 'request_failed';
        if (is_array($decoded) && isset($decoded['error']['message'])) {
            $message = (string) $decoded['error']['message'];
        }
        return ['ok' => false, 'error' => $message];
    }

    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'bad_response'];
    }

    $text_output = ai_extract_output_text($decoded);
    if ($text_output === '') {
        return ['ok' => false, 'error' => 'empty_output'];
    }

    $drink = ai_decode_json($text_output);
    if (!is_array($drink)) {
        return ['ok' => false, 'error' => 'invalid_json'];
    }

    $drink = ai_normalize_drink($drink);
    if ($drink === null) {
        return ['ok' => false, 'error' => 'invalid_shape'];
    }

    return ['ok' => true, 'drink' => $drink];
}

function ai_extract_output_text(array $response): string
{
    $output = $response['output'] ?? [];
    if (!is_array($output)) {
        return '';
    }

    $text = '';
    foreach ($output as $item) {
        if (!is_array($item) || ($item['type'] ?? '') !== 'message') {
            continue;
        }
        $content = $item['content'] ?? [];
        if (!is_array($content)) {
            continue;
        }
        foreach ($content as $part) {
            if (is_array($part) && ($part['type'] ?? '') === 'output_text') {
                $text .= (string) ($part['text'] ?? '');
            }
        }
    }

    return trim($text);
}

function ai_decode_json(string $text): ?array
{
    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }

    $snippet = substr($text, $start, $end - $start + 1);
    $decoded = json_decode($snippet, true);
    return is_array($decoded) ? $decoded : null;
}

function ai_normalize_drink(array $drink): ?array
{
    $required = ['name', 'description', 'instructions', 'quote', 'ingredients'];
    foreach ($required as $key) {
        if (!array_key_exists($key, $drink)) {
            return null;
        }
    }

    if (!is_array($drink['ingredients'])) {
        return null;
    }

    $ingredients = [];
    foreach ($drink['ingredients'] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = trim((string) ($item['name'] ?? ''));
        $amount = trim((string) ($item['amount'] ?? ''));
        if ($name === '') {
            continue;
        }
        $ingredients[] = [
            'name' => $name,
            'amount' => $amount,
        ];
    }

    if ($ingredients === []) {
        return null;
    }

    return [
        'name' => trim((string) $drink['name']),
        'description' => trim((string) $drink['description']),
        'instructions' => trim((string) $drink['instructions']),
        'quote' => trim((string) $drink['quote']),
        'ingredients' => $ingredients,
    ];
}
