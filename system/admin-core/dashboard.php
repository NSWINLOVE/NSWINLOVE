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
$serverStateText = file_exists($baseDir . '/storage/install.lock') ? '运行正常' : '待确认';
$serverStateColor = file_exists($baseDir . '/storage/install.lock') ? '#16a34a' : '#dc2626';
$dbStateText = (!empty($db['host']) && !empty($db['database'])) ? '配置已填写' : '配置待确认';
$dbStateColor = (!empty($db['host']) && !empty($db['database'])) ? '#16a34a' : '#dc2626';
require __DIR__ . '/layout-top.php';

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
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
      <div style="display:flex;align-items:center;gap:8px;color:#64748b;font-size:13px;"><span>📦</span><span>总下载量</span></div>
      <span style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:rgba(37,99,235,.10);color:#2563eb;font-size:12px;font-weight:900;">总览</span>
    </div>
    <div style="margin-top:8px;font-size:32px;font-weight:900;color:#0f172a;"><?= h((string)$totalHits) ?></div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
      <div style="display:flex;align-items:center;gap:8px;color:#64748b;font-size:13px;"><span>🤖</span><span>Android</span></div>
      <span style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:rgba(34,197,94,.10);color:#16a34a;font-size:12px;font-weight:900;">平台</span>
    </div>
    <div style="margin-top:8px;font-size:30px;font-weight:900;"><?= h((string)($downloads['android']['hits'] ?? 0)) ?></div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
      <div style="display:flex;align-items:center;gap:8px;color:#64748b;font-size:13px;"><span>🍎</span><span>iOS</span></div>
      <span style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:rgba(34,197,94,.10);color:#16a34a;font-size:12px;font-weight:900;">平台</span>
    </div>
    <div style="margin-top:8px;font-size:30px;font-weight:900;"><?= h((string)($downloads['ios']['hits'] ?? 0)) ?></div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
      <div style="display:flex;align-items:center;gap:8px;color:#64748b;font-size:13px;"><span>🪟</span><span>Windows</span></div>
      <span style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:rgba(34,197,94,.10);color:#16a34a;font-size:12px;font-weight:900;">平台</span>
    </div>
    <div style="margin-top:8px;font-size:30px;font-weight:900;"><?= h((string)($downloads['windows']['hits'] ?? 0)) ?></div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
      <div style="display:flex;align-items:center;gap:8px;color:#64748b;font-size:13px;"><span>💻</span><span>macOS</span></div>
      <span style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:rgba(34,197,94,.10);color:#16a34a;font-size:12px;font-weight:900;">平台</span>
    </div>
    <div style="margin-top:8px;font-size:30px;font-weight:900;"><?= h((string)($downloads['mac']['hits'] ?? 0)) ?></div>
  </div>
</div>

<div class="info-grid">
  <div class="panel">
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
  <div class="panel">
    <h3 class="state-head" style="margin-top:0;margin-bottom:14px;">
      <span class="state-title"><span>🗄️</span><span>数据库状态</span></span>
      <span class="state-badge green">已配置</span>
    </h3>
    <div class="simple-list">
      <div class="simple-item"><strong>配置状态：</strong><span style="font-weight:900;color:<?= h($dbStateColor) ?>;"><?= h($dbStateText) ?></span></div>
      <div class="simple-item"><strong>主机：</strong><?= h($db['host'] ?? '') ?>:<?= h($db['port'] ?? '') ?></div>
      <div class="simple-item"><strong>数据库名：</strong><?= h($db['database'] ?? '') ?></div>
      <div class="simple-item"><strong>字符集：</strong><?= h($db['charset'] ?? 'utf8mb4') ?></div>
    </div>
  </div>
  <div class="panel">
    <h3 class="state-head" style="margin-top:0;margin-bottom:14px;">
      <span class="state-title"><span>📊</span><span>下载状态</span></span>
      <span class="state-badge blue">实时统计</span>
    </h3>
    <div class="simple-list">
      <div class="simple-item"><strong>Android：</strong><span style="font-weight:900;"><?= h((string)($downloads['android']['hits'] ?? 0)) ?></span> 次</div>
      <div class="simple-item"><strong>iOS：</strong><span style="font-weight:900;"><?= h((string)($downloads['ios']['hits'] ?? 0)) ?></span> 次</div>
      <div class="simple-item"><strong>Windows：</strong><span style="font-weight:900;"><?= h((string)($downloads['windows']['hits'] ?? 0)) ?></span> 次</div>
      <div class="simple-item"><strong>macOS：</strong><span style="font-weight:900;"><?= h((string)($downloads['mac']['hits'] ?? 0)) ?></span> 次</div>
      <div class="simple-item"><strong>总计：</strong><span style="font-weight:900;color:#2563eb;"><?= h((string)$totalHits) ?></span> 次</div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
