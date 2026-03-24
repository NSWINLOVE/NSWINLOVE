<?php
require __DIR__ . '/session_bootstrap.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
$configFile = dirname(__DIR__, 2) . '/config/install.php';
$config = file_exists($configFile) ? require $configFile : [];
$slug = trim(($config['site']['admin_slug'] ?? 'admin'), '/');
$slug = $slug !== '' ? $slug : 'admin';
header('Location: /' . $slug . '/');
exit;
