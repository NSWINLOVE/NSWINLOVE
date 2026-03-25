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

if (empty($_SESSION['login_captcha']) || !is_array($_SESSION['login_captcha'])) {
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['login_captcha'] = [
        'a' => $a,
        'b' => $b,
        'answer' => (string)($a + $b),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $captcha = trim($_POST['captcha'] ?? '');
    $captchaAnswer = (string)($_SESSION['login_captcha']['answer'] ?? '');

    if ($captcha === '' || $captcha !== $captchaAnswer) {
        $error = '验证码错误。';
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $_SESSION['login_captcha'] = [
            'a' => $a,
            'b' => $b,
            'answer' => (string)($a + $b),
        ];
    } else {
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
                unset($_SESSION['login_captcha']);
                admin_log('login_success', ['username' => $admin['username']]);
                header('Location: /' . $currentSlug . '/dashboard.php');
                exit;
            }

            admin_log('login_failed', ['username' => $username]);
            $error = '账号或密码错误。';
        } catch (Throwable $e) {
            $error = '登录失败：' . $e->getMessage();
        }

        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $_SESSION['login_captcha'] = [
            'a' => $a,
            'b' => $b,
            'answer' => (string)($a + $b),
        ];
    }
}

$captchaA = (int)($_SESSION['login_captcha']['a'] ?? 1);
$captchaB = (int)($_SESSION['login_captcha']['b'] ?? 1);

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
:root{--text:#111827;--muted:#6b7280;--line:rgba(255,255,255,.54);--line-soft:rgba(148,163,184,.14);--panel:rgba(255,255,255,.64);--panel-strong:rgba(255,255,255,.82);--accent:#2563eb;--accent2:#60a5fa;--shadow:0 20px 54px rgba(15,23,42,.08);--shadow-soft:0 10px 22px rgba(15,23,42,.04)}
*{box-sizing:border-box}html,body{height:100%}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:30px;font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display","SF Pro Text",Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:radial-gradient(circle at 18% 12%,rgba(255,255,255,.98),transparent 22%),radial-gradient(circle at 84% 12%,rgba(219,234,254,.42),transparent 18%),linear-gradient(180deg,#fcfdff 0%,#f4f7fd 46%,#fbfcff 100%);color:var(--text);position:relative;overflow:hidden}.glow,.glow-2{position:fixed;border-radius:50%;filter:blur(36px);pointer-events:none}.glow{width:320px;height:320px;left:-100px;top:-100px;background:rgba(255,255,255,.92)}.glow-2{width:220px;height:220px;right:-60px;bottom:-80px;background:rgba(191,219,254,.18)}.shell{width:min(100%,960px);display:grid;grid-template-columns:1fr 420px;gap:30px;align-items:center}.hero{padding:0 6px 0 6px}.eyebrow{display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.66);border:1px solid rgba(255,255,255,.58);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);box-shadow:var(--shadow-soft);font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#3b82f6}.hero h1{margin:24px 0 12px;font-size:58px;line-height:1.0;letter-spacing:-1.9px;font-weight:700}.hero p{margin:0;max-width:420px;color:#4b5563;font-size:17px;line-height:1.9}.hero-note{margin-top:16px;max-width:400px;color:#94a3b8;font-size:13px;line-height:1.85}.card{position:relative;padding:42px 36px;border-radius:40px;background:linear-gradient(180deg,var(--panel-strong),var(--panel));border:1px solid var(--line);backdrop-filter:blur(32px) saturate(160%);-webkit-backdrop-filter:blur(32px) saturate(160%);box-shadow:var(--shadow)}.card:before{content:"";position:absolute;left:1px;right:1px;top:1px;height:88px;border-radius:38px 38px 24px 24px;background:linear-gradient(180deg,rgba(255,255,255,.50),rgba(255,255,255,0));pointer-events:none}.brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;margin-bottom:18px;position:relative;z-index:1}.brand-mark{display:flex;align-items:center;justify-content:center;width:60px;height:60px;border-radius:24px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-size:18px;font-weight:800;box-shadow:0 12px 24px rgba(59,130,246,.12)}.brand-copy small{display:block;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#60a5fa;margin-bottom:6px}.brand-copy h2{margin:0;font-size:32px;line-height:1.08;letter-spacing:-1px}.sub{margin:0 0 24px;color:var(--muted);line-height:1.8;text-align:center;position:relative;z-index:1}.error{padding:14px 16px;border-radius:18px;background:rgba(255,241,242,.82);border:1px solid rgba(251,113,133,.20);color:#be123c;line-height:1.7;margin-bottom:14px;backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)}.group{display:grid;gap:8px;margin-bottom:14px;position:relative;z-index:1}.group label{font-size:13px;font-weight:700;color:#334155}.group input{width:100%;min-height:58px;padding:0 18px;border-radius:20px;border:1px solid var(--line-soft);background:rgba(255,255,255,.86);color:#111827;outline:none;transition:border-color .18s ease,box-shadow .18s ease,background .18s ease,transform .18s ease;backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);box-shadow:inset 0 1px 0 rgba(255,255,255,.62)}.group input:focus{border-color:rgba(96,165,250,.28);box-shadow:0 0 0 4px rgba(96,165,250,.07),0 10px 20px rgba(59,130,246,.05);background:rgba(255,255,255,.98);transform:translateY(-1px)}.captcha-box{display:flex;align-items:center;justify-content:center;min-height:58px;padding:0 18px;border-radius:20px;border:1px solid var(--line-soft);background:rgba(255,255,255,.72);font-size:18px;font-weight:800;color:#1f2937;letter-spacing:.08em}.captcha-grid{display:grid;grid-template-columns:140px 1fr;gap:12px}.meta{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;font-size:12px;color:#64748b;margin:8px 0 20px;position:relative;z-index:1}.meta span{padding:7px 12px;border-radius:999px;background:rgba(255,255,255,.66);border:1px solid rgba(255,255,255,.58);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px)}.helper-links{margin-top:14px;text-align:center;position:relative;z-index:1}.helper-links a{color:#2563eb;text-decoration:none;font-size:13px;font-weight:600}.helper-links a:hover{text-decoration:underline}button{width:100%;min-height:58px;border:none;border-radius:22px;background:linear-gradient(135deg,#2f6df6,#63b3ff);color:#fff;font-weight:700;font-size:15px;letter-spacing:.01em;cursor:pointer;box-shadow:0 14px 26px rgba(59,130,246,.14);transition:transform .18s ease,box-shadow .18s ease,filter .18s ease;position:relative;z-index:1}button:hover{transform:translateY(-1px);box-shadow:0 18px 30px rgba(59,130,246,.17);filter:saturate(103%)}@media (max-width:920px){.shell{grid-template-columns:1fr}.hero{padding:0 4px 2px}.hero h1{font-size:42px}.card{max-width:460px;width:100%;margin:0 auto;padding:30px}}@media (max-width:720px){.hero-note{display:none}.captcha-grid{grid-template-columns:1fr}}@media (prefers-reduced-motion: reduce){*,*::before,*::after{transition:none !important;animation:none !important}button:hover,.group input:focus{transform:none}}
</style>
</head>
<body>
  <div class="glow"></div>
  <div class="glow-2"></div>

  <div class="shell">
    <section class="hero">
      <div class="eyebrow">Apple-like Sign In</div>
      <h1><?= h($site['title'] ?? 'POP 官方下载') ?></h1>
      <p><?= h($site['description'] ?? '欢迎进入管理后台，在这里维护站点内容、下载配置与公告信息。') ?></p>
      <div class="hero-note">页面重心已进一步收向右侧登录盒子，整体更接近 Apple ID 官方登录那种单卡片主视觉感。</div>
    </section>

    <section class="card">
      <div class="brand">
        <div class="brand-mark">管</div>
        <div class="brand-copy">
          <small>Admin Access</small>
          <h2>后台登录</h2>
        </div>
      </div>
      <p class="sub"></p>
      <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_input() ?>
        <div class="group"><label>管理员账号</label><input type="text" name="username" placeholder="请输入管理员账号"></div>
        <div class="group"><label>管理员密码</label><input type="password" name="password" placeholder="请输入管理员密码"></div>
        <div class="group">
          <label>登录验证码</label>
          <div class="captcha-grid">
            <div class="captcha-box"><?= h((string)$captchaA) ?> + <?= h((string)$captchaB) ?> = ?</div>
            <input type="text" name="captcha" inputmode="numeric" placeholder="请输入计算结果">
          </div>
        </div>
        <button type="submit">进入后台</button>
      </form>
      <div class="helper-links"><a href="/<?= h($currentSlug) ?>/forgot-password.php">忘记密码？</a></div>
    </section>
  </div>
</body>
</html>