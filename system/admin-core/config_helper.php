<?php
function load_install_config(string $configFile): array {
    if (!file_exists($configFile)) {
        return [];
    }

    $config = require $configFile;
    return is_array($config) ? $config : [];
}

function export_install_config(array $data): string {
    return "<?php\nreturn " . var_export($data, true) . ";\n";
}

function save_install_config(string $configFile, callable $mutator): bool {
    $current = load_install_config($configFile);
    $updated = $mutator($current);

    if (!is_array($updated)) {
        return false;
    }

    $dir = dirname($configFile);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        return false;
    }

    $tempFile = $configFile . '.tmp';
    $bytes = file_put_contents($tempFile, export_install_config($updated), LOCK_EX);
    if ($bytes === false) {
        return false;
    }

    return rename($tempFile, $configFile);
}


function db_settings_pdo(string $configFile): ?PDO {
    $config = load_install_config($configFile);
    $db = $config['db'] ?? [];
    if (!$db) {
        return null;
    }
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'] ?? '127.0.0.1',
            $db['port'] ?? '3306',
            $db['database'] ?? '',
            $db['charset'] ?? 'utf8mb4'
        );
        return new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        return null;
    }
}

function db_setting_get(string $configFile, string $key, $default = null) {
    $pdo = db_settings_pdo($configFile);
    if (!$pdo) {
        return $default;
    }
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : $value;
}

function db_setting_set(string $configFile, string $key, string $value): bool {
    $pdo = db_settings_pdo($configFile);
    if (!$pdo) {
        return false;
    }
    $stmt = $pdo->prepare('INSERT INTO settings(`key`, `value`) VALUES(:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
    return $stmt->execute(['key' => $key, 'value' => $value]);
}

function site_navigation_load(string $configFile): array {
    $raw = db_setting_get($configFile, 'site_navigation');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    $config = load_install_config($configFile);
    return array_values($config['navigation'] ?? []);
}

function site_navigation_save(string $configFile, array $navigation): bool {
    return db_setting_set($configFile, 'site_navigation', json_encode(array_values($navigation), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}


function site_downloads_defaults(): array {
    return [
        'android' => ['url' => '#', 'version' => 'v1.0.0', 'notes' => '', 'updated_at' => date('Y-m-d'), 'target_range' => '', 'target_user' => '', 'package_size' => '', 'checksum' => '', 'hits' => 0],
        'ios' => ['url' => '#', 'version' => 'v1.0.0', 'notes' => '', 'updated_at' => date('Y-m-d'), 'target_range' => '', 'target_user' => '', 'package_size' => '', 'checksum' => '', 'hits' => 0],
        'windows' => ['url' => '#', 'version' => 'v1.0.0', 'notes' => '', 'updated_at' => date('Y-m-d'), 'target_range' => '', 'target_user' => '', 'package_size' => '', 'checksum' => '', 'hits' => 0],
        'mac' => ['url' => '#', 'version' => 'v1.0.0', 'notes' => '', 'updated_at' => date('Y-m-d'), 'target_range' => '', 'target_user' => '', 'package_size' => '', 'checksum' => '', 'hits' => 0],
    ];
}

function site_downloads_load(string $configFile): array {
    $defaults = site_downloads_defaults();
    $raw = db_setting_get($configFile, 'site_downloads');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($defaults as $key => $item) {
                $decoded[$key] = array_merge($item, $decoded[$key] ?? []);
            }
            return $decoded;
        }
    }
    $config = load_install_config($configFile);
    $downloads = $config['downloads'] ?? [];
    foreach ($defaults as $key => $item) {
        $downloads[$key] = array_merge($item, $downloads[$key] ?? []);
    }
    return $downloads;
}

function site_downloads_save(string $configFile, array $downloads): bool {
    return db_setting_set($configFile, 'site_downloads', json_encode($downloads, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function site_release_load(string $configFile): array {
    $raw = db_setting_get($configFile, 'site_release');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_merge(['title' => '最新版本更新', 'content' => ''], $decoded);
        }
    }
    $config = load_install_config($configFile);
    return array_merge(['title' => '最新版本更新', 'content' => ''], $config['release'] ?? []);
}

function site_release_save(string $configFile, array $release): bool {
    return db_setting_set($configFile, 'site_release', json_encode($release, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
