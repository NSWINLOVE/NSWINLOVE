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
$notice = $config['notice'] ?? [];
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'notice';
$message = '';
$error = '';

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $newNotice = [
        'enabled' => isset($_POST['enabled']),
        'title' => trim($_POST['title'] ?? '站点公告'),
        'content' => trim($_POST['content'] ?? ''),
    ];
    $saved = save_install_config($configFile, function (array $current) use ($newNotice) {
        $current['notice'] = $newNotice;
        return $current;
    });
    if (!$saved) {
        $error = '公告保存失败。';
    } else {
        $config = load_install_config($configFile);
        $notice = $config['notice'] ?? [];
        admin_log('update_notice', ['enabled' => !empty($notice['enabled'])]);
        $message = '公告已保存。';
    }
}
require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>公告管理</h1><p>控制前台顶部公告条的显示与内容。</p></div></div>
<div class="panel">
<?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>
<form method="post">
  <?= csrf_input() ?>
  <p><label><input type="checkbox" name="enabled" <?= !empty($notice['enabled']) ? 'checked' : '' ?>> 启用前台公告</label></p>
  <p><label>公告标题</label><br><input style="width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" type="text" name="title" value="<?= h($notice['title'] ?? '站点公告') ?>"></p>
  <p><label>公告内容</label><br><textarea style="width:100%;min-height:140px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" name="content"><?= h($notice['content'] ?? '') ?></textarea></p>
  <p><button class="btn primary" type="submit">保存公告</button></p>
</form>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
