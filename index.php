<?php
$configFile = __DIR__ . '/config/install.php';
$config = file_exists($configFile) ? require $configFile : [];
$site = $config['site'] ?? [];
$downloads = $config['downloads'] ?? [];
$release = $config['release'] ?? [];
$content = $config['content'] ?? [];
$notice = $config['notice'] ?? [];
$installed = !empty($config['installed']);
$navigation = $config['navigation'] ?? [];

if (!$installed) {
    header('Location: /install/');
    exit;
}

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function dval(array $downloads, string $platform, string $key, string $default = ''): string { return (string)($downloads[$platform][$key] ?? $default); }
function platformIcon(string $platform): string { return match ($platform) { 'android' => '🤖', 'ios' => '🍎', 'windows' => '🪟', 'mac' => '💻', default => '⬇', }; }
function lines(string $text): array { return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)))); }
function faqPairs(string $text): array {
    $rows = lines($text);
    $pairs = [];
    foreach ($rows as $row) {
        $parts = explode('=>', $row, 2);
        $pairs[] = ['q' => trim($parts[0] ?? ''), 'a' => trim($parts[1] ?? '')];
    }
    return $pairs;
}

$title = $site['title'] ?? 'POP 官方下载';
$description = $site['description'] ?? '官方正版下载中心，提供最新版本与更新说明';
$keywords = $site['keywords'] ?? 'POP,官方下载,安装包,客户端下载';
$logo = $site['logo'] ?? '';
$favicon = $site['favicon'] ?? '';
$releaseTitle = $release['title'] ?? '最新版本更新';
$releaseLines = lines($release['content'] ?? '');
$requirementsTitle = $content['requirements_title'] ?? '系统要求';
$requirementsLines = lines($content['requirements'] ?? '');
$guideTitle = $content['guide_title'] ?? '安装教程';
$guideLines = lines($content['guide'] ?? '');
$faqTitle = $content['faq_title'] ?? '常见问题';
$faqItems = faqPairs($content['faq'] ?? '');
$noticeEnabled = !empty($notice['enabled']);
$noticeTitle = $notice['title'] ?? '站点公告';
$noticeContent = $notice['content'] ?? '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$currentUrl = $scheme . '://' . $host . ($_SERVER['REQUEST_URI'] ?? '/');

$downloadPlatforms = ['android' => 'Android', 'ios' => 'iOS', 'windows' => 'Windows', 'mac' => 'macOS'];
$totalPlatforms = count($downloadPlatforms);
$availablePlatforms = 0;
$latestUpdatedAt = '';
foreach ($downloadPlatforms as $key => $label) {
    $url = dval($downloads, $key, 'url', '#');
    if ($url !== '' && $url !== '#') {
        $availablePlatforms++;
    }
    $updatedAt = dval($downloads, $key, 'updated_at', '');
    if ($updatedAt !== '' && $updatedAt > $latestUpdatedAt) {
        $latestUpdatedAt = $updatedAt;
    }
}
$latestUpdatedAt = $latestUpdatedAt !== '' ? $latestUpdatedAt : '待发布';
$releaseSummary = $releaseLines ? $releaseLines[0] : '当前暂无公开更新日志，可先查看各平台版本状态与发布时间。';
$releaseStage = $availablePlatforms > 0 ? '公开发行版' : '发布准备中';
$packageHint = '待补充';
$checksumHint = '待提供';
$defaultNavigation = [
    ['label' => '下载中心', 'anchor' => '#downloads', 'children' => []],
    ['label' => '版本更新', 'anchor' => '#release', 'children' => []],
    ['label' => '使用说明', 'anchor' => '', 'children' => [
        ['label' => '安装教程', 'anchor' => '#guide'],
        ['label' => '常见问题', 'anchor' => '#faq'],
    ]],
];
$navigation = is_array($navigation) && $navigation ? $navigation : $defaultNavigation;
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?></title>
<?php if ($favicon): ?><link rel="icon" href="<?= h($favicon) ?>"><?php endif; ?>
<meta name="description" content="<?= h($description) ?>">
<meta name="keywords" content="<?= h($keywords) ?>">
<meta name="robots" content="index,follow">
<link rel="canonical" href="<?= h($currentUrl) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= h($title) ?>">
<meta property="og:description" content="<?= h($description) ?>">
<meta property="og:url" content="<?= h($currentUrl) ?>">
<meta property="og:site_name" content="<?= h($title) ?>">
<?php if ($logo): ?><meta property="og:image" content="<?= h($logo) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($title) ?>">
<meta name="twitter:description" content="<?= h($description) ?>">
<?php if ($logo): ?><meta name="twitter:image" content="<?= h($logo) ?>"><?php endif; ?>
<style>
:root{--bg:#f5f8fc;--bg2:#e8f0ff;--panel:#ffffff;--panel-soft:#f8fbff;--line:rgba(15,23,42,.08);--line-strong:rgba(15,23,42,.12);--text:#0f172a;--muted:#64748b;--accent:#2563eb;--accent2:#38bdf8;--accent3:#7c3aed;--disabled:#94a3b8;--shadow:0 20px 50px rgba(15,23,42,.08);--shadow-lg:0 32px 90px rgba(15,23,42,.14);--glass:rgba(255,255,255,.74);--hero:linear-gradient(135deg,#dbeafe 0%,#ffffff 44%,#ede9fe 100%)}
body.dark{--bg:#07111f;--bg2:#101c32;--panel:#0f172a;--panel-soft:#101b31;--line:rgba(255,255,255,.08);--line-strong:rgba(255,255,255,.12);--text:#e8f1ff;--muted:#9cb0cf;--accent:#60a5fa;--accent2:#38bdf8;--accent3:#a78bfa;--disabled:#64748b;--shadow:0 30px 80px rgba(0,0,0,.28);--shadow-lg:0 36px 96px rgba(0,0,0,.38);--glass:rgba(255,255,255,.05);--hero:linear-gradient(135deg,#0e1d36 0%,#0b1424 44%,#23153d 100%)}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:radial-gradient(circle at top,var(--bg2) 0%,var(--bg) 40%,var(--bg) 100%);color:var(--text);transition:background .45s ease,color .35s ease}a{text-decoration:none;color:inherit}.wrap{max-width:1240px;margin:0 auto;padding:26px 18px 72px}.site-header{position:sticky;top:0;z-index:40;padding-top:8px}.nav{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 18px;border:1px solid var(--line);background:var(--glass);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border-radius:22px;box-shadow:0 12px 30px rgba(15,23,42,.06)}.brand{display:flex;align-items:center;gap:14px;min-width:0}.brand-home{display:flex;align-items:center;gap:14px;min-width:0;color:inherit}.brand-badge{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:17px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-size:18px;font-weight:900;box-shadow:0 14px 34px rgba(37,99,235,.22);overflow:hidden;flex:0 0 auto}.brand-badge img{width:100%;height:100%;object-fit:cover}.brand-copy{display:grid;gap:2px;min-width:0}.brand-title{font-size:18px;font-weight:900;line-height:1.2}.brand-sub{font-size:12px;color:var(--muted);line-height:1.5}.nav-links{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end;flex:1 1 auto;margin-left:auto}.nav-item-wrap{position:relative}.nav-link{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;border:1px solid transparent;color:var(--muted);font-weight:700;font-size:13px;transition:.2s ease;white-space:nowrap}.nav-link:hover,.nav-item-wrap:hover>.nav-link{color:var(--text);background:rgba(255,255,255,.48);border-color:var(--line)}.nav-caret{font-size:10px;opacity:.7}.subnav{position:absolute;top:calc(100% + 10px);right:0;min-width:180px;padding:8px;border-radius:16px;background:rgba(255,255,255,.94);border:1px solid var(--line);box-shadow:0 18px 40px rgba(15,23,42,.10);display:none;z-index:30}.body.dark .subnav{background:rgba(15,23,42,.94)}.nav-item-wrap:hover .subnav{display:grid}.subnav a{display:block;padding:10px 12px;border-radius:12px;color:var(--muted);font-size:13px;font-weight:700}.subnav a:hover{background:rgba(37,99,235,.06);color:var(--text)}.nav-tools{display:flex;align-items:center;gap:10px;flex:0 0 auto}.mobile-nav-toggle{display:none;align-items:center;justify-content:center;width:42px;height:42px;border:none;border-radius:12px;background:rgba(37,99,235,.08);border:1px solid var(--line);color:var(--text);font-size:18px;cursor:pointer}.theme-toggle{position:relative;width:64px;height:36px;border:none;border-radius:999px;cursor:pointer;background:linear-gradient(135deg,rgba(219,234,254,.92),rgba(255,255,255,.98));box-shadow:inset 0 0 0 1px rgba(15,23,42,.08),0 10px 22px rgba(15,23,42,.10);transition:.22s ease;padding:0}.theme-toggle-track{position:absolute;inset:0;display:flex;align-items:center;justify-content:space-between;padding:0 9px;font-size:12px}.theme-toggle-thumb{position:absolute;top:5px;left:5px;width:26px;height:26px;border-radius:50%;background:#fff;box-shadow:0 6px 14px rgba(15,23,42,.16);transition:.3s cubic-bezier(.22,1,.36,1);display:flex;align-items:center;justify-content:center;font-size:12px}.theme-toggle.dark .theme-toggle-thumb{transform:translateX(28px);background:#0f172a;color:#fff}.notice-bar{margin:18px 0 0;padding:14px 16px;border-radius:18px;background:rgba(250,204,21,.12);border:1px solid rgba(250,204,21,.24);color:#b45309}.hero{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(350px,.8fr);gap:22px;margin-top:22px}.hero-main{position:relative;padding:46px;border-radius:34px;overflow:hidden;background:var(--hero);border:1px solid var(--line);box-shadow:var(--shadow-lg)}.hero-main:before,.hero-main:after{content:"";position:absolute;border-radius:999px;filter:blur(8px)}.hero-main:before{width:240px;height:240px;right:-70px;top:-50px;background:rgba(56,189,248,.18)}.hero-main:after{width:220px;height:220px;left:-80px;bottom:-110px;background:rgba(124,58,237,.14)}.eyebrow{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.14);font-size:12px;font-weight:800;color:var(--accent)}.hero-main h1{margin:20px 0 14px;font-size:60px;line-height:1;letter-spacing:-1px;max-width:740px}.hero-main p{margin:0;max-width:720px;color:var(--muted);font-size:17px;line-height:1.9}.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:30px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:54px;padding:0 22px;border-radius:16px;font-weight:900;transition:.22s ease;border:none;cursor:pointer}.btn:hover{transform:translateY(-1px)}.btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 18px 40px rgba(37,99,235,.22)}.btn.secondary{background:rgba(255,255,255,.6);color:var(--text);border:1px solid var(--line)}.hero-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:30px}.stat-card{padding:16px 14px;border-radius:20px;background:rgba(255,255,255,.58);border:1px solid var(--line-strong);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)}.stat-label{font-size:12px;color:var(--muted);font-weight:700}.stat-value{margin-top:8px;font-size:24px;font-weight:900}.panel{background:linear-gradient(180deg,var(--panel),var(--panel-soft));border:1px solid var(--line);border-radius:30px;box-shadow:var(--shadow)}.panel.pad{padding:24px}.section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}.section-title{margin:0;font-size:20px;font-weight:900}.section-sub{padding:6px 10px;border-radius:999px;background:rgba(37,99,235,.06);border:1px solid var(--line);font-size:12px;color:var(--muted);font-weight:800}.release-list,.simple-list,.faq-list{display:grid;gap:10px}.release-item,.simple-item,.faq-item{padding:14px 15px;border-radius:18px;background:rgba(37,99,235,.04);border:1px solid rgba(15,23,42,.06)}.faq-item strong{display:block;margin-bottom:8px}.timeline{display:grid;gap:12px}.timeline-item{display:grid;grid-template-columns:18px 1fr;gap:12px;align-items:flex-start;padding:14px 15px;border-radius:18px;background:rgba(37,99,235,.04);border:1px solid rgba(15,23,42,.06)}.timeline-dot{width:12px;height:12px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));margin-top:4px;box-shadow:0 0 0 4px rgba(37,99,235,.08)}.timeline-title{font-weight:900;margin-bottom:6px}.timeline-desc{color:var(--muted);font-size:13px;line-height:1.8}.faq-item details{display:block}.faq-item summary{cursor:pointer;list-style:none;font-weight:900}.faq-item summary::-webkit-details-marker{display:none}.faq-answer{margin-top:10px;color:var(--muted);line-height:1.8;font-size:13px}.download-panel{margin-top:22px;padding:24px}.download-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.download-item{display:flex;flex-direction:column;gap:14px;padding:20px;border-radius:24px;background:linear-gradient(180deg,#ffffff,#f8fbff);border:1px solid rgba(15,23,42,.08);min-height:100%;box-shadow:0 12px 30px rgba(15,23,42,.04);transition:.22s ease}body.dark .download-item{background:linear-gradient(180deg,var(--panel),var(--panel-soft));border-color:var(--line)}.download-item:hover{transform:translateY(-3px);box-shadow:0 20px 44px rgba(15,23,42,.10)}.download-head{display:flex;align-items:center;gap:12px}.platform-icon{display:flex;align-items:center;justify-content:center;width:58px;height:58px;border-radius:20px;background:rgba(37,99,235,.06);border:1px solid rgba(15,23,42,.08);font-size:27px;flex:0 0 auto}.download-item strong{display:block;font-size:17px}.version-tag{display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:6px 10px;border-radius:999px;background:rgba(59,130,246,.12);border:1px solid rgba(96,165,250,.22);color:var(--accent);font-size:12px;font-weight:800}.download-meta{color:var(--muted);font-size:13px;line-height:1.8;min-height:46px}.download-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:auto}.download-actions a,.download-actions button{padding:10px 14px;border-radius:12px;border:none;cursor:pointer;font-weight:800}.download-actions a{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}.download-actions button{background:rgba(37,99,235,.06);border:1px solid rgba(15,23,42,.08);color:var(--text)}.download-actions .disabled{background:rgba(148,163,184,.18)!important;border:1px solid rgba(148,163,184,.18)!important;color:var(--disabled)!important;cursor:not-allowed;pointer-events:none}.download-topnote{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;padding:16px 18px;border-radius:20px;background:rgba(37,99,235,.04);border:1px solid rgba(15,23,42,.06)}.download-topnote strong{display:block;margin-bottom:6px;font-size:15px}.download-topnote p{margin:0;color:var(--muted);line-height:1.8;font-size:13px;max-width:760px}.download-note-stack{display:grid;gap:10px}.download-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(22,163,74,.10);color:#15803d;font-size:12px;font-weight:900;white-space:nowrap}.release-stage{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.14);color:var(--accent);font-size:12px;font-weight:900}.platform-status{display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:6px 10px;border-radius:999px;background:rgba(15,23,42,.05);border:1px solid rgba(15,23,42,.07);font-size:12px;font-weight:800;color:var(--muted)}.platform-status.live{background:rgba(22,163,74,.10);border-color:rgba(22,163,74,.16);color:#15803d}.platform-status.pending{background:rgba(245,158,11,.10);border-color:rgba(245,158,11,.18);color:#b45309}.download-extra{display:grid;gap:8px}.download-extra-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:14px;background:rgba(15,23,42,.03);border:1px solid rgba(15,23,42,.05);font-size:12px}.download-extra-item span:first-child{color:var(--muted);font-weight:700}.download-extra-item span:last-child{font-weight:800}.download-ops{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}.ops-badge{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:rgba(15,23,42,.05);border:1px solid rgba(15,23,42,.06);font-size:12px;font-weight:800;color:var(--muted)}.content-grid{display:grid;grid-template-columns:1.15fr 1fr 1fr;gap:18px;margin-top:22px}.footer{margin-top:28px;padding:18px 4px;color:var(--muted);font-size:14px;text-align:center}.toast{position:fixed;right:18px;bottom:18px;min-width:220px;max-width:320px;padding:14px 16px;border-radius:16px;background:rgba(15,23,42,.92);border:1px solid rgba(255,255,255,.08);color:#fff;box-shadow:0 16px 40px rgba(0,0,0,.28);opacity:0;transform:translateY(10px);pointer-events:none;transition:.22s ease;z-index:99}.toast.show{opacity:1;transform:translateY(0)}body.theme-transition,body.theme-transition *,body.theme-transition *::before,body.theme-transition *::after{transition-duration:.45s !important}.placeholder-note{margin-top:12px;color:var(--muted);font-size:13px;line-height:1.8}.anchor-offset{scroll-margin-top:110px}.hero-kicker{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.hero-chip{padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.52);border:1px solid var(--line-strong);font-size:12px;font-weight:800;color:var(--text)}@media (prefers-reduced-motion: reduce){html{scroll-behavior:auto}*,*::before,*::after{animation:none !important;transition:none !important}}@media (max-width:1120px){.hero{grid-template-columns:1fr}.download-grid,.content-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.hero-main h1{font-size:48px}}@media (max-width:760px){.wrap{padding:18px 14px 56px}.nav{padding:14px;align-items:flex-start}.mobile-nav-toggle{display:inline-flex}.nav-links{display:none;width:100%;order:3;flex-direction:column;align-items:stretch;gap:8px;margin-top:8px}.nav.mobile-open .nav-links{display:flex}.nav-item-wrap{width:100%}.nav-link{width:100%;justify-content:space-between;border-radius:14px;background:rgba(255,255,255,.45);border-color:var(--line)}.subnav{position:static;display:none;min-width:0;margin-top:8px;background:transparent;border:none;box-shadow:none;padding:0 0 0 8px}.nav-item-wrap.open .subnav{display:grid}.nav-item-wrap:hover .subnav{display:none}.nav-item-wrap.open:hover .subnav{display:grid}.nav-tools{margin-left:auto}.hero-main{padding:30px}.hero-main h1{font-size:38px}.hero-stats,.download-grid,.content-grid{grid-template-columns:1fr}.section-title{font-size:18px}}</style>
<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'SoftwareApplication',
  'name' => $title,
  'description' => $description,
  'applicationCategory' => 'UtilitiesApplication',
  'operatingSystem' => 'Android, iOS, Windows, macOS',
  'url' => $currentUrl,
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) ?>
</script>
<script>
function showToast(text){const el=document.getElementById('toast');el.textContent=text;el.classList.add('show');clearTimeout(window.__toastTimer);window.__toastTimer=setTimeout(()=>el.classList.remove('show'),1800)}
function copyLink(url){if(!url||url==='#'){showToast('当前还没有可复制的下载链接');return;}navigator.clipboard.writeText(url).then(()=>showToast('下载链接已复制')).catch(()=>showToast('复制失败，请手动复制'))}
function beforeDownload(url){if(!url||url==='#'){showToast('当前版本暂未提供下载');return false;}showToast('正在为你打开下载链接');return true;}
window.addEventListener('DOMContentLoaded',()=>{const key='site-theme';const body=document.body;const btn=document.getElementById('themeToggle');const thumb=btn.querySelector('.theme-toggle-thumb');const nav=document.querySelector('.nav');const mobileNavToggle=document.getElementById('mobileNavToggle');const navItems=Array.from(document.querySelectorAll('.nav-item-wrap.has-children'));const navLinks=Array.from(document.querySelectorAll('.nav-links a'));const setTheme=(mode, animate=false)=>{if(animate){body.classList.add('theme-transition');setTimeout(()=>body.classList.remove('theme-transition'),500);}const dark=mode==='dark';body.classList.toggle('dark',dark);btn.classList.toggle('dark',dark);btn.setAttribute('aria-label',dark?'切换到白天模式':'切换到黑夜模式');thumb.textContent=dark?'🌙':'☀️';localStorage.setItem(key,mode);};const saved=localStorage.getItem(key);setTheme(saved==='dark'?'dark':'light');btn.addEventListener('click',()=>setTheme(body.classList.contains('dark')?'light':'dark',true));if(mobileNavToggle&&nav){mobileNavToggle.addEventListener('click',()=>{const open=nav.classList.toggle('mobile-open');mobileNavToggle.setAttribute('aria-label',open?'收起导航':'展开导航');mobileNavToggle.textContent=open?'✕':'☰';});}navItems.forEach(item=>{const trigger=item.querySelector('.nav-link');if(!trigger)return;trigger.addEventListener('click',e=>{if(window.innerWidth>760)return;if(!item.classList.contains('has-children'))return;const href=trigger.getAttribute('href')||'';if(href==='javascript:void(0)'||href==='#'){e.preventDefault();item.classList.toggle('open');}});});navLinks.forEach(link=>{link.addEventListener('click',()=>{const href=link.getAttribute('href')||'';if(window.innerWidth<=760&&href.startsWith('#')&&nav){nav.classList.remove('mobile-open');if(mobileNavToggle){mobileNavToggle.setAttribute('aria-label','展开导航');mobileNavToggle.textContent='☰';}navItems.forEach(item=>item.classList.remove('open'));}});});});
</script>
</head>
<body>
<div class="wrap">
  <header class="site-header">
    <div class="nav">
      <div class="brand">
        <a class="brand-home" href="/" aria-label="返回首页">
          <span class="brand-badge"><?php if ($logo): ?><img src="<?= h($logo) ?>" alt="logo"><?php else: ?>P<?php endif; ?></span>
          <span class="brand-copy">
            <span class="brand-title"><?= h($title) ?></span>
            <span class="brand-sub">官方发布 / 安全下载 / 多端支持</span>
          </span>
        </a>
      </div>
      <div class="nav-links">
        <?php foreach ($navigation as $navItem): $children = $navItem['children'] ?? []; $hasChildren = is_array($children) && $children; $anchor = (string)($navItem['anchor'] ?? ''); ?>
        <div class="nav-item-wrap<?= $hasChildren ? ' has-children' : '' ?>">
          <a class="nav-link" href="<?= h($anchor !== '' ? $anchor : 'javascript:void(0)') ?>">
            <span><?= h($navItem['label'] ?? '') ?></span>
            <?php if ($hasChildren): ?><span class="nav-caret">▾</span><?php endif; ?>
          </a>
          <?php if ($hasChildren): ?>
          <div class="subnav">
            <?php foreach ($children as $child): ?>
            <a href="<?= h((string)($child['anchor'] ?? '#')) ?>"><?= h($child['label'] ?? '') ?></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="nav-tools">
        <button id="mobileNavToggle" type="button" class="mobile-nav-toggle" aria-label="展开导航">☰</button>
        <button id="themeToggle" type="button" class="theme-toggle" aria-label="切换到黑夜模式">
          <span class="theme-toggle-track"><span>☀️</span><span>🌙</span></span>
          <span class="theme-toggle-thumb">☀️</span>
        </button>
      </div>
    </div>
  </header>

  <?php if ($noticeEnabled && $noticeContent): ?><div class="notice-bar"><strong><?= h($noticeTitle) ?></strong><span><?= h($noticeContent) ?></span></div><?php endif; ?>

  <section class="hero">
    <div class="hero-main">
      <div class="eyebrow">官方正版 · 持续更新 · 统一下载入口</div>
      <h1><?= h($title) ?></h1>
      <p><?= h($description) ?></p>
      <div class="hero-actions">
        <a class="btn primary" href="#downloads">立即下载</a>
        <a class="btn secondary" href="#release">查看更新说明</a>
      </div>
      <div class="hero-kicker">
        <span class="hero-chip">统一版本入口</span>
        <span class="hero-chip">多平台覆盖</span>
        <span class="hero-chip">适合个人与团队分发</span>
      </div>
      <div class="hero-stats">
        <div class="stat-card"><div class="stat-label">支持平台</div><div class="stat-value"><?= h((string)$totalPlatforms) ?></div></div>
        <div class="stat-card"><div class="stat-label">可下载版本</div><div class="stat-value"><?= h((string)$availablePlatforms) ?></div></div>
        <div class="stat-card"><div class="stat-label">最近更新时间</div><div class="stat-value" style="font-size:18px;"><?= h($latestUpdatedAt) ?></div></div>
      </div>
    </div>
    <aside class="panel pad">
      <div class="section-head">
        <h2 class="section-title">下载说明</h2>
        <span class="section-sub">快速开始</span>
      </div>
      <div class="simple-list">
        <div class="simple-item">请根据你的设备系统选择对应版本下载安装。</div>
        <div class="simple-item">若当前按钮为灰色，说明该平台版本暂未发布。</div>
        <div class="simple-item">如需批量部署或企业分发，建议先查看更新说明与系统要求。</div>
      </div>
      <div class="simple-list" style="margin-top:10px;">
        <div class="simple-item"><strong>发布方式：</strong>统一由本站提供最新版本入口</div>
        <div class="simple-item"><strong>适用场景：</strong>正式下载、内部发版、安装引导</div>
      </div>
      <div class="placeholder-note">后续你可以直接在后台继续补充真实版本号、更新记录、下载地址与帮助文案。</div>
    </aside>
  </section>

  <section class="panel download-panel anchor-offset" id="downloads">
    <div class="section-head">
      <h2 class="section-title">下载中心</h2>
      <span class="section-sub">按设备选择版本</span>
    </div>
    <div class="download-topnote">
      <div class="download-note-stack">
        <div>
          <strong>当前发布概览</strong>
          <p><?= h($releaseSummary) ?></p>
        </div>
        <span class="release-stage">当前状态：<?= h($releaseStage) ?></span>
      </div>
      <span class="download-badge">可用平台 <?= h((string)$availablePlatforms) ?> / <?= h((string)$totalPlatforms) ?></span>
    </div>
    <div class="download-grid">
      <?php foreach ($downloadPlatforms as $key => $label): $url = dval($downloads, $key, 'url', '#'); $version = dval($downloads, $key, 'version', 'v1.0.0'); $notes = dval($downloads, $key, 'notes', ''); $updatedAt = dval($downloads, $key, 'updated_at', ''); $disabled = (!$url || $url === '#'); $statusLabel = $disabled ? '待发布' : '已发布'; $targetRange = dval($downloads, $key, 'target_range', match ($key) { 'android' => 'Android 8.0 及以上', 'ios' => 'iOS 13 及以上', 'windows' => 'Windows 10 / 11', 'mac' => 'macOS 11 及以上', default => '待补充' }); $targetUser = dval($downloads, $key, 'target_user', match ($key) { 'android' => '移动端安装与分发', 'ios' => 'iPhone / iPad 用户', 'windows' => '桌面办公环境', 'mac' => 'Apple 芯片与 Intel 设备', default => '通用用户' }); $packageSize = dval($downloads, $key, 'package_size', $packageHint); $checksum = dval($downloads, $key, 'checksum', $checksumHint); ?>
      <div class="download-item">
        <div class="download-head">
          <div class="platform-icon"><?= h(platformIcon($key)) ?></div>
          <div>
            <strong><?= h($label) ?> 版本</strong>
            <div class="version-tag">当前版本 <?= h($version) ?></div>
            <div class="platform-status <?= $disabled ? 'pending' : 'live' ?>">状态：<?= h($statusLabel) ?></div>
          </div>
        </div>
        <div class="download-meta"><?= h($notes ?: '暂无说明') ?></div>
        <div class="download-extra">
          <div class="download-extra-item"><span>最近更新</span><span><?= h($updatedAt ?: '待发布') ?></span></div>
          <div class="download-extra-item"><span>下载方式</span><span><?= $disabled ? '暂未开放' : '站内直达' ?></span></div>
          <div class="download-extra-item"><span>适用系统</span><span><?= h($targetRange) ?></span></div>
          <div class="download-extra-item"><span>推荐对象</span><span><?= h($targetUser) ?></span></div>
          <div class="download-extra-item"><span>安装包大小</span><span><?= h($packageSize) ?></span></div>
          <div class="download-extra-item"><span>校验信息</span><span><?= h($checksum) ?></span></div>
        </div>
        <div class="download-ops">
          <span class="ops-badge"><?= $disabled ? '未开放下载' : '支持直接下载' ?></span>
          <span class="ops-badge"><?= $updatedAt ? '已标记更新时间' : '等待发布时间' ?></span>
        </div>
        <div class="download-actions">
          <a class="<?= $disabled ? 'disabled' : '' ?>" href="<?= $disabled ? 'javascript:void(0)' : '/download.php?platform=' . $key ?>" <?= $disabled ? '' : 'onclick="return beforeDownload(\'' . h($url) . '\')"' ?>><?= $disabled ? '暂未发布' : '立即下载' ?></a>
          <button type="button" onclick="copyLink('<?= h($url) ?>')">复制链接</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="content-grid">
    <div class="panel pad anchor-offset" id="release">
      <div class="section-head"><h3 class="section-title"><?= h($releaseTitle) ?></h3><span class="section-sub">Release</span></div>
      <?php if ($releaseLines): ?>
      <div class="release-list"><?php foreach ($releaseLines as $line): ?><div class="release-item"><?= h($line) ?></div><?php endforeach; ?></div>
      <?php else: ?>
      <div class="timeline">
        <div class="timeline-item"><span class="timeline-dot"></span><div><div class="timeline-title">版本状态</div><div class="timeline-desc">当前尚未填写正式更新日志，建议在后台补充版本特性、修复项与发布时间。</div></div></div>
        <div class="timeline-item"><span class="timeline-dot"></span><div><div class="timeline-title">发布建议</div><div class="timeline-desc">先补版本号、更新日期、主要改动，再发布各平台下载链接。</div></div></div>
        <div class="timeline-item"><span class="timeline-dot"></span><div><div class="timeline-title">上线检查</div><div class="timeline-desc">建议同步补充安装包大小、校验信息、适用系统和常见问题说明。</div></div></div>
      </div>
      <?php endif; ?>
    </div>
    <div class="panel pad anchor-offset" id="guide">
      <div class="section-head"><h3 class="section-title"><?= h($guideTitle) ?></h3><span class="section-sub">Guide</span></div>
      <div class="simple-list"><?php foreach ($guideLines ?: ['暂无安装教程'] as $line): ?><div class="simple-item"><?= h($line) ?></div><?php endforeach; ?></div>
    </div>
    <div class="panel pad">
      <div class="section-head"><h3 class="section-title"><?= h($requirementsTitle) ?></h3><span class="section-sub">Requirements</span></div>
      <div class="simple-list"><?php foreach ($requirementsLines ?: ['暂无系统要求'] as $line): ?><div class="simple-item"><?= h($line) ?></div><?php endforeach; ?></div>
    </div>
  </section>

  <section class="panel pad anchor-offset" id="faq" style="margin-top:22px;">
    <div class="section-head"><h3 class="section-title"><?= h($faqTitle) ?></h3><span class="section-sub">FAQ</span></div>
    <div class="faq-list"><?php if ($faqItems): foreach ($faqItems as $item): ?><div class="faq-item"><details><summary><?= h($item['q']) ?></summary><div class="faq-answer"><?= h($item['a']) ?></div></details></div><?php endforeach; else: ?><div class="faq-item"><details open><summary>暂无常见问题</summary><div class="faq-answer">后续可在后台继续补充。</div></details></div><?php endif; ?></div>
  </section>

  <?php require __DIR__ . '/system/common-footer.php'; site_footer_render($site); ?>
</div>
<div id="toast" class="toast"></div>
</body>
</html>
