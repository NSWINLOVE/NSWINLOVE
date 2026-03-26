<?php
require __DIR__ . '/session_bootstrap.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/upload_helper.php';
require __DIR__ . '/logger.php';
require __DIR__ . '/config_helper.php';
require __DIR__ . '/settings_helper.php';
require __DIR__ . '/mail_helper.php';

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
$site = site_meta_load($configFile);
$displaySettings = site_display_load($configFile);
$db = $config['db'] ?? [];
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'settings';
$message = '';
$error = '';
$activeSettingsPane = 'basicPane';

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['host'] ?? '127.0.0.1',
        $db['port'] ?? '3306',
        $db['database'] ?? '',
        $db['charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    $pdo = null;
    $error = '数据库连接失败：' . $e->getMessage();
}

$mailSettings = [
    'smtp_enabled' => '0',
    'smtp_host' => '',
    'smtp_port' => '465',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_from_email' => '',
    'smtp_from_name' => '',
    'smtp_encryption' => 'ssl',
];

if ($pdo) {
    $dbMail = settings_get_many($pdo, array_keys($mailSettings));
    foreach ($mailSettings as $key => $default) {
        if (array_key_exists($key, $dbMail)) {
            $mailSettings[$key] = (string)$dbMail[$key];
        } elseif (isset($config['mail'])) {
            $fallbackMap = [
                'smtp_enabled' => !empty($config['mail']['enabled']) ? '1' : '0',
                'smtp_host' => (string)($config['mail']['host'] ?? ''),
                'smtp_port' => (string)($config['mail']['port'] ?? '465'),
                'smtp_username' => (string)($config['mail']['username'] ?? ''),
                'smtp_password' => (string)($config['mail']['password'] ?? ''),
                'smtp_from_email' => (string)($config['mail']['from_email'] ?? ''),
                'smtp_from_name' => (string)($config['mail']['from_name'] ?? ''),
                'smtp_encryption' => (string)($config['mail']['encryption'] ?? 'ssl'),
            ];
            $mailSettings[$key] = (string)($fallbackMap[$key] ?? $default);
        }
    }
}

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $section = trim($_POST['section'] ?? '');
    if ($section !== '') {
        $activeSettingsPane = $section . 'Pane';
    }

    if ($section === 'basic') {
        $title = trim($_POST['site_title'] ?? '');
        $description = trim($_POST['site_subtitle'] ?? '');
        $adminSlug = trim($_POST['admin_slug'] ?? $currentSlug);
        $adminSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $adminSlug) ?: 'admin';
        $keywords = trim($_POST['keywords'] ?? '');

        if ($title === '') {
            $error = '站点标题不能为空。';
        } else {
            $previousSlug = $currentSlug;
            $saved = save_install_config($configFile, function (array $current) use ($title, $description, $adminSlug, $keywords) {
$current['site'] = $current['site'] ?? [];
                $current['site']['admin_slug'] = $adminSlug;
                return $current;
            });
            if (!$saved) {
                $error = '基础信息保存失败。';
            } else {
                $config = load_install_config($configFile);
                $site = site_meta_load($configFile);
$displaySettings = site_display_load($configFile);
                $targetDir = $baseDir . '/' . $adminSlug;
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $entryMap = [
                    'index.php' => 'index.php',
                    'dashboard.php' => 'dashboard.php',
                    'logout.php' => 'logout.php',
                    'settings.php' => 'settings.php',
                    'downloads.php' => 'downloads.php',
                    'content.php' => 'content.php',
                    'navigation.php' => 'navigation.php',
                    'notice.php' => 'notice.php',
                    'password.php' => 'password.php',
                    'footer.php' => 'footer.php',
                    'about.php' => 'about.php',
                ];
                foreach ($entryMap as $entry => $core) {
                    $entryPath = $targetDir . '/' . $entry;
                    $stub = "<?php require __DIR__ . '/../system/admin-core/{$core}';\n";
                    if (!file_exists($entryPath)) {
                        @file_put_contents($entryPath, $stub);
                        continue;
                    }
                    if (is_writable($entryPath)) {
                        @file_put_contents($entryPath, $stub);
                    }
                }

                $cleanupWarnings = [];
                if ($previousSlug !== $adminSlug) {
                    $oldDir = $baseDir . '/' . $previousSlug;
                    if (is_dir($oldDir)) {
                        foreach (array_keys($entryMap) as $entry) {
                            $oldEntryPath = $oldDir . '/' . $entry;
                            if (!file_exists($oldEntryPath)) {
                                continue;
                            }
                            if (is_file($oldEntryPath) && is_writable($oldEntryPath)) {
                                if (!@unlink($oldEntryPath)) {
                                    $cleanupWarnings[] = $previousSlug . '/' . $entry;
                                }
                            } else {
                                $cleanupWarnings[] = $previousSlug . '/' . $entry;
                            }
                        }
                    }
                }

                admin_log('update_site_settings_basic', [
                    'title' => $title,
                    'admin_slug' => $adminSlug,
                    'previous_admin_slug' => $previousSlug,
                    'cleanup_warnings' => $cleanupWarnings,
                ]);
                $message = '基础信息已保存。';
                if ($previousSlug !== $adminSlug) {
                    $message .= ' 后台入口已切换到 /' . $adminSlug . '/';
                    if ($cleanupWarnings) {
                        $message .= ' 旧入口目录中仍有部分壳文件未清理，请手动检查：' . implode('，', $cleanupWarnings) . '。';
                    } else {
                        $message .= ' 旧入口壳文件已清理。';
                    }
                }
                $currentSlug = $adminSlug;
            }
        }
    } elseif ($section === 'logo') {
        $logo = trim($_POST['logo'] ?? ($site['logo'] ?? ''));
        try {
            $uploadedLogo = upload_image('logo_file', $baseDir . '/uploads/site', '/uploads/site');
            if ($uploadedLogo !== '') {
                $logo = $uploadedLogo;
            }
$meta = site_meta_load($configFile);
            $meta['logo'] = $logo;
            $saved = site_meta_save($configFile, $meta);
            if (!$saved) {
                $error = 'Logo 保存失败。';
            } else {
                $config = load_install_config($configFile);
                $site = site_meta_load($configFile);
$displaySettings = site_display_load($configFile);
                admin_log('update_site_logo', ['logo' => $logo]);
                $message = 'Logo 设置已保存。';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } elseif ($section === 'favicon') {
        $favicon = trim($_POST['favicon'] ?? ($site['favicon'] ?? ''));
        try {
            $uploadedFavicon = upload_image('favicon_file', $baseDir . '/uploads/site', '/uploads/site');
            if ($uploadedFavicon !== '') {
                $favicon = $uploadedFavicon;
            }
$meta = site_meta_load($configFile);
            $meta['favicon'] = $favicon;
            $saved = site_meta_save($configFile, $meta);
            if (!$saved) {
                $error = 'Favicon 保存失败。';
            } else {
                $config = load_install_config($configFile);
                $site = site_meta_load($configFile);
$displaySettings = site_display_load($configFile);
                admin_log('update_site_favicon', ['favicon' => $favicon]);
                $message = 'Favicon 设置已保存。';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } elseif ($section === 'display') {
$footerText = trim($_POST['footer_text'] ?? ($displaySettings['footer_text'] ?? ''));
        $displaySettings['footer_text'] = $footerText;
        $saved = site_display_save($configFile, $displaySettings);
        if (!$saved) {
            $error = '显示设置保存失败。';
        } else {
            $config = load_install_config($configFile);
            $site = site_meta_load($configFile);
$displaySettings = site_display_load($configFile);
            admin_log('update_site_display');
            $message = '显示设置已保存。';
        }

    } elseif ($section === 'mail') {
        if (!$pdo) {
            $error = '数据库未连接，无法保存邮件设置。';
        } else {
            $mailAction = trim($_POST['mail_action'] ?? 'save');
            $smtpEnabled = !empty($_POST['smtp_enabled']);
            $smtpHost = trim($_POST['smtp_host'] ?? '');
            $smtpPort = trim($_POST['smtp_port'] ?? '465');
            $smtpUsername = trim($_POST['smtp_username'] ?? '');
            $smtpPasswordInput = trim($_POST['smtp_password'] ?? '');
            $smtpFromEmail = trim($_POST['smtp_from_email'] ?? '');
            $smtpFromName = trim($_POST['smtp_from_name'] ?? '');
            $smtpEncryption = trim($_POST['smtp_encryption'] ?? 'ssl');
            $testEmail = trim($_POST['test_email'] ?? '');

            if (!in_array($smtpEncryption, ['ssl', 'tls'], true)) {
                $error = '加密方式不合法。';
            } elseif ($smtpEnabled) {
                if ($smtpHost === '') {
                    $error = 'SMTP 主机不能为空。';
                } elseif ($smtpPort === '' || !ctype_digit($smtpPort)) {
                    $error = 'SMTP 端口格式不正确。';
                } elseif ($smtpUsername === '') {
                    $error = 'SMTP 用户名不能为空。';
                } elseif ($smtpFromEmail === '' || !filter_var($smtpFromEmail, FILTER_VALIDATE_EMAIL)) {
                    $error = '发件邮箱格式不正确。';
                } elseif ($smtpFromName === '') {
                    $error = '发件名称不能为空。';
                }
            }

            if ($error === '') {
                $passwordToSave = $mailSettings['smtp_password'] ?? '';
                if ($smtpPasswordInput !== '') {
                    $passwordToSave = $smtpPasswordInput;
                }
                settings_set_many($pdo, [
                    'smtp_enabled' => $smtpEnabled ? '1' : '0',
                    'smtp_host' => $smtpHost,
                    'smtp_port' => $smtpPort,
                    'smtp_username' => $smtpUsername,
                    'smtp_password' => $passwordToSave,
                    'smtp_from_email' => $smtpFromEmail,
                    'smtp_from_name' => $smtpFromName,
                    'smtp_encryption' => $smtpEncryption,
                ]);
                $mailSettings = [
                    'smtp_enabled' => $smtpEnabled ? '1' : '0',
                    'smtp_host' => $smtpHost,
                    'smtp_port' => $smtpPort,
                    'smtp_username' => $smtpUsername,
                    'smtp_password' => $passwordToSave,
                    'smtp_from_email' => $smtpFromEmail,
                    'smtp_from_name' => $smtpFromName,
                    'smtp_encryption' => $smtpEncryption,
                ];

                if ($mailAction === 'test' || $mailAction === 'save_test') {
                    if ($testEmail === '' || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                        $error = '测试收件邮箱格式不正确。';
                    } else {
                        try {
                            $subject = 'SMTP 测试邮件';
                            $html = '<p>这是一封来自站点后台的测试邮件。</p><p>如果你看到这封邮件，说明 SMTP 配置已可正常使用。</p>';
                            $text = "这是一封来自站点后台的测试邮件。\n\n如果你看到这封邮件，说明 SMTP 配置已可正常使用。";
                            send_mail($configFile, $testEmail, $subject, $html, $text, $pdo);
                            admin_log('test_mail_settings', ['to' => $testEmail, 'with_save' => $mailAction === 'save_test']);
                            $message = $mailAction === 'save_test'
                                ? '邮件设置已保存，并已发送测试邮件，请检查收件箱。'
                                : '测试邮件已发送，请检查收件箱。';
                        } catch (Throwable $e) {
                            $error = '测试邮件发送失败：' . $e->getMessage();
                        }
                    }
                } else {
                    admin_log('update_mail_settings');
                    $message = '邮件设置已保存。';
                }
            }
        }
    }
}

require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>站点设置</h1><p>集中维护站点配置。</p></div></div>
<div class="panel">
  <?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>

  <style>
    .settings-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;padding:4px;background:#f3f4f6;border:1px solid rgba(15,23,42,.06);border-radius:12px}
    .settings-tab{appearance:none;border:none;background:transparent;color:#6b7280;padding:9px 14px;border-radius:10px;font:inherit;font-weight:700;font-size:13px;cursor:pointer;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
    .settings-tab.active{background:#fff;color:#111827;box-shadow:inset 0 0 0 1px rgba(15,23,42,.06)}
    .settings-pane{display:none}
    .settings-pane.active{display:block}
    .settings-editor{padding:14px;border-radius:14px;background:#f8fafc;border:1px solid rgba(15,23,42,.05)}
    .settings-editor .field-label{font-size:13px}
    .preview-box{padding:12px;border-radius:14px;background:#fff;border:1px solid rgba(15,23,42,.06);text-align:center}
    .toggle-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .toggle-item{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;background:#fff;border:1px solid rgba(15,23,42,.06)}
    .settings-actions{display:flex;justify-content:flex-end;margin-top:14px}
    .password-row{position:relative}
    .password-row .input-ui{padding-right:108px}
    .password-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);border:none;background:#eef2f7;color:#334155;border-radius:10px;padding:7px 10px;font-size:12px;font-weight:700;cursor:pointer}
    .mail-hint{margin-top:8px;color:#64748b;font-size:12px;line-height:1.6}
    @media (max-width:980px){.toggle-grid{grid-template-columns:1fr}}
  </style>

  <div class="settings-tabs" id="settingsTabs">
    <button class="settings-tab active" type="button" data-pane="basicPane">基础信息</button>
    <button class="settings-tab" type="button" data-pane="logoPane">Logo</button>
    <button class="settings-tab" type="button" data-pane="faviconPane">Favicon</button>
    <button class="settings-tab" type="button" data-pane="displayPane">显示设置</button>
    <button class="settings-tab" type="button" data-pane="footerPane">公共底部</button>
    <button class="settings-tab" type="button" data-pane="mailPane">邮件设置</button>
  </div>

  <div class="settings-pane active" id="basicPane">
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="section" value="basic">
      <div class="settings-editor" style="margin-bottom:18px;">
        <label class="field-label">站点标题</label>
        <input class="input-ui" type="text" name="site_title" value="<?= h($site['title'] ?? '') ?>">
        <label class="field-label">站点副标题</label>
        <input class="input-ui" type="text" name="site_subtitle" value="<?= h($site['description'] ?? '') ?>">
        <label class="field-label">后台入口目录</label>
        <input class="input-ui" type="text" name="admin_slug" value="<?= h($currentSlug) ?>">
        <label class="field-label">SEO 关键词</label>
        <input class="input-ui" type="text" name="keywords" value="<?= h($site['keywords'] ?? '') ?>">
        <div class="settings-actions"><button class="btn primary" type="submit">保存基础信息</button></div>
      </div>
    </form>
  </div>

  <div class="settings-pane" id="logoPane">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_input() ?>
      <input type="hidden" name="section" value="logo">
      <div class="settings-editor" style="margin-bottom:18px;">
        <div class="split-grid">
          <div>
            <label class="field-label">Logo 图片地址</label>
            <input class="input-ui" type="text" name="logo" value="<?= h($site['logo'] ?? '') ?>">
            <label class="field-label">上传 Logo</label>
            <input class="file-ui" type="file" name="logo_file" accept=".jpg,.jpeg,.png,.gif,.webp,.ico">
          </div>
          <div class="preview-box">
            <div style="font-size:12px;color:#64748b;margin-bottom:8px;">当前 Logo</div>
            <?php if (!empty($site['logo'])): ?><img src="<?= h($site['logo']) ?>" alt="logo" style="max-width:100%;max-height:120px;border-radius:12px;"><?php else: ?><div style="color:#94a3b8;font-size:13px;">暂无</div><?php endif; ?>
          </div>
        </div>
        <div class="settings-actions"><button class="btn primary" type="submit">保存 Logo</button></div>
      </div>
    </form>
  </div>

  <div class="settings-pane" id="faviconPane">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_input() ?>
      <input type="hidden" name="section" value="favicon">
      <div class="settings-editor" style="margin-bottom:18px;">
        <div class="split-grid">
          <div>
            <label class="field-label">Favicon 图片地址</label>
            <input class="input-ui" type="text" name="favicon" value="<?= h($site['favicon'] ?? '') ?>">
            <label class="field-label">上传 Favicon</label>
            <input class="file-ui" type="file" name="favicon_file" accept=".jpg,.jpeg,.png,.gif,.webp,.ico">
          </div>
          <div class="preview-box">
            <div style="font-size:12px;color:#64748b;margin-bottom:8px;">当前 Favicon</div>
            <?php if (!empty($site['favicon'])): ?><img src="<?= h($site['favicon']) ?>" alt="favicon" style="max-width:64px;max-height:64px;border-radius:12px;"><?php else: ?><div style="color:#94a3b8;font-size:13px;">暂无</div><?php endif; ?>
          </div>
        </div>
        <div class="settings-actions"><button class="btn primary" type="submit">保存 Favicon</button></div>
      </div>
    </form>
  </div>

  <div class="settings-pane" id="displayPane">
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="section" value="display">
      <div class="settings-editor" style="margin-bottom:18px;">
        <div class="field-help" style="margin:0 0 12px;">当前首页的下载中心、更新说明、安装教程、系统要求和常见问题都属于固定结构，不再通过这里的旧开关控制显示。这里保留站点底部展示文案设置。</div>
        <label class="field-label">底部文字</label>
        <input class="input-ui" type="text" name="footer_text" value="<?= h($displaySettings['footer_text'] ?? '') ?>">
        <div class="field-help">用于首页最底部的简短版权或补充说明文本。</div>
        <div class="settings-actions"><button class="btn primary" type="submit">保存显示设置</button></div>
      </div>
    </form>
  </div>



  <div class="settings-pane" id="mailPane">
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="section" value="mail">
      <div class="settings-editor" style="margin-bottom:18px;">
        <div class="toggle-grid" style="grid-template-columns:1fr; margin-bottom:14px;">
          <label class="toggle-item"><input type="checkbox" name="smtp_enabled" <?= ($mailSettings['smtp_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> <span>启用 SMTP 邮件设置</span></label>
        </div>
        <label class="field-label">SMTP 主机</label>
        <input class="input-ui" type="text" name="smtp_host" value="<?= h($mailSettings['smtp_host'] ?? '') ?>">
        <div class="field-grid-2">
          <div>
            <label class="field-label">SMTP 端口</label>
            <input class="input-ui" type="text" name="smtp_port" value="<?= h($mailSettings['smtp_port'] ?? '465') ?>">
          </div>
          <div>
            <label class="field-label">加密方式</label>
            <select class="input-ui" name="smtp_encryption">
              <option value="ssl" <?= ($mailSettings['smtp_encryption'] ?? 'ssl') === 'ssl' ? 'selected' : '' ?>>SSL</option>
              <option value="tls" <?= ($mailSettings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS</option>
            </select>
          </div>
        </div>
        <label class="field-label">SMTP 用户名</label>
        <input class="input-ui" type="text" name="smtp_username" value="<?= h($mailSettings['smtp_username'] ?? '') ?>">
        <label class="field-label">SMTP 密码</label>
        <div class="password-row">
          <input class="input-ui" id="smtpPasswordInput" type="password" name="smtp_password" value="" placeholder="留空则保持当前密码不变">
          <button class="password-toggle" type="button" id="smtpPasswordToggle">显示密码</button>
        </div>
        <div class="mail-hint">留空不覆盖当前密码；发送测试邮件会使用当前表单里的配置。</div>
        <label class="field-label">发件邮箱</label>
        <input class="input-ui" type="email" name="smtp_from_email" id="smtpFromEmailInput" value="<?= h($mailSettings['smtp_from_email'] ?? '') ?>">
        <label class="field-label">发件名称</label>
        <input class="input-ui" type="text" name="smtp_from_name" value="<?= h($mailSettings['smtp_from_name'] ?? '') ?>">
        <label class="field-label">测试收件邮箱</label>
        <input class="input-ui" type="email" name="test_email" id="testEmailInput" value="<?= h($mailSettings['smtp_from_email'] ?? '') ?>" placeholder="填写一个用于接收测试邮件的邮箱">
        <div class="settings-actions" style="gap:10px;">
          <button class="btn secondary" type="submit" name="mail_action" value="test">发送测试邮件</button>
          <button class="btn secondary" type="submit" name="mail_action" value="save_test">保存并测试</button>
          <button class="btn primary" type="submit" name="mail_action" value="save">保存邮件设置</button>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  const tabs = Array.from(document.querySelectorAll('.settings-tab'));
  const panes = Array.from(document.querySelectorAll('.settings-pane'));
  const activePaneId = <?= json_encode($activeSettingsPane, JSON_UNESCAPED_UNICODE) ?>;

  function activatePane(target) {
    tabs.forEach(function(item){ item.classList.toggle('active', item.getAttribute('data-pane') === target); });
    panes.forEach(function(pane){ pane.classList.toggle('active', pane.id === target); });
  }

  tabs.forEach(function(tab){
    tab.addEventListener('click', function(){
      activatePane(tab.getAttribute('data-pane'));
    });
  });

  if (activePaneId) {
    activatePane(activePaneId);
  }

  const smtpPasswordInput = document.getElementById('smtpPasswordInput');
  const smtpPasswordToggle = document.getElementById('smtpPasswordToggle');
  const smtpFromEmailInput = document.getElementById('smtpFromEmailInput');
  const testEmailInput = document.getElementById('testEmailInput');

  if (smtpPasswordInput && smtpPasswordToggle) {
    smtpPasswordToggle.addEventListener('click', function(){
      const isPassword = smtpPasswordInput.type === 'password';
      smtpPasswordInput.type = isPassword ? 'text' : 'password';
      smtpPasswordToggle.textContent = isPassword ? '隐藏密码' : '显示密码';
    });
  }

  if (smtpFromEmailInput && testEmailInput) {
    smtpFromEmailInput.addEventListener('input', function(){
      if (!testEmailInput.value || testEmailInput.value === testEmailInput.defaultValue) {
        testEmailInput.value = smtpFromEmailInput.value;
      }
    });
  }
})();
</script>
<?php require __DIR__ . '/layout-bottom.php'; ?>
