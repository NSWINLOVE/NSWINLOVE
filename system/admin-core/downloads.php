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
$downloads = site_downloads_load($configFile);
$release = site_release_load($configFile);
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'downloads';
$message = '';
$error = '';
$activeDownloadPane = 'androidPane';

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $section = trim($_POST['section'] ?? 'android');
    $activeDownloadPane = $section . 'Pane';

    try {
        if ($section === 'release') {
            $newRelease = [
                'title' => trim($_POST['release_title'] ?? '最新版本更新'),
                'content' => trim($_POST['release_content'] ?? ''),
            ];
            $saved = site_release_save($configFile, $newRelease);
            if (!$saved) {
                $error = '更新说明保存失败。';
            } else {
                $release = site_release_load($configFile);
                admin_log('update_downloads_release');
                $message = '更新说明已保存。';
            }
        } else {
            $currentItem = $downloads[$section] ?? [];
            $newItem = [
                'url' => trim($_POST[$section . '_url'] ?? '#'),
                'version' => trim($_POST[$section . '_version'] ?? 'v1.0.0'),
                'notes' => trim($_POST[$section . '_notes'] ?? ''),
                'updated_at' => trim($_POST[$section . '_updated_at'] ?? date('Y-m-d')),
                'target_range' => trim($_POST[$section . '_target_range'] ?? ''),
                'target_user' => trim($_POST[$section . '_target_user'] ?? ''),
                'package_size' => trim($_POST[$section . '_package_size'] ?? ''),
                'checksum' => trim($_POST[$section . '_checksum'] ?? ''),
                'hits' => (int)($currentItem['hits'] ?? 0),
            ];
            $downloads[$section] = $newItem;
            $saved = site_downloads_save($configFile, $downloads);
            if (!$saved) {
                $error = '平台下载配置保存失败。';
            } else {
                $downloads = site_downloads_load($configFile);
                admin_log('update_downloads', ['platform' => $section]);
                $message = '当前平台下载配置已保存。';
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>下载中心</h1><p>对应首页锚点 #downloads 和 #release，集中维护平台包与版本发布说明。</p></div></div>
<div class="panel">
<?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>

<style>
  .download-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;padding:4px;background:#f3f4f6;border:1px solid rgba(15,23,42,.06);border-radius:12px}
  .download-tab{appearance:none;border:none;background:transparent;color:#6b7280;padding:9px 14px;border-radius:10px;font:inherit;font-weight:700;font-size:13px;cursor:pointer;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
  .download-tab.active{background:#fff;color:#111827;box-shadow:inset 0 0 0 1px rgba(15,23,42,.06)}
  .download-pane{display:none}
  .download-pane.active{display:block}
  .download-editor{padding:14px;border-radius:14px;background:#f8fafc;border:1px solid rgba(15,23,42,.05)}
  .download-editor .field-label{font-size:13px}
  .download-editor .input-ui{margin-bottom:14px}
  .download-editor .textarea-ui{min-height:100px;border-radius:12px;background:#fff;border:1px solid rgba(15,23,42,.10);color:#0f172a;line-height:1.75;box-shadow:none;padding:16px}
  .download-editor .textarea-ui:focus{border-color:#1d4ed8;box-shadow:0 0 0 3px rgba(59,130,246,.14)}
  .editor-note{margin-bottom:14px;padding:12px 14px;border-radius:12px;background:#eff6ff;border:1px solid rgba(59,130,246,.14);color:#1e3a8a;font-size:13px;line-height:1.7}
  .preview-mini{margin-top:14px;padding:14px;border-radius:14px;background:#fff;border:1px solid rgba(15,23,42,.06)}
  .preview-mini-title{font-size:13px;font-weight:800;margin-bottom:10px;color:#0f172a}
  .preview-mini-grid{display:grid;gap:8px}
  .preview-mini-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:12px;background:#f8fafc;border:1px solid rgba(15,23,42,.05);font-size:12px}
  .preview-mini-item span:first-child{color:#64748b;font-weight:700}
  .preview-mini-item span:last-child{font-weight:800;color:#0f172a}
</style>

<div class="download-tabs" id="downloadTabs">
  <button class="download-tab" type="button" data-pane="androidPane">Android</button>
  <button class="download-tab" type="button" data-pane="iosPane">iOS</button>
  <button class="download-tab" type="button" data-pane="windowsPane">Windows</button>
  <button class="download-tab" type="button" data-pane="macPane">macOS</button>
  <button class="download-tab" type="button" data-pane="releasePane">更新说明</button>
</div>

<?php foreach (['android' => 'Android', 'ios' => 'iOS', 'windows' => 'Windows', 'mac' => 'macOS'] as $key => $label): ?>
  <div class="download-pane" id="<?= h($key) ?>Pane">
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="section" value="<?= h($key) ?>">
      <div class="download-editor" style="margin-bottom:18px;">
        <div class="editor-note">对应首页 <strong>#downloads</strong> 平台卡片。这里填写的版本、状态相关信息会直接影响首页下载卡展示。</div>
        <label class="field-label"><?= h($label) ?> 下载链接</label>
        <input class="input-ui" type="text" name="<?= h($key) ?>_url" value="<?= h($downloads[$key]['url'] ?? '#') ?>">

        <div class="field-grid-2">
          <div>
            <label class="field-label">版本号</label>
            <input class="input-ui" type="text" name="<?= h($key) ?>_version" value="<?= h($downloads[$key]['version'] ?? 'v1.0.0') ?>">
          </div>
          <div>
            <label class="field-label">更新时间</label>
            <input class="input-ui" type="text" name="<?= h($key) ?>_updated_at" value="<?= h($downloads[$key]['updated_at'] ?? date('Y-m-d')) ?>">
          </div>
        </div>

        <div class="field-grid-2">
          <div>
            <label class="field-label">适用系统</label>
            <input class="input-ui" type="text" name="<?= h($key) ?>_target_range" value="<?= h($downloads[$key]['target_range'] ?? '') ?>" placeholder="如：Windows 10 / 11">
          </div>
          <div>
            <label class="field-label">推荐对象</label>
            <input class="input-ui" type="text" name="<?= h($key) ?>_target_user" value="<?= h($downloads[$key]['target_user'] ?? '') ?>" placeholder="如：桌面办公环境">
          </div>
        </div>

        <div class="field-grid-2">
          <div>
            <label class="field-label">安装包大小</label>
            <input class="input-ui" type="text" name="<?= h($key) ?>_package_size" value="<?= h($downloads[$key]['package_size'] ?? '') ?>" placeholder="如：128 MB">
          </div>
          <div>
            <label class="field-label">校验信息</label>
            <input class="input-ui" type="text" name="<?= h($key) ?>_checksum" value="<?= h($downloads[$key]['checksum'] ?? '') ?>" placeholder="如：SHA256 / MD5">
          </div>
        </div>

        <label class="field-label">版本说明</label>
        <textarea class="textarea-ui" name="<?= h($key) ?>_notes" style="min-height:200px;"><?= h($downloads[$key]['notes'] ?? '') ?></textarea>
        <div class="field-help">建议填写该平台当前版本的主要说明、适用提醒或发布备注。</div>

        <div class="preview-mini">
          <div class="preview-mini-title">首页卡片预览字段</div>
          <div class="preview-mini-grid">
            <div class="preview-mini-item"><span>前台状态</span><span><?= (!empty($downloads[$key]['url'] ?? '') && ($downloads[$key]['url'] ?? '') !== '#') ? '已发布' : '待发布' ?></span></div>
            <div class="preview-mini-item"><span>显示版本</span><span><?= h($downloads[$key]['version'] ?? 'v1.0.0') ?></span></div>
            <div class="preview-mini-item"><span>适用系统</span><span><?= h($downloads[$key]['target_range'] ?? '待补充') ?></span></div>
            <div class="preview-mini-item"><span>推荐对象</span><span><?= h($downloads[$key]['target_user'] ?? '待补充') ?></span></div>
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:14px;"><button class="btn primary" type="submit">保存 <?= h($label) ?></button></div>
      </div>
    </form>
  </div>
<?php endforeach; ?>

<div class="download-pane" id="releasePane">
  <form method="post">
    <?= csrf_input() ?>
    <input type="hidden" name="section" value="release">
    <div class="download-editor" style="margin-bottom:18px;">
      <div class="editor-note">对应首页 <strong>#release</strong> 区块。建议按每行一条填写版本特性、修复项、发布时间或发版提示。</div>
      <label class="field-label">更新说明标题</label>
      <input class="input-ui" type="text" name="release_title" value="<?= h($release['title'] ?? '最新版本更新') ?>">
      <label class="field-label">更新说明内容（建议每行一条）</label>
      <textarea class="textarea-ui" name="release_content" style="min-height:320px;font-family:SFMono-Regular,Consolas,Monaco,monospace;"><?= h($release['content'] ?? '') ?></textarea>
      <div style="display:flex;justify-content:flex-end;margin-top:14px;"><button class="btn primary" type="submit">保存更新说明</button></div>
    </div>
  </form>
</div>
</div>
<script>
(function(){
  const tabs = Array.from(document.querySelectorAll('.download-tab'));
  const panes = Array.from(document.querySelectorAll('.download-pane'));
  const activePaneId = <?= json_encode($activeDownloadPane, JSON_UNESCAPED_UNICODE) ?>;

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
})();
</script>
<?php require __DIR__ . '/layout-bottom.php'; ?>
