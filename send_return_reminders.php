<?php
/**
 * TOOLTRACE - Return Reminder Sender (PDO-backed)
 *
 * Run daily via Task Scheduler / cron:
 *   php C:\xampp\htdocs\ToolTrace\send_return_reminders.php
 */

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . DIRECTORY_SEPARATOR . 'mailer.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.php';

function buildReminderHtml($title, $intro, $requestId, $item, $returnDate, $note) {
    $titleSafe      = htmlspecialchars($title,     ENT_QUOTES, 'UTF-8');
    $introSafe      = nl2br(htmlspecialchars($intro,      ENT_QUOTES, 'UTF-8'));
    $requestIdSafe  = htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8');
    $itemSafe       = htmlspecialchars($item,      ENT_QUOTES, 'UTF-8');
    $returnDateSafe = htmlspecialchars($returnDate,ENT_QUOTES, 'UTF-8');
    $noteSafe       = nl2br(htmlspecialchars($note, ENT_QUOTES, 'UTF-8'));

    return '<!doctype html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Segoe UI,Arial,sans-serif;color:#111111;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#ffffff;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;border:1px solid #111111;background:#ffffff;">
          <tr>
            <td style="background:#f1c40f;color:#111111;padding:18px 22px;text-align:center;">
              ' . tooltrace_mail_brand_header_html() . '
            </td>
          </tr>
          <tr>
            <td style="padding:20px 22px 8px 22px;">
              <h2 style="margin:0 0 10px 0;color:#111111;font-size:22px;line-height:1.25;">' . $titleSafe . '</h2>
              <p style="margin:0 0 14px 0;color:#111111;font-size:14px;line-height:1.6;">' . $introSafe . '</p>
            </td>
          </tr>
          <tr>
            <td style="padding:0 22px 10px 22px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                <tr>
                  <td style="width:34%;padding:10px;border:1px solid #111111;background:#f1c40f;font-weight:700;">Request ID</td>
                  <td style="padding:10px;border:1px solid #111111;background:#ffffff;">' . $requestIdSafe . '</td>
                </tr>
                <tr>
                  <td style="width:34%;padding:10px;border:1px solid #111111;background:#f1c40f;font-weight:700;">Item</td>
                  <td style="padding:10px;border:1px solid #111111;background:#ffffff;">' . $itemSafe . '</td>
                </tr>
                <tr>
                  <td style="width:34%;padding:10px;border:1px solid #111111;background:#f1c40f;font-weight:700;">Return Date</td>
                  <td style="padding:10px;border:1px solid #111111;background:#ffffff;">' . $returnDateSafe . '</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 22px 22px 22px;">
              <p style="margin:0;color:#111111;font-size:14px;line-height:1.6;">' . $noteSafe . '</p>
            </td>
          </tr>
          <tr>
            <td style="background:#111111;color:#ffffff;padding:12px 22px;font-size:12px;line-height:1.5;">
              TOOLTRACE Automated Reminder
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

// ── Load active/overdue borrows from DB ───────────────────────────────────────
$pdo = db();

$stmt = $pdo->query("
    SELECT
        bt.transaction_id   AS request_id,
        bt.due_date         AS return_date,
        bt.status,
        bt.purpose,
        e.name              AS item,
        o.org_name          AS organization_name,
        o.org_email         AS organization_email
    FROM borrow_transactions bt
    LEFT JOIN equipment     e ON bt.equipment_id = e.equipment_id
    LEFT JOIN organizations o ON bt.org_id       = o.org_id
    WHERE bt.status IN ('Borrowed', 'Overdue')
      AND bt.due_date IS NOT NULL
    ORDER BY bt.due_date ASC
");
$rows = $stmt->fetchAll();

$logPath   = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'reminder_mail_log.txt';
$today     = new DateTimeImmutable('today');
$sentCount = 0;
$processed = 0;

foreach ($rows as $request) {
    $processed++;

    $orgEmail      = trim((string) ($request['organization_email'] ?? ''));
    $orgName       = trim((string) ($request['organization_name'] ?? 'Organization'));
    $item          = trim((string) ($request['item']              ?? 'Borrowed equipment'));
    $requestId     = trim((string) ($request['request_id']        ?? 'N/A'));
    $returnDateRaw = trim((string) ($request['return_date']       ?? ''));

    if ($returnDateRaw === '' || !filter_var($orgEmail, FILTER_VALIDATE_EMAIL)) {
        continue;
    }

    try {
        $returnDate = new DateTimeImmutable($returnDateRaw);
    } catch (Exception $e) {
        continue;
    }

    $daysUntilDue = (int) $today->diff($returnDate)->format('%r%a');

    // Only send on due date (0 days) or 1 day before
    if ($daysUntilDue > 1 || $daysUntilDue < 0) {
        continue;
    }

    // Check if reminder was already sent today (via log file)
    $logKey = $requestId . '|' . $today->format('Y-m-d');
    $alreadySent = false;
    if (file_exists($logPath)) {
        $alreadySent = str_contains(file_get_contents($logPath), $logKey);
    }
    if ($alreadySent) {
        continue;
    }

    $isOneDay = ($daysUntilDue === 1);
    $subject  = $isOneDay
        ? "TOOLTRACE Reminder: Return due tomorrow ({$requestId})"
        : "TOOLTRACE Reminder: Return due today ({$requestId})";

    $note = $isOneDay
        ? 'Your return date is tomorrow. Please prepare to return the equipment on time.'
        : 'Your return date is today. Please return the equipment as soon as possible.';

    $html = buildReminderHtml(
        $isOneDay ? 'Return Due Tomorrow' : 'Return Due Today',
        "Hello {$orgName},\nThis is a reminder about borrowed equipment.",
        $requestId,
        $item,
        $returnDate->format('Y-m-d'),
        $note
    );

    $textBody  = "Hello {$orgName},\n\n";
    $textBody .= "Request ID: {$requestId}\nItem: {$item}\nReturn Date: " . $returnDate->format('Y-m-d') . "\n\n";
    $textBody .= $note . "\n\nThank you,\nTOOLTRACE Team";

    [$sent, $mailError] = tooltrace_send_mail($orgEmail, $subject, $textBody, $html);

    $logLine = '[' . date('Y-m-d H:i:s') . "] {$logKey} | {$orgEmail} | {$subject} | " . ($sent ? 'SENT' : 'FAILED');
    if (!$sent && $mailError) {
        $logLine .= " | {$mailError}";
    }
    $logLine .= PHP_EOL;

    if (!is_dir(dirname($logPath))) {
        mkdir(dirname($logPath), 0755, true);
    }
    file_put_contents($logPath, $logLine, FILE_APPEND);

    if ($sent) {
        $sentCount++;
    }
}

echo "Processed: {$processed}" . PHP_EOL;
echo "Reminders sent: {$sentCount}" . PHP_EOL;
echo "Log file: {$logPath}" . PHP_EOL;