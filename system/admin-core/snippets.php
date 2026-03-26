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
$site = site_meta_load($configFile);
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'snippets';
$message = '';
$error = '';
$snippets = site_snippets_load($configFile);

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $action = $_POST['snippet_action'] ?? 'save';
    $working = site_snippets_load($configFile);

    if ($action === 'add') {
        $key = trim((string)($_POST['snippet_key'] ?? ''));
        $name = trim((string)($_POST['snippet_name'] ?? ''));
        $code = (string)($_POST['snippet_code'] ?? '');
        if ($key === '' || $name === '') {
            $error = '代码标识和名称不能为空。';
        } else {
            $working[] = ['key' => $key, 'name' => $name, 'code' => $code];
        }
    } elseif ($action === 'remove') {
        $index = (int)($_POST['remove_index'] ?? -1);
        if (isset($working[$index])) {
            array_splice($working, $index, 1);
        }
    } elseif ($action === 'save') {
        $index = (int)($_POST['item_index'] ?? -1);
        if (!isset($working[$index])) {
            $error = '未找到对应代码片段。';
        } else {
            $key = trim((string)($_POST['snippet_key'] ?? ''));
            $name = trim((string)($_POST['snippet_name'] ?? ''));
            $code = (string)($_POST['snippet_code'] ?? '');
            if ($key === '' || $name === '') {
                $error = '代码标识和名称不能为空。';
            } else {
                $working[$index] = ['key' => $key, 'name' => $name, 'code' => $code];
            }
        }
    }

    if ($error === '') {
        $saved = site_snippets_save($configFile, $working);
        if (!$saved) {
            $error = '代码片段保存失败。';
        } else {
            $snippets = site_snippets_load($configFile);
            admin_log('update_site_snippets', ['count' => count($snippets), 'action' => $action]);
            $message = in_array($action, ['add', 'remove'], true) ? '代码片段已自动保存。' : '代码片段已保存。';
        }
    } else {
        $snippets = $working;
    }
}

require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>代码片段</h1><p>统一维护可调用代码，内容区只允许使用 {{code:标识}} 形式调用。</p></div></div>
<div class="panel">
  <?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>

  <style>
    .snippet-shell{display:grid;gap:18px}
    .snippet-head{padding:18px;border-radius:18px;background:#f8fafc;border:1px solid rgba(15,23,42,.06)}
    .snippet-list{display:grid;gap:14px}
    .snippet-card{padding:18px;border-radius:18px;background:#fff;border:1px solid rgba(15,23,42,.08);box-shadow:0 10px 24px rgba(15,23,42,.04)}
    .snippet-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
    .tiny-btn{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 12px;border-radius:10px;border:1px solid rgba(15,23,42,.08);background:#fff;color:#0f172a;font-weight:700;cursor:pointer}
    .tiny-btn.danger{color:#b91c1c;border-color:rgba(185,28,28,.16);background:#fff5f5}
    .muted-empty{padding:24px;border-radius:16px;background:#f8fafc;border:1px dashed rgba(15,23,42,.10);color:#64748b;text-align:center}
  </style>

  <div class="snippet-shell">
    <div class="snippet-head">
      <div class="field-help" style="margin:0;">先在这里写代码片段并保存，再到页面内容中通过 <code>{{code:标识}}</code> 调用。内容区不建议直接写整段自定义代码。</div>
    </div>

    <form method="post" class="snippet-card">
      <?= csrf_input() ?>
      <input type="hidden" name="snippet_action" value="add">
      <div class="snippet-card-head"><div style="font-size:16px;font-weight:800;color:#0f172a;">新增代码片段</div></div>
      <div class="field-grid-2">
        <div>
          <label class="field-label">代码标识</label>
          <input class="input-ui" type="text" name="snippet_key" placeholder="如：footer_links">
        </div>
        <div>
          <label class="field-label">名称</label>
          <input class="input-ui" type="text" name="snippet_name" placeholder="如：页脚快捷链接">
        </div>
      </div>
      <div style="margin-top:12px;">
        <label class="field-label">代码内容</label>
        <textarea class="textarea-ui" name="snippet_code" style="min-height:180px;"></textarea>
      </div>
      <div class="settings-actions"><button class="btn primary" type="submit">添加代码片段</button></div>
    </form>

    <?php if (!$snippets): ?><div class="muted-empty">当前还没有代码片段，先新增一条后再在内容里调用。</div><?php endif; ?>
    <div class="snippet-list">
      <?php foreach ($snippets as $index => $item): ?>
      <div class="snippet-card">
        <div class="snippet-card-head">
          <div style="font-size:16px;font-weight:800;color:#0f172a;"><?= h($item['name'] ?? '未命名代码片段') ?> <span style="font-size:12px;color:#64748b;">{{code:<?= h($item['key'] ?? '') ?>}}</span></div>
          <form method="post" style="margin:0;">
            <?= csrf_input() ?>
            <input type="hidden" name="snippet_action" value="remove">
            <input type="hidden" name="remove_index" value="<?= $index ?>">
            <button class="tiny-btn danger" type="submit">删除</button>
          </form>
        </div>
        <form method="post">
          <?= csrf_input() ?>
          <input type="hidden" name="snippet_action" value="save">
          <input type="hidden" name="item_index" value="<?= $index ?>">
          <div class="field-grid-2">
            <div>
              <label class="field-label">代码标识</label>
              <input class="input-ui" type="text" name="snippet_key" value="<?= h($item['key'] ?? '') ?>">
            </div>
            <div>
              <label class="field-label">名称</label>
              <input class="input-ui" type="text" name="snippet_name" value="<?= h($item['name'] ?? '') ?>">
            </div>
          </div>
          <div style="margin-top:12px;">
            <label class="field-label">代码内容</label>
            <textarea class="textarea-ui" name="snippet_code" style="min-height:180px;"><?= h($item['code'] ?? '') ?></textarea>
          </div>
          <div class="settings-actions"><button class="btn primary" type="submit">保存代码片段</button></div>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
