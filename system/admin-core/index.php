<?php
require __DIR__ . '/session_bootstrap.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/logger.php';

$baseDir = dirname(__DIR__, 2);
$configFile = $baseDir . '/config/install.php';
$lockFile = $baseDir . '/storage/install.lock';

if (!file_exists($configFile) || !file_exists($lockFile)) {
    header('Location: /install/');
    exit;
}

$config = require $configFile;
$db = $config['db'] ?? [];
$site = $config['site'] ?? [];
$error = '';
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: /' . $currentSlug . '/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['login_time'] = time();
            admin_log('login_success', ['username' => $admin['username']]);
            header('Location: /' . $currentSlug . '/dashboard.php');
            exit;
        }

        admin_log('login_failed', ['username' => $username]);
        $error = '账号或密码错误。';
    } catch (Throwable $e) {
        $error = '登录失败：' . $e->getMessage();
    }
}

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($site['title'] ?? 'POP 后台登录') ?></title>
<style>
:root{--line:#dbeafe;--text:#0f172a;--muted:#64748b;--accent:#2563eb;--accent2:#38bdf8}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:linear-gradient(180deg,#f3f8ff,#eaf2ff);color:var(--text)}.card{width:min(100%,460px);background:rgba(255,255,255,.96);border:1px solid var(--line);border-radius:28px;padding:28px;box-shadow:0 20px 50px rgba(15,23,42,.08)}h1{margin:0 0 8px;font-size:30px}.sub{margin:0 0 18px;color:var(--muted);line-height:1.8}.group{display:grid;gap:8px;margin-bottom:14px}.group label{font-size:13px;font-weight:800;color:#334155}.group input{width:100%;min-height:50px;padding:0 14px;border-radius:14px;border:1px solid #cbd5e1;background:#fff;color:#0f172a}.error{padding:14px 16px;border-radius:16px;background:#fff1f2;border:1px solid #fecdd3;color:#be123c;line-height:1.7;margin-bottom:14px}button{width:100%;min-height:50px;border:none;border-radius:16px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-weight:900;font-size:15px;cursor:pointer;box-shadow:0 14px 30px rgba(37,99,235,.22)}
</style>
</head>
<body>
  <div class="card">
    <h1>后台登录</h1>
    <p class="sub"><?= h($site['title'] ?? 'POP 官方下载') ?> · <?= h($site['description'] ?? '') ?></p>
    <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_input() ?>
      <div class="group"><label>管理员账号</label><input type="text" name="username" placeholder="请输入管理员账号"></div>
      <div class="group"><label>管理员密码</label><input type="password" name="password" placeholder="请输入管理员密码"></div>
      <button type="submit">进入后台</button>
    </form>
  </div>
</body>
</html>
