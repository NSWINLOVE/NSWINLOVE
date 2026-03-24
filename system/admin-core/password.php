<?php
require __DIR__ . '/session_bootstrap.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/logger.php';

if (empty($_SESSION['admin_logged_in'])) {
    $configFile = dirname(__DIR__, 2) . '/config/install.php';
    $config = file_exists($configFile) ? require $configFile : [];
    $slug = trim(($config['site']['admin_slug'] ?? 'admin'), '/');
    $slug = $slug !== '' ? $slug : 'admin';
    header('Location: /' . $slug . '/');
    exit;
}

$baseDir = dirname(__DIR__, 2);
$configFile = $baseDir . '/config/install.php';
$config = file_exists($configFile) ? require $configFile : [];
$site = $config['site'] ?? [];
$db = $config['db'] ?? [];
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'password';
$message = '';
$error = '';

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $oldPassword = trim($_POST['old_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    if ($newPassword === '' || $confirmPassword === '') {
        $error = '新密码不能为空。';
    } elseif ($newPassword !== $confirmPassword) {
        $error = '两次输入的新密码不一致。';
    } else {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset'] ?? 'utf8mb4');
            $pdo = new PDO($dsn, $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $_SESSION['admin_username']]);
            $admin = $stmt->fetch();
            if (!$admin || !password_verify($oldPassword, $admin['password'])) {
                $error = '旧密码不正确。';
            } else {
                $update = $pdo->prepare('UPDATE admins SET password = :password WHERE id = :id');
                $update->execute(['password' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $admin['id']]);
                admin_log('update_password');
                $message = '管理员密码已更新。';
            }
        } catch (Throwable $e) {
            $error = '密码修改失败：' . $e->getMessage();
        }
    }
}
require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>修改密码</h1><p>更新当前管理员账号的登录密码。</p></div></div>
<div class="panel">
<?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>
<form method="post">
  <?= csrf_input() ?>
  <div class="section-head"><h3 class="section-title"><span>🔐</span><span>密码配置</span></h3><span class="section-sub">账户安全</span></div>
  <p><label class="field-label">旧密码</label><input class="input-ui" type="password" name="old_password"></p>
  <p><label class="field-label">新密码</label><input class="input-ui" type="password" name="new_password"></p>
  <p><label class="field-label">确认新密码</label><input class="input-ui" type="password" name="confirm_password"></p>
  <p><button class="btn primary" type="submit">更新密码</button></p>
</form>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
