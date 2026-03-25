<?php
if (!function_exists('site_footer_render')) {
    function site_footer_render(array $site = []): void
    {
        $footerCode = trim((string)($site['footer_code'] ?? ''));
        if ($footerCode !== '') {
            echo $footerCode;
            return;
        }

        $footerText = trim((string)($site['footer_text'] ?? ''));
        if ($footerText === '') {
            $footerText = '© ' . date('Y') . ' ' . (trim((string)($site['title'] ?? '下载站')) ?: '下载站') . ' · 保留所有权利';
        }
        ?>
        <footer class="footer"><?= htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8') ?></footer>
        <?php
    }
}
