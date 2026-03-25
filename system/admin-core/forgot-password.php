<?php
require __DIR__ . '/csrf.php';
require __DIR__ . '/mail_helper.php';

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

function current_base_url(string $slug): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/' . trim($slug, '/');
}

function build_reset_email_html(string $siteTitle, string $resetUrl): string
{
    return '<h2>重置你的管理员密码</h2>'
        . '<p>我们收到了一个来自 <strong>' . h($siteTitle) . '</strong> 的管理员密码重置请求。</p>'
        . '<p>请点击下面的链接继续：</p>'
        . '<p><a href="' . h($resetUrl) . '">' . h($resetUrl) . '</a></p>'
        . '<p>该链接 30 分钟内有效。</p>'
        . '<p>如果不是你本人操作，请忽略本邮件。</p>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($username === '' || $email === '') {
        $error = '账号和邮箱不能为空。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确。';
    } else {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset'] ?? 'utf8mb4');
            $pdo = new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :username AND email = :email LIMIT 1');
            $stmt->execute([
                'username' => $username,
                'email' => $email,
            ]);
            $admin = $stmt->fetch();

            if ($admin && !empty($admin['email_verified'])) {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 1800);
                $update = $pdo->prepare('UPDATE admins SET reset_token = :token, reset_expires_at = :expires_at, updated_at = NOW() WHERE id = :id');
                $update->execute([
                    'token' => $token,
                    'expires_at' => $expiresAt,
                    'id' => $admin['id'],
                ]);

                $resetUrl = current_base_url($currentSlug) . '/reset-password.php?token=' . urlencode($token);
                $subject = '重置你的管理员密码';
                $html = build_reset_email_html($site['title'] ?? '站点管理后台', $resetUrl);
                $text = "重置你的管理员密码\n\n" . $resetUrl . "\n\n该链接 30 分钟内有效。";
                send_mail($configFile, $email, $subject, $html, $text, $pdo);
            }

            $message = '如果信息匹配，重置邮件已发送。';
        } catch (Throwable $e) {
            $error = '找回密码处理失败：' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($site['title'] ?? 'POP 官方下载') ?> - 找回密码</title>
<style>
:root{--text:#111827;--muted:#6b7280;--line:rgba(255,255,255,.54);--line-soft:rgba(148,163,184,.14);--panel:rgba(255,255,255,.66);--panel-strong:rgba(255,255,255,.84);--accent:#2563eb;--accent2:#60a5fa;--shadow:0 18px 46px rgba(15,23,42,.08)}
*{box-sizing:border-box}html,body{height:100%}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:28px;font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display","SF Pro Text",Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:linear-gradient(180deg,#fbfdff 0%,#f4f7fd 100%);color:var(--text)}.card{width:min(100%,480px);padding:34px;border-radius:34px;background:linear-gradient(180deg,var(--panel-strong),var(--panel));border:1px solid var(--line);backdrop-filter:blur(28px);-webkit-backdrop-filter:blur(28px);box-shadow:var(--shadow)}.brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;margin-bottom:18px}.mark{display:flex;align-items:center;justify-content:center;width:58px;height:58px;border-radius:22px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-size:18px;font-weight:800}.brand h1{margin:0;font-size:30px;letter-spacing:-.8px}.sub{margin:0 0 22px;color:var(--muted);line-height:1.85;text-align:center}.alert-success,.alert-error{padding:14px 16px;border-radius:16px;line-height:1.7;margin-bottom:14px}.alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}.group{display:grid;gap:8px;margin-bottom:14px}.group label{font-size:13px;font-weight:700;color:#334155}.group input{width:100%;min-height:56px;padding:0 16px;border-radius:18px;border:1px solid var(--line-soft);background:rgba(255,255,255,.88);outline:none}.group input:focus{border-color:rgba(96,165,250,.3);box-shadow:0 0 0 4px rgba(96,165,250,.08)}button{width:100%;min-height:56px;border:none;border-radius:20px;background:linear-gradient(135deg,#2f6df6,#63b3ff);color:#fff;font-weight:700;font-size:15px;cursor:pointer}.links{margin-top:14px;text-align:center}.links a{color:#2563eb;text-decoration:none;font-size:13px;font-weight:600}.links a:hover{text-decoration:underline}
</style>
</head>
<body>
  <div class="card">
    <div class="brand">
      <div class="mark">?</div>
      <h1>找回密码</h1>
    </div>
    <p class="sub">输入管理员账号与已绑定邮箱。如果信息匹配，系统会发送一封重置密码邮件。</p>
    <?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_input() ?>
      <div class="group"><label>管理员账号</label><input type="text" name="username" placeholder="请输入管理员账号"></div>
      <div class="group"><label>绑定邮箱</label><input type="email" name="email" placeholder="请输入已验证邮箱"></div>
      <button type="submit">发送重置邮件</button>
    </form>
    <div class="links"><a href="/<?= h($currentSlug) ?>/">返回登录</a></div>
  </div>
</body>
</html>
