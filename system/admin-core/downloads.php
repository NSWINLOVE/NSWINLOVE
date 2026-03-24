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
$release = $config['release'] ?? [];
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'downloads';
$message = '';
$error = '';

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();

    try {
        $newDownloads = [];
        foreach (['android', 'ios', 'windows', 'mac'] as $key) {
            $newDownloads[$key] = [
                'url' => trim($_POST[$key . '_url'] ?? '#'),
                'version' => trim($_POST[$key . '_version'] ?? 'v1.0.0'),
                'notes' => trim($_POST[$key . '_notes'] ?? ''),
                'updated_at' => trim($_POST[$key . '_updated_at'] ?? date('Y-m-d')),
                'hits' => (int)($downloads[$key]['hits'] ?? 0),
            ];
        }

        $newRelease = [
            'title' => trim($_POST['release_title'] ?? '最新版本更新'),
            'content' => trim($_POST['release_content'] ?? ''),
        ];

        $saved = save_install_config($configFile, function (array $current) use ($newDownloads, $newRelease) {
            $current['downloads'] = $newDownloads;
            $current['release'] = $newRelease;
            return $current;
        });

        if (!$saved) {
            $error = '下载配置保存失败。';
        } else {
            $config = load_install_config($configFile);
            $downloads = $config['downloads'] ?? [];
            $release = $config['release'] ?? [];
            admin_log('update_downloads', ['platforms' => ['android', 'ios', 'windows', 'mac'], 'qrcode_removed' => true]);
            $message = '下载配置和更新说明已保存，首页二维码模块已彻底移除。';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>下载管理</h1><p>统一维护下载链接、版本信息与更新说明。</p></div></div>
<div class="panel">
<?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>
<form method="post">
<?= csrf_input() ?>
<?php foreach (['android' => 'Android', 'ios' => 'iOS', 'windows' => 'Windows', 'mac' => 'macOS'] as $key => $label): ?>
  <h3><?= h($label) ?></h3>
  <div class="field-grid-3">
    <p><label>主下载链接</label><br><input style="width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" type="text" name="<?= h($key) ?>_url" value="<?= h($downloads[$key]['url'] ?? '#') ?>"></p>
    <p><label>版本号</label><br><input style="width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" type="text" name="<?= h($key) ?>_version" value="<?= h($downloads[$key]['version'] ?? 'v1.0.0') ?>"></p>
    <p><label>更新时间</label><br><input style="width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" type="text" name="<?= h($key) ?>_updated_at" value="<?= h($downloads[$key]['updated_at'] ?? date('Y-m-d')) ?>"></p>
  </div>
  <p><label>说明</label><br><input style="width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" type="text" name="<?= h($key) ?>_notes" value="<?= h($downloads[$key]['notes'] ?? '') ?>"></p>
<?php endforeach; ?>
<h3>更新说明</h3>
<p><label>标题</label><br><input style="width:100%;min-height:48px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" type="text" name="release_title" value="<?= h($release['title'] ?? '最新版本更新') ?>"></p>
<p><label>内容</label><br><textarea style="width:100%;min-height:140px;padding:12px 14px;border-radius:14px;border:1px solid #cbd5e1;" name="release_content"><?= h($release['content'] ?? '') ?></textarea></p>
<p><button class="btn primary" type="submit">保存下载配置</button></p>
</form>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
