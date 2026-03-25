<?php

function settings_get(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $value = $stmt->fetchColumn();

    if ($value === false || $value === null) {
        return $default;
    }

    return (string)$value;
}

function settings_get_many(PDO $pdo, array $keys): array
{
    if (!$keys) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ($placeholders)");
    $stmt->execute(array_values($keys));

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];

    foreach ($rows as $row) {
        $result[(string)$row['setting_key']] = (string)($row['setting_value'] ?? '');
    }

    return $result;
}

function settings_set(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare('
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_at = CURRENT_TIMESTAMP
    ');
    $stmt->execute([
        'key' => $key,
        'value' => $value,
    ]);
}

function settings_set_many(PDO $pdo, array $items): void
{
    foreach ($items as $key => $value) {
        settings_set($pdo, (string)$key, (string)$value);
    }
}
