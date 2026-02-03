<?php
declare(strict_types=1);

function db_connect(array $config): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $db = new mysqli(
        $config['db']['host'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['name'],
        (int) $config['db']['port']
    );

    $db->set_charset($config['db']['charset'] ?? 'utf8mb4');

    return $db;
}

function fetch_drinks(mysqli $db): array
{
    $sql = "SELECT d.id, d.name, d.description, d.instructions, d.quote, d.is_classic, d.created_at,
                   GROUP_CONCAT(CONCAT(i.name, '::', IFNULL(di.amount,'')) ORDER BY i.name SEPARATOR '||') AS ingredient_pairs
            FROM drinks d
            LEFT JOIN drink_ingredients di ON di.drink_id = d.id
            LEFT JOIN ingredients i ON i.id = di.ingredient_id
            GROUP BY d.id
            ORDER BY d.is_classic DESC, d.name ASC";

    $result = $db->query($sql);
    $drinks = [];

    while ($row = $result->fetch_assoc()) {
        $ingredients = [];
        $ingredient_norms = [];

        if (!empty($row['ingredient_pairs'])) {
            $pairs = explode('||', $row['ingredient_pairs']);
            foreach ($pairs as $pair) {
                if ($pair === '') {
                    continue;
                }
                $parts = explode('::', $pair, 2);
                $name = $parts[0];
                $amount = $parts[1] ?? '';

                $ingredients[] = ['name' => $name, 'amount' => $amount];
                $ingredient_norms[] = normalize_ingredient($name);
            }
        }

        $row['ingredients'] = $ingredients;
        $row['ingredient_norms'] = array_values(array_unique($ingredient_norms));
        $drinks[] = $row;
    }

    return $drinks;
}

function insert_drink(mysqli $db, array $drink, array $ingredients): int
{
    $db->begin_transaction();

    $stmt = $db->prepare(
        "INSERT INTO drinks (name, description, instructions, quote, is_classic) VALUES (?, ?, ?, ?, ?)"
    );

    $is_classic = (int) $drink['is_classic'];

    $stmt->bind_param(
        "ssssi",
        $drink['name'],
        $drink['description'],
        $drink['instructions'],
        $drink['quote'],
        $is_classic
    );

    $stmt->execute();
    $drink_id = (int) $stmt->insert_id;

    $stmtIng = $db->prepare(
        "INSERT INTO ingredients (name) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
    );

    $stmtLink = $db->prepare(
        "INSERT INTO drink_ingredients (drink_id, ingredient_id, amount) VALUES (?, ?, ?)"
    );

    $seen = [];
    foreach ($ingredients as $ingredient) {
        $name = normalize_ingredient($ingredient['name']);
        if ($name === '') {
            continue;
        }
        if (isset($seen[$name])) {
            continue;
        }
        $seen[$name] = true;

        $stmtIng->bind_param("s", $name);
        $stmtIng->execute();
        $ingredient_id = (int) $stmtIng->insert_id;

        $amount = $ingredient['amount'] !== '' ? $ingredient['amount'] : null;
        $stmtLink->bind_param("iis", $drink_id, $ingredient_id, $amount);
        $stmtLink->execute();
    }

    $db->commit();

    return $drink_id;
}

function fetch_user_by_email(mysqli $db, string $email): ?array
{
    $stmt = $db->prepare(
        "SELECT id, name, email, password_hash, is_admin, is_approved, language
         FROM users
         WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row ?: null;
}

function fetch_user_by_id(mysqli $db, int $user_id): ?array
{
    $stmt = $db->prepare(
        "SELECT id, name, email, password_hash, is_admin, is_approved, language
         FROM users
         WHERE id = ?"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row ?: null;
}

function create_user(mysqli $db, string $name, string $email, string $password_hash, string $language): int
{
    $stmt = $db->prepare(
        "INSERT INTO users (name, email, password_hash, is_admin, is_approved, language)
         VALUES (?, ?, ?, 0, 0, ?)"
    );
    $stmt->bind_param("ssss", $name, $email, $password_hash, $language);
    $stmt->execute();

    return (int) $stmt->insert_id;
}

function set_user_approved(mysqli $db, int $user_id, bool $approved): void
{
    $value = $approved ? 1 : 0;
    $stmt = $db->prepare("UPDATE users SET is_approved = ? WHERE id = ?");
    $stmt->bind_param("ii", $value, $user_id);
    $stmt->execute();
}

function set_user_admin(mysqli $db, int $user_id, bool $is_admin): void
{
    $value = $is_admin ? 1 : 0;
    $stmt = $db->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
    $stmt->bind_param("ii", $value, $user_id);
    $stmt->execute();
}

function update_user_language(mysqli $db, int $user_id, string $language): void
{
    $stmt = $db->prepare("UPDATE users SET language = ? WHERE id = ?");
    $stmt->bind_param("si", $language, $user_id);
    $stmt->execute();
}

function fetch_pending_users(mysqli $db): array
{
    $result = $db->query(
        "SELECT id, name, email, is_admin, is_approved, language
         FROM users
         WHERE is_approved = 0
         ORDER BY created_at ASC"
    );
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function fetch_all_users(mysqli $db): array
{
    $result = $db->query(
        "SELECT id, name, email, is_admin, is_approved, language
         FROM users
         ORDER BY created_at ASC"
    );
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function fetch_user_pantry(mysqli $db, int $user_id): array
{
    $stmt = $db->prepare(
        "SELECT i.name
         FROM user_pantry up
         JOIN ingredients i ON i.id = up.ingredient_id
         WHERE up.user_id = ?
         ORDER BY i.name ASC"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    $names = [];
    foreach ($rows as $row) {
        $names[] = $row['name'];
    }

    return $names;
}

function replace_user_pantry(mysqli $db, int $user_id, array $ingredients): void
{
    $db->begin_transaction();

    $stmtDelete = $db->prepare("DELETE FROM user_pantry WHERE user_id = ?");
    $stmtDelete->bind_param("i", $user_id);
    $stmtDelete->execute();

    if ($ingredients !== []) {
        $stmtIng = $db->prepare(
            "INSERT INTO ingredients (name) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
        );
        $stmtLink = $db->prepare(
            "INSERT INTO user_pantry (user_id, ingredient_id) VALUES (?, ?)"
        );

        $seen = [];
        foreach ($ingredients as $ingredient) {
            $name = normalize_ingredient($ingredient);
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            $stmtIng->bind_param("s", $name);
            $stmtIng->execute();
            $ingredient_id = (int) $stmtIng->insert_id;

            $stmtLink->bind_param("ii", $user_id, $ingredient_id);
            $stmtLink->execute();
        }
    }

    $db->commit();
}
