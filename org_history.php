<?php
/**
 * ToolTrace - Borrowing History (from data/borrow_requests.json for this organization)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/require_org_session.php';
require_once __DIR__ . '/includes/borrow_requests_store.php';

$orgEmail = (string) $_SESSION['organization_email'];
$today = new DateTimeImmutable('today');

$history = [];
foreach (tooltrace_borrow_requests_for_org($orgEmail) as $r) {
    $ap = tooltrace_borrow_request_approval_status($r);
    if ($ap === 'rejected') continue;

    $id         = isset($r['request_id']) ? (string) $r['request_id'] : 'REQ';
    $item       = tooltrace_borrow_item_label($r);
    $borrowed   = isset($r['date_needed'])
                    ? (string) $r['date_needed']
                    : (isset($r['date_requested']) ? (string) $r['date_requested'] : date('Y-m-d'));
    $retDate    = isset($r['return_date']) ? trim((string) $r['return_date']) : '';
    $returned   = !empty($r['returned']);
    $returnedOn = $returned && isset($r['returned_at']) ? (string) $r['returned_at'] : '';

    if ($ap === 'pending') {
        $status = 'Pending approval';
    } elseif ($returned) {
        $status = 'Returned';
    } elseif ($retDate !== '') {
        try {
            $rd = new DateTimeImmutable($retDate);
            $status = ($rd < $today) ? 'Overdue' : 'Currently Borrowing';
        } catch (Exception $e) {
            $status = 'Currently Borrowing';
        }
    } else {
        $status = 'Currently Borrowing';
    }

    $history[] = [
        'id'            => $id,
        'item'          => $item,
        'date_borrowed' => $borrowed,
        'due_date'      => $retDate,
        'date_returned' => $returnedOn,
        'status'        => $status,
    ];
}

usort($history, static fn($a, $b) =>
    strtotime($b['date_borrowed']) <=> strtotime($a['date_borrowed'])
);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';

$filtered_history = array_filter($history, static function ($row) use ($filter, $search) {
    $matches_filter = ($filter === 'All' || $row['status'] === $filter);
    $matches_search = ($search === '' ||
                       stripos($row['item'], $search) !== false ||
                       stripos($row['id'],   $search) !== false);
    return $matches_filter && $matches_search;
});
$filtered_history = array_values($filtered_history);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | History & Logs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #2c3e50; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: var(--bg); color: var(--primary); }

        .controls-container { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }

        .top-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }

        .search-box {
            display: flex; gap: 10px; background: white;
            padding: 5px; border-radius: 30px; border: 1px solid #ddd;
            max-width: 460px; flex: 1;
        }
        .search-input { flex: 1; border: none; padding: 10px 20px; outline: none; border-radius: 30px; font-size: 14px; }
        .mic-btn { background: none; border: none; cursor: pointer; font-size: 16px; color: #7f8c8d; }
        .btn-search {
            background: var(--primary); color: white; border: none;
            padding: 0 20px; border-radius: 20px; cursor: pointer; font-size: 13px;
        }

        .export-btn {
            background: white; color: var(--primary);
            border: 1px solid #ddd; padding: 9px 18px;
            border-radius: 20px; font-size: 13px; font-weight: 600;
            cursor: pointer; white-space: nowrap;
        }
        .export-btn:hover { background: #f0f0f0; }

        .filter-list { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn {
            text-decoration: none; padding: 8px 18px; border-radius: 20px;
            font-size: 13px; font-weight: 600; background: white;
            color: #7f8c8d; border: 1px solid #ddd; transition: 0.2s;
            cursor: pointer;
        }
        .filter-btn:hover { background: #eee; }
        .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

        .history-card { background: white; border-radius: 12px; padding: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th {
            text-align: left; padding: 12px 14px;
            background: #fafafa; color: #95a5a6;
            font-size: 11px; text-transform: uppercase; letter-spacing: .04em;
            border-bottom: 1px solid #eee;
        }
        td { padding: 13px 14px; border-bottom: 1px solid #f9f9f9; font-size: 13px; word-break: break-word; }
        tr:last-child td { border-bottom: none; }

        .status { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 4px; white-space: nowrap; }
        .returned            { background: #ecfaf0; color: #1a8a44; }
        .overdue             { background: #fdedec; color: #c0392b; }
        .currently-borrowing { background: #ebf5fb; color: #1a6fa5; }
        .pending-approval    { background: #fef9ec; color: #d4830f; }

        .muted { color: #aaa; font-style: italic; }

        .empty-state { text-align: center; padding: 50px 20px; color: #95a5a6; }
        .empty-state i { font-size: 36px; margin-bottom: 14px; display: block; color: #c8d6df; }
        .empty-state a { color: #2c3e50; font-weight: 700; text-decoration: none; }
        .empty-state a:hover { text-decoration: underline; }

        @media (max-width: 800px) {
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="main-wrapper">
        <h1 style="margin-bottom: 20px;">History &amp; Logs</h1>

        <div class="controls-container">
            <div class="top-bar">
                <form action="" method="GET" id="searchForm" class="search-box">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <input type="text" name="search" id="searchInput" class="search-input"
                           placeholder="Search by item or ID..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="button" class="mic-btn" id="micBtn">🎤</button>
                    <button type="submit" class="btn-search">Search</button>
                </form>

                <button class="export-btn" onclick="exportCSV()">⬇ Export CSV</button>
            </div>

            <div class="filter-list">
                <?php
                $categories = ['All', 'Currently Borrowing', 'Pending approval', 'Returned', 'Overdue'];
                foreach ($categories as $cat):
                    $activeClass = ($filter === $cat) ? 'active' : '';
                ?>
                    <button type="button" class="filter-btn <?php echo $activeClass; ?>" 
                            onclick="setFilter('<?php echo htmlspecialchars($cat); ?>')">
                        <?php echo htmlspecialchars($cat); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="history-card">
            <table id="historyTable">
                <thead>
                    <tr>
                        <th style="width:14%">Request ID</th>
                        <th style="width:26%">Item</th>
                        <th style="width:15%">Date Borrowed</th>
                        <th style="width:15%">Due Date</th>
                        <th style="width:15%">Date Returned</th>
                        <th style="width:15%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filtered_history)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <?php if (empty($history)): ?>
                                        No borrow history yet.<br>
                                        <a href="borrow_list.php">Browse equipment</a> to get started.
                                    <?php else: ?>
                                        No records match your search or filter criteria.
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filtered_history as $row):
                            $cssStatus = strtolower(str_replace(' ', '-', $row['status']));
                            $searchText = strtolower($row['id'] . ' ' . $row['item']);
                        ?>
                        <tr data-status="<?php echo htmlspecialchars($row['status']); ?>" 
                            data-search="<?php echo htmlspecialchars($searchText); ?>">
                            <td style="font-family:monospace; color:#7f8c8d;">
                                <?php echo htmlspecialchars($row['id']); ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['item']); ?></strong></td>
                            <td><?php echo $row['date_borrowed'] ? htmlspecialchars(date('M d, Y', strtotime($row['date_borrowed']))) : '<span class="muted">—</span>'; ?></td>
                            <td><?php echo $row['due_date'] ? htmlspecialchars(date('M d, Y', strtotime($row['due_date']))) : '<span class="muted">—</span>'; ?></td>
                            <td><?php echo $row['date_returned'] ? htmlspecialchars(date('M d, Y', strtotime($row['date_returned']))) : '<span class="muted">—</span>'; ?></td>
                            <td>
                                <span class="status <?php echo htmlspecialchars($cssStatus); ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="assets/js/tooltrace-speech.js"></script>
    <script>
        tooltraceInitVoiceSearch({ micId: 'micBtn', inputId: 'searchInput', formId: 'searchForm' });

        // Client-side filtering with proper data attributes
        let currentFilter = '<?php echo htmlspecialchars($filter); ?>';

        function filterHistory() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const tbody = document.querySelector('#historyTable tbody');
            const rows = tbody.querySelectorAll('tr');
            let visibleCount = 0;

            rows.forEach(tr => {
                if (tr.querySelector('.empty-state')) return; // Skip empty state row

                const rowStatus = tr.dataset.status || '';
                const rowSearch = tr.dataset.search || '';
                
                const matchSearch = search === '' || rowSearch.includes(search);
                const matchFilter = currentFilter === 'All' || rowStatus === currentFilter;
                
                if (matchSearch && matchFilter) {
                    tr.style.display = '';
                    visibleCount++;
                } else {
                    tr.style.display = 'none';
                }
            });

            // Show empty state if no results
            if (visibleCount === 0 && rows.length > 0) {
                const emptyRow = tbody.querySelector('tr:has(.empty-state)');
                if (emptyRow) {
                    emptyRow.style.display = '';
                }
            }
        }

        function setFilter(filterValue) {
            currentFilter = filterValue;
            
            // Update active button state
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.textContent.trim() === filterValue);
            });
            
            // Update hidden filter input
            document.querySelector('input[name="filter"]').value = filterValue;
            
            // Apply filter
            filterHistory();
        }

        // Event listener for search input
        document.getElementById('searchInput').addEventListener('input', filterHistory);

        function exportCSV() {
            const rows = [['Request ID', 'Item', 'Date Borrowed', 'Due Date', 'Date Returned', 'Status']];
            document.querySelectorAll('#historyTable tbody tr').forEach(tr => {
                if (tr.querySelector('.empty-state')) return;
                if (tr.style.display === 'none') return;
                
                const cells = tr.querySelectorAll('td');
                if (cells.length < 6) return;

                rows.push([
                    cells[0].innerText.trim(),
                    cells[1].innerText.trim(),
                    cells[2].innerText.trim(),
                    cells[3].innerText.trim(),
                    cells[4].innerText.trim(),
                    cells[5].innerText.trim(),
                ]);
            });
            const csv = rows.map(r => r.map(c => '"' + c.replace(/"/g, '""') + '"').join(',')).join('\n');
            const a = document.createElement('a');
            a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            a.download = 'borrow_history.csv';
            a.click();
        }
    </script>
</body>
</html>