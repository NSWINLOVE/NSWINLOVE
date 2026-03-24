<?php
require __DIR__ . '/session_bootstrap.php';

$currentPage = 'about';
$pageTitle = '关于程序';
require __DIR__ . '/layout-top.php';

$configFile = dirname(__DIR__, 2) . '/config/install.php';
$config = file_exists($configFile) ? require $configFile : [];
$site = $config['site'] ?? [];
$downloads = $config['downloads'] ?? [];
$notice = $config['notice'] ?? [];
$baseDir = dirname(__DIR__, 2);
$totalPlatforms = count(array_filter(['android', 'ios', 'windows', 'mac'], function ($key) use ($downloads) {
    return !empty($downloads[$key]['url']) && ($downloads[$key]['url'] ?? '#') !== '#';
}));
$installed = !empty($config['installed']);
$lockExists = file_exists($baseDir . '/storage/install.lock');
$configExists = file_exists($configFile);
$currentSlug = trim(($site['admin_slug'] ?? 'admin'), '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$moduleCount = 8;
$serverHost = php_uname('n') ?: 'unknown';
$phpVersion = PHP_VERSION;
$serverTime = date('Y-m-d H:i:s');
$loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
$loadText = is_array($loadAvg) ? implode(' / ', array_map(static fn($v) => number_format((float)$v, 2), array_slice($loadAvg, 0, 3))) : '不可用';
$diskFree = @disk_free_space($baseDir);
$diskTotal = @disk_total_space($baseDir);
$diskText = ($diskFree !== false && $diskTotal !== false && $diskTotal > 0)
    ? sprintf('%s / %s 可用', format_bytes((float)$diskFree), format_bytes((float)$diskTotal))
    : '不可用';
$serverStateText = ($installed && $lockExists && $configExists) ? '运行正常' : '配置待确认';
$pathRows = [
    ['path' => '/index.php', 'desc' => '前台首页入口，负责站点展示与下载入口渲染'],
    ['path' => '/download.php', 'desc' => '下载统计跳转入口，先计数再跳转'],
    ['path' => '/install/index.php', 'desc' => '安装向导页面，负责初始化数据库与管理员'],
    ['path' => '/config/install.php', 'desc' => '站点核心配置文件'],
    ['path' => '/storage/install.lock', 'desc' => '安装锁文件，用于标记已安装状态'],
    ['path' => '/system/admin-core/', 'desc' => '后台核心逻辑目录'],
    ['path' => '/' . $currentSlug . '/', 'desc' => '当前后台实际访问入口目录'],
];
$updateRows = [
    ['title' => '前台主题切换', 'desc' => '首页已支持白天 / 黑夜切换，并保留本地记忆。'],
    ['title' => '后台响应式优化', 'desc' => '后台导航支持收缩，主要管理页已接入统一响应式布局。'],
    ['title' => '关于程序页面', 'desc' => '新增后台专属介绍页，用于说明程序定位、结构与维护方式。'],
];
$maintainRows = [
    '修改 PHP 文件后先执行 php -l 做语法检查。',
    '调整后台入口目录时，确认入口壳文件已同步生成。',
    '清理 storage/ 前先确认统计与安装锁是否仍需要保留。',
    '涉及配置结构调整时，优先兼容旧字段，避免覆盖历史数据。',
];

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
<style>
.about-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#eff6ff 0%,#ffffff 52%,#e0f2fe 100%)!important;border-color:#cfe4ff!important}.about-hero:after{content:"";position:absolute;right:-50px;top:-50px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(56,189,248,.18),transparent 70%)}.about-hero:before{content:"";position:absolute;left:-80px;bottom:-80px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(37,99,235,.12),transparent 70%)}.about-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#dbeafe;border:1px solid #bfdbfe;color:#1d4ed8;font-size:12px;font-weight:900;margin-bottom:12px}.feature-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:18px}.feature-card{padding:20px;border-radius:22px;background:linear-gradient(180deg,#ffffff,#f8fbff);border:1px solid #dbeafe;box-shadow:0 18px 40px rgba(15,23,42,.05)}.feature-icon{display:inline-flex;align-items:center;justify-content:center;width:46px;height:46px;border-radius:16px;background:linear-gradient(135deg,#2563eb,#38bdf8);color:#fff;font-size:22px;box-shadow:0 12px 24px rgba(37,99,235,.2);margin-bottom:14px}.feature-card strong{display:block;font-size:18px}.feature-card p{margin:8px 0 0;color:#64748b;line-height:1.85}.about-section-title{margin:0 0 14px;font-size:22px}.timeline{display:grid;gap:12px}.timeline-item{display:grid;grid-template-columns:120px 1fr;gap:16px;padding:16px 18px;border-radius:18px;background:#f8fbff;border:1px solid #e5efff}.timeline-item strong{color:#1d4ed8}.timeline-item span{color:#64748b;line-height:1.8}.pill-list{display:flex;flex-wrap:wrap;gap:10px}.pill{display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:13px;font-weight:800}.about-note{padding:18px;border-radius:20px;background:linear-gradient(135deg,#eff6ff,#ffffff);border:1px solid #dbeafe;color:#475569;line-height:1.95}.path-table{display:grid;gap:10px}.path-row{display:grid;grid-template-columns:minmax(220px,.9fr) 1fr;gap:16px;padding:14px 16px;border-radius:18px;background:#f8fbff;border:1px solid #e5efff}.path-row code{font-size:13px;color:#0f172a;background:#eef6ff;border:1px solid #dbeafe;padding:4px 8px;border-radius:10px;display:inline-block}.path-row span{color:#64748b;line-height:1.8}.check-list{display:grid;gap:10px}.check-item{padding:14px 16px;border-radius:16px;background:#f8fbff;border:1px solid #e5efff;color:#475569;line-height:1.8}.update-list{display:grid;gap:12px}.update-item{padding:16px 18px;border-radius:18px;background:linear-gradient(180deg,#ffffff,#f8fbff);border:1px solid #dbeafe}.update-item strong{display:block;font-size:16px}.update-item p{margin:8px 0 0;color:#64748b;line-height:1.8}@media (max-width:980px){.feature-grid{grid-template-columns:1fr}.timeline-item,.path-row{grid-template-columns:1fr}}
</style>

<div class="panel about-hero" style="margin-bottom:18px;">
  <div class="topbar" style="position:relative;z-index:1;margin-bottom:0;">
    <div class="topbar-main">
      <div class="about-badge">程序介绍 · 后台专属页面</div>
      <h1><?= h($site['title'] ?? 'POP 官方下载') ?> 管理系统</h1>
      <p>一套围绕软件下载、版本发布、安装引导与后台维护设计的轻量管理程序。这个页面只在后台显示，用来帮助管理员快速理解系统定位、模块结构、运行状态与维护方式。</p>
    </div>
  </div>
</div>

<div class="stats-grid stats-grid-4">
  <div class="panel" style="padding:18px;">
    <div style="color:#64748b;font-size:13px;">后台模块</div>
    <div style="margin-top:8px;font-size:28px;font-weight:900;"><?= h((string)$moduleCount) ?> 个</div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="color:#64748b;font-size:13px;">下载平台</div>
    <div style="margin-top:8px;font-size:28px;font-weight:900;"><?= h((string)$totalPlatforms) ?> 个</div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="color:#64748b;font-size:13px;">安装状态</div>
    <div style="margin-top:8px;font-size:28px;font-weight:900;<?= ($installed && $lockExists) ? 'color:#16a34a;' : 'color:#dc2626;' ?>"><?= ($installed && $lockExists) ? '已安装' : '未完成' ?></div>
  </div>
  <div class="panel" style="padding:18px;">
    <div style="color:#64748b;font-size:13px;">后台入口</div>
    <div style="margin-top:8px;font-size:28px;font-weight:900;"><?= h($currentSlug) ?></div>
  </div>
</div>

<div class="panel" style="margin-top:18px;">
  <div class="section-head"><h3 class="section-title"><span>✨</span><span>核心亮点</span></h3><span class="section-sub">产品能力</span></div>
  <div class="feature-grid">
    <div class="feature-card">
      <div class="feature-icon">⚡</div>
      <strong>快速部署</strong>
      <p>通过安装向导完成数据库连接、管理员初始化与后台入口生成，适合快速落地独立下载站。</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🧭</div>
      <strong>统一管理</strong>
      <p>把站点设置、下载维护、内容编辑、公告控制、密码修改与统计查看集中到同一套后台里。</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">📈</div>
      <strong>可持续维护</strong>
      <p>采用配置驱动与核心目录分层结构，方便后续迭代页面、扩展模块、保留长期维护能力。</p>
    </div>
  </div>
</div>

<div class="info-grid" style="margin-top:18px;">
  <div class="panel">
    <h3 style="margin-top:0;">产品定位</h3>
    <p style="color:#64748b;line-height:1.9;">
      <strong><?= h($site['title'] ?? 'POP 官方下载') ?></strong> 是一套基于 <strong>PHP 8.2</strong> 的轻量下载站与后台管理系统，
      核心面向软件下载站点的搭建与日常维护。它把安装、发布、下载、公告、内容编辑与统计查看这些常用动作集中在一套统一后台里，
      让部署、维护和后续迭代都更轻。
    </p>
  </div>

  <div class="panel">
    <h3 style="margin-top:0;">运行概览</h3>
    <div class="simple-list">
      <div class="simple-item">服务器主机：<?= h($serverHost) ?></div>
      <div class="simple-item">PHP 版本：<?= h($phpVersion) ?></div>
      <div class="simple-item">当前时间：<?= h($serverTime) ?></div>
      <div class="simple-item">系统负载：<?= h($loadText) ?></div>
      <div class="simple-item">磁盘可用：<?= h($diskText) ?></div>
      <div class="simple-item">服务状态：<?= h($serverStateText) ?></div>
    </div>
  </div>

  <div class="panel">
    <h3 style="margin-top:0;">技术标签</h3>
    <div class="pill-list">
      <span class="pill">PHP 8.2</span>
      <span class="pill">配置驱动</span>
      <span class="pill">后台响应式</span>
      <span class="pill">下载统计</span>
      <span class="pill">安装向导</span>
      <span class="pill">轻量部署</span>
    </div>
  </div>
</div>

<div class="panel" style="margin-top:18px;">
  <div class="section-head"><h3 class="section-title"><span>🏗️</span><span>程序架构</span></h3><span class="section-sub">结构</span></div>
  <div class="field-grid-2">
    <div class="soft-card">
      <strong>前台层</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">负责首页展示、下载入口、版本说明、FAQ、公告与主题切换，直接面向访问者。</p>
    </div>
    <div class="soft-card">
      <strong>后台层</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">负责设置、内容、下载、公告、密码、统计与程序说明，只在管理端显示。</p>
    </div>
    <div class="soft-card">
      <strong>核心目录</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">后台核心逻辑集中在 <code>system/admin-core</code>，入口壳目录负责实际访问路径映射。</p>
    </div>
    <div class="soft-card">
      <strong>配置与状态</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">站点配置使用 <code>config/install.php</code>，安装锁与统计数据保存在 <code>storage/</code>。</p>
    </div>
  </div>
</div>

<div class="panel" style="margin-top:18px;">
  <div class="section-head"><h3 class="section-title"><span>📂</span><span>关键路径说明</span></h3><span class="section-sub">路径</span></div>
  <div class="path-table">
    <?php foreach ($pathRows as $row): ?>
      <div class="path-row">
        <div><code><?= h($row['path']) ?></code></div>
        <span><?= h($row['desc']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel" style="margin-top:18px;">
  <div class="section-head"><h3 class="section-title"><span>🪜</span><span>使用流程</span></h3><span class="section-sub">流程</span></div>
  <div class="timeline">
    <div class="timeline-item"><strong>01 · 安装初始化</strong><span>通过安装页完成数据库连接测试、管理员创建、配置写入与后台入口部署。</span></div>
    <div class="timeline-item"><strong>02 · 配置站点</strong><span>在站点设置里维护标题、描述、Logo、favicon、后台目录与 SEO 关键词。</span></div>
    <div class="timeline-item"><strong>03 · 维护下载</strong><span>在下载管理里维护各平台链接、版本号、更新时间与发布说明。</span></div>
    <div class="timeline-item"><strong>04 · 完善内容</strong><span>在内容管理与公告管理里补充教程、系统要求、FAQ 与前台公告内容。</span></div>
    <div class="timeline-item"><strong>05 · 查看统计</strong><span>通过下载统计页查看平台点击数据、7 天趋势与每日分布。</span></div>
  </div>
</div>

<div class="panel" style="margin-top:18px;">
  <div class="section-head"><h3 class="section-title"><span>📝</span><span>近期版本变更</span></h3><span class="section-sub">更新</span></div>
  <div class="update-list">
    <?php foreach ($updateRows as $row): ?>
      <div class="update-item">
        <strong><?= h($row['title']) ?></strong>
        <p><?= h($row['desc']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel" style="margin-top:18px;">
  <div class="section-head"><h3 class="section-title"><span>🧩</span><span>主要模块</span></h3><span class="section-sub">模块</span></div>
  <div class="field-grid-2">
    <div class="soft-card">
      <strong>站点设置</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">维护站点标题、描述、后台入口、SEO 关键词、Logo 与 favicon。</p>
    </div>
    <div class="soft-card">
      <strong>下载管理</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">统一维护 Android、iOS、Windows、macOS 的下载链接、版本号与更新时间。</p>
    </div>
    <div class="soft-card">
      <strong>内容管理</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">用于维护安装教程、系统要求与 FAQ 内容，前后台文案联动展示。</p>
    </div>
    <div class="soft-card">
      <strong>公告管理</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">控制前台公告条的开关、标题与正文内容。</p>
    </div>
    <div class="soft-card">
      <strong>下载统计</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">查看各平台下载次数、7 天趋势与每日分布，并支持清零统计。</p>
    </div>
    <div class="soft-card">
      <strong>权限与安装</strong>
      <p style="margin:8px 0 0;color:#64748b;line-height:1.8;">通过安装页完成初始化，通过管理员登录、密码修改与 Session 控制保护后台。</p>
    </div>
  </div>
</div>

<div class="panel" style="margin-top:18px;">
  <div class="section-head"><h3 class="section-title"><span>🛠️</span><span>维护者须知</span></h3><span class="section-sub">维护</span></div>
  <div class="check-list">
    <?php foreach ($maintainRows as $row): ?>
      <div class="check-item"><?= h($row) ?></div>
    <?php endforeach; ?>
    <div class="check-item">当前页面仅在后台显示，不参与前台导航，也不直接暴露给访客。</div>
  </div>
</div>

<div class="about-note" style="margin-top:18px;">
  这不是前台展示页，而是后台专用的程序介绍页。它的价值不在于给访客看，而在于帮助管理员、维护者、后续接手的人快速理解：这套系统是什么、能做什么、怎么维护、适合继续往哪个方向扩展。
</div>
<?php require __DIR__ . '/layout-bottom.php';
