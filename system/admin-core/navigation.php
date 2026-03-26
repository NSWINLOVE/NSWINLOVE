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
$currentSlug = trim($site['admin_slug'] ?? 'admin', '/');
$currentSlug = $currentSlug !== '' ? $currentSlug : 'admin';
$currentPage = 'navigation';
$message = '';
$error = '';
$navigation = site_navigation_load($configFile);

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function normalize_link(?string $value): string { return trim((string)$value); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $action = $_POST['nav_action'] ?? 'save_item';
    $working = site_navigation_load($configFile);

    if ($action === 'add_top') {
        $working[] = ['label' => '', 'anchor' => '', 'children' => []];
    } elseif ($action === 'remove_top') {
        $index = (int)($_POST['remove_index'] ?? -1);
        if (isset($working[$index])) {
            array_splice($working, $index, 1);
        }
    } elseif ($action === 'add_child') {
        $parent = (int)($_POST['parent_index'] ?? -1);
        if (isset($working[$parent])) {
            $working[$parent]['children'] = array_values($working[$parent]['children'] ?? []);
            $working[$parent]['children'][] = ['label' => '', 'anchor' => ''];
        }
    } elseif ($action === 'remove_child') {
        $parent = (int)($_POST['parent_index'] ?? -1);
        $child = (int)($_POST['child_index'] ?? -1);
        if (isset($working[$parent]['children'][$child])) {
            array_splice($working[$parent]['children'], $child, 1);
        }
    } elseif ($action === 'save_item') {
        $index = (int)($_POST['item_index'] ?? -1);
        if (!isset($working[$index])) {
            $error = '未找到对应导航。';
        } else {
            $label = trim((string)($_POST['nav_label'] ?? ''));
            $anchor = normalize_link($_POST['nav_anchor'] ?? '');
            if ($label === '') {
                $error = '导航名称不能为空。';
            } else {
                $children = [];
                foreach (($_POST['child_label'] ?? []) as $childIndex => $childLabel) {
                    $childLabel = trim((string)$childLabel);
                    $childAnchor = normalize_link($_POST['child_anchor'][$childIndex] ?? '');
                    if ($childLabel === '' && $childAnchor === '') {
                        continue;
                    }
                    if ($childLabel === '') {
                        $error = '二级导航名称不能为空。';
                        break;
                    }
                    $children[] = ['label' => $childLabel, 'anchor' => $childAnchor];
                }
                if ($error === '') {
                    $working[$index] = ['label' => $label, 'anchor' => $anchor, 'children' => $children];
                }
            }
        }
    }

    if ($error === '') {
        $saved = site_navigation_save($configFile, $working);
        if (!$saved) {
            $error = '导航保存失败。';
        } else {
            $config = load_install_config($configFile);
            $navigation = site_navigation_load($configFile);
            admin_log('update_site_navigation', ['count' => count($navigation), 'action' => $action]);
            $message = in_array($action, ['add_top', 'add_child', 'remove_top', 'remove_child'], true) ? '导航已自动保存。' : '导航修改已保存。';
        }
    } else {
        $navigation = $working;
    }
}

require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>导航管理</h1><p>手动维护首页导航和二级菜单，添加或删除后自动保存。</p></div></div>
<div class="panel">
  <?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>

  <style>
    .nav-settings-shell{display:grid;gap:18px}
    .nav-settings-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:18px 20px;border-radius:18px;background:#f8fafc;border:1px solid rgba(15,23,42,.06)}
    .nav-settings-title{margin:0 0 8px;font-size:18px;font-weight:800;color:#0f172a}
    .nav-settings-desc{margin:0;color:#64748b;font-size:13px;line-height:1.8;max-width:760px}
    .nav-list{display:grid;gap:14px}
    .nav-item-card{padding:18px;border-radius:18px;background:#fff;border:1px solid rgba(15,23,42,.08);box-shadow:0 10px 24px rgba(15,23,42,.04)}
    .nav-item-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
    .nav-item-title{font-size:16px;font-weight:800;color:#0f172a}
    .sub-list{display:grid;gap:10px;margin-top:12px}
    .sub-card{padding:12px;border-radius:14px;background:#f8fafc;border:1px solid rgba(15,23,42,.06)}
    .inline-actions{display:flex;gap:10px;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-top:12px}
    .inline-actions-left{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .tiny-btn{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 12px;border-radius:10px;border:1px solid rgba(15,23,42,.08);background:#fff;color:#0f172a;font-weight:700;cursor:pointer}
    .tiny-btn.danger{color:#b91c1c;border-color:rgba(185,28,28,.16);background:#fff5f5}
    .muted-empty{padding:24px;border-radius:16px;background:#f8fafc;border:1px dashed rgba(15,23,42,.10);color:#64748b;text-align:center}
    .inline-form{display:inline-flex;margin:0}
  </style>

  <div class="nav-settings-shell">
    <div class="nav-settings-head">
      <div>
        <h2 class="nav-settings-title">导航设置</h2>
        <p class="nav-settings-desc">可手动配置首页顶部导航。支持填写站内路径、锚点或完整链接；有二级导航时会自动显示下拉菜单。</p>
      </div>
      <form method="post" class="inline-form">
        <?= csrf_input() ?>
        <input type="hidden" name="nav_action" value="add_top">
        <button class="btn primary" type="submit">添加导航</button>
      </form>
    </div>

    <?php if (!$navigation): ?>
      <div class="muted-empty">当前还没有导航项，点击右上角“添加导航”开始创建。</div>
    <?php else: ?>
      <div class="nav-list">
        <?php foreach ($navigation as $index => $item): ?>
        <div class="nav-item-card">
          <div class="nav-item-head">
            <div class="nav-item-title">导航 <?= $index + 1 ?></div>
            <form method="post" class="inline-form">
              <?= csrf_input() ?>
              <input type="hidden" name="nav_action" value="remove_top">
              <input type="hidden" name="remove_index" value="<?= $index ?>">
              <button class="tiny-btn danger" type="submit">删除导航</button>
            </form>
          </div>

          <form method="post">
            <?= csrf_input() ?>
            <input type="hidden" name="nav_action" value="save_item">
            <input type="hidden" name="item_index" value="<?= $index ?>">
            <div class="field-grid-2">
              <div>
                <label class="field-label">导航名称</label>
                <input class="input-ui" type="text" name="nav_label" value="<?= h($item['label'] ?? '') ?>" placeholder="如：下载中心">
              </div>
              <div>
                <label class="field-label">链接</label>
                <input class="input-ui" type="text" name="nav_anchor" value="<?= h($item['anchor'] ?? '') ?>" placeholder="如：#downloads / /about / https://example.com">
              </div>
            </div>

            <div class="sub-list">
              <?php foreach (($item['children'] ?? []) as $childIndex => $child): ?>
              <div class="sub-card">
                <div class="inline-actions" style="margin-top:0;margin-bottom:10px;">
                  <div class="field-label" style="margin:0;">二级导航 <?= $childIndex + 1 ?></div>
                  <button class="tiny-btn danger" type="submit" name="nav_action" value="remove_child">删除二级导航</button>
                  <input type="hidden" name="parent_index" value="<?= $index ?>">
                  <input type="hidden" name="child_index" value="<?= $childIndex ?>">
                </div>
                <div class="field-grid-2">
                  <div>
                    <label class="field-label">导航名称</label>
                    <input class="input-ui" type="text" name="child_label[<?= $childIndex ?>]" value="<?= h($child['label'] ?? '') ?>" placeholder="如：安装教程">
                  </div>
                  <div>
                    <label class="field-label">链接</label>
                    <input class="input-ui" type="text" name="child_anchor[<?= $childIndex ?>]" value="<?= h($child['anchor'] ?? '') ?>" placeholder="如：#guide / /docs / https://example.com">
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="inline-actions">
              <div class="inline-actions-left">
                <button class="btn secondary" type="submit" name="nav_action" value="add_child">添加二级导航</button>
                <input type="hidden" name="parent_index" value="<?= $index ?>">
              </div>
              <button class="btn primary" type="submit" name="nav_action" value="save_item">保存导航修改</button>
            </div>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
