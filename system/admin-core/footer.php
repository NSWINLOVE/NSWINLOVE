<?php
require __DIR__ . '/session_bootstrap.php';

$configFile = dirname(__DIR__, 2) . '/config/install.php';
$config = file_exists($configFile) ? require $configFile : [];
$slug = trim(($config['site']['admin_slug'] ?? 'admin'), '/');
$slug = $slug !== '' ? $slug : 'admin';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /' . $slug . '/');
    exit;
}

header('Location: /' . $slug . '/settings.php#footerPane');
exit;
