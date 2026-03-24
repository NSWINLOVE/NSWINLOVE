<?php
function upload_image(string $field, string $targetDir, string $publicPrefix): string {
    if (empty($_FILES[$field]) || !isset($_FILES[$field]['error'])) {
        return '';
    }

    $uploadError = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => '上传失败：文件超过服务器 upload_max_filesize 限制。',
            UPLOAD_ERR_FORM_SIZE => '上传失败：文件超过表单允许大小。',
            UPLOAD_ERR_PARTIAL => '上传失败：文件只上传了一部分。',
            UPLOAD_ERR_NO_FILE => '',
            UPLOAD_ERR_NO_TMP_DIR => '上传失败：服务器缺少临时目录。',
            UPLOAD_ERR_CANT_WRITE => '上传失败：服务器无法写入临时文件。',
            UPLOAD_ERR_EXTENSION => '上传失败：文件被服务器扩展中止。',
        ];
        $message = $messages[$uploadError] ?? '上传失败：未知错误。';
        if ($message === '') {
            return '';
        }
        throw new RuntimeException($message);
    }

    $tmp = $_FILES[$field]['tmp_name'];
    $name = $_FILES[$field]['name'] ?? 'file';
    $size = (int)($_FILES[$field]['size'] ?? 0);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
    $allowedMime = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/x-icon',
        'image/vnd.microsoft.icon',
        'image/bmp',
    ];
    $maxSize = 10 * 1024 * 1024;

    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('仅支持 jpg/jpeg/png/gif/webp/ico 图片上传。');
    }

    if ($size <= 0 || $size > $maxSize) {
        throw new RuntimeException('上传图片大小不能超过 10MB。');
    }

    $mime = '';
    if (function_exists('finfo_open') && defined('FILEINFO_MIME_TYPE')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
    }

    if ($mime === '' && function_exists('getimagesize')) {
        $imageInfo = @getimagesize($tmp);
        if (is_array($imageInfo) && !empty($imageInfo['mime'])) {
            $mime = (string)$imageInfo['mime'];
        }
    }

    if ($mime === '' && $ext === 'ico') {
        $mime = 'image/x-icon';
    }

    if ($mime === '' || !in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('上传文件 MIME 类型不合法，或服务器缺少必要图片识别能力。');
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new RuntimeException('上传目录创建失败。');
    }

    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = rtrim($targetDir, '/') . '/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('文件上传失败。');
    }

    return rtrim($publicPrefix, '/') . '/' . $filename;
}
