<?php
require __DIR__ . '/session_bootstrap.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/upload_helper.php';
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
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'settings';
$message = '';
$error = '';

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $title = trim($_POST['site_title'] ?? '');
    $description = trim($_POST['site_description'] ?? '');
    $adminSlug = trim($_POST['admin_slug'] ?? $currentSlug);
    $adminSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $adminSlug) ?: 'admin';
    $keywords = trim($_POST['keywords'] ?? '');
    $logo = trim($_POST['logo'] ?? ($site['logo'] ?? ''));
    $favicon = trim($_POST['favicon'] ?? ($site['favicon'] ?? ''));

    try {
        $uploadedLogo = upload_image('logo_file', $baseDir . '/uploads/site', '/uploads/site');
        if ($uploadedLogo !== '') {
            $logo = $uploadedLogo;
        }
        $uploadedFavicon = upload_image('favicon_file', $baseDir . '/uploads/site', '/uploads/site');
        if ($uploadedFavicon !== '') {
            $favicon = $uploadedFavicon;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    if ($error === '') {
        if ($title === '') {
            $error = '站点标题不能为空。';
        } else {
            $saved = save_install_config($configFile, function (array $current) use ($title, $description, $adminSlug, $keywords, $logo, $favicon) {
                $current['site'] = $current['site'] ?? [];
                $current['site']['title'] = $title;
                $current['site']['description'] = $description;
                $current['site']['admin_slug'] = $adminSlug;
                $current['site']['keywords'] = $keywords;
                $current['site']['logo'] = $logo;
                $current['site']['favicon'] = $favicon;
                return $current;
            });

            if (!$saved) {
                $error = '配置保存失败。';
            } else {
                $config = load_install_config($configFile);
                $site = $config['site'] ?? [];
                $targetDir = $baseDir . '/' . $adminSlug;
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $entryMap = [
                    'index.php' => 'index.php',
                    'dashboard.php' => 'dashboard.php',
                    'logout.php' => 'logout.php',
                    'stats.php' => 'stats.php',
                    'settings.php' => 'settings.php',
                    'downloads.php' => 'downloads.php',
                    'content.php' => 'content.php',
                    'notice.php' => 'notice.php',
                    'password.php' => 'password.php',
                ];
                foreach ($entryMap as $entry => $core) {
                    file_put_contents($targetDir . '/' . $entry, "<?php require __DIR__ . '/../system/admin-core/{$core}';\n");
                }
                admin_log('update_site_settings', ['title' => $title, 'admin_slug' => $adminSlug]);
                $message = '站点设置已保存。';
                $currentSlug = $adminSlug;
            }
        }
    }
}
require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>站点设置</h1><p>在这里维护站点标题、描述、后台目录、关键词、Logo 与 favicon。</p></div></div>
<div class="panel">
  <?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_input() ?>
    <p><label class="field-label">站点标题</label><input class="input-ui" type="text" name="site_title" value="<?= h($site['title'] ?? '') ?>"></p>
    <p><label class="field-label">站点描述</label><input class="input-ui" type="text" name="site_description" value="<?= h($site['description'] ?? '') ?>"></p>
    <p><label class="field-label">后台入口目录</label><input class="input-ui" type="text" name="admin_slug" value="<?= h($currentSlug) ?>"></p>
    <p><label class="field-label">SEO 关键词</label><input class="input-ui" type="text" name="keywords" value="<?= h($site['keywords'] ?? '') ?>"></p>

    <div class="split-grid">
      <div>
        <p><label class="field-label">Logo 图片地址</label><input class="input-ui" type="text" name="logo" value="<?= h($site['logo'] ?? '') ?>"></p>
        <p><label class="field-label">上传 Logo</label><input class="file-ui" type="file" name="logo_file" accept=".jpg,.jpeg,.png,.gif,.webp,.ico"></p>
      </div>
      <div style="padding:12px;border-radius:16px;background:#f8fbff;border:1px solid #e5efff;text-align:center;">
        <div style="font-size:12px;color:#64748b;margin-bottom:8px;">当前 Logo</div>
        <?php if (!empty($site['logo'])): ?><img src="<?= h($site['logo']) ?>" alt="logo" style="max-width:100%;max-height:120px;border-radius:12px;"><?php else: ?><div style="color:#94a3b8;font-size:13px;">暂无</div><?php endif; ?>
      </div>
    </div>

    <div class="section-head"><h3 class="section-title"><span>🪪</span><span>Favicon 设置</span></h3><span class="section-sub">图标</span></div>
    <div class="split-grid">
        <p><label>上传 Favicon</label><br><input type="file" name="favicon_file" accept=".jpg,.jpeg,.png,.gif,.webp,.ico"></p>
      </div>
      <div style="padding:12px;border-radius:16px;background:#f8fbff;border:1px solid #e5efff;text-align:center;">
        <div style="font-size:12px;color:#64748b;margin-bottom:8px;">当前 Favicon</div>
        <?php if (!empty($site['favicon'])): ?><img src="<?= h($site['favicon']) ?>" alt="favicon" style="max-width:64px;max-height:64px;border-radius:12px;"><?php else: ?><div style="color:#94a3b8;font-size:13px;">暂无</div><?php endif; ?>
      </div>
    </div>

    <p><button class="btn primary" type="submit">保存设置</button></p>
  </form>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
