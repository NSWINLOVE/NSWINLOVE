<?php
function load_install_config(string $configFile): array {
    if (!file_exists($configFile)) {
        return [];
    }
    $config = require $configFile;
    return is_array($config) ? $config : [];
}

function export_install_config(array $data): string {
    return "<?php
return " . var_export($data, true) . ";
";
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
    try {
        $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : $value;
    } catch (Throwable $e) {
        return $default;
    }
}

function db_setting_set(string $configFile, string $key, string $value): bool {
    $pdo = db_settings_pdo($configFile);
    if (!$pdo) {
        return false;
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO settings(`key`, `value`) VALUES(:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
        return $stmt->execute(['key' => $key, 'value' => $value]);
    } catch (Throwable $e) {
        return false;
    }
}

function site_navigation_load(string $configFile): array {
    $raw = db_setting_get($configFile, 'site_navigation');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_values($decoded);
    }
    $config = load_install_config($configFile);
    return array_values($config['navigation'] ?? []);
}
function site_navigation_save(string $configFile, array $navigation): bool {
    return db_setting_set($configFile, 'site_navigation', json_encode(array_values($navigation), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
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
            foreach ($defaults as $k => $v) $decoded[$k] = array_merge($v, $decoded[$k] ?? []);
            return $decoded;
        }
    }
    $config = load_install_config($configFile);
    $downloads = $config['downloads'] ?? [];
    foreach ($defaults as $k => $v) $downloads[$k] = array_merge($v, $downloads[$k] ?? []);
    return $downloads;
}
function site_downloads_save(string $configFile, array $downloads): bool {
    return db_setting_set($configFile, 'site_downloads', json_encode($downloads, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}

function site_release_load(string $configFile): array {
    $raw = db_setting_get($configFile, 'site_release');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_merge(['title'=>'最新版本更新','content'=>''], $decoded);
    }
    $config = load_install_config($configFile);
    return array_merge(['title'=>'最新版本更新','content'=>''], $config['release'] ?? []);
}
function site_release_save(string $configFile, array $release): bool {
    return db_setting_set($configFile, 'site_release', json_encode($release, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}

function site_content_defaults(): array {
    return ['requirements_title'=>'系统要求','requirements'=>'','guide_title'=>'安装教程','guide'=>'','faq_title'=>'常见问题','faq'=>''];
}
function site_content_load(string $configFile): array {
    $raw = db_setting_get($configFile, 'site_content');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_merge(site_content_defaults(), $decoded);
    }
    $config = load_install_config($configFile);
    return array_merge(site_content_defaults(), $config['content'] ?? []);
}
function site_content_save(string $configFile, array $content): bool {
    return db_setting_set($configFile, 'site_content', json_encode($content, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}

function site_notice_load(string $configFile): array {
    $raw = db_setting_get($configFile, 'site_notice');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_merge(['enabled'=>false,'title'=>'站点公告','content'=>''], $decoded);
    }
    $config = load_install_config($configFile);
    return array_merge(['enabled'=>false,'title'=>'站点公告','content'=>''], $config['notice'] ?? []);
}
function site_notice_save(string $configFile, array $notice): bool {
    return db_setting_set($configFile, 'site_notice', json_encode($notice, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}

function site_meta_load(string $configFile): array {
    $defaults = ['title'=>'POP 官方下载','description'=>'','keywords'=>'','admin_slug'=>'admin','logo'=>'','favicon'=>''];
    $raw = db_setting_get($configFile, 'site_meta');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_merge($defaults, $decoded);
    }
    $config = load_install_config($configFile);
    return array_merge($defaults, $config['site'] ?? []);
}
function site_meta_save(string $configFile, array $meta): bool {
    return db_setting_set($configFile, 'site_meta', json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}

function site_display_load(string $configFile): array {
    $raw = db_setting_get($configFile, 'site_display');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_merge(['footer_text'=>''], $decoded);
    }
    $config = load_install_config($configFile);
    return ['footer_text'=>$config['site']['footer_text'] ?? ''];
}
function site_display_save(string $configFile, array $display): bool {
    return db_setting_set($configFile, 'site_display', json_encode($display, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}


function site_snippets_load(string $configFile): array {
    $raw = db_setting_get($configFile, 'site_snippets');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values($decoded);
        }
    }
    return [];
}

function site_snippets_save(string $configFile, array $snippets): bool {
    return db_setting_set($configFile, 'site_snippets', json_encode(array_values($snippets), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function render_content_with_snippets(string $content, array $snippets): string {
    if ($content === '' || !$snippets) {
        return $content;
    }
    $map = [];
    foreach ($snippets as $item) {
        $key = trim((string)($item['key'] ?? ''));
        if ($key === '') continue;
        $map[$key] = (string)($item['code'] ?? '');
    }
    return preg_replace_callback('/\{\{\s*code:([a-zA-Z0-9_-]+)\s*\}\}/', static function ($m) use ($map) {
        $key = $m[1] ?? '';
        return $map[$key] ?? '';
    }, $content) ?? $content;
}
