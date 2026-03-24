<?php
require __DIR__ . '/session_bootstrap.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/logger.php';
require __DIR__ . '/config_helper.php';

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
$config = load_install_config($configFile);
$site = $config['site'] ?? [];
$downloads = $config['downloads'] ?? [];
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'stats';
$message = '';
$statsFile = $baseDir . '/storage/download-stats.json';
$dailyStats = file_exists($statsFile) ? json_decode((string)file_get_contents($statsFile), true) : [];
if (!is_array($dailyStats)) {
    $dailyStats = [];
}

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_stats') {
    csrf_verify_or_die();
    $saved = save_install_config($configFile, function (array $current) {
        foreach (['android', 'ios', 'windows', 'mac'] as $key) {
            if (isset($current['downloads'][$key])) {
                $current['downloads'][$key]['hits'] = 0;
            }
        }
        return $current;
    });
    if ($saved) {
        if (file_exists($statsFile)) {
            $backupDir = $baseDir . '/.trash';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0777, true);
            }
            @rename($statsFile, $backupDir . '/download-stats-' . date('Ymd-His') . '.json');
            $dailyStats = [];
        }
        $config = load_install_config($configFile);
        $downloads = $config['downloads'] ?? [];
        admin_log('reset_stats', ['platforms' => ['android', 'ios', 'windows', 'mac']]);
        $message = '统计数据已清零。';
    } else {
        $message = '统计数据清零失败。';
    }
}

$totalHits = 0;
foreach ($downloads as $item) {
    $totalHits += (int)($item['hits'] ?? 0);
}
$recentDays = array_slice(array_reverse(array_keys($dailyStats)), 0, 7);
$chartMax = 0;
foreach ($recentDays as $day) {
    $chartMax = max($chartMax, (int)($dailyStats[$day]['total'] ?? 0));
}
$chartMax = max($chartMax, 1);

require __DIR__ . '/layout-top.php';
?>
<div class="topbar">
  <div class="topbar-main">
    <h1>下载统计详情</h1>
    <p>查看各平台下载点击数据与版本信息。</p>
  </div>
  <div class="top-actions">
    <form method="post" onsubmit="return confirm('确认清零全部下载统计吗？');">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="reset_stats">
      <button class="btn secondary" type="submit">清零统计</button>
    </form>
  </div>
</div>

<div class="panel">
  <?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
  <div class="stats-grid stats-grid-4">
    <div class="soft-card">
      <div class="metric-head">
        <strong class="metric-label"><span>📦</span><span>总下载量</span></strong>
        <span class="metric-badge blue">总览</span>
      </div>
      <div style="margin-top:8px;font-size:28px;font-weight:900;"><?= h((string)$totalHits) ?></div>
    </div>
    <div class="soft-card">
      <div class="metric-head">
        <strong class="metric-label"><span>🤖</span><span>Android</span></strong>
        <span class="metric-badge green">Android</span>
      </div>
      <div style="margin-top:8px;font-size:28px;font-weight:900;"><?= h((string)($downloads['android']['hits'] ?? 0)) ?></div>
    </div>
    <div class="soft-card">
      <div class="metric-head">
        <strong class="metric-label"><span>🍎</span><span>iOS</span></strong>
        <span class="metric-badge gray">iOS</span>
      </div>
      <div style="margin-top:8px;font-size:28px;font-weight:900;"><?= h((string)($downloads['ios']['hits'] ?? 0)) ?></div>
    </div>
    <div class="soft-card">
      <div class="metric-head">
        <strong class="metric-label"><span>🖥️</span><span>桌面端</span></strong>
        <span class="metric-badge blue">桌面端</span>
      </div>
      <div style="margin-top:8px;font-size:28px;font-weight:900;"><?= h((string)((int)($downloads['windows']['hits'] ?? 0) + (int)($downloads['mac']['hits'] ?? 0))) ?></div>
    </div>
  </div>

  <div class="section-head"><h3 class="section-title"><span>📈</span><span>最近 7 天趋势</span></h3><span class="section-sub">近 7 天</span></div>
  <?php if (!$recentDays): ?>
    <div class="empty-state" style="margin-bottom:18px;">还没有按日期记录的下载数据。</div>
  <?php else: ?>
    <div class="table-wrap">
      <div class="chart-grid">
        <?php foreach (array_reverse($recentDays) as $day): $value = (int)($dailyStats[$day]['total'] ?? 0); $height = max(18, (int)round(($value / $chartMax) * 150)); ?>
          <div style="display:flex;flex-direction:column;align-items:center;justify-content:end;gap:8px;height:100%;">
            <div style="font-size:12px;color:#334155;font-weight:800;"><?= h((string)$value) ?></div>
            <div style="width:100%;max-width:54px;height:<?= h((string)$height) ?>px;border-radius:14px 14px 8px 8px;background:linear-gradient(180deg,#38bdf8,#2563eb);box-shadow:0 10px 24px rgba(37,99,235,.18);"></div>
            <div style="font-size:12px;color:#64748b;"><?= h(substr($day, 5)) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="section-head"><h3 class="section-title"><span>🧾</span><span>平台统计明细</span></h3><span class="section-sub">明细</span></div>
  <div class="table-wrap">
    <table class="table-ui">
      <thead>
        <tr>
          <th>平台</th>
          <th>版本号</th>
          <th>更新时间</th>
          <th>下载次数</th>
          <th>下载地址</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (['android' => 'Android', 'ios' => 'iOS', 'windows' => 'Windows', 'mac' => 'macOS'] as $key => $label): ?>
        <tr>
          <td><?= h($label) ?></td>
          <td><?= h($downloads[$key]['version'] ?? 'v1.0.0') ?></td>
          <td><?= h($downloads[$key]['updated_at'] ?? '') ?></td>
          <td style="font-weight:800;"><?= h((string)($downloads[$key]['hits'] ?? 0)) ?></td>
          <td style="word-break:break-all;"><?= h($downloads[$key]['url'] ?? '#') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="section-head"><h3 class="section-title"><span>🗓️</span><span>最近 7 天下载记录</span></h3><span class="section-sub">记录</span></div>
  <?php if (!$recentDays): ?>
    <div class="empty-state">还没有按日期记录的下载数据。</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table-ui">
        <thead>
          <tr>
            <th>日期</th>
            <th>总计</th>
            <th>Android</th>
            <th>iOS</th>
            <th>Windows</th>
            <th>macOS</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentDays as $day): $row = $dailyStats[$day] ?? []; ?>
          <tr>
            <td><?= h($day) ?></td>
            <td style="font-weight:800;"><?= h((string)($row['total'] ?? 0)) ?></td>
            <td><?= h((string)($row['android'] ?? 0)) ?></td>
            <td><?= h((string)($row['ios'] ?? 0)) ?></td>
            <td><?= h((string)($row['windows'] ?? 0)) ?></td>
            <td><?= h((string)($row['mac'] ?? 0)) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
