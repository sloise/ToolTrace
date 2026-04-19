<?php

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function tooltrace_mail_config() {
    $configPath = __DIR__ . DIRECTORY_SEPARATOR . 'mail_config.php';

    if (file_exists($configPath)) {
        $cfg = require $configPath;
        if (is_array($cfg)) {
            foreach (['username', 'password', 'from_email', 'from_name', 'contact_inbox'] as $k) {
                if (isset($cfg[$k]) && is_string($cfg[$k])) {
                    $cfg[$k] = trim($cfg[$k]);
                }
            }
            return $cfg;
        }
    }

    return [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls',
        'username' => '',
        'password' => '',
        'from_email' => 'no-reply@tooltrace.local',
        'from_name' => 'TOOLTRACE',
        /** Public site URL (no trailing slash) so HTML emails can load images, e.g. http://localhost/ToolTrace */
        'public_base_url' => '',
        /** Inbox for landing-page contact form (defaults to from_email if empty) */
        'contact_inbox' => '',
    ];
}

/** Where contact form submissions are delivered (mail_config contact_inbox or from_email). */
function tooltrace_contact_inbox_email(): string
{
    $config = tooltrace_mail_config();
    $to = isset($config['contact_inbox']) ? trim((string) $config['contact_inbox']) : '';
    if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return $to;
    }
    $from = trim((string) ($config['from_email'] ?? ''));
    return filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : '';
}

/** Base URL for embedded email images (logo). Empty = logo falls back to text in templates. */
function tooltrace_mail_public_base_url(): string
{
    $config = tooltrace_mail_config();
    $u = isset($config['public_base_url']) ? trim((string) $config['public_base_url']) : '';
    return $u === '' ? '' : rtrim($u, '/');
}

/**
 * Header block for HTML emails: logo image if public_base_url is set, else styled text.
 */
function tooltrace_mail_brand_header_html(): string
{
    $base = tooltrace_mail_public_base_url();
    if ($base !== '') {
        $src = $base . '/assets/images/tooltracelogo.png';
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
            . '" alt="ToolTrace" width="200" style="display:block;max-width:200px;height:auto;border:0;" />';
    }
    return '<span style="font-size:22px;font-weight:800;letter-spacing:0.4px;">TOOL<span style="color:#FFFFFF;">TRACE</span></span>';
}

function tooltrace_send_mail($to, $subject, $textBody, $htmlBody = null) {
    $config = tooltrace_mail_config();

    if (empty($config['username']) || empty($config['password'])) {
        return [false, 'Missing SMTP credentials. Set username/password in mail_config.php'];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = (string)$config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = (string)$config['username'];
        $mail->Password = (string)$config['password'];
        $mail->Port = (int)$config['port'];

        $secure = strtolower((string)($config['secure'] ?? 'tls'));
        if ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom((string)$config['from_email'], (string)$config['from_name']);
        $mail->addAddress((string)$to);
        $mail->Subject = (string)$subject;

        if ($htmlBody !== null && trim($htmlBody) !== '') {
            $mail->isHTML(true);
            $mail->Body = (string)$htmlBody;
            $mail->AltBody = (string)$textBody;
        } else {
            $mail->isHTML(false);
            $mail->Body = (string)$textBody;
        }

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        return [false, $mail->ErrorInfo ?: $e->getMessage()];
    }
}

// Backward-compatible aliases for older calls.
if (!function_exists('equilink_mail_config')) {
    function equilink_mail_config() {
        return tooltrace_mail_config();
    }
}
if (!function_exists('equilink_send_mail')) {
    function equilink_send_mail($to, $subject, $textBody, $htmlBody = null) {
        return tooltrace_send_mail($to, $subject, $textBody, $htmlBody);
    }
}

