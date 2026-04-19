<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'mailer.php';

$to = '';
$subject = 'TOOLTRACE Test Email';
$message = "Hello,\n\nThis is a test email from TOOLTRACE PHPMailer setup.\n\nIf you received this, your SMTP configuration works.\n";
$status = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim((string)($_POST['to'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? 'TOOLTRACE Test Email'));
    $message = trim((string)($_POST['message'] ?? ''));

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $status = 'error';
        $error = 'Please enter a valid recipient email.';
    } elseif ($subject === '' || $message === '') {
        $status = 'error';
        $error = 'Subject and message are required.';
    } else {
        [$ok, $mailError] = tooltrace_send_mail($to, $subject, $message);
        if ($ok) {
            $status = 'success';
        } else {
            $status = 'error';
            $error = $mailError ?: 'Unknown mailer error.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOOLTRACE | Test Mail</title>
    <style>
        :root { --primary: #2c3e50; --accent: #f1c40f; --bg: #f4f7f6; }
        body { margin: 0; background: var(--bg); font-family: 'Segoe UI', sans-serif; color: var(--primary); }
        .wrap { max-width: 760px; margin: 40px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.08); }
        h1 { margin: 0 0 16px 0; }
        p { margin-top: 0; color: #667; }
        .row { margin-bottom: 14px; }
        label { display: block; font-size: 13px; margin-bottom: 6px; font-weight: 600; }
        input, textarea { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 8px; font: inherit; }
        textarea { min-height: 140px; resize: vertical; }
        button { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; }
        button:hover { opacity: 0.95; }
        .msg { margin-bottom: 14px; border-radius: 8px; padding: 10px 12px; font-size: 14px; }
        .ok { background: #e8f5e9; color: #2e7d32; }
        .err { background: #ffebee; color: #c62828; }
        .hint { font-size: 13px; color: #666; margin-top: 14px; }
        code { background: #f1f1f1; padding: 2px 4px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Test Mail Sender</h1>
        <p>Use this page to verify your PHPMailer + Gmail SMTP configuration.</p>

        <?php if ($status === 'success'): ?>
            <div class="msg ok">Email sent successfully to <strong><?php echo htmlspecialchars($to); ?></strong>.</div>
        <?php elseif ($status === 'error'): ?>
            <div class="msg err">Failed to send email: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <label for="to">Recipient Email</label>
                <input type="email" id="to" name="to" required value="<?php echo htmlspecialchars($to); ?>" placeholder="student@school.edu">
            </div>

            <div class="row">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required value="<?php echo htmlspecialchars($subject); ?>">
            </div>

            <div class="row">
                <label for="message">Message</label>
                <textarea id="message" name="message" required><?php echo htmlspecialchars($message); ?></textarea>
            </div>

            <button type="submit">Send Test Email</button>
        </form>

        <p class="hint">Make sure your <code>mail_config.php</code> exists and has valid Gmail app password credentials.</p>
    </div>
</body>
</html>

