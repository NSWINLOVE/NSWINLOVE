<?php
require __DIR__ . '/session_bootstrap.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/settings_helper.php';
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
$currentSlug = trim(($site['admin_slug'] ?? 'admin'), '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'dashboard';
$message = '';
$error = '';
$activeModal = '';
$serverHost = php_uname('n') ?: 'unknown';
$phpVersion = PHP_VERSION;
$serverTime = date('Y-m-d H:i:s');
$loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
$loadText = is_array($loadAvg) ? implode(' / ', array_map(static fn($v) => number_format((float)$v, 2), array_slice($loadAvg, 0, 3))) : '不可用';
$diskFree = @disk_free_space($baseDir);
$diskTotal = @disk_total_space($baseDir);
$diskText = ($diskFree !== false && $diskTotal !== false && $diskTotal > 0)
    ? format_bytes((float)$diskFree) . ' / ' . format_bytes((float)$diskTotal) . ' 可用'
    : '不可用';
$serverStateText = '运行正常';
$serverStateColor = '#16a34a';
$dbStateText = (!empty($db['host']) && !empty($db['database'])) ? '配置已填写' : '配置待确认';
$dbStateColor = (!empty($db['host']) && !empty($db['database'])) ? '#16a34a' : '#dc2626';
$currentAdmin = null;
$emailStatus = '未绑定';
$emailRecoverStatus = '暂不可用';
$currentEmail = '';

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_bytes(float $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $index = 0;
    while ($bytes >= 1024 && $index < count($units) - 1) {
        $bytes /= 1024;
        $index++;
    }
    return number_format($bytes, $index === 0 ? 0 : 2) . ' ' . $units[$index];
}

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
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $_SESSION['admin_username']]);
    $currentAdmin = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentAdmin) {
        csrf_verify_or_die();
        $action = trim($_POST['action'] ?? '');

        if ($action === 'change_password') {
            $activeModal = 'password';
            $oldPassword = trim($_POST['old_password'] ?? '');
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            if ($newPassword === '' || $confirmPassword === '') {
                $error = '新密码不能为空。';
            } elseif ($newPassword !== $confirmPassword) {
                $error = '两次输入的新密码不一致。';
            } elseif (!password_verify($oldPassword, $currentAdmin['password'])) {
                $error = '旧密码不正确。';
            } else {
                $update = $pdo->prepare('UPDATE admins SET password = :password, updated_at = NOW() WHERE id = :id');
                $update->execute(['password' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $currentAdmin['id']]);
                admin_log('update_password');
                $message = '管理员密码已更新。';
                $activeModal = '';
            }
        } elseif ($action === 'bind_email' || $action === 'change_email') {
            $activeModal = $action === 'bind_email' ? 'bind-email' : 'change-email';
            $email = trim($_POST['email'] ?? '');
            if ($email === '') {
                $error = '邮箱不能为空。';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = '邮箱格式不正确。';
            } else {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 1800);
                $update = $pdo->prepare('UPDATE admins SET email = :email, email_verified = 0, email_verify_token = :token, email_verify_expires_at = :expires_at, updated_at = NOW() WHERE id = :id');
                $update->execute([
                    'email' => $email,
                    'token' => $token,
                    'expires_at' => $expiresAt,
                    'id' => $currentAdmin['id'],
                ]);
                $verifyUrl = current_base_url($currentSlug) . '/email-verify.php?token=' . urlencode($token);
                $subject = '请验证你的管理员邮箱';
                $html = build_verify_email_html($site['title'] ?? '站点管理后台', $verifyUrl);
                $text = "请验证你的管理员邮箱\n\n" . $verifyUrl . "\n\n该链接 30 分钟内有效。";
                send_mail($configFile, $email, $subject, $html, $text, $pdo);
                admin_log('bind_email', ['email' => $email]);
                $message = '验证邮件已发送，请前往邮箱完成验证。';
                $activeModal = '';
            }
        }

        $stmt->execute(['username' => $_SESSION['admin_username']]);
        $currentAdmin = $stmt->fetch();
    }

    $currentEmail = (string)($currentAdmin['email'] ?? '');
    $emailVerified = !empty($currentAdmin['email_verified']);
    if ($currentEmail === '') {
        $emailStatus = '未绑定';
        $emailRecoverStatus = '暂不可用';
    } elseif ($emailVerified) {
        $emailStatus = '已验证';
        $emailRecoverStatus = '可用于找回密码';
    } else {
        $emailStatus = '待验证';
        $emailRecoverStatus = '暂不可用';
    }
} catch (Throwable $e) {
    $error = $error !== '' ? $error : ('仪表盘数据加载失败：' . $e->getMessage());
}

require __DIR__ . '/layout-top.php';
?>
<div class="topbar">
  <div class="topbar-main">
    <h1>仪表盘</h1>
  </div>
  <div class="top-actions">
    <a class="btn secondary" href="/" target="_blank" rel="noopener noreferrer">查看前台</a>
    <a class="btn primary" href="/<?= h($currentSlug) ?>/logout.php">退出登录</a>
  </div>
</div>

<?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>

<style>
.profile-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
.profile-actions .btn{min-width:140px;justify-content:center}
.modal-mask{position:fixed;inset:0;background:rgba(15,23,42,.45);display:none;align-items:center;justify-content:center;padding:24px;z-index:9999}
.modal-mask.show{display:flex}
.modal-card{width:min(560px,100%);background:#fff;border-radius:24px;box-shadow:0 24px 80px rgba(15,23,42,.22);overflow:hidden;display:flex;flex-direction:column}
.modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid rgba(15,23,42,.08);background:#f8fbff}
.modal-head strong{font-size:16px;color:#0f172a}.modal-close{border:none;background:rgba(15,23,42,.06);color:#0f172a;border-radius:12px;padding:10px 14px;cursor:pointer;font-weight:700}
.modal-body{padding:18px}.modal-body p{margin:0 0 12px 0}.modal-body .btn{min-width:140px;justify-content:center}
</style>

<div class="info-grid">
  <div class="panel" style="padding:18px;display:flex;flex-direction:column;justify-content:space-between;min-height:100%;">
    <div>
      <h3 class="state-head" style="margin-top:0;margin-bottom:14px;">
        <span class="state-title"><span>👤</span><span>个人信息</span></span>
        <span class="state-badge blue">账户</span>
      </h3>
      <div class="simple-list">
        <div class="simple-item"><strong>当前账号：</strong><?= h($_SESSION['admin_username'] ?? 'admin') ?></div>
        <div class="simple-item"><strong>当前邮箱：</strong><?= h($currentEmail !== '' ? $currentEmail : '未绑定') ?></div>
        <div class="simple-item"><strong>验证状态：</strong><?= h($emailStatus) ?></div>
        <div class="simple-item"><strong>管理入口：</strong>/<?= h($currentSlug) ?>/dashboard.php</div>
      </div>
      <div class="profile-actions">
        <button type="button" class="btn primary" onclick="openProfileModal('password')">修改密码</button>
        <button type="button" class="btn secondary" onclick="openProfileModal('bind-email')">绑定邮箱</button>
        <button type="button" class="btn secondary" onclick="openProfileModal('change-email')">修改邮箱</button>
      </div>
    </div>
  </div>
  <div class="panel" style="padding:18px;display:flex;flex-direction:column;justify-content:space-between;min-height:100%;">
    <div>
      <h3 class="state-head" style="margin-top:0;margin-bottom:14px;">
        <span class="state-title"><span>📧</span><span>账号邮箱状态</span></span>
        <span class="state-badge green">邮箱</span>
      </h3>
      <div class="simple-list">
        <div class="simple-item"><strong>当前邮箱：</strong><?= h($currentEmail !== '' ? $currentEmail : '未绑定') ?></div>
        <div class="simple-item"><strong>验证状态：</strong><?= h($emailStatus) ?></div>
        <div class="simple-item"><strong>找回密码：</strong><?= h($emailRecoverStatus) ?></div>
        <div class="simple-item"><strong>入口位置：</strong>个人信息弹窗</div>
        <div class="simple-item"><strong>发信位置：</strong>请前往站点设置 → 邮件设置</div>
      </div>
    </div>
  </div>
  <div class="panel" style="padding:18px;min-height:100%;">
    <h3 class="state-head" style="margin-top:0;margin-bottom:14px;">
      <span class="state-title"><span>🖥️</span><span>服务器状态</span></span>
      <span class="state-badge green">在线</span>
    </h3>
    <div class="simple-list">
      <div class="simple-item"><strong>服务器主机：</strong><?= h($serverHost) ?></div>
      <div class="simple-item"><strong>PHP 版本：</strong><?= h($phpVersion) ?></div>
      <div class="simple-item"><strong>当前时间：</strong><?= h($serverTime) ?></div>
      <div class="simple-item"><strong>系统负载：</strong><?= h($loadText) ?></div>
      <div class="simple-item"><strong>磁盘可用：</strong><?= h($diskText) ?></div>
      <div class="simple-item"><strong>服务状态：</strong><span style="font-weight:900;color:<?= h($serverStateColor) ?>;"><?= h($serverStateText) ?></span></div>
    </div>
  </div>
  <div class="panel" style="padding:18px;min-height:100%;">
    <h3 class="state-head" style="margin-top:0;margin-bottom:14px;">
      <span class="state-title"><span>🗄️</span><span>数据库状态</span></span>
      <span class="state-badge green">已配置</span>
    </h3>
    <div class="simple-list">
      <div class="simple-item"><strong>配置状态：</strong><span style="font-weight:900;color:<?= h($dbStateColor) ?>;"><?= h($dbStateText) ?></span></div>
      <div class="simple-item"><strong>主机：</strong><?= h($db['host'] ?? '') ?>:<?= h($db['port'] ?? '') ?></div>
      <div class="simple-item"><strong>数据库名：</strong><?= h($db['database'] ?? '') ?></div>
      <div class="simple-item"><strong>字符集：</strong><?= h($db['charset'] ?? 'utf8mb4') ?></div>
      <div class="simple-item"><strong>后台入口：</strong>/<?= h($currentSlug) ?>/</div>
      <div class="simple-item"><strong>邮件配置：</strong>请前往站点设置 → 邮件设置</div>
    </div>
  </div>
</div>


<div id="passwordModal" class="modal-mask" onclick="if(event.target===this) closeProfileModal('password')">
  <div class="modal-card">
    <div class="modal-head"><strong>修改密码</strong><button type="button" class="modal-close" onclick="closeProfileModal('password')">关闭</button></div>
    <div class="modal-body">
      <form method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="change_password">
        <p><label class="field-label">旧密码</label><input class="input-ui" type="password" name="old_password"></p>
        <p><label class="field-label">新密码</label><input class="input-ui" type="password" name="new_password"></p>
        <p><label class="field-label">确认新密码</label><input class="input-ui" type="password" name="confirm_password"></p>
        <p style="margin-top:16px;"><button class="btn primary" type="submit">保存新密码</button></p>
      </form>
    </div>
  </div>
</div>

<div id="bind-emailModal" class="modal-mask" onclick="if(event.target===this) closeProfileModal('bind-email')">
  <div class="modal-card">
    <div class="modal-head"><strong>绑定邮箱</strong><button type="button" class="modal-close" onclick="closeProfileModal('bind-email')">关闭</button></div>
    <div class="modal-body">
      <form method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="bind_email">
        <p><label class="field-label">邮箱地址</label><input class="input-ui" type="email" name="email" value="<?= h($currentEmail) ?>" placeholder="请输入用于找回密码的邮箱"></p>
        <p class="field-help">绑定后会发送验证邮件，验证通过后才可用于找回密码。</p>
        <p style="margin-top:16px;"><button class="btn primary" type="submit">发送验证邮件</button></p>
      </form>
    </div>
  </div>
</div>

<div id="change-emailModal" class="modal-mask" onclick="if(event.target===this) closeProfileModal('change-email')">
  <div class="modal-card">
    <div class="modal-head"><strong>修改邮箱</strong><button type="button" class="modal-close" onclick="closeProfileModal('change-email')">关闭</button></div>
    <div class="modal-body">
      <form method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="change_email">
        <p><label class="field-label">新邮箱地址</label><input class="input-ui" type="email" name="email" value="<?= h($currentEmail) ?>" placeholder="请输入新的邮箱地址"></p>
        <p class="field-help">修改后会重新进入待验证状态，并发送一封新的验证邮件。</p>
        <p style="margin-top:16px;"><button class="btn primary" type="submit">保存并发送验证</button></p>
      </form>
    </div>
  </div>
</div>

<script>
function openProfileModal(name){var el=document.getElementById(name+'Modal'); if(!el) return; el.classList.add('show'); document.body.style.overflow='hidden';}
function closeProfileModal(name){var el=document.getElementById(name+'Modal'); if(!el) return; el.classList.remove('show'); document.body.style.overflow='';}
document.addEventListener('keydown', function(event){ if(event.key === 'Escape'){ ['password','bind-email','change-email'].forEach(closeProfileModal); } });
<?php if ($activeModal !== ''): ?>openProfileModal('<?= h($activeModal) ?>');<?php endif; ?>
</script>
<?php require __DIR__ . '/layout-bottom.php'; ?>
