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
$content = $config['content'] ?? [];
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'content';
$message = '';
$error = '';

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $newContent = [
        'requirements_title' => trim($_POST['requirements_title'] ?? '系统要求'),
        'requirements' => trim($_POST['requirements'] ?? ''),
        'guide_title' => trim($_POST['guide_title'] ?? '安装教程'),
        'guide' => trim($_POST['guide'] ?? ''),
        'faq_title' => trim($_POST['faq_title'] ?? '常见问题'),
        'faq' => trim($_POST['faq'] ?? ''),
    ];
    $saved = save_install_config($configFile, function (array $current) use ($newContent) {
        $current['content'] = array_merge($current['content'] ?? [], $newContent);
        unset(
            $current['content']['showcase_title'],
            $current['content']['showcase_text'],
            $current['content']['showcase_main'],
            $current['content']['showcase_1'],
            $current['content']['showcase_2'],
            $current['content']['showcase_3']
        );
        return $current;
    });
    if (!$saved) {
        $error = '内容保存失败。';
    } else {
        $config = load_install_config($configFile);
        $content = $config['content'] ?? [];
        admin_log('update_content', ['sections' => ['requirements', 'guide', 'faq']]);
        $message = '前台内容模块已保存。';
    }
}
require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>内容管理</h1><p>维护教程、系统要求与 FAQ 内容。</p></div></div>
<div class="panel">
<?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>
<form method="post">
  <?= csrf_input() ?>
  <div class="section-head"><h3 class="section-title"><span>📚</span><span>内容配置</span></h3><span class="section-sub">前台内容</span></div>
  <div class="field-grid-2">
    <p><label>系统要求标题</label><br><input style="width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" type="text" name="requirements_title" value="<?= h($content['requirements_title'] ?? '系统要求') ?>"></p>
    <p><label>安装教程标题</label><br><input style="width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" type="text" name="guide_title" value="<?= h($content['guide_title'] ?? '安装教程') ?>"></p>
  </div>
  <p><label>系统要求（每行一条）</label><br><textarea style="width:100%;min-height:120px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" name="requirements"><?= h($content['requirements'] ?? '') ?></textarea></p>
  <p><label>安装教程（每行一步）</label><br><textarea style="width:100%;min-height:120px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" name="guide"><?= h($content['guide'] ?? '') ?></textarea></p>
  <p><label>常见问题标题</label><br><input style="width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" type="text" name="faq_title" value="<?= h($content['faq_title'] ?? '常见问题') ?>"></p>
  <p><label>常见问题（格式：问题=>答案，每行一组）</label><br><textarea style="width:100%;min-height:120px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" name="faq"><?= h($content['faq'] ?? '') ?></textarea></p>
  <p><button class="btn primary" type="submit">保存内容</button></p>
</form>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
