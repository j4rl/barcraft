<?php
declare(strict_types=1);

function render_drink_card(array $drink): void
{
    $title = e($drink['name']);
    $description = trim((string) ($drink['description'] ?? ''));
    $instructions = trim((string) ($drink['instructions'] ?? ''));
    $quote = trim((string) ($drink['quote'] ?? ''));
    $is_classic = (int) ($drink['is_classic'] ?? 0) === 1;
    $missing_norms = [];
    if (!empty($drink['missing_ingredients']) && is_array($drink['missing_ingredients'])) {
        $missing_norms = array_fill_keys($drink['missing_ingredients'], true);
    }

    echo '<article class="card">';
    echo '<div class="card-head">';
    echo '<h3>' . $title . '</h3>';
    echo '<span class="tag ' . ($is_classic ? 'tag-classic' : 'tag-user') . '">' . ($is_classic ? e(t('card_classic')) : e(t('card_user'))) . '</span>';
    echo '</div>';

    if ($description !== '') {
        echo '<p class="desc">' . nl2br(e($description)) . '</p>';
    }

    if (!empty($drink['ingredients'])) {
        echo '<div class="chips">';
        foreach ($drink['ingredients'] as $ingredient) {
            $label = display_ingredient($ingredient['name']);
            $norm = normalize_ingredient((string) ($ingredient['name'] ?? ''));
            $is_missing = $norm !== '' && isset($missing_norms[$norm]);
            if (!empty($ingredient['amount'])) {
                $label .= ' - ' . $ingredient['amount'];
            }
            echo '<span class="chip' . ($is_missing ? ' chip-missing' : '') . '">' . e($label) . '</span>';
        }
        echo '</div>';
    }

    if ($instructions !== '') {
        echo '<div class="instructions"><h4>' . e(t('card_how')) . '</h4><div class="text">' . nl2br(e($instructions)) . '</div></div>';
    }

    if ($quote !== '') {
        echo '<blockquote>' . nl2br(e($quote)) . '</blockquote>';
    }

    echo '</article>';
}

function ingredients_to_text(array $ingredients): string
{
    $lines = [];
    foreach ($ingredients as $ingredient) {
        if (!is_array($ingredient)) {
            continue;
        }
        $name = trim((string) ($ingredient['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $amount = trim((string) ($ingredient['amount'] ?? ''));
        $lines[] = $amount !== '' ? ($name . ' - ' . $amount) : $name;
    }

    return implode("\n", $lines);
}
