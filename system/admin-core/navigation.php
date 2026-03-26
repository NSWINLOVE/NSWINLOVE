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

$defaultNavigation = [
    ['label' => '下载中心', 'anchor' => '#downloads', 'children' => []],
    ['label' => '版本更新', 'anchor' => '#release', 'children' => []],
    ['label' => '使用说明', 'anchor' => '', 'children' => [
        ['label' => '安装教程', 'anchor' => '#guide'],
        ['label' => '常见问题', 'anchor' => '#faq'],
    ]],
];
$navigation = $config['navigation'] ?? $defaultNavigation;

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function normalize_anchor(?string $value): string {
    $value = trim((string)$value);
    if ($value === '') return '';
    if ($value[0] !== '#') $value = '#' . ltrim($value, '#');
    return preg_replace('/[^a-zA-Z0-9_-#]/', '', $value) ?: '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();
    $labels = $_POST['nav_label'] ?? [];
    $anchors = $_POST['nav_anchor'] ?? [];
    $childLabels = $_POST['child_label'] ?? [];
    $childAnchors = $_POST['child_anchor'] ?? [];
    $newNavigation = [];

    foreach ($labels as $index => $label) {
        $itemLabel = trim((string)$label);
        $itemAnchor = normalize_anchor($anchors[$index] ?? '');
        $children = [];
        foreach (($childLabels[$index] ?? []) as $childIndex => $childLabel) {
            $childLabel = trim((string)$childLabel);
            $childAnchor = normalize_anchor($childAnchors[$index][$childIndex] ?? '');
            if ($childLabel === '' && $childAnchor === '') {
                continue;
            }
            if ($childLabel === '') {
                $error = '二级导航名称不能为空。';
                break 2;
            }
            $children[] = ['label' => $childLabel, 'anchor' => $childAnchor];
        }
        if ($itemLabel === '' && $itemAnchor === '' && !$children) {
            continue;
        }
        if ($itemLabel === '') {
            $error = '一级导航名称不能为空。';
            break;
        }
        $newNavigation[] = ['label' => $itemLabel, 'anchor' => $itemAnchor, 'children' => $children];
    }

    if ($error === '') {
        $saved = save_install_config($configFile, function (array $current) use ($newNavigation) {
            $current['navigation'] = $newNavigation;
            return $current;
        });
        if (!$saved) {
            $error = '导航配置保存失败。';
        } else {
            $config = load_install_config($configFile);
            $navigation = $config['navigation'] ?? [];
            admin_log('update_site_navigation', ['count' => count($navigation)]);
            $message = '导航配置已保存。';
        }
    }
}

$navigation = array_values($navigation ?: $defaultNavigation);
for ($i = count($navigation); $i < 6; $i++) {
    $navigation[] = ['label' => '', 'anchor' => '', 'children' => []];
}

require __DIR__ . '/layout-top.php';
?>
<div class="topbar"><div class="topbar-main"><h1>导航管理</h1><p>管理首页顶部导航、右侧一级入口与二级下拉锚点。</p></div></div>
<div class="panel">
  <?php if ($message): ?><div class="alert-success"><?= h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert-error"><?= h($error) ?></div><?php endif; ?>

  <style>
    .nav-manager{display:grid;gap:16px}
    .nav-card{padding:18px;border-radius:18px;background:#f8fafc;border:1px solid rgba(15,23,42,.06)}
    .nav-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
    .nav-card-title{font-size:16px;font-weight:800;color:#0f172a}
    .nav-help{margin:0 0 12px;color:#64748b;font-size:13px;line-height:1.7}
    .sub-grid{display:grid;gap:10px;margin-top:12px}
    .sub-item{padding:12px;border-radius:14px;background:#fff;border:1px solid rgba(15,23,42,.06)}
    .muted-tip{margin-top:8px;color:#64748b;font-size:12px;line-height:1.7}
  </style>

  <form method="post">
    <?= csrf_input() ?>
    <div class="nav-manager">
      <div class="field-help" style="margin:0;">一级导航可直接绑定锚点；如果填写了二级导航，前台会自动显示下拉菜单。锚点建议填写为 <code>#downloads</code>、<code>#release</code>、<code>#guide</code>、<code>#faq</code> 这类格式。</div>
      <?php foreach ($navigation as $index => $item): ?>
      <div class="nav-card">
        <div class="nav-card-head">
          <div class="nav-card-title">一级导航 <?= $index + 1 ?></div>
        </div>
        <div class="field-grid-2">
          <div>
            <label class="field-label">导航名称</label>
            <input class="input-ui" type="text" name="nav_label[<?= $index ?>]" value="<?= h($item['label'] ?? '') ?>" placeholder="如：下载中心">
          </div>
          <div>
            <label class="field-label">一级锚点</label>
            <input class="input-ui" type="text" name="nav_anchor[<?= $index ?>]" value="<?= h($item['anchor'] ?? '') ?>" placeholder="如：#downloads">
          </div>
        </div>
        <div class="muted-tip">如果这个一级导航只作为下拉容器，可以把一级锚点留空。</div>
        <div class="sub-grid">
          <?php $children = $item['children'] ?? []; for ($child = 0; $child < 4; $child++): $childItem = $children[$child] ?? ['label' => '', 'anchor' => '']; ?>
          <div class="sub-item">
            <div class="field-grid-2">
              <div>
                <label class="field-label">二级导航名称</label>
                <input class="input-ui" type="text" name="child_label[<?= $index ?>][<?= $child ?>]" value="<?= h($childItem['label'] ?? '') ?>" placeholder="如：安装教程">
              </div>
              <div>
                <label class="field-label">二级锚点</label>
                <input class="input-ui" type="text" name="child_anchor[<?= $index ?>][<?= $child ?>]" value="<?= h($childItem['anchor'] ?? '') ?>" placeholder="如：#guide">
              </div>
            </div>
          </div>
          <?php endfor; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="settings-actions"><button class="btn primary" type="submit">保存导航配置</button></div>
    </div>
  </form>
</div>
<?php require __DIR__ . '/layout-bottom.php'; ?>
