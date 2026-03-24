<?php
function load_install_config(string $configFile): array {
    if (!file_exists($configFile)) {
        return [];
    }

    $config = require $configFile;
    return is_array($config) ? $config : [];
}

function export_install_config(array $data): string {
    return "<?php\nreturn " . var_export($data, true) . ";\n";
}

function save_install_config(string $configFile, callable $mutator): bool {
    $current = load_install_config($configFile);
    $updated = $mutator($current);

    if (!is_array($updated)) {
        return false;
    }

    $dir = dirname($configFile);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        return false;
    }

    $tempFile = $configFile . '.tmp';
    $bytes = file_put_contents($tempFile, export_install_config($updated), LOCK_EX);
    if ($bytes === false) {
        return false;
    }

    return rename($tempFile, $configFile);
}
