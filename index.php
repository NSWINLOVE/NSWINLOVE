<?php
$configFile = __DIR__ . '/config/install.php';
$config = file_exists($configFile) ? require $configFile : [];
$site = $config['site'] ?? [];
$downloads = $config['downloads'] ?? [];
$release = $config['release'] ?? [];
$content = $config['content'] ?? [];
$notice = $config['notice'] ?? [];
$installed = !empty($config['installed']);

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function dval(array $downloads, string $platform, string $key, string $default = ''): string { return (string)($downloads[$platform][$key] ?? $default); }
function platformIcon(string $platform): string { return match ($platform) { 'android' => '🤖', 'ios' => '🍎', 'windows' => '🪟', 'mac' => '💻', default => '⬇', }; }
function lines(string $text): array { return array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text))); }
function faqPairs(string $text): array { $rows = lines($text); $pairs = []; foreach ($rows as $row) { $parts = explode('=>', $row, 2); $pairs[] = ['q' => trim($parts[0] ?? ''), 'a' => trim($parts[1] ?? '')]; } return $pairs; }
$title = $site['title'] ?? 'POP 官方下载';
$description = $site['description'] ?? '官方正版下载中心，提供最新版本与更新说明';
$keywords = $site['keywords'] ?? 'POP,官方下载,安装包,客户端下载';
$logo = $site['logo'] ?? '';
$favicon = $site['favicon'] ?? '';
$adminSlug = trim(($site['admin_slug'] ?? 'admin'), '/'); $adminSlug = $adminSlug !== '' ? $adminSlug : 'admin';
$releaseTitle = $release['title'] ?? '最新版本更新'; $releaseLines = lines($release['content'] ?? '');
$requirementsTitle = $content['requirements_title'] ?? '系统要求'; $requirementsLines = lines($content['requirements'] ?? '');
$guideTitle = $content['guide_title'] ?? '安装教程'; $guideLines = lines($content['guide'] ?? '');
$faqTitle = $content['faq_title'] ?? '常见问题'; $faqItems = faqPairs($content['faq'] ?? '');
$noticeEnabled = !empty($notice['enabled']); $noticeTitle = $notice['title'] ?? '站点公告'; $noticeContent = $notice['content'] ?? '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$currentUrl = $scheme . '://' . $host . ($_SERVER['REQUEST_URI'] ?? '/');
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
:root{--bg:#f7fbff;--bg2:#eaf4ff;--panel:#ffffff;--panel2:#f8fbff;--line:rgba(15,23,42,.10);--text:#0f172a;--muted:#64748b;--accent:#2563eb;--accent2:#38bdf8;--disabled:#94a3b8;--warn:#b45309;--shadow:0 20px 50px rgba(15,23,42,.08);--hero:linear-gradient(135deg,#dceeff,#ffffff 55%,#d8ecff);--softBlock:rgba(37,99,235,.05);--softBorder:rgba(15,23,42,.08);--glass:rgba(255,255,255,.62);--cardGlow:0 24px 60px rgba(37,99,235,.08)}
body.dark{--bg:#08101d;--bg2:#13213c;--panel:#0f1728;--panel2:#101b31;--line:rgba(255,255,255,.08);--text:#e8f1ff;--muted:#9cb0cf;--accent:#3b82f6;--accent2:#60a5fa;--disabled:#64748b;--warn:#fde68a;--shadow:0 30px 80px rgba(0,0,0,.25);--hero:linear-gradient(135deg,#10213d,#0a1424 55%,#133a77);--softBlock:rgba(255,255,255,.04);--softBorder:rgba(255,255,255,.08);--glass:rgba(255,255,255,.05);--cardGlow:0 28px 80px rgba(0,0,0,.26)}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;background:radial-gradient(circle at top,var(--bg2) 0%,var(--bg) 42%,var(--bg) 100%);color:var(--text);transition:background .45s cubic-bezier(.22,1,.36,1),color .35s ease}a{text-decoration:none}.wrap{max-width:1280px;margin:0 auto;padding:26px 18px 60px}.nav{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}.brand{display:flex;align-items:center;gap:12px;font-size:22px;font-weight:900}.brand-badge{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:14px;background:linear-gradient(135deg,var(--accent),var(--accent2));box-shadow:0 12px 30px rgba(37,99,235,.22);overflow:hidden;transition:transform .25s ease,box-shadow .35s ease}.brand-badge img{width:100%;height:100%;object-fit:cover}.nav-links{display:flex;gap:12px;flex-wrap:wrap;align-items:center}.nav-links a{padding:10px 14px;border-radius:999px;background:var(--glass);border:1px solid var(--line);color:var(--text);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);transition:transform .22s ease,background .35s ease,border-color .35s ease,color .35s ease,box-shadow .35s ease}body.dark .nav-links a{background:var(--glass)}.nav-links a:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(15,23,42,.08)}.theme-toggle{position:relative;width:64px;height:34px;border:none;border-radius:999px;cursor:pointer;background:linear-gradient(135deg,rgba(219,234,254,.92),rgba(255,255,255,.98));box-shadow:inset 0 0 0 1px rgba(15,23,42,.08),0 10px 22px rgba(15,23,42,.10);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);transition:transform .22s ease,background .35s ease,box-shadow .35s ease;padding:0}.theme-toggle:hover{transform:translateY(-1px)}body.dark .theme-toggle{background:linear-gradient(135deg,rgba(15,23,42,.92),rgba(30,41,59,.96));box-shadow:inset 0 0 0 1px rgba(255,255,255,.08),0 12px 24px rgba(0,0,0,.28)}.theme-toggle-track{position:absolute;inset:0;display:flex;align-items:center;justify-content:space-between;padding:0 9px;font-size:12px}.theme-toggle-thumb{position:absolute;top:4px;left:4px;width:26px;height:26px;border-radius:50%;background:#fff;box-shadow:0 6px 14px rgba(15,23,42,.16);transition:transform .32s cubic-bezier(.22,1,.36,1),background .35s ease,color .35s ease,box-shadow .35s ease;display:flex;align-items:center;justify-content:center;font-size:12px}.theme-toggle.dark .theme-toggle-thumb{transform:translateX(30px);background:#0f172a;color:#fff;box-shadow:0 6px 14px rgba(0,0,0,.24)}.notice-bar{margin-bottom:16px;padding:14px 16px;border-radius:18px;background:rgba(250,204,21,.12);border:1px solid rgba(250,204,21,.22);color:var(--warn);transition:background .35s ease,border-color .35s ease,color .35s ease,transform .25s ease}.notice-bar:hover{transform:translateY(-1px)}.notice-bar strong{display:block;margin-bottom:6px}.hero,.sections,.extra{display:grid;grid-template-columns:1fr;gap:22px}.hero-main{position:relative;padding:34px;border-radius:30px;overflow:hidden;background:var(--hero);border:1px solid var(--line);box-shadow:var(--shadow),var(--cardGlow);transition:background .45s cubic-bezier(.22,1,.36,1),border-color .35s ease,box-shadow .35s ease,transform .25s ease}.hero-main:hover{transform:translateY(-2px)}.hero-main:before{content:"";position:absolute;right:-80px;top:-80px;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,rgba(96,165,250,.22),transparent 70%)}.badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.14);font-size:12px;font-weight:800;color:var(--accent);position:relative;z-index:1;transition:background .35s ease,border-color .35s ease,color .35s ease}body.dark .badge{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.14);color:#fff}.hero-main h1{position:relative;z-index:1;margin:18px 0 12px;font-size:52px;line-height:1.02;letter-spacing:-.6px}.hero-main p{position:relative;z-index:1;margin:0;max-width:760px;color:var(--muted);font-size:17px;line-height:1.9;transition:color .35s ease}.actions{position:relative;z-index:1;display:flex;gap:14px;flex-wrap:wrap;margin-top:28px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 22px;border-radius:16px;font-weight:900;transition:transform .22s ease,box-shadow .35s ease,background .35s ease,color .35s ease}.btn:hover{transform:translateY(-1px)}.btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 18px 40px rgba(37,99,235,.22)}.notice{position:relative;z-index:1;margin-top:18px;padding:14px 16px;border-radius:16px;background:rgba(250,204,21,.10);border:1px solid rgba(250,204,21,.20);color:var(--warn);transition:background .35s ease,border-color .35s ease,color .35s ease}.card{background:linear-gradient(180deg,var(--panel),var(--panel2));border:1px solid var(--line);border-radius:28px;box-shadow:var(--shadow),var(--cardGlow);transition:background .45s cubic-bezier(.22,1,.36,1),border-color .35s ease,box-shadow .35s ease,transform .25s ease}.card:hover{transform:translateY(-2px)}.download-section{padding:24px}.panel-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 14px;font-size:20px}.panel-sub{font-size:12px;color:var(--muted);padding:6px 10px;border-radius:999px;background:rgba(37,99,235,.06);border:1px solid var(--line);transition:background .35s ease,border-color .35s ease,color .35s ease}body.dark .panel-sub{background:rgba(255,255,255,.05)}.download-list{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}.download-item{display:flex;flex-direction:column;gap:14px;padding:18px;border-radius:22px;background:linear-gradient(180deg,#ffffff,#f8fbff);border:1px solid rgba(15,23,42,.08);min-height:100%;box-shadow:0 12px 30px rgba(15,23,42,.04);transition:background .45s cubic-bezier(.22,1,.36,1),border-color .35s ease,box-shadow .35s ease,transform .22s ease}body.dark .download-item{background:linear-gradient(180deg,var(--panel),var(--panel2));border:1px solid var(--line)}.download-item:hover{transform:translateY(-2px);box-shadow:0 18px 40px rgba(15,23,42,.08)}body.dark .download-item:hover{box-shadow:0 22px 48px rgba(0,0,0,.18)}.download-head{display:flex;align-items:center;gap:12px}.platform-icon{display:flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:18px;background:rgba(37,99,235,.06);border:1px solid rgba(15,23,42,.08);font-size:26px;flex:0 0 auto;transition:background .35s ease,border-color .35s ease,transform .25s ease}body.dark .platform-icon{background:rgba(255,255,255,.05);border:1px solid var(--line)}.download-item:hover .platform-icon{transform:scale(1.04)}.download-item strong{display:block;font-size:17px}.version-tag{display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:6px 10px;border-radius:999px;background:rgba(59,130,246,.12);border:1px solid rgba(96,165,250,.22);color:var(--accent);font-size:12px;font-weight:800;transition:background .35s ease,border-color .35s ease,color .35s ease}body.dark .version-tag{color:#d6ecff}.download-item .meta{color:var(--muted);font-size:13px;line-height:1.8;min-height:46px;transition:color .35s ease}.download-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:auto}.download-actions a,.download-actions button{padding:10px 14px;border-radius:12px;border:none;cursor:pointer;font-weight:800}.download-actions a{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;transition:transform .22s ease,box-shadow .35s ease}.download-actions a:hover{transform:translateY(-1px);box-shadow:0 12px 24px rgba(37,99,235,.24)}.download-actions button{background:rgba(37,99,235,.06);border:1px solid rgba(15,23,42,.08);color:var(--text);transition:background .35s ease,border-color .35s ease,color .35s ease,transform .22s ease}body.dark .download-actions button{background:rgba(255,255,255,.05);border:1px solid var(--line)}.download-actions button:hover{transform:translateY(-1px)}.download-actions .disabled{background:rgba(148,163,184,.18)!important;border:1px solid rgba(148,163,184,.18)!important;color:var(--disabled)!important;cursor:not-allowed;pointer-events:none}.section-card{padding:22px}.section-card h3{margin:0 0 10px;font-size:20px}.section-card p{margin:0;color:var(--muted);line-height:1.8;transition:color .35s ease}.release-list,.simple-list,.faq-list{display:grid;gap:10px;margin-top:14px}.release-item,.simple-item,.faq-item{padding:12px 14px;border-radius:16px;background:var(--softBlock);border:1px solid var(--softBorder);color:var(--text);transition:background .35s ease,border-color .35s ease,color .35s ease,transform .22s ease}.release-item:hover,.simple-item:hover,.faq-item:hover{transform:translateY(-1px)}.faq-item strong{display:block;margin-bottom:8px}.sections{grid-template-columns:2fr 1fr;margin-top:22px}.extra{grid-template-columns:repeat(3,1fr);margin-top:18px}.footer{margin-top:26px;padding:18px 4px;color:var(--muted);font-size:14px;text-align:center;transition:color .35s ease}.toast{position:fixed;right:18px;bottom:18px;min-width:220px;max-width:320px;padding:14px 16px;border-radius:16px;background:rgba(15,23,42,.92);border:1px solid rgba(255,255,255,.08);color:#fff;box-shadow:0 16px 40px rgba(0,0,0,.28);opacity:0;transform:translateY(10px);pointer-events:none;transition:.22s ease;z-index:99}body.theme-transition,body.theme-transition *,body.theme-transition *::before,body.theme-transition *::after{transition-duration:.45s !important;transition-timing-function:cubic-bezier(.22,1,.36,1) !important}.toast.show{opacity:1;transform:translateY(0)}@media (prefers-reduced-motion: reduce){html{scroll-behavior:auto}*,*::before,*::after{animation:none !important;transition:none !important}.hero-main:hover,.card:hover,.download-item:hover,.release-item:hover,.simple-item:hover,.faq-item:hover,.btn:hover,.nav-links a:hover,.theme-toggle:hover,.notice-bar:hover{transform:none !important}}@media (max-width:1100px){.download-list{grid-template-columns:repeat(2,1fr)}}@media (max-width:960px){.sections,.extra{grid-template-columns:1fr}.download-list{grid-template-columns:1fr}.download-item{min-height:auto}.platform-icon{display:none}.hero-main h1{font-size:38px}}
</style>
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
window.addEventListener('DOMContentLoaded',()=>{const key='site-theme';const body=document.body;const btn=document.getElementById('themeToggle');const thumb=btn.querySelector('.theme-toggle-thumb');const setTheme=(mode, animate=false)=>{if(animate){body.classList.add('theme-transition');setTimeout(()=>body.classList.remove('theme-transition'),500);}const dark=mode==='dark';body.classList.toggle('dark',dark);btn.classList.toggle('dark',dark);btn.setAttribute('aria-label',dark?'切换到白天模式':'切换到黑夜模式');thumb.textContent=dark?'🌙':'☀️';localStorage.setItem(key,mode);};const saved=localStorage.getItem(key);setTheme(saved==='dark'?'dark':'light');btn.addEventListener('click',()=>setTheme(body.classList.contains('dark')?'light':'dark',true));});
</script>
</head>
<body>
<div class="wrap">
  <div class="nav">
    <div class="brand"><span class="brand-badge"><?php if ($logo): ?><img src="<?= h($logo) ?>" alt="logo"><?php else: ?>P<?php endif; ?></span><span><?= h($title) ?></span></div>
    <div class="nav-links">
      <button id="themeToggle" type="button" class="theme-toggle" aria-label="切换到黑夜模式">
        <span class="theme-toggle-track"><span>☀️</span><span>🌙</span></span>
        <span class="theme-toggle-thumb">☀️</span>
      </button>
      <?php if (!$installed): ?><a href="/install/">安装</a><?php endif; ?>
    </div>
  </div>
  <?php if ($noticeEnabled && $noticeContent): ?><div class="notice-bar"><strong><?= h($noticeTitle) ?></strong><span><?= h($noticeContent) ?></span></div><?php endif; ?>

  <div class="hero">
    <div class="hero-main">
      <div class="badge">官方正版 · 安全下载 · 持续更新</div>
      <h1><?= h($title) ?></h1>
      <p><?= h($description) ?></p>
      <div class="actions"><a class="btn primary" href="#downloads">立即下载</a></div>
      <?php if (!$installed): ?><div class="notice">当前站点尚未完成安装。你可以先访问 <strong>/install/</strong> 完成数据库配置、管理员初始化和后台入口生成。</div><?php endif; ?>
    </div>

    <div class="card download-section" id="downloads">
      <div class="panel-title"><span>下载中心</span><span class="panel-sub">按设备选择版本</span></div>
      <div class="download-list"><?php foreach (['android' => 'Android', 'ios' => 'iOS', 'windows' => 'Windows', 'mac' => 'macOS'] as $key => $label): $url=dval($downloads,$key,'url','#');$version=dval($downloads,$key,'version','v1.0.0');$notes=dval($downloads,$key,'notes','');$updatedAt=dval($downloads,$key,'updated_at','');$disabled=(!$url||$url==='#');?>
        <div class="download-item">
          <div class="download-head">
            <div class="platform-icon"><?= h(platformIcon($key)) ?></div>
            <div>
              <strong><?= h($label) ?> 版本</strong>
              <div class="version-tag">当前版本 <?= h($version) ?><?php if ($updatedAt): ?> · 更新于 <?= h($updatedAt) ?><?php endif; ?></div>
            </div>
          </div>
          <div class="meta"><?= h($notes ?: '暂无说明') ?></div>
          <div class="download-actions"><a class="<?= $disabled ? 'disabled' : '' ?>" href="<?= $disabled ? 'javascript:void(0)' : '/download.php?platform=' . $key ?>" <?= $disabled ? '' : 'onclick="return beforeDownload(\'' . h($url) . '\')"' ?>>立即下载</a><button type="button" onclick="copyLink('<?= h($url) ?>')">复制链接</button></div>
        </div><?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="sections" id="features"><div class="card section-card"><h3><?= h($releaseTitle) ?></h3><div class="release-list"><?php if ($releaseLines): foreach ($releaseLines as $line): ?><div class="release-item"><?= h($line) ?></div><?php endforeach; else: ?><div class="release-item">暂无更新说明</div><?php endif; ?></div></div><div class="card section-card"><h3>页面说明</h3><p>白天 / 黑夜切换现在已经带整页平滑过渡，背景、卡片、按钮和内容区会一起柔和切换。</p></div></div>
  <div class="extra"><div class="card section-card" id="guide"><h3><?= h($guideTitle) ?></h3><div class="simple-list"><?php foreach ($guideLines ?: ['暂无安装教程'] as $line): ?><div class="simple-item"><?= h($line) ?></div><?php endforeach; ?></div></div><div class="card section-card"><h3><?= h($requirementsTitle) ?></h3><div class="simple-list"><?php foreach ($requirementsLines ?: ['暂无系统要求'] as $line): ?><div class="simple-item"><?= h($line) ?></div><?php endforeach; ?></div></div><div class="card section-card"><h3><?= h($faqTitle) ?></h3><div class="faq-list"><?php if ($faqItems): foreach ($faqItems as $item): ?><div class="faq-item"><strong><?= h($item['q']) ?></strong><span><?= h($item['a']) ?></span></div><?php endforeach; else: ?><div class="faq-item"><strong>暂无常见问题</strong><span>后续可在后台继续补充。</span></div><?php endif; ?></div></div></div>
  <div class="footer"><?= h($title) ?> · 官方下载与安装入口</div>
</div>
<div id="toast" class="toast"></div>
</body>
</html>
