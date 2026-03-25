<?php
require __DIR__ . '/csrf.php';

$baseDir = dirname(__DIR__, 2);
$configFile = $baseDir . '/config/install.php';
$config = file_exists($configFile) ? require $configFile : [];
$db = $config['db'] ?? [];
$site = $config['site'] ?? [];
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$message = '';
$error = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenValid = false;

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($token === '') {
    $error = '重置链接无效。';
} else {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare('SELECT * FROM admins WHERE reset_token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $admin = $stmt->fetch();

        if (!$admin) {
            $error = '重置链接无效或已失效。';
        } elseif (empty($admin['reset_expires_at']) || strtotime((string)$admin['reset_expires_at']) < time()) {
            $error = '重置链接已过期，请重新申请找回密码。';
        } else {
            $tokenValid = true;

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                csrf_verify_or_die();
                $newPassword = trim($_POST['new_password'] ?? '');
                $confirmPassword = trim($_POST['confirm_password'] ?? '');

                if ($newPassword === '' || $confirmPassword === '') {
                    $error = '新密码不能为空。';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = '两次输入的新密码不一致。';
                } else {
                    $update = $pdo->prepare('UPDATE admins SET password = :password, reset_token = NULL, reset_expires_at = NULL, updated_at = NOW() WHERE id = :id');
                    $update->execute([
                        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'id' => $admin['id'],
                    ]);
                    $message = '密码重置成功，你现在可以返回登录页使用新密码登录。';
                    $tokenValid = false;
                }
            }
        }
    } catch (Throwable $e) {
        $error = '密码重置失败：' . $e->getMessage();
        $tokenValid = false;
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($site['title'] ?? 'POP 官方下载') ?> - 重置密码</title>
<style>
:root{--text:#111827;--muted:#6b7280;--line:rgba(255,255,255,.54);--line-soft:rgba(148,163,184,.14);--panel:rgba(255,255,255,.66);--panel-strong:rgba(255,255,255,.84);--accent:#2563eb;--accent2:#60a5fa;--shadow:0 18px 46px rgba(15,23,42,.08)}
*{box-sizing:border-box}html,body{height:100%}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:28px;font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display","SF Pro Text",Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:linear-gradient(180deg,#fbfdff 0%,#f4f7fd 100%);color:var(--text)}.card{width:min(100%,480px);padding:34px;border-radius:34px;background:linear-gradient(180deg,var(--panel-strong),var(--panel));border:1px solid var(--line);backdrop-filter:blur(28px);-webkit-backdrop-filter:blur(28px);box-shadow:var(--shadow)}.brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;margin-bottom:18px}.mark{display:flex;align-items:center;justify-content:center;width:58px;height:58px;border-radius:22px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-size:18px;font-weight:800}.brand h1{margin:0;font-size:30px;letter-spacing:-.8px}.sub{margin:0 0 22px;color:var(--muted);line-height:1.85;text-align:center}.alert-success,.alert-error{padding:14px 16px;border-radius:16px;line-height:1.7;margin-bottom:14px}.alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}.group{display:grid;gap:8px;margin-bottom:14px}.group label{font-size:13px;font-weight:700;color:#334155}.group input{width:100%;min-height:56px;padding:0 16px;border-radius:18px;border:1px solid var(--line-soft);background:rgba(255,255,255,.88);outline:none}.group input:focus{border-color:rgba(96,165,250,.3);box-shadow:0 0 0 4px rgba(96,165,250,.08)}button,.btn{width:100%;min-height:56px;border:none;border-radius:20px;background:linear-gradient(135deg,#2f6df6,#63b3ff);color:#fff;font-weight:700;font-size:15px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}.links{margin-top:14px;text-align:center}.links a{color:#2563eb;text-decoration:none;font-size:13px;font-weight:600}.links a:hover{text-decoration:underline}
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
      <div class="mark">🔑</div>
      <h1>重置密码</h1>
    </div>
    <?php if ($message): ?>
      <p class="sub">密码重置已经完成。</p>
      <div class="alert-success"><?= h($message) ?></div>
      <a class="btn" href="/<?= h($currentSlug) ?>/">返回登录</a>
    <?php else: ?>
      <p class="sub">请输入新的管理员密码。重置链接仅在有效期内可用。</p>
      <?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>
      <?php if ($tokenValid): ?>
        <form method="post">
          <?= csrf_input() ?>
          <input type="hidden" name="token" value="<?= h($token) ?>">
          <div class="group"><label>新密码</label><input type="password" name="new_password" placeholder="请输入新密码"></div>
          <div class="group"><label>确认新密码</label><input type="password" name="confirm_password" placeholder="请再次输入新密码"></div>
          <button type="submit">保存新密码</button>
        </form>
      <?php endif; ?>
      <div class="links"><a href="/<?= h($currentSlug) ?>/forgot-password.php">返回找回密码</a></div>
    <?php endif; ?>
  </div>
</body>
</html>
