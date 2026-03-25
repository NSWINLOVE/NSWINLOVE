<?php
$baseDir = dirname(__DIR__, 2);
$configFile = $baseDir . '/config/install.php';
$config = file_exists($configFile) ? require $configFile : [];
$db = $config['db'] ?? [];
$site = $config['site'] ?? [];
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$message = '';
$error = '';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    $error = '验证链接无效。';
} else {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email_verify_token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $admin = $stmt->fetch();

        if (!$admin) {
            $error = '验证链接无效或已失效。';
        } elseif (empty($admin['email_verify_expires_at']) || strtotime((string)$admin['email_verify_expires_at']) < time()) {
            $error = '验证链接已过期，请返回后台重新发送验证邮件。';
        } else {
            $update = $pdo->prepare('UPDATE admins SET email_verified = 1, email_verify_token = NULL, email_verify_expires_at = NULL, updated_at = NOW() WHERE id = :id');
            $update->execute(['id' => $admin['id']]);
            $message = '邮箱验证成功。';
        }
    } catch (Throwable $e) {
        $error = '邮箱验证失败：' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($site['title'] ?? 'POP 官方下载') ?> - 邮箱验证</title>
<style>
:root{--text:#0f172a;--muted:#64748b;--line:#dbeafe;--ok:#16a34a;--ok-bg:#f0fdf4;--ok-line:#bbf7d0;--err:#dc2626;--err-bg:#fef2f2;--err-line:#fecaca}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:linear-gradient(180deg,#f8fbff 0%,#eef5ff 100%);color:var(--text)}.card{width:min(100%,560px);background:rgba(255,255,255,.94);border:1px solid var(--line);border-radius:30px;padding:34px;box-shadow:0 20px 50px rgba(15,23,42,.08)}.badge{display:inline-flex;align-items:center;justify-content:center;width:60px;height:60px;border-radius:22px;font-size:26px;font-weight:800;margin-bottom:16px}.badge.ok{background:var(--ok-bg);color:var(--ok);border:1px solid var(--ok-line)}.badge.err{background:var(--err-bg);color:var(--err);border:1px solid var(--err-line)}h1{margin:0 0 10px;font-size:30px;letter-spacing:-.6px}p{margin:0;color:var(--muted);line-height:1.9}.actions{margin-top:24px;display:flex;flex-wrap:wrap;gap:12px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 18px;border-radius:16px;text-decoration:none;font-weight:800}.btn.primary{background:linear-gradient(135deg,#2563eb,#60a5fa);color:#fff;box-shadow:0 14px 28px rgba(37,99,235,.16)}.btn.ghost{background:#fff;color:#334155;border:1px solid #dbeafe}
</style>
</head>
<body>
  <div class="card">
    <?php if ($message): ?>
      <div class="badge ok">✓</div>
      <h1>验证成功</h1>
      <p><?= h($message) ?> 你现在可以返回后台继续使用账号安全功能。</p>
      <div class="actions">
        <a class="btn primary" href="/<?= h($currentSlug) ?>/">返回后台登录</a>
        <a class="btn ghost" href="/<?= h($currentSlug) ?>/password.php">打开密码页</a>
      </div>
    <?php else: ?>
      <div class="badge err">!</div>
      <h1>验证失败</h1>
      <p><?= h($error !== '' ? $error : '邮箱验证失败，请稍后重试。') ?></p>
      <div class="actions">
        <a class="btn primary" href="/<?= h($currentSlug) ?>/password.php">返回密码页</a>
        <a class="btn ghost" href="/<?= h($currentSlug) ?>/">返回后台登录</a>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
