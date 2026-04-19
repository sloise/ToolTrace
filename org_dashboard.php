<?php
/**
 * Organization dashboard — stats and pending requests from data/borrow_requests.json.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/require_org_session.php';
require_once __DIR__ . '/includes/borrow_requests_store.php';

$orgEmail = (string) $_SESSION['organization_email'];
$orgName  = (string) ($_SESSION['organization_name'] ?? $_SESSION['user_name'] ?? 'Organization');

$requestsAll = tooltrace_borrow_requests_for_org($orgEmail);
$today = new DateTimeImmutable('today');

$open        = 0;
$dueSoon     = 0;
$overdue     = 0;
$returnedTotal = 0;

foreach ($requestsAll as $r) {
    $ap = tooltrace_borrow_request_approval_status($r);
    if ($ap === 'rejected') continue;
    if (!empty($r['returned'])) { $returnedTotal++; continue; }
    if ($ap === 'pending') continue;

    if (!tooltrace_borrow_is_active($r)) {
        continue;
    }
    $open++;
    $rd = isset($r['return_date']) ? trim((string) $r['return_date']) : '';
    if ($rd !== '') {
        try {
            $ret  = new DateTimeImmutable($rd);
            $days = (int) $today->diff($ret)->format('%r%a');
            if ($days < 0) {
                $overdue++;
            } elseif ($days <= 3) {
                $dueSoon++;
            }
        } catch (Exception $e) {}
    }
}

$stats = [
    'open'     => $open,
    'overdue'  => $overdue,
    'due_soon' => $dueSoon,
];

// Build most-borrowed tallies across all non-rejected, non-returned requests
$borrowCounts = [];
foreach ($requestsAll as $r) {
    $ap = tooltrace_borrow_request_approval_status($r);
    if ($ap === 'rejected') continue;
    $items = (!empty($r['items']) && is_array($r['items'])) ? $r['items'] : [];
    if ($items === []) {
        $label = tooltrace_borrow_item_label($r);
        $borrowCounts[$label] = ($borrowCounts[$label] ?? 0) + 1;
    } else {
        foreach ($items as $it) {
            if (!is_array($it)) continue;
            $nm = isset($it['name']) ? trim((string) $it['name']) : '';
            if ($nm === '') continue;
            $borrowCounts[$nm] = ($borrowCounts[$nm] ?? 0) + (int) ($it['qty'] ?? 1);
        }
    }
}
arsort($borrowCounts);
$mostBorrowed = array_slice($borrowCounts, 0, 6, true);
$maxCount     = $mostBorrowed ? max($mostBorrowed) : 1;

// Build pending-requests table rows (open + pending, latest first, max 15)
usort($requestsAll, static fn($a, $b) =>
    (strtotime((string) ($b['date_requested'] ?? '')) ?: 0) <=>
    (strtotime((string) ($a['date_requested'] ?? '')) ?: 0)
);

$requests = [];
$grouped  = [];
foreach (array_slice($requestsAll, 0, 50) as $r) {
    $ap = tooltrace_borrow_request_approval_status($r);
    if ($ap === 'rejected' || !empty($r['returned'])) continue;

    $requestId = (string) ($r['request_id'] ?? $r['transaction_id'] ?? '');
    if ($requestId === '') continue;

    if (!isset($grouped[$requestId])) {
        $grouped[$requestId] = [
            'id'          => $requestId,
            'items'       => [],
            'status'      => '',
            'approved_by' => '',
            'due_date'    => '',
            '_any_borrowed' => false,
            '_all_returned' => true,
        ];
    }

    $label = '';
    if (!empty($r['equipment_name'])) {
        $label = (string) $r['equipment_name'];
    }
    if ($label === '') {
        $label = tooltrace_borrow_item_label($r);
    }
    if ($label !== '' && !in_array($label, $grouped[$requestId]['items'], true)) {
        $grouped[$requestId]['items'][] = $label;
    }

    $rowStatus = strtolower(trim((string) ($r['status'] ?? '')));
    if ($rowStatus === 'borrowed') {
        $grouped[$requestId]['_any_borrowed'] = true;
    }
    if ($rowStatus !== 'returned') {
        $grouped[$requestId]['_all_returned'] = false;
    }

    $rd = isset($r['return_date']) ? trim((string) $r['return_date']) : '';
    if ($ap === 'pending') {
        $status = 'Pending';
    } else {
        $status = tooltrace_borrow_request_display_status($r);
    }

    $approvedBy = (string) ($r['approved_by'] ?? '');
    $dueDate = isset($r['due_date']) ? trim((string) $r['due_date']) : '';

    if (!empty($grouped[$requestId]['_all_returned'])) {
        $status = 'Returned';
    } elseif (!empty($grouped[$requestId]['_any_borrowed'])) {
        $status = 'Picked Up';
    }

    $grouped[$requestId]['status']      = $status;
    $grouped[$requestId]['approved_by'] = $approvedBy;
    $grouped[$requestId]['due_date']    = $dueDate;
}

foreach ($grouped as $g) {
    $requests[] = [
        'id'          => $g['id'],
        'item'        => implode(', ', $g['items']),
        'status'      => $g['status'],
        'approved_by' => $g['approved_by'],
        'due_date'    => $g['due_date'],
    ];
    if (count($requests) >= 15) break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Organization Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2c3e50;
            --bg-gray: #f4f7f6;
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background: var(--bg-gray); }

        .welcome-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin: 0 0 14px 0;
        }
        .welcome-text h1 {
            font-weight: 700;
            font-size: 2.2rem;
            margin: 0;
            line-height: 1.1;
            color: var(--primary-blue);
            letter-spacing: -0.02em;
        }
        .welcome-text p {
            margin: 8px 0 0 0;
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.4;
        }

        /* Stat cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: white;
            padding: 20px 24px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .stat-card h3 {
            font-size: 11px;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin: 0 0 8px;
        }
        .stat-card .val { font-size: 30px; font-weight: 700; color: var(--primary-blue); }
        .stat-card .val.red    { color: #e74c3c; }
        .stat-card .val.amber  { color: #f39c12; }

        /* Table card */
        .table-card {
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            margin-bottom: 24px;
        }
        .table-card h2 { font-size: 16px; font-weight: 600; margin: 0 0 16px; }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            color: #95a5a6;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        td { padding: 12px 0; border-bottom: 1px solid #f9f9f9; font-size: 14px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        /* Status badges */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 5px; font-size: 11px; font-weight: 600; }
        .badge-pending  { background: #fef9ec; color: #d4830f; }
        .badge-approved { background: #ebf5fb; color: #1a6fa5; }
        .badge-overdue  { background: #fdedec; color: #c0392b; }

        /* Most borrowed */
        .bar-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 32px; }
        .bar-row { display: flex; flex-direction: column; gap: 5px; }
        .bar-meta { display: flex; justify-content: space-between; font-size: 12px; color: #7f8c8d; }
        .bar-track { height: 6px; background: #ecf0f1; border-radius: 3px; overflow: hidden; }
        .bar-fill  { height: 100%; background: #2980b9; border-radius: 3px; }

        .empty-hint { color: #95a5a6; font-size: 14px; padding: 20px 0; }

        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .bar-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-wrapper">

    <section class="welcome-section">
        <div class="welcome-text">
            <h1>Welcome, <?php echo htmlspecialchars($orgName); ?>!</h1>
            <p>Here is your borrowing activity for <?php echo htmlspecialchars($orgName); ?>.</p>
        </div>
    </section>

    <!-- Summary stats -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <h3>Open borrows</h3>
            <div class="val"><?php echo (int) $stats['open']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Overdue</h3>
            <div class="val red"><?php echo (int) $stats['overdue']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Due within 3 days</h3>
            <div class="val amber"><?php echo (int) $stats['due_soon']; ?></div>
        </div>
    </div>

    <!-- Pending requests table -->
    <div class="table-card">
        <h2>Your pending requests</h2>
        <?php if ($requests === []): ?>
            <p class="empty-hint">No open requests right now. Browse the catalog to submit one.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:22%">Request ID</th>
                    <th>Item</th>
                    <th style="width:18%">Due Date</th>
                    <th style="width:18%">Approved By</th>
                    <th style="width:22%">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r):
                    $badgeClass = match($r['status']) {
                        'Overdue'  => 'badge-overdue',
                        'Approved', 'Picked Up', 'Returned' => 'badge-approved',
                        default    => 'badge-pending',
                    };
                    $due = !empty($r['due_date']) ? date('m/d/Y', strtotime($r['due_date'])) : '—';
                    $appr = !empty($r['approved_by']) ? (string) $r['approved_by'] : '—';
                ?>
                <tr>
                    <td style="font-family:monospace; color:#7f8c8d;">
                        <strong><?php echo htmlspecialchars($r['id'] !== '' ? $r['id'] : '—'); ?></strong>
                    </td>
                    <td><strong><?php echo htmlspecialchars($r['item']); ?></strong></td>
                    <td><?php echo htmlspecialchars($due, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($appr, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                        <?php if ($r['status'] === 'Approved'): ?>
                            <div style="font-size:12px; color:#2c3e50; margin-top:6px; font-weight:600;">
                                You may now pick up the equipment and present the QR code to the staff.
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Most borrowed equipment -->
    <?php if ($mostBorrowed !== []): ?>
    <div class="table-card">
        <h2>Most borrowed equipment</h2>
        <div class="bar-grid">
            <?php foreach ($mostBorrowed as $itemName => $count):
                $pct = $maxCount > 0 ? round(($count / $maxCount) * 100) : 0;
            ?>
            <div class="bar-row">
                <div class="bar-meta">
                    <span><?php echo htmlspecialchars($itemName); ?></span>
                    <span><?php echo (int) $count; ?>x</span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?php echo $pct; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

</body>
</html>