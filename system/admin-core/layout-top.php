<?php
require __DIR__ . '/session_bootstrap.php';

$configFile = dirname(__DIR__, 2) . '/config/install.php';
$config = file_exists($configFile) ? require $configFile : [];
$site = $config['site'] ?? [];
$currentSlug = trim(($site['admin_slug'] ?? 'admin'), '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = $currentPage ?? 'dashboard';
$siteLogo = $site['logo'] ?? '';
$pageTitleMap = [
    'dashboard' => '仪表盘',
    'stats' => '下载统计',
    'settings' => '站点设置',
    'downloads' => '下载管理',
    'content' => '内容管理',
    'notice' => '公告管理',
    'password' => '修改密码',
];
$pageTitle = $pageTitle ?? ($pageTitleMap[$currentPage] ?? '后台管理');
$browserTitle = $pageTitle . ' - ' . (($site['title'] ?? 'POP') ?: 'POP') . ' 后台';

function nav_active(string $page, string $currentPage): string {
    return $page === $currentPage ? 'nav-item active' : 'nav-item';
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($browserTitle, ENT_QUOTES, 'UTF-8') ?></title>
<style>
:root{--line:#dbeafe;--text:#0f172a;--muted:#64748b;--accent:#2563eb;--accent2:#38bdf8;--bg:#f6f9ff;--side:#0f172a;--sideText:#e5eefc;--sideMuted:#94a3b8;--mainGap:22px}
*{box-sizing:border-box}
body{margin:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:linear-gradient(180deg,#f3f8ff,#eaf2ff);color:var(--text)}
.layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh;transition:grid-template-columns .25s ease}
.sidebar{background:linear-gradient(180deg,#0f172a,#111827);color:var(--sideText);padding:24px 18px;display:flex;flex-direction:column;gap:18px;position:sticky;top:0;height:100vh;overflow:hidden;transition:width .25s ease,padding .25s ease,transform .25s ease}
.brand-wrap{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.brand{display:flex;align-items:center;gap:12px;font-size:22px;font-weight:900;min-width:0}
.brand-badge{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:14px;background:linear-gradient(135deg,var(--accent),var(--accent2));box-shadow:0 12px 30px rgba(37,99,235,.35);overflow:hidden;flex:0 0 auto}
.brand-badge img{width:100%;height:100%;object-fit:cover}
.brand-text,.side-desc,.nav-label,.side-footer{transition:opacity .2s ease,max-width .2s ease,margin .2s ease}
.brand-text{white-space:nowrap}
.side-desc{color:var(--sideMuted);font-size:13px;line-height:1.7}
.sidebar-toggle{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:12px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.06);color:#fff;cursor:pointer;flex:0 0 auto;transition:.2s ease}
.sidebar-toggle:hover{background:rgba(255,255,255,.12);transform:translateY(-1px)}
.nav{display:grid;gap:10px}
.nav-item{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:14px;color:var(--sideText);text-decoration:none;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.04);transition:.2s ease;min-height:48px;white-space:nowrap;overflow:hidden;position:relative}.nav-item:hover{background:rgba(255,255,255,.08);transform:translateX(2px)}.nav-item.active{background:linear-gradient(135deg,rgba(37,99,235,.42),rgba(56,189,248,.26));border-color:rgba(96,165,250,.42);box-shadow:inset 0 0 0 1px rgba(147,197,253,.18),0 10px 24px rgba(37,99,235,.16)}.nav-item[data-tip]::after{content:attr(data-tip);position:absolute;left:calc(100% + 10px);top:50%;transform:translateY(-50%) translateX(-4px);padding:8px 10px;border-radius:10px;background:rgba(15,23,42,.96);color:#fff;font-size:12px;line-height:1;white-space:nowrap;opacity:0;pointer-events:none;box-shadow:0 12px 24px rgba(0,0,0,.22);transition:.18s ease;z-index:20}.nav-item[data-tip]::before{content:"";position:absolute;left:calc(100% + 4px);top:50%;transform:translateY(-50%) translateX(-4px);border:6px solid transparent;border-right-color:rgba(15,23,42,.96);opacity:0;pointer-events:none;transition:.18s ease;z-index:20}.nav-icon{display:inline-flex;align-items:center;justify-content:center;width:20px;flex:0 0 20px}
.side-footer{margin-top:auto;color:var(--sideMuted);font-size:12px;line-height:1.7}
.main{padding:28px 22px;min-width:0}.topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:20px}.topbar-main{min-width:0;flex:1 1 320px}.topbar h1{margin:0;font-size:30px;line-height:1.2}.topbar p{margin:6px 0 0;color:var(--muted);line-height:1.7}.top-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;flex:0 1 auto}.btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:14px;text-decoration:none;font-weight:800}.btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.btn.secondary{background:#eef6ff;color:#1e3a8a;border:1px solid #bfdbfe}.panel{background:rgba(255,255,255,.96);border:1px solid var(--line);border-radius:24px;padding:22px;box-shadow:0 20px 50px rgba(15,23,42,.08)}.stats-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px;margin-bottom:18px}.stats-grid.stats-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}.info-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.split-grid{display:grid;grid-template-columns:minmax(0,1fr) 220px;gap:18px;align-items:start}.field-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.field-grid-3{display:grid;grid-template-columns:2fr .8fr 1fr;gap:12px}.alert-success,.alert-error{padding:14px 16px;border-radius:16px;margin-bottom:14px}.alert-success{background:#effdf5;border:1px solid #bbf7d0;color:#166534}.alert-error{background:#fff1f2;border:1px solid #fecdd3;color:#be123c}.soft-card{padding:16px;border-radius:18px;background:#f8fbff;border:1px solid #e5efff}.empty-state{padding:18px;border-radius:16px;background:#f8fbff;border:1px solid #e5efff;color:#64748b}.table-wrap{overflow:auto;margin-bottom:18px}.table-ui{width:100%;border-collapse:collapse;background:#fff;border-radius:18px;overflow:hidden}.table-ui thead tr{background:#eff6ff}.table-ui th,.table-ui td{text-align:left;padding:14px;border-bottom:1px solid #eef2ff}.table-ui thead th{border-bottom:1px solid #dbeafe}.chart-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:12px;align-items:end;height:220px;padding:18px;border-radius:20px;background:#f8fbff;border:1px solid #e5efff;margin-bottom:18px}
body.sidebar-collapsed .layout{grid-template-columns:88px 1fr}
body.sidebar-collapsed .sidebar{padding-left:12px;padding-right:12px}
body.sidebar-collapsed .brand-wrap{flex-direction:column;align-items:center}
body.sidebar-collapsed .brand{justify-content:center;width:100%}
body.sidebar-collapsed .brand-text,
body.sidebar-collapsed .side-desc,
body.sidebar-collapsed .nav-label,
body.sidebar-collapsed .side-footer{opacity:0;max-width:0;overflow:hidden;pointer-events:none;margin:0}
body.sidebar-collapsed .nav-item{justify-content:center;padding-left:12px;padding-right:12px}
body.sidebar-collapsed .nav-item[data-tip]:hover::after,
body.sidebar-collapsed .nav-item[data-tip]:hover::before{opacity:1;transform:translateY(-50%) translateX(0)}
body.sidebar-collapsed .nav-item.active{box-shadow:inset 0 0 0 1px rgba(147,197,253,.28),0 0 0 1px rgba(59,130,246,.2),0 14px 28px rgba(37,99,235,.18)}
body.sidebar-collapsed .sidebar-toggle{width:36px;height:36px}
.mobile-toggle{display:none;align-items:center;justify-content:center;width:42px;height:42px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;cursor:pointer;box-shadow:0 12px 30px rgba(37,99,235,.22)}
.mobile-backdrop{display:none}
@media (max-width:980px){
  .layout{grid-template-columns:1fr}
  .sidebar{position:fixed;left:0;top:0;bottom:0;width:min(82vw,300px);height:100vh;z-index:40;transform:translateX(-100%);box-shadow:0 24px 60px rgba(15,23,42,.24)}
  .main{padding:12px 14px 18px}
  .topbar{gap:14px}
  .topbar h1{font-size:24px}
  .topbar-main,.top-actions{flex:1 1 100%}
  .top-actions{justify-content:flex-start}
  .top-actions .btn{width:100%}
  .stats-grid,.info-grid,.split-grid,.field-grid-2,.field-grid-3{grid-template-columns:1fr}
  .chart-grid{grid-template-columns:repeat(7,minmax(72px,1fr));min-width:640px;height:200px}
  .panel{padding:18px;border-radius:20px}
  .main{padding-top:12px}
  .mobile-toggle{display:inline-flex}
  body.sidebar-open .sidebar{transform:translateX(0)}
  .mobile-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.35);z-index:30}
  body.sidebar-open .mobile-backdrop{display:block}
  body.sidebar-collapsed .layout{grid-template-columns:1fr}
  body.sidebar-collapsed .sidebar{padding-left:18px;padding-right:18px}
  body.sidebar-collapsed .brand-wrap{flex-direction:row;align-items:flex-start}
  body.sidebar-collapsed .brand{justify-content:flex-start}
  body.sidebar-collapsed .brand-text,
  body.sidebar-collapsed .side-desc,
  body.sidebar-collapsed .nav-label,
  body.sidebar-collapsed .side-footer{opacity:1;max-width:none;overflow:visible;pointer-events:auto}
  body.sidebar-collapsed .nav-item{justify-content:flex-start;padding-left:14px;padding-right:14px}
}
</style>
</head>
<body>
<div class="mobile-backdrop" id="mobileBackdrop"></div>
<div class="layout">
  <aside class="sidebar" id="adminSidebar">
    <div>
      <div class="brand-wrap">
        <div class="brand"><span class="brand-badge"><?php if ($siteLogo): ?><img src="<?= htmlspecialchars($siteLogo, ENT_QUOTES, 'UTF-8') ?>" alt="logo"><?php else: ?>P<?php endif; ?></span><span class="brand-text">POP 后台</span></div>
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="收缩导航">☰</button>
      </div>
      <div class="side-desc">把站点设置、下载内容、公告与前台说明统一收进这里。</div>
    </div>
    <nav class="nav">
      <a class="<?= nav_active('dashboard', $currentPage) ?>" data-tip="仪表盘" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/dashboard.php"><span class="nav-icon">🏠</span><span class="nav-label">仪表盘</span></a>
      <a class="<?= nav_active('stats', $currentPage) ?>" data-tip="下载统计" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/stats.php"><span class="nav-icon">📈</span><span class="nav-label">下载统计</span></a>
      <a class="<?= nav_active('settings', $currentPage) ?>" data-tip="站点设置" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/settings.php"><span class="nav-icon">⚙️</span><span class="nav-label">站点设置</span></a>
      <a class="<?= nav_active('downloads', $currentPage) ?>" data-tip="下载管理" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/downloads.php"><span class="nav-icon">⬇️</span><span class="nav-label">下载管理</span></a>
      <a class="<?= nav_active('content', $currentPage) ?>" data-tip="内容管理" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/content.php"><span class="nav-icon">📝</span><span class="nav-label">内容管理</span></a>
      <a class="<?= nav_active('notice', $currentPage) ?>" data-tip="公告管理" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/notice.php"><span class="nav-icon">📢</span><span class="nav-label">公告管理</span></a>
      <a class="<?= nav_active('password', $currentPage) ?>" data-tip="修改密码" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/password.php"><span class="nav-icon">🔐</span><span class="nav-label">修改密码</span></a>
    </nav>
    <div class="side-footer">
      当前后台入口：/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/
    </div>
  </aside>
  <main class="main">
    <button class="mobile-toggle" id="mobileSidebarToggle" type="button" aria-label="打开导航">☰</button>
<script>
(function(){
  const body = document.body;
  const desktopToggle = document.getElementById('sidebarToggle');
  const mobileToggle = document.getElementById('mobileSidebarToggle');
  const backdrop = document.getElementById('mobileBackdrop');
  const key = 'admin-sidebar-collapsed';
  const mq = window.matchMedia('(max-width: 980px)');

  function syncMode(){
    if (mq.matches) {
      body.classList.remove('sidebar-collapsed');
      desktopToggle && desktopToggle.setAttribute('aria-label', '展开导航');
      return;
    }
    const collapsed = localStorage.getItem(key) === '1';
    body.classList.toggle('sidebar-collapsed', collapsed);
    desktopToggle && desktopToggle.setAttribute('aria-label', collapsed ? '展开导航' : '收缩导航');
  }

  desktopToggle && desktopToggle.addEventListener('click', function(){
    if (mq.matches) {
      body.classList.toggle('sidebar-open');
      return;
    }
    const collapsed = !body.classList.contains('sidebar-collapsed');
    body.classList.toggle('sidebar-collapsed', collapsed);
    localStorage.setItem(key, collapsed ? '1' : '0');
    desktopToggle.setAttribute('aria-label', collapsed ? '展开导航' : '收缩导航');
  });

  mobileToggle && mobileToggle.addEventListener('click', function(){
    body.classList.add('sidebar-open');
  });

  backdrop && backdrop.addEventListener('click', function(){
    body.classList.remove('sidebar-open');
  });

  window.addEventListener('resize', function(){
    if (!mq.matches) {
      body.classList.remove('sidebar-open');
    }
    syncMode();
  });

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
      body.classList.remove('sidebar-open');
    }
  });

  syncMode();
})();
</script>
