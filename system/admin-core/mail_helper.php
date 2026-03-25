<?php
require_once __DIR__ . '/settings_helper.php';

function load_mail_config_from_file(string $configFile): array
{
    $config = file_exists($configFile) ? require $configFile : [];
    return $config['mail'] ?? [];
}

function load_mail_config_from_db(PDO $pdo): array
{
    $items = settings_get_many($pdo, [
        'smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_from_email',
        'smtp_from_name',
        'smtp_encryption',
    ]);

    return [
        'enabled' => ($items['smtp_enabled'] ?? '0') === '1',
        'host' => $items['smtp_host'] ?? '',
        'port' => $items['smtp_port'] ?? '',
        'username' => $items['smtp_username'] ?? '',
        'password' => $items['smtp_password'] ?? '',
        'from_email' => $items['smtp_from_email'] ?? '',
        'from_name' => $items['smtp_from_name'] ?? '',
        'encryption' => $items['smtp_encryption'] ?? '',
    ];
}

function load_mail_config(string $configFile, ?PDO $pdo = null): array
{
    if ($pdo instanceof PDO) {
        $dbMail = load_mail_config_from_db($pdo);
        if (
            !empty($dbMail['host']) ||
            !empty($dbMail['username']) ||
            !empty($dbMail['from_email']) ||
            !empty($dbMail['from_name']) ||
            !empty($dbMail['port']) ||
            !empty($dbMail['encryption']) ||
            !empty($dbMail['enabled'])
        ) {
            return $dbMail;
        }
    }

    return load_mail_config_from_file($configFile);
}

function validate_mail_config(array $mail): void
{
    if (empty($mail['enabled'])) {
        throw new RuntimeException('邮件功能未启用。');
    }

    $required = ['host', 'port', 'username', 'password', 'from_email', 'from_name', 'encryption'];
    foreach ($required as $key) {
        if (!isset($mail[$key]) || trim((string)$mail[$key]) === '') {
            throw new RuntimeException('邮件配置不完整：' . $key);
        }
    }
}

function send_mail(string $configFile, string $to, string $subject, string $html, string $text = '', ?PDO $pdo = null): void
{
    $mail = load_mail_config($configFile, $pdo);
    validate_mail_config($mail);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('收件邮箱格式不正确。');
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $autoloads = [
            dirname(__DIR__, 2) . '/vendor/autoload.php',
            '/usr/share/php/vendor/autoload.php',
        ];
        foreach ($autoloads as $autoload) {
            if (file_exists($autoload)) {
                require_once $autoload;
                break;
            }
        }
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        throw new RuntimeException('未找到 PHPMailer，请先安装邮件依赖。');
    }

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mailer->isSMTP();
        $mailer->Host = (string)$mail['host'];
        $mailer->Port = (int)$mail['port'];
        $mailer->SMTPAuth = true;
        $mailer->Username = (string)$mail['username'];
        $mailer->Password = (string)$mail['password'];

        $encryption = strtolower((string)$mail['encryption']);
        if ($encryption === 'ssl') {
            $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            throw new RuntimeException('不支持的邮件加密方式：' . $mail['encryption']);
        }

        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom((string)$mail['from_email'], (string)$mail['from_name']);
        $mailer->addAddress($to);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $html;
        $mailer->AltBody = $text !== '' ? $text : trim(strip_tags($html));
        $mailer->send();
    } catch (Throwable $e) {
        throw new RuntimeException('邮件发送失败：' . $e->getMessage(), 0, $e);
    }
}
