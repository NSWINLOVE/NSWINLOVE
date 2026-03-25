<?php
require __DIR__ . '/session_bootstrap.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/logger.php';
require __DIR__ . '/mail_helper.php';

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
$admin = null;

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function current_base_url(string $slug): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/' . trim($slug, '/');
}
function build_verify_email_html(string $siteTitle, string $verifyUrl): string {
    return '<h2>请验证你的管理员邮箱</h2>'
        . '<p>你正在为 <strong>' . h($siteTitle) . '</strong> 绑定管理员邮箱。</p>'
        . '<p>请点击下面的链接完成验证：</p>'
        . '<p><a href="' . h($verifyUrl) . '">' . h($verifyUrl) . '</a></p>'
        . '<p>该链接 30 分钟内有效。</p>'
        . '<p>如果不是你本人操作，请忽略本邮件。</p>';
}

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $_SESSION['admin_username']]);
    $admin = $stmt->fetch();
} catch (Throwable $e) {
    $error = '管理员信息读取失败：' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    csrf_verify_or_die();
    $action = trim($_POST['action'] ?? 'change_password');

    if ($action === 'bind_email') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $error = '邮箱不能为空。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '邮箱格式不正确。';
        } else {
            try {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 1800);
                $update = $pdo->prepare('UPDATE admins SET email = :email, email_verified = 0, email_verify_token = :token, email_verify_expires_at = :expires_at, updated_at = NOW() WHERE id = :id');
                $update->execute([
                    'email' => $email,
                    'token' => $token,
                    'expires_at' => $expiresAt,
                    'id' => $admin['id'],
                ]);

                $verifyUrl = current_base_url($currentSlug) . '/email-verify.php?token=' . urlencode($token);
                $subject = '请验证你的管理员邮箱';
                $html = build_verify_email_html($site['title'] ?? '站点管理后台', $verifyUrl);
                $text = "请验证你的管理员邮箱\n\n" . $verifyUrl . "\n\n该链接 30 分钟内有效。";
                send_mail($configFile, $email, $subject, $html, $text, $pdo);
                admin_log('bind_email', ['email' => $email]);
                $message = '验证邮件已发送，请前往邮箱完成验证。';
                $stmt->execute(['username' => $_SESSION['admin_username']]);
                $admin = $stmt->fetch();
            } catch (Throwable $e) {
                $error = '邮箱绑定失败：' . $e->getMessage();
            }
        }
    } else {
        $oldPassword = trim($_POST['old_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        if ($newPassword === '' || $confirmPassword === '') {
            $error = '新密码不能为空。';
        } elseif ($newPassword !== $confirmPassword) {
            $error = '两次输入的新密码不一致。';
        } elseif (!password_verify($oldPassword, $admin['password'])) {
            $error = '旧密码不正确。';
        } else {
            try {
                $update = $pdo->prepare('UPDATE admins SET password = :password, updated_at = NOW() WHERE id = :id');
                $update->execute(['password' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $admin['id']]);
                admin_log('update_password');
                $message = '管理员密码已更新。';
                $stmt->execute(['username' => $_SESSION['admin_username']]);
                $admin = $stmt->fetch();
            } catch (Throwable $e) {
                $error = '密码修改失败：' . $e->getMessage();
            }
        }
    }
}

$currentEmail = $admin['email'] ?? '';
$emailVerified = !empty($admin['email_verified']);
$emailStateText = $currentEmail === '' ? '未绑定' : ($emailVerified ? '已验证' : '待验证');
require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>修改密码</h1><p>更新当前管理员账号的登录密码，并维护邮箱绑定状态。</p></div></div>
<div class="panel">
<?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>

<div class="soft-card" style="margin-bottom:18px;">
  <form method="post">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="change_password">
    <div class="section-head"><h3 class="section-title"><span>🔐</span><span>密码配置</span></h3><span class="section-sub">账户安全</span></div>
    <p><label class="field-label">旧密码</label><input class="input-ui" type="password" name="old_password"></p>
    <p><label class="field-label">新密码</label><input class="input-ui" type="password" name="new_password"></p>
    <p><label class="field-label">确认新密码</label><input class="input-ui" type="password" name="confirm_password"></p>
    <p><button class="btn primary" type="submit">更新密码</button></p>
  </form>
</div>

<div class="soft-card">
  <div class="section-head"><h3 class="section-title"><span>📧</span><span>账号邮箱</span></h3><span class="section-sub">绑定与验证</span></div>
  <div class="field-grid-2" style="margin-bottom:12px;">
    <p><label class="field-label">当前邮箱</label><input class="input-ui" type="text" value="<?= h($currentEmail !== '' ? $currentEmail : '未绑定') ?>" readonly></p>
    <p><label class="field-label">验证状态</label><input class="input-ui" type="text" value="<?= h($emailStateText) ?>" readonly></p>
  </div>
  <form method="post">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="bind_email">
    <p><label class="field-label">绑定邮箱</label><input class="input-ui" type="email" name="email" value="<?= h($currentEmail) ?>" placeholder="请输入用于找回密码的邮箱"></p>
    <p class="field-help">绑定新邮箱后将重新进入待验证状态，验证通过后才可用于找回密码。</p>
    <p><button class="btn primary" type="submit"><?= $currentEmail !== '' && !$emailVerified ? '重新发送验证邮件' : '发送验证邮件' ?></button></p>
  </form>
</div>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
