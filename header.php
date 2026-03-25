<?php
if (!defined('SITE_HEADER_INCLUDED')) {
    http_response_code(404);
    exit;
}

$siteTitle = isset($siteTitle) ? (string)$siteTitle : '官方下载';
$siteDescription = isset($siteDescription) ? (string)$siteDescription : '';
$siteLogo = isset($siteLogo) ? (string)$siteLogo : '';
$h = isset($h) && is_callable($h)
    ? $h
    : static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<div class="nav">
  <div class="brand">
    <span class="brand-badge"><?php if ($siteLogo !== ''): ?><img src="<?= $h($siteLogo) ?>" alt="logo"><?php else: ?>P<?php endif; ?></span>
    <span class="brand-meta">
      <span class="brand-title"><?= $h($siteTitle) ?></span>
      <span class="brand-subtitle"><?= $h($siteDescription) ?></span>
    </span>
  </div>
  <div class="nav-links">
    <a href="#downloads">下载</a>
    <a href="#guide">教程</a>
    <a href="#faq">FAQ</a>
  </div>
</div>
