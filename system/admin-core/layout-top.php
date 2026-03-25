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
    'downloads' => '下载中心',
    'content' => '页面内容',
    'notice' => '公告管理',
    'password' => '修改密码',
    'about' => '关于程序',
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
:root{--line:#e5e7eb;--text:#111827;--muted:#6b7280;--accent:#4f86f7;--accent2:#76a7fa;--bg:#f2f4f7;--side:#f6f7f9;--sideText:#111827;--sideMuted:#6b7280;--mainGap:22px;--sidebar-width:236px;--sidebar-collapsed-width:70px;--topbar-height:60px}
*{box-sizing:border-box}
body{margin:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:var(--bg);color:var(--text);transition:background .25s ease,color .25s ease}
a{color:inherit}
.layout{display:grid;grid-template-columns:var(--sidebar-width) 1fr;min-height:100vh;transition:grid-template-columns .25s ease}
.sidebar{background:var(--side);color:var(--sideText);padding:0;display:flex;flex-direction:column;gap:0;position:sticky;top:0;height:100vh;overflow-x:visible;overflow-y:auto;transition:width .25s ease,padding .25s ease,transform .25s ease,border-color .25s ease,background .25s ease;border-right:1px solid rgba(15,23,42,.06);box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none}
.sidebar-head{position:sticky;top:0;z-index:26;display:flex;align-items:center;min-height:64px;padding:12px 14px;background:var(--side);border:none;border-bottom:1px solid rgba(15,23,42,.05);border-radius:0;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none}
.brand-wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative;width:100%}
.brand{display:flex;align-items:center;gap:10px;font-size:16px;font-weight:700;min-width:0}
.brand-badge{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:#fff;box-shadow:none;overflow:hidden;flex:0 0 auto;border:1px solid rgba(15,23,42,.05)}
.brand-badge img{width:100%;height:100%;object-fit:cover}
.brand-text,.side-desc,.nav-label,.sidebar-tool-label{transition:opacity .2s ease,max-width .2s ease,margin .2s ease}
.brand-text{white-space:nowrap;letter-spacing:.1px}
.side-desc{color:var(--sideMuted);font-size:13px;line-height:1.7}
.nav{display:grid;gap:1px;padding:8px 7px 0}
.nav-item{display:flex;align-items:center;gap:11px;padding:8px 11px;border-radius:8px;color:#4b5563;text-decoration:none;background:transparent;border:none;transition:background .18s ease,color .18s ease;min-height:38px;white-space:nowrap;overflow:hidden;position:relative;font-weight:600;font-size:12px;letter-spacing:0}.nav-item:hover{background:rgba(15,23,42,.035);color:#111827}.nav-item.active{background:#ffffff;color:#111827;box-shadow:inset 0 0 0 1px rgba(15,23,42,.045)}.nav-item[data-tip]::after{content:attr(data-tip);position:absolute;left:calc(100% + 10px);top:50%;transform:translateY(-50%) translateX(-4px);padding:8px 10px;border-radius:10px;background:rgba(17,24,39,.96);color:#fff;font-size:12px;line-height:1;white-space:nowrap;opacity:0;pointer-events:none;box-shadow:0 12px 24px rgba(0,0,0,.18);transition:.18s ease;z-index:20}.nav-item[data-tip]::before{content:"";position:absolute;left:calc(100% + 4px);top:50%;transform:translateY(-50%) translateX(-4px);border:6px solid transparent;border-right-color:rgba(17,24,39,.96);opacity:0;pointer-events:none;transition:.18s ease;z-index:20}.nav-icon{display:inline-flex;align-items:center;justify-content:center;width:16px;flex:0 0 16px;font-size:14px;opacity:.8}
.sidebar-tools{margin-top:auto;display:grid;gap:4px;padding:10px 7px;border-top:1px solid rgba(15,23,42,.05)}
.sidebar-tool-group{display:grid;gap:0;padding:0;background:transparent;border:none;box-shadow:none}
.sidebar-tool{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;min-height:30px;padding:0 4px;border:none;border-radius:7px;background:transparent;color:#6b7280;cursor:pointer;font:inherit;font-weight:600;font-size:11px;letter-spacing:0;transition:background .18s ease,color .18s ease,opacity .18s ease}
.sidebar-tool:hover{background:rgba(15,23,42,.026);color:#111827}
.sidebar-tool:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(59,130,246,.09)}
.sidebar-tool-main{display:inline-flex;align-items:center;gap:8px;min-width:0}
.sidebar-tool-icon{display:inline-flex;align-items:center;justify-content:center;width:15px;flex:0 0 15px;font-size:13px;opacity:.82}
.sidebar-tool-arrow{display:inline-flex;align-items:center;justify-content:center;font-size:10px;color:#9ca3af;opacity:.7}
.side-footer{color:var(--sideMuted);font-size:10px;line-height:1.5;padding:2px 6px 0;opacity:.72}
.main{padding:0 24px 28px;min-width:0;background:#fff;transition:background .25s ease,color .25s ease}.topbar{position:sticky;top:0;z-index:25;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 -24px 20px;padding:16px 24px 14px;min-height:72px;background:#fff;border:none;border-bottom:1px solid rgba(15,23,42,.06);border-radius:0;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none}.topbar-main{min-width:0;flex:1 1 320px}.topbar h1{margin:0;font-size:24px;line-height:1.15;font-weight:700;letter-spacing:-.22px}.topbar p{margin:4px 0 0;color:var(--muted);line-height:1.55;font-size:12px}.top-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;flex:0 1 auto;align-items:center}.btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:12px;text-decoration:none;font-weight:700;transition:background .2s ease,border-color .2s ease,color .2s ease,box-shadow .2s ease,transform .2s ease}.btn:hover{transform:none}.btn.primary{background:#111827;color:#fff;box-shadow:none}.btn.secondary{background:#fff;color:#374151;border:1px solid rgba(15,23,42,.08);box-shadow:none}.panel{background:#fff;border:1px solid rgba(15,23,42,.06);border-radius:18px;padding:22px;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none;transition:background .25s ease,border-color .25s ease,box-shadow .25s ease}.stats-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px;margin-bottom:18px}.stats-grid.stats-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}.info-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.split-grid{display:grid;grid-template-columns:minmax(0,1fr) 220px;gap:18px;align-items:start}.field-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.field-grid-3{display:grid;grid-template-columns:2fr .8fr 1fr;gap:12px}.alert-success,.alert-error{padding:14px 16px;border-radius:16px;margin-bottom:14px}.alert-success{background:#effdf5;border:1px solid #bbf7d0;color:#166534}.alert-error{background:#fff1f2;border:1px solid #fecdd3;color:#be123c}.soft-card{padding:16px;border-radius:18px;background:rgba(250,250,250,.9);border:1px solid rgba(15,23,42,.05)}.empty-state{padding:18px;border-radius:16px;background:rgba(250,250,250,.9);border:1px solid rgba(15,23,42,.05);color:#64748b}.table-wrap{overflow:auto;margin-bottom:18px}.table-ui{width:100%;border-collapse:collapse;background:#fff;border-radius:18px;overflow:hidden}.table-ui thead tr{background:#f8fafc}.table-ui th,.table-ui td{text-align:left;padding:14px;border-bottom:1px solid #eef2f7}.table-ui thead th{border-bottom:1px solid #e5e7eb}.chart-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:12px;align-items:end;height:220px;padding:18px;border-radius:20px;background:rgba(250,250,250,.9);border:1px solid rgba(15,23,42,.05);margin-bottom:18px}.metric-head{display:flex;align-items:center;justify-content:space-between;gap:10px}.metric-label{display:flex;align-items:center;gap:8px;color:#64748b;font-size:13px}.metric-badge{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:900}.metric-badge.blue{background:rgba(79,134,247,.08);color:#4f86f7}.metric-badge.green{background:rgba(34,197,94,.10);color:#16a34a}.metric-badge.gray{background:rgba(148,163,184,.12);color:#475569}.metric-badge.dark{background:rgba(71,85,105,.12);color:#334155}.state-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}.state-title{display:flex;align-items:center;gap:8px}.state-badge{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:900}.state-badge.green{background:rgba(22,163,74,.10);color:#16a34a}.state-badge.blue{background:rgba(79,134,247,.08);color:#4f86f7}.section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 12px}.section-title{display:flex;align-items:center;gap:8px;font-size:20px;font-weight:800;margin:0}.section-sub{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#f3f4f6;border:1px solid #e5e7eb;color:#4b5563;font-size:12px;font-weight:800}.field-label{display:block;margin-bottom:8px;font-weight:700;color:#0f172a}.input-ui,.textarea-ui,.file-ui{width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid rgba(15,23,42,.10);background:rgba(255,255,255,.96);color:#0f172a;transition:border-color .2s ease,box-shadow .2s ease,background .2s ease,color .2s ease}.textarea-ui{min-height:120px;resize:vertical}.file-ui{padding:10px 12px;background:#fafafa}.input-ui:focus,.textarea-ui:focus,.file-ui:focus{outline:none;border-color:#b8cdfc;box-shadow:0 0 0 3px rgba(79,134,247,.10)}.field-help{margin-top:8px;color:#6b7280;font-size:13px;line-height:1.7}
body.sidebar-collapsed .layout{grid-template-columns:var(--sidebar-collapsed-width) 1fr}
body.sidebar-collapsed .sidebar{padding:0;align-items:center;overflow:visible}
body.sidebar-collapsed .sidebar-head{padding:14px 0;min-height:72px;width:100%;justify-content:center}
body.sidebar-collapsed .brand-wrap{display:grid;grid-template-columns:1fr;justify-items:center;align-items:center;gap:10px;width:100%}
body.sidebar-collapsed .brand{display:flex;justify-content:center;align-items:center;width:100%}
body.sidebar-collapsed .brand-badge{width:36px;height:36px;border-radius:10px}
body.sidebar-collapsed .brand-text,
body.sidebar-collapsed .side-desc,
body.sidebar-collapsed .sidebar-tool-label{display:none}
body.sidebar-collapsed .nav{width:100%;justify-items:center}
body.sidebar-collapsed .nav-item{display:grid;place-items:center;width:52px;min-width:52px;height:52px;min-height:52px;padding:0;border-radius:16px;justify-content:center;align-items:center;overflow:visible}
body.sidebar-collapsed .nav-icon{display:flex;align-items:center;justify-content:center;width:22px;height:22px;flex:none;margin:0;font-size:18px;line-height:1}
body.sidebar-collapsed .nav-label{display:none}
body.sidebar-collapsed .nav-item:hover{transform:none}
body.sidebar-collapsed .nav-item[data-tip]::after{left:calc(100% + 12px)}
body.sidebar-collapsed .nav-item[data-tip]::before{left:calc(100% + 1px)}
body.sidebar-collapsed .nav-item[data-tip]:hover::after,
body.sidebar-collapsed .nav-item[data-tip]:hover::before{opacity:1;transform:translateY(-50%) translateX(0)}
body.sidebar-collapsed .nav-item.active{box-shadow:inset 0 0 0 1px rgba(147,197,253,.24),0 0 0 1px rgba(59,130,246,.18),0 12px 24px rgba(37,99,235,.16)}
body.sidebar-collapsed .sidebar-tools{width:100%;align-items:center;padding-top:6px}
body.sidebar-collapsed .sidebar-tool-group{padding:0;width:100%;border-top:none}
body.sidebar-collapsed .sidebar-tool{justify-content:center;padding:0;width:34px;min-height:34px;border-radius:10px;margin:0 auto}
body.sidebar-collapsed .sidebar-tool-main{justify-content:center;gap:0}
body.sidebar-collapsed .sidebar-tool-icon{width:16px;flex:none}
body.sidebar-collapsed .sidebar-tool-arrow{display:none}
body.sidebar-collapsed .sidebar-tool + .sidebar-tool{margin-top:4px}
.mobile-toggle{display:none;align-items:center;justify-content:center;width:42px;height:42px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;cursor:pointer;box-shadow:0 12px 30px rgba(37,99,235,.22)}
.mobile-backdrop{display:none}
body.admin-dark{background:#0f172a;color:#e5eefb}
body.admin-dark .sidebar{background:#111827;color:#e5eefb;border-right-color:rgba(148,163,184,.14);box-shadow:none}
body.admin-dark .sidebar-head{background:#111827;border-bottom:1px solid rgba(148,163,184,.12);box-shadow:none}
body.admin-dark .brand-badge{background:#1f2937;border-color:rgba(148,163,184,.16);color:#fff}
body.admin-dark .side-desc,body.admin-dark .side-footer{color:#94a3b8}
body.admin-dark .nav-item{color:#cbd5e1}
body.admin-dark .nav-item:hover{background:rgba(148,163,184,.08);color:#f8fafc}
body.admin-dark .nav-item.active{background:#1f2937;color:#fff;box-shadow:inset 0 0 0 1px rgba(148,163,184,.12)}
body.admin-dark .sidebar-tool-group{background:transparent;border:none;box-shadow:none}
body.admin-dark .sidebar-tool{color:#94a3b8}
body.admin-dark .sidebar-tool:hover{background:rgba(148,163,184,.06);color:#fff}
body.admin-dark .sidebar-tool-arrow{color:#64748b}
body.admin-dark .sidebar-tools{border-top-color:rgba(148,163,184,.12)}
body.admin-dark .main{background:#0f172a}
body.admin-dark .topbar{background:#0f172a;border-bottom:1px solid rgba(148,163,184,.12);box-shadow:none}
body.admin-dark .topbar p,body.admin-dark .field-help,body.admin-dark .empty-state{color:#94a3b8}
body.admin-dark .panel,body.admin-dark .soft-card,body.admin-dark .empty-state{background:#111827;border-color:rgba(148,163,184,.12);box-shadow:none;color:#e5eefb}
body.admin-dark .table-ui{background:#111827;color:#e5eefb}
body.admin-dark .table-ui thead tr{background:#1f2937}
body.admin-dark .table-ui th,body.admin-dark .table-ui td{border-bottom-color:rgba(148,163,184,.1)}
body.admin-dark .section-sub{background:#1f2937;border-color:rgba(148,163,184,.14);color:#cbd5e1}
body.admin-dark .field-label{color:#e5eefb}
body.admin-dark .input-ui,body.admin-dark .textarea-ui,body.admin-dark .file-ui{background:#0f172a;border-color:rgba(148,163,184,.16);color:#f8fafc}
body.admin-dark .input-ui::placeholder,body.admin-dark .textarea-ui::placeholder{color:#64748b}
body.admin-dark .btn.secondary{background:#111827;border-color:rgba(148,163,184,.12);color:#e5eefb}
body.admin-dark .preview-box,body.admin-dark .content-editor,body.admin-dark .download-editor,body.admin-dark .settings-editor,body.admin-dark .toggle-item{background:#111827;border-color:rgba(148,163,184,.12);color:#e5eefb}
body.admin-dark .preview-box *,body.admin-dark .content-editor *,body.admin-dark .download-editor *,body.admin-dark .settings-editor *,body.admin-dark .toggle-item *{color:inherit}
body.admin-dark [style*="background:#fff"],body.admin-dark [style*="background: #fff"],body.admin-dark [style*="background:#f8"],body.admin-dark [style*="background: #f8"],body.admin-dark [style*="background:#f3"],body.admin-dark [style*="background: #f3"]{background:#111827 !important;color:#e5eefb !important;border-color:rgba(148,163,184,.12) !important}
body.admin-dark [style*="color:#64748b"],body.admin-dark [style*="color: #64748b"],body.admin-dark [style*="color:#94a3b8"],body.admin-dark [style*="color: #94a3b8"],body.admin-dark [style*="color:#475569"],body.admin-dark [style*="color: #475569"]{color:#94a3b8 !important}
body.admin-dark [style*="color:#111827"],body.admin-dark [style*="color: #111827"],body.admin-dark [style*="color:#0f172a"],body.admin-dark [style*="color: #0f172a"],body.admin-dark [style*="color:#1f2937"],body.admin-dark [style*="color: #1f2937"]{color:#e5eefb !important}
body.admin-dark .content-tabs,body.admin-dark .download-tabs,body.admin-dark .settings-tabs{background:#0f172a;border-color:rgba(148,163,184,.12)}
body.admin-dark .content-tab,body.admin-dark .download-tab,body.admin-dark .settings-tab{color:#94a3b8}
body.admin-dark .content-tab.active,body.admin-dark .download-tab.active,body.admin-dark .settings-tab.active{background:#1f2937;color:#fff;box-shadow:inset 0 0 0 1px rgba(148,163,184,.12)}
@media (max-width:980px){
  .layout{grid-template-columns:1fr}
  .sidebar{position:fixed;left:0;top:0;bottom:0;width:min(82vw,300px);height:100vh;z-index:40;transform:translateX(-100%);box-shadow:0 24px 60px rgba(15,23,42,.24);overflow-y:auto;padding:0}
  .main{padding:0 14px 18px;background:#fff}
  .topbar{gap:10px;top:0;margin:0 -14px 14px;padding:14px 14px 12px;min-height:68px;border-radius:0}
  .topbar h1{font-size:22px}
  .topbar p{font-size:12px;line-height:1.5}
  .topbar-main,.top-actions{flex:1 1 100%}
  .top-actions{justify-content:flex-start}
  .top-actions .btn{width:100%}
  .stats-grid,.info-grid,.split-grid,.field-grid-2,.field-grid-3{grid-template-columns:1fr}
  .chart-grid{grid-template-columns:repeat(7,minmax(72px,1fr));min-width:640px;height:200px}
  .panel{padding:18px;border-radius:16px}
  .main{padding-top:0}
  .mobile-toggle{display:inline-flex}
  body.sidebar-open .sidebar{transform:translateX(0)}
  .mobile-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.35);z-index:30}
  body.sidebar-open .mobile-backdrop{display:block}
  body.sidebar-collapsed .layout{grid-template-columns:1fr}
  body.sidebar-collapsed .sidebar{padding-left:18px;padding-right:18px;align-items:stretch;overflow-y:auto}
  body.sidebar-collapsed .brand-wrap{display:flex;flex-direction:row;align-items:flex-start;justify-content:space-between}
  body.sidebar-collapsed .brand{display:flex;justify-content:flex-start;width:auto}
  body.sidebar-collapsed .brand-badge{width:42px;height:42px;border-radius:14px}
  body.sidebar-collapsed .brand-text,
  body.sidebar-collapsed .side-desc,
  body.sidebar-collapsed .sidebar-tool-label{display:block}
  body.sidebar-collapsed .nav{width:auto;justify-items:stretch}
  body.sidebar-collapsed .nav-item{display:flex;justify-content:flex-start;align-items:center;padding-left:14px;padding-right:14px;width:auto;min-width:0;height:auto;min-height:48px}
  body.sidebar-collapsed .nav-label{display:inline}
  body.sidebar-collapsed .nav-icon{width:20px;height:auto;flex:0 0 20px;font-size:inherit}
  body.sidebar-collapsed .sidebar-tools{width:auto;align-items:stretch}
  body.sidebar-collapsed .sidebar-tool-group{width:auto;padding:10px}
  body.sidebar-collapsed .sidebar-tool{justify-content:flex-start;width:100%;min-height:46px;padding:0 14px}
}
</style>
</head>
<body>
<div class="mobile-backdrop" id="mobileBackdrop"></div>
<div class="layout">
  <aside class="sidebar" id="adminSidebar">
    <div class="sidebar-head">
      <div class="brand-wrap">
        <div class="brand"><span class="brand-badge"><?php if ($siteLogo): ?><img src="<?= htmlspecialchars($siteLogo, ENT_QUOTES, 'UTF-8') ?>" alt="logo"><?php else: ?>P<?php endif; ?></span><span class="brand-text">POP 后台</span></div>
      </div>
    </div>
    <nav class="nav">
      <a class="<?= nav_active('dashboard', $currentPage) ?>" data-tip="仪表盘" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/dashboard.php"><span class="nav-icon">🏠</span><span class="nav-label">仪表盘</span></a>
      <a class="<?= nav_active('settings', $currentPage) ?>" data-tip="站点设置" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/settings.php"><span class="nav-icon">⚙️</span><span class="nav-label">站点设置</span></a>
      <a class="<?= nav_active('downloads', $currentPage) ?>" data-tip="下载中心" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/downloads.php"><span class="nav-icon">⬇️</span><span class="nav-label">下载中心</span></a>
      <a class="<?= nav_active('content', $currentPage) ?>" data-tip="页面内容" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/content.php"><span class="nav-icon">📝</span><span class="nav-label">页面内容</span></a>
      <a class="<?= nav_active('notice', $currentPage) ?>" data-tip="公告管理" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/notice.php"><span class="nav-icon">📢</span><span class="nav-label">公告管理</span></a>
      <a class="<?= nav_active('about', $currentPage) ?>" data-tip="关于程序" href="/<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>/about.php"><span class="nav-icon">ℹ️</span><span class="nav-label">关于程序</span></a>
    </nav>

    <div class="sidebar-tools">
      <div class="sidebar-tool-group">
        <button class="sidebar-tool" id="themeToggle" type="button" aria-label="切换深色模式">
          <span class="sidebar-tool-main">
            <span class="sidebar-tool-icon" id="themeToggleIcon">🌙</span>
            <span class="sidebar-tool-label" id="themeToggleLabel">深色模式</span>
          </span>
          <span class="sidebar-tool-arrow" aria-hidden="true">›</span>
        </button>
        <button class="sidebar-tool" id="sidebarToggle" type="button" aria-label="收缩导航">
          <span class="sidebar-tool-main">
            <span class="sidebar-tool-icon" id="sidebarToggleIcon">⇤</span>
            <span class="sidebar-tool-label" id="sidebarToggleLabel">收起导航</span>
          </span>
          <span class="sidebar-tool-arrow" aria-hidden="true">›</span>
        </button>
      </div>
    </div>
  </aside>
  <main class="main">
    <button class="mobile-toggle" id="mobileSidebarToggle" type="button" aria-label="打开导航">☰</button>
<script>
(function(){
  const body = document.body;
  const desktopToggle = document.getElementById('sidebarToggle');
  const desktopToggleIcon = document.getElementById('sidebarToggleIcon');
  const desktopToggleLabel = document.getElementById('sidebarToggleLabel');
  const themeToggle = document.getElementById('themeToggle');
  const themeToggleIcon = document.getElementById('themeToggleIcon');
  const themeToggleLabel = document.getElementById('themeToggleLabel');
  const mobileToggle = document.getElementById('mobileSidebarToggle');
  const backdrop = document.getElementById('mobileBackdrop');
  const sidebarKey = 'admin-sidebar-collapsed';
  const themeKey = 'admin-theme';
  const mq = window.matchMedia('(max-width: 980px)');

  function syncTheme(){
    const dark = localStorage.getItem(themeKey) === 'dark';
    body.classList.toggle('admin-dark', dark);
    if (themeToggle) {
      themeToggle.setAttribute('aria-label', dark ? '切换浅色模式' : '切换深色模式');
    }
    if (themeToggleIcon) {
      themeToggleIcon.textContent = dark ? '☀️' : '🌙';
    }
    if (themeToggleLabel) {
      themeToggleLabel.textContent = dark ? '浅色模式' : '深色模式';
    }
  }

  function syncSidebar(){
    if (mq.matches) {
      body.classList.remove('sidebar-collapsed');
      if (desktopToggle) {
        desktopToggle.setAttribute('aria-label', '收缩导航');
      }
      if (desktopToggleIcon) {
        desktopToggleIcon.textContent = '⇤';
      }
      if (desktopToggleLabel) {
        desktopToggleLabel.textContent = '收起导航';
      }
      return;
    }
    const collapsed = localStorage.getItem(sidebarKey) === '1';
    body.classList.toggle('sidebar-collapsed', collapsed);
    if (desktopToggle) {
      desktopToggle.setAttribute('aria-label', collapsed ? '展开导航' : '收缩导航');
    }
    if (desktopToggleIcon) {
      desktopToggleIcon.textContent = collapsed ? '⇥' : '⇤';
    }
    if (desktopToggleLabel) {
      desktopToggleLabel.textContent = collapsed ? '展开导航' : '收起导航';
    }
  }

  desktopToggle && desktopToggle.addEventListener('click', function(){
    if (mq.matches) {
      body.classList.toggle('sidebar-open');
      return;
    }
    const collapsed = !body.classList.contains('sidebar-collapsed');
    body.classList.toggle('sidebar-collapsed', collapsed);
    localStorage.setItem(sidebarKey, collapsed ? '1' : '0');
    syncSidebar();
  });

  themeToggle && themeToggle.addEventListener('click', function(){
    const dark = !body.classList.contains('admin-dark');
    localStorage.setItem(themeKey, dark ? 'dark' : 'light');
    syncTheme();
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
    syncSidebar();
  });

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
      body.classList.remove('sidebar-open');
    }
  });

  syncTheme();
  syncSidebar();
})();
</script>
