<?php
require __DIR__ . '/session_bootstrap.php';

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
$downloads = $config['downloads'] ?? [];
$currentSlug = trim(($site['admin_slug'] ?? 'admin'), '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'dashboard';
$totalHits = 0;
foreach (['android', 'ios', 'windows', 'mac'] as $key) {
    $totalHits += (int)($downloads[$key]['hits'] ?? 0);
}
require __DIR__ . '/layout-top.php';

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<div class="topbar">
  <div class="topbar-main">
    <h1>仪表盘</h1>
    <p>欢迎回来，<?= h($_SESSION['admin_username'] ?? 'admin') ?>。</p>
  </div>
  <div class="top-actions">
    <a class="btn secondary" href="/">查看前台</a>
    <a class="btn primary" href="/<?= h($currentSlug) ?>/logout.php">退出登录</a>
  </div>
</div>

<div class="stats-grid">
  <div class="panel" style="padding:18px;">
    <div style="color:#64748b;font-size:13px;">总下载量</div>
    <div style="margin-top:8px;font-size:32px;font-weight:900;color:#0f172a;"><?= h((string)$totalHits) ?></div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="color:#64748b;font-size:13px;">Android</div>
    <div style="margin-top:8px;font-size:30px;font-weight:900;"><?= h((string)($downloads['android']['hits'] ?? 0)) ?></div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="color:#64748b;font-size:13px;">iOS</div>
    <div style="margin-top:8px;font-size:30px;font-weight:900;"><?= h((string)($downloads['ios']['hits'] ?? 0)) ?></div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="color:#64748b;font-size:13px;">Windows</div>
    <div style="margin-top:8px;font-size:30px;font-weight:900;"><?= h((string)($downloads['windows']['hits'] ?? 0)) ?></div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="color:#64748b;font-size:13px;">macOS</div>
    <div style="margin-top:8px;font-size:30px;font-weight:900;"><?= h((string)($downloads['mac']['hits'] ?? 0)) ?></div>
  </div>
</div>

<div class="info-grid">
  <div class="panel">
    <h3>站点信息</h3>
    <p><strong>站点标题：</strong><?= h($site['title'] ?? '') ?></p>
    <p><strong>站点描述：</strong><?= h($site['description'] ?? '') ?></p>
    <p><strong>后台目录：</strong><?= h($currentSlug) ?></p>
  </div>
  <div class="panel">
    <h3>数据库配置</h3>
    <p><strong>主机：</strong><?= h($db['host'] ?? '') ?>:<?= h($db['port'] ?? '') ?></p>
    <p><strong>数据库名：</strong><?= h($db['database'] ?? '') ?></p>
    <p><strong>字符集：</strong><?= h($db['charset'] ?? 'utf8mb4') ?></p>
  </div>
  <div class="panel">
    <h3>下载统计概览</h3>
    <p><strong>Android：</strong><?= h((string)($downloads['android']['hits'] ?? 0)) ?> 次</p>
    <p><strong>iOS：</strong><?= h((string)($downloads['ios']['hits'] ?? 0)) ?> 次</p>
    <p><strong>Windows：</strong><?= h((string)($downloads['windows']['hits'] ?? 0)) ?> 次</p>
    <p><strong>macOS：</strong><?= h((string)($downloads['mac']['hits'] ?? 0)) ?> 次</p>
    <p><strong>总计：</strong><?= h((string)$totalHits) ?> 次</p>
  </div>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
