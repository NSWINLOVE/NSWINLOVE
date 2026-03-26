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
$content = site_content_load($configFile);
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'content';
$message = '';
$error = '';
$activeContentPane = 'requirementsPane';

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $section = trim($_POST['section'] ?? 'requirements');
    $activeContentPane = $section . 'Pane';

    $newContent = [];
    if ($section === 'requirements') {
        $newContent = [
            'requirements_title' => trim($_POST['requirements_title'] ?? '系统要求'),
            'requirements' => trim($_POST['requirements'] ?? ''),
        ];
    } elseif ($section === 'guide') {
        $newContent = [
            'guide_title' => trim($_POST['guide_title'] ?? '安装教程'),
            'guide' => trim($_POST['guide'] ?? ''),
        ];
    } elseif ($section === 'faq') {
        $newContent = [
            'faq_title' => trim($_POST['faq_title'] ?? '常见问题'),
            'faq' => trim($_POST['faq'] ?? ''),
        ];
    }

$content = array_merge($content, $newContent);
    unset(
        $content['showcase_title'],
        $content['showcase_text'],
        $content['showcase_main'],
        $content['showcase_1'],
        $content['showcase_2'],
        $content['showcase_3']
    );
    $saved = site_content_save($configFile, $content);
    if (!$saved) {
        $error = '内容保存失败。';
    } else {
        $config = load_install_config($configFile);
        $content = site_content_load($configFile);
        admin_log('update_content', ['section' => $section]);
        $message = '当前内容已保存。';
    }
}
require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>页面内容</h1><p>对应首页锚点 #guide 与 #faq，同时维护系统要求辅助区块。</p></div></div>
<div class="panel">
<?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>

  <style>
    .content-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;padding:4px;background:#f3f4f6;border:1px solid rgba(15,23,42,.06);border-radius:12px}
    .content-tab{appearance:none;border:none;background:transparent;color:#6b7280;padding:9px 14px;border-radius:10px;font:inherit;font-weight:700;font-size:13px;cursor:pointer;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
    .content-tab.active{background:#fff;color:#111827;box-shadow:inset 0 0 0 1px rgba(15,23,42,.06)}
    .content-pane{display:none}
    .content-pane.active{display:block}
    .content-editor{padding:14px;border-radius:14px;background:#f8fafc;border:1px solid rgba(15,23,42,.05)}
    .content-editor .field-label{font-size:13px}
    .content-editor .input-ui{margin-bottom:14px}
    .content-editor .textarea-ui{min-height:220px;border-radius:12px;background:#fff;border:1px solid rgba(15,23,42,.10);color:#0f172a;font-family:SFMono-Regular,Consolas,Monaco,monospace;line-height:1.75;box-shadow:none;padding:16px}
    .content-editor .textarea-ui:focus{border-color:#1d4ed8;box-shadow:0 0 0 3px rgba(59,130,246,.14)}
    .editor-note{margin-bottom:14px;padding:12px 14px;border-radius:12px;background:#eff6ff;border:1px solid rgba(59,130,246,.14);color:#1e3a8a;font-size:13px;line-height:1.7}
  </style>

  <div class="content-tabs" id="contentTabs">
    <button class="content-tab" type="button" data-pane="requirementsPane">系统要求</button>
    <button class="content-tab" type="button" data-pane="guidePane">安装教程</button>
    <button class="content-tab" type="button" data-pane="faqPane">常见问题</button>
  </div>

  <div class="content-pane" id="requirementsPane">
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="section" value="requirements">
      <div class="content-editor" style="margin-bottom:18px;">
        <div class="editor-note">这是首页的辅助内容区，不单独对应锚点，但会展示在下载页下方作为系统要求说明。</div>
        <label class="field-label">系统要求标题</label>
        <input class="input-ui" type="text" name="requirements_title" value="<?= h($content['requirements_title'] ?? '系统要求') ?>">
        <label class="field-label">系统要求内容（支持直接编写代码或每行一条）</label>
        <textarea class="textarea-ui" name="requirements"><?= h($content['requirements'] ?? '') ?></textarea>
        <div style="display:flex;justify-content:flex-end;margin-top:14px;"><button class="btn primary" type="submit">保存系统要求</button></div>
      </div>
    </form>
  </div>

  <div class="content-pane" id="guidePane">
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="section" value="guide">
      <div class="content-editor" style="margin-bottom:18px;">
        <div class="editor-note">对应首页 <strong>#guide</strong> 区块。建议每行一条步骤，前台会自动拆成步骤列表。</div>
        <label class="field-label">安装教程标题</label>
        <input class="input-ui" type="text" name="guide_title" value="<?= h($content['guide_title'] ?? '安装教程') ?>">
        <label class="field-label">安装教程内容（支持直接编写代码或每行一步）</label>
        <textarea class="textarea-ui" name="guide"><?= h($content['guide'] ?? '') ?></textarea>
        <div style="display:flex;justify-content:flex-end;margin-top:14px;"><button class="btn primary" type="submit">保存安装教程</button></div>
      </div>
    </form>
  </div>

  <div class="content-pane" id="faqPane">
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="section" value="faq">
      <div class="content-editor" style="margin-bottom:18px;">
        <div class="editor-note">对应首页 <strong>#faq</strong> 区块。请按 <code>问题 => 答案</code> 一行一条填写。</div>
        <label class="field-label">常见问题标题</label>
        <input class="input-ui" type="text" name="faq_title" value="<?= h($content['faq_title'] ?? '常见问题') ?>">
        <label class="field-label">常见问题内容（按“问题 => 答案”分行）</label>
        <textarea class="textarea-ui" name="faq"><?= h($content['faq'] ?? '') ?></textarea>
        <div style="display:flex;justify-content:flex-end;margin-top:14px;"><button class="btn primary" type="submit">保存常见问题</button></div>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  const tabs = Array.from(document.querySelectorAll('.content-tab'));
  const panes = Array.from(document.querySelectorAll('.content-pane'));
  const activePaneId = <?= json_encode($activeContentPane, JSON_UNESCAPED_UNICODE) ?>;

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
