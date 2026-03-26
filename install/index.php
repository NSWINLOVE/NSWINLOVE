<?php
$baseDir = dirname(__DIR__);
require $baseDir . '/system/admin-core/csrf.php';
$configFile = $baseDir . '/config/install.php';
$lockFile = $baseDir . '/storage/install.lock';

$config = file_exists($configFile) ? require $configFile : [];
$defaultDb = $config['db'] ?? [];
$defaultSite = $config['site'] ?? [];

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function envChecks(string $baseDir): array {
    return [
        ['label' => 'PHP >= 8.2', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')],
        ['label' => 'PDO 扩展', 'ok' => extension_loaded('pdo')],
        ['label' => 'PDO MySQL 扩展', 'ok' => extension_loaded('pdo_mysql')],
        ['label' => 'storage 目录可写', 'ok' => is_dir($baseDir . '/storage') && is_writable($baseDir . '/storage')],
        ['label' => 'config 目录可写', 'ok' => is_dir($baseDir . '/config') && is_writable($baseDir . '/config')],
    ];
}

function exportConfig(array $data): string {
    return "<?php\nreturn " . var_export($data, true) . ";\n";
}

function deployAdminEntry(string $baseDir, string $slug): void {
    $slug = trim($slug, '/');
    $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug) ?: 'admin';
    $targetDir = $baseDir . '/' . $slug;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new RuntimeException('后台目录创建失败。');
    }
    $map = [
        'index.php' => "<?php require __DIR__ . '/../system/admin-core/index.php';\n",
        'dashboard.php' => "<?php require __DIR__ . '/../system/admin-core/dashboard.php';\n",
        'logout.php' => "<?php require __DIR__ . '/../system/admin-core/logout.php';\n",
        'settings.php' => "<?php require __DIR__ . '/../system/admin-core/settings.php';\n",
        'downloads.php' => "<?php require __DIR__ . '/../system/admin-core/downloads.php';\n",
        'content.php' => "<?php require __DIR__ . '/../system/admin-core/content.php';\n",
        'notice.php' => "<?php require __DIR__ . '/../system/admin-core/notice.php';\n",
        'password.php' => "<?php require __DIR__ . '/../system/admin-core/password.php';\n",
        'about.php' => "<?php require __DIR__ . '/../system/admin-core/about.php';\n",
    ];
    foreach ($map as $file => $content) {
        if (file_put_contents($targetDir . '/' . $file, $content) === false) {
            throw new RuntimeException('后台入口文件写入失败：' . $file);
        }
    }
}

$checks = envChecks($baseDir);
$allPassed = !in_array(false, array_column($checks, 'ok'), true);
$error = '';
$success = '';
$steps = [1 => '欢迎', 2 => '数据库', 3 => '管理员', 4 => '确认安装', 5 => '安装完成'];
$step = max(1, min(5, (int)($_REQUEST['step'] ?? 1)));
$form = [
    'db_host' => $defaultDb['host'] ?? '127.0.0.1',
    'db_port' => $defaultDb['port'] ?? '3306',
    'db_name' => $defaultDb['database'] ?? '',
    'db_user' => $defaultDb['username'] ?? '',
    'db_pass' => $defaultDb['password'] ?? '',
    'admin_user' => 'admin',
    'admin_pass' => '',
    'site_title' => $defaultSite['title'] ?? 'POP 官方下载',
    'site_description' => $defaultSite['description'] ?? '官方正版下载中心，提供最新版本与更新说明',
    'admin_slug' => $defaultSite['admin_slug'] ?? 'admin',
];

if (file_exists($lockFile)) {
    $installedConfig = file_exists($configFile) ? require $configFile : [];
    $installedSlug = trim(($installedConfig['site']['admin_slug'] ?? 'admin'), '/');
    $installedSlug = $installedSlug !== '' ? $installedSlug : 'admin';
    $success = '程序已安装。如需重新安装，请先删除 storage/install.lock 文件。当前后台入口：/' . $installedSlug . '/';
    $step = 5;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$success) {
    csrf_verify_or_die();
    foreach ($form as $key => $value) {
        $form[$key] = trim($_POST[$key] ?? $value);
    }
    $form['admin_slug'] = preg_replace('/[^a-zA-Z0-9_-]/', '', $form['admin_slug']) ?: 'admin';
    $action = $_POST['install_action'] ?? 'next';
    $postedStep = max(1, min(4, (int)($_POST['step'] ?? 1)));

    if ($action === 'prev') {
        $step = max(1, $postedStep - 1);
    } elseif ($action === 'next') {
        if ($postedStep === 1 && !$allPassed) {
            $error = '环境检测未通过，请先处理未通过项。';
            $step = 1;
        } elseif ($postedStep === 2 && ($form['db_name'] === '' || $form['db_user'] === '')) {
            $error = '数据库名和数据库用户名不能为空。';
            $step = 2;
        } elseif ($postedStep === 3 && $form['admin_pass'] === '') {
            $error = '管理员密码不能为空。';
            $step = 3;
        } else {
            $step = min(4, $postedStep + 1);
        }
    } elseif ($action === 'install') {
        if (!$allPassed) {
            $error = '环境检测未通过，请先处理未通过项。';
            $step = 1;
        } elseif ($form['db_name'] === '' || $form['db_user'] === '' || $form['admin_pass'] === '') {
            $error = '数据库名、数据库用户名、管理员密码不能为空。';
            $step = 4;
        } else {
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $form['db_host'], $form['db_port'], $form['db_name']);
                $pdo = new PDO($dsn, $form['db_user'], $form['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $installSummary[] = '已检查/创建数据表：admins';
                $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `key` VARCHAR(191) NOT NULL UNIQUE,
                    `value` LONGTEXT NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $installSummary[] = '已检查/创建数据表：settings';
                $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `setting_key` VARCHAR(191) NOT NULL UNIQUE,
                    `setting_value` LONGTEXT NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $installSummary[] = '已检查/创建数据表：system_settings';
                $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = :username LIMIT 1');
                $stmt->execute(['username' => $form['admin_user']]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $update = $pdo->prepare('UPDATE admins SET password = :password WHERE id = :id');
                    $update->execute(['password' => password_hash($form['admin_pass'], PASSWORD_DEFAULT), 'id' => $existing['id']]);
                } else {
                    $insert = $pdo->prepare('INSERT INTO admins (username, password) VALUES (:username, :password)');
                    $insert->execute(['username' => $form['admin_user'], 'password' => password_hash($form['admin_pass'], PASSWORD_DEFAULT)]);
                }
                $newConfig = [
                    'installed' => true,
                    'db' => [
                        'host' => $form['db_host'],
                        'port' => $form['db_port'],
                        'database' => $form['db_name'],
                        'username' => $form['db_user'],
                        'password' => $form['db_pass'],
                        'charset' => 'utf8mb4',
                    ],
                    'site' => [
                        'title' => $form['site_title'],
                        'description' => $form['site_description'],
                        'admin_slug' => $form['admin_slug'],
                        'keywords' => $defaultSite['keywords'] ?? 'POP,官方下载,安装包,客户端下载',
                        'logo' => $defaultSite['logo'] ?? '',
                        'favicon' => $defaultSite['favicon'] ?? '',
                    ],
                    'downloads' => $config['downloads'] ?? [],
                    'release' => $config['release'] ?? [],
                    'content' => $config['content'] ?? [],
                    'notice' => $config['notice'] ?? [],
                ];
                if (file_put_contents($configFile, exportConfig($newConfig)) === false) {
                    throw new RuntimeException('配置文件写入失败。');
                }

                $defaultSettings = [
                    'site_meta' => [
                        'title' => $newConfig['site']['title'],
                        'description' => $newConfig['site']['description'],
                        'keywords' => $newConfig['site']['keywords'],
                        'admin_slug' => $newConfig['site']['admin_slug'],
                        'logo' => $newConfig['site']['logo'],
                        'favicon' => $newConfig['site']['favicon'],
                    ],
                    'site_display' => [
                        'footer_text' => $newConfig['site']['footer_text'] ?? '',
                        'footer_code' => $newConfig['site']['footer_code'] ?? '',
                    ],
                    'site_notice' => $newConfig['notice'] ?? ['enabled' => false, 'title' => '站点公告', 'content' => ''],
                    'site_navigation' => $newConfig['navigation'] ?? [],
                    'site_downloads' => $newConfig['downloads'] ?? [],
                    'site_release' => $newConfig['release'] ?? ['title' => '最新版本更新', 'content' => ''],
                    'site_content' => $newConfig['content'] ?? [],
                ];
                $settingStmt = $pdo->prepare('INSERT INTO settings(`key`, `value`) VALUES(:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
                foreach ($defaultSettings as $key => $value) {
                    $payload = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $settingStmt->execute([
                        'key' => $key,
                        'value' => $payload,
                    ]);
                }

                $systemSettings = [
                    'site_name' => $newConfig['site']['title'],
                    'site_description' => $newConfig['site']['description'],
                    'site_keywords' => $newConfig['site']['keywords'],
                    'site_logo' => $newConfig['site']['logo'],
                    'site_favicon' => $newConfig['site']['favicon'],
                    'admin_slug' => $newConfig['site']['admin_slug'],
                    'footer_text' => $newConfig['site']['footer_text'] ?? '',
                    'footer_code' => $newConfig['site']['footer_code'] ?? '',
                ];
                $systemStmt = $pdo->prepare('INSERT INTO system_settings(`setting_key`, `setting_value`) VALUES(:key, :value) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)');
                foreach ($systemSettings as $key => $value) {
                    $systemStmt->execute([
                        'key' => $key,
                        'value' => (string)$value,
                    ]);
                }
                $installSummary[] = '已初始化 system_settings 基础键：site_name、site_description、site_keywords、site_logo、site_favicon、admin_slug、footer_text、footer_code';

                deployAdminEntry($baseDir, $form['admin_slug']);
                if (file_put_contents($lockFile, date('c')) === false) {
                    throw new RuntimeException('安装锁写入失败。');
                }
                $success = '安装完成。数据库连接成功，管理员已初始化，配置与安装锁已写入。当前后台入口：/' . $form['admin_slug'] . '/';
                $step = 5;
            } catch (Throwable $e) {
                $error = '安装失败：' . $e->getMessage();
                $step = 4;
            }
        }
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>POP 程序安装向导</title>
<style>
:root{--bg1:#f6f9ff;--bg2:#edf4ff;--line:#dbeafe;--text:#0f172a;--muted:#64748b;--accent:#2563eb;--accent2:#38bdf8;--ok:#16a34a;--bad:#dc2626;--soft:#f8fbff}*{box-sizing:border-box}body{margin:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:radial-gradient(circle at top,#fff 0%,var(--bg1) 35%,var(--bg2) 100%);color:var(--text)}.wrap{max-width:1120px;margin:0 auto;padding:28px 18px 56px}.hero{position:relative;margin-bottom:18px;padding:24px 26px;border-radius:28px;background:linear-gradient(135deg,rgba(255,255,255,.88),rgba(239,246,255,.92));border:1px solid var(--line);box-shadow:0 24px 56px rgba(15,23,42,.06);overflow:hidden}.hero:after{content:"";position:absolute;right:-60px;top:-60px;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(56,189,248,.18),transparent 70%)}.hero h1{margin:0 0 8px;font-size:36px}.hero p{margin:0;color:var(--muted);line-height:1.9;max-width:820px}.stepbar{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin:20px 0 22px}.step{position:relative;padding:16px 16px 14px;border-radius:20px;background:rgba(255,255,255,.82);border:1px solid var(--line);box-shadow:0 10px 24px rgba(15,23,42,.04);transition:.22s ease}.step:not(:last-child):after{content:"";position:absolute;right:-12px;top:50%;width:12px;height:2px;background:#cfe1ff;transform:translateY(-50%)}.step:hover{transform:translateY(-1px)}.step-num{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:999px;background:#e6efff;color:#1d4ed8;font-size:13px;font-weight:900;margin-bottom:10px}.step-title{font-weight:900}.step-sub{margin-top:4px;color:#64748b;font-size:12px}.step.active{background:linear-gradient(135deg,#eff6ff,#ffffff);border-color:#93c5fd;box-shadow:0 18px 40px rgba(37,99,235,.12)}.step.active .step-num{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 8px 20px rgba(37,99,235,.24)}.step.active:after{background:linear-gradient(90deg,#93c5fd,#cfe1ff)}.layout{display:grid;grid-template-columns:360px 1fr;gap:18px}.card{background:rgba(255,255,255,.97);border:1px solid var(--line);border-radius:30px;padding:24px;box-shadow:0 24px 56px rgba(15,23,42,.08)}.card h2{margin:0 0 16px;font-size:24px}.checks{display:grid;gap:10px}.check{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:16px;background:var(--soft);border:1px solid #e5efff}.check span{font-weight:700}.ok{color:var(--ok);font-weight:900}.bad{color:var(--bad);font-weight:900}.group{display:grid;gap:8px;margin-bottom:14px}.group label{font-size:13px;font-weight:800;color:#334155}.group input{width:100%;min-height:52px;padding:0 15px;border-radius:16px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;box-shadow:inset 0 1px 0 rgba(255,255,255,.65);transition:.18s ease}.group input:focus{outline:none;border-color:#93c5fd;box-shadow:0 0 0 4px rgba(59,130,246,.12)}.two{display:grid;grid-template-columns:1fr 1fr;gap:14px}.summary{display:grid;gap:12px}.summary-item{padding:15px 16px;border-radius:18px;background:var(--soft);border:1px solid #e5efff;transition:.18s ease}.summary-item:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(15,23,42,.05)}.summary-item strong{display:block;margin-bottom:6px}.error{padding:14px 16px;border-radius:16px;background:#fff1f2;border:1px solid #fecdd3;color:#be123c;line-height:1.7;margin-bottom:14px}.success{padding:18px;border-radius:18px;background:#effdf5;border:1px solid #bbf7d0;color:#166534;line-height:1.8}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:18px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 20px;border-radius:16px;text-decoration:none;font-weight:900;border:none;cursor:pointer;transition:.2s ease}.btn:hover{transform:translateY(-1px)}.btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 16px 30px rgba(37,99,235,.22)}.btn.secondary{background:#eef6ff;color:#1e3a8a;border:1px solid #bfdbfe}.mini-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#eef6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:12px;font-weight:800;margin-bottom:14px}.side-note{margin-top:14px;padding:14px 16px;border-radius:18px;background:#f8fbff;border:1px solid #e5efff;color:#64748b;line-height:1.8}.pulse{display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 0 rgba(34,197,94,.45);animation:pulse 1.8s infinite}.shine{position:relative;overflow:hidden}.shine:before{content:"";position:absolute;inset:0;transform:translateX(-120%);background:linear-gradient(90deg,transparent,rgba(255,255,255,.26),transparent);animation:shine 3.6s infinite}.success-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:14px;margin-top:14px}.finish-card{padding:18px;border-radius:22px;background:linear-gradient(135deg,#effdf5,#ffffff);border:1px solid #bbf7d0}.finish-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#dcfce7;border:1px solid #86efac;color:#166534;font-size:12px;font-weight:900;margin-bottom:12px}@keyframes pulse{70%{box-shadow:0 0 0 10px rgba(34,197,94,0)}100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}}@keyframes shine{40%,100%{transform:translateX(120%)}}@media (max-width:980px){.layout,.stepbar,.two,.success-grid{grid-template-columns:1fr}.step:not(:last-child):after{display:none}.hero h1{font-size:28px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="hero shine">
    <h1>POP 程序安装向导</h1>
    <p>最后一轮把字段说明、按钮文字和每步文案也往参考站靠近，让整个安装流程从外观到语气都更统一。</p>
  </div>

  <div class="stepbar">
    <?php foreach ($steps as $num => $label): ?>
      <div class="step <?= $step === $num ? 'active' : '' ?>">
        <div class="step-num"><?= h((string)$num) ?></div>
        <div class="step-title"><?= h($label) ?></div>
        <div class="step-sub">安装步骤 <?= h((string)$num) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="layout">
    <div class="card">
      <div class="mini-badge"><span class="pulse"></span> 环境检测</div>
      <h2>安装环境检查</h2>
      <div class="checks">
        <?php foreach ($checks as $item): ?>
          <div class="check"><span><?= h($item['label']) ?></span><strong class="<?= $item['ok'] ? 'ok' : 'bad' ?>"><?= $item['ok'] ? '通过' : '失败' ?></strong></div>
        <?php endforeach; ?>
      </div>
      <div class="side-note"><?= $allPassed ? '检测项目均已通过，可以继续下一步。' : '检测存在未通过项，建议先处理后再继续。' ?></div>
    </div>

    <div class="card">
      <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
      <?php if ($success): ?>
        <div class="finish-card">
          <div class="finish-badge"><span class="pulse"></span> 安装完成</div>
          <h2>程序安装成功</h2>
          <div class="success"><?= h($success) ?></div>
        </div>
        <div class="success-grid">
          <div class="summary-item"><strong>安装结果</strong><span>数据库连接、管理员初始化、配置写入、安装锁写入均已完成。</span></div>
          <div class="summary-item"><strong>下一步</strong><span>现在可以进入后台继续配置下载、公告、内容、统计与日志。</span></div>
        </div>
        <div class="actions">
          <a class="btn primary" href="/">访问前台首页</a>
          <a class="btn secondary" href="/<?= h($form['admin_slug']) ?>/">进入系统后台</a>
        </div>
      <?php else: ?>
        <form method="post">
          <?= csrf_input() ?>
          <input type="hidden" name="step" value="<?= h((string)$step) ?>">
          <?php foreach ($form as $k => $v): ?>
            <?php if ($k === 'admin_pass' && $step !== 3 && $step !== 4): continue; endif; ?>
            <input type="hidden" name="<?= h($k) ?>" value="<?= h($v) ?>">
          <?php endforeach; ?>

          <?php if ($step === 1): ?>
            <div class="mini-badge">Step 1 / 5</div>
            <h2>欢迎使用安装向导</h2>
            <div class="summary">
              <div class="summary-item"><strong>欢迎</strong><span>本向导将帮助你完成程序初始化、数据库配置、管理员创建和后台入口生成。</span></div>
              <div class="summary-item"><strong>开始前</strong><span><?= $allPassed ? '当前环境已满足安装要求，可以继续。' : '当前环境尚未完全满足要求，请先处理检测失败项。' ?></span></div>
            </div>
          <?php elseif ($step === 2): ?>
            <div class="mini-badge">Step 2 / 5</div>
            <h2>填写数据库信息</h2>
            <div class="two">
              <div class="group"><label>数据库主机</label><input type="text" name="db_host" value="<?= h($form['db_host']) ?>"></div>
              <div class="group"><label>数据库端口</label><input type="text" name="db_port" value="<?= h($form['db_port']) ?>"></div>
            </div>
            <div class="group"><label>数据库名称</label><input type="text" name="db_name" value="<?= h($form['db_name']) ?>"></div>
            <div class="group"><label>数据库用户名</label><input type="text" name="db_user" value="<?= h($form['db_user']) ?>"></div>
            <div class="group"><label>数据库密码</label><input type="text" name="db_pass" value="<?= h($form['db_pass']) ?>"></div>
          <?php elseif ($step === 3): ?>
            <div class="mini-badge">Step 3 / 5</div>
            <h2>设置管理员信息</h2>
            <div class="two">
              <div class="group"><label>管理员账号</label><input type="text" name="admin_user" value="<?= h($form['admin_user']) ?>"></div>
              <div class="group"><label>管理员密码</label><input type="text" name="admin_pass" value="<?= h($form['admin_pass']) ?>"></div>
            </div>
            <div class="group"><label>后台访问目录</label><input type="text" name="admin_slug" value="<?= h($form['admin_slug']) ?>"></div>
          <?php elseif ($step === 4): ?>
            <div class="mini-badge">Step 4 / 5</div>
            <h2>确认安装信息</h2>
            <div class="summary">
              <div class="summary-item"><strong>数据库配置</strong><span><?= h($form['db_host']) ?>:<?= h($form['db_port']) ?> / <?= h($form['db_name']) ?> / <?= h($form['db_user']) ?></span></div>
              <div class="summary-item"><strong>管理员配置</strong><span><?= h($form['admin_user']) ?> / 后台入口：/<?= h($form['admin_slug']) ?>/</span></div>
              <div class="summary-item"><strong>站点配置</strong><span><?= h($form['site_title']) ?> · <?= h($form['site_description']) ?></span></div>
            </div>
            <div class="group"><label>站点标题</label><input type="text" name="site_title" value="<?= h($form['site_title']) ?>"></div>
            <div class="group"><label>站点描述</label><input type="text" name="site_description" value="<?= h($form['site_description']) ?>"></div>
          <?php endif; ?>

          <div class="actions">
            <?php if ($step > 1): ?><button class="btn secondary" type="submit" name="install_action" value="prev">返回上一步</button><?php endif; ?>
            <?php if ($step < 4): ?><button class="btn primary" type="submit" name="install_action" value="next">继续下一步</button><?php else: ?><button class="btn primary" type="submit" name="install_action" value="install">确认并开始安装</button><?php endif; ?>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require $baseDir . '/system/common-footer.php'; site_footer_render($defaultSite); ?>
</body>
</html>
