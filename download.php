<?php
require __DIR__ . '/system/admin-core/config_helper.php';

$configFile = __DIR__ . '/config/install.php';
$config = load_install_config($configFile);
$downloads = $config['downloads'] ?? [];
$platform = $_GET['platform'] ?? '';

if (!isset($downloads[$platform])) {
    http_response_code(404);
    exit('Invalid platform');
}

$url = $downloads[$platform]['url'] ?? '#';
if (!$url || $url === '#') {
    http_response_code(404);
    exit('Download not available');
}

$saved = save_install_config($configFile, function (array $current) use ($platform) {
    $current['downloads'] = $current['downloads'] ?? [];
    $current['downloads'][$platform] = $current['downloads'][$platform] ?? [];
    $current['downloads'][$platform]['hits'] = (int)($current['downloads'][$platform]['hits'] ?? 0) + 1;
    return $current;
});

$statsFile = __DIR__ . '/storage/download-stats.json';
$statsDir = dirname($statsFile);
if (!is_dir($statsDir)) {
    mkdir($statsDir, 0777, true);
}
$today = date('Y-m-d');
$daily = file_exists($statsFile) ? json_decode((string)file_get_contents($statsFile), true) : [];
if (!is_array($daily)) {
    $daily = [];
}
$daily[$today] = $daily[$today] ?? [];
$daily[$today][$platform] = (int)($daily[$today][$platform] ?? 0) + 1;
$daily[$today]['total'] = (int)($daily[$today]['total'] ?? 0) + 1;
file_put_contents($statsFile, json_encode($daily, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);

header('Location: ' . $url, true, 302);
exit;
