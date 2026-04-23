<?php
/**
 * ToolTrace - Staff Inventory CRUD API (PDO-backed)
 * FIXED: Images stored as base64 data URLs in MySQL (Railway-compatible)
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (($_SESSION['role'] ?? '') !== 'Maintenance Staff') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/includes/inventory_store.php';

function tooltrace_inventory_build_units_from_item(array $item): array
{
    if (!empty($item['units']) && is_array($item['units'])) {
        $units = [];
        foreach ($item['units'] as $index => $unit) {
            if (!is_array($unit)) {
                continue;
            }
            $unitNumber = isset($unit['unit_number']) ? (int) $unit['unit_number'] : ($index + 1);
            if ($unitNumber <= 0) {
                $unitNumber = $index + 1;
            }
            $units[] = [
                'unit_number' => $unitNumber,
                'condition_tag' => (string) ($unit['condition_tag'] ?? 'GOOD'),
            ];
        }
        if (!empty($units)) {
            usort($units, static fn(array $a, array $b): int => $a['unit_number'] <=> $b['unit_number']);
            return $units;
        }
    }

    $quantity = max(0, (int) ($item['quantity'] ?? 0));
    $conditions = isset($item['conditions']) && is_array($item['conditions']) ? array_values($item['conditions']) : [];
    $units = [];
    for ($i = 1; $i <= $quantity; $i++) {
        $units[] = [
            'unit_number' => $i,
            'condition_tag' => (string) ($conditions[$i - 1] ?? 'GOOD'),
        ];
    }
    return $units;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body = null;
$action = '';
$mode = '';

// Support both JSON requests and multipart/form-data (file upload).
if (!empty($_FILES) || !empty($_POST)) {
    $action = (string) ($_POST['action'] ?? '');
    $mode = strtolower(trim((string) ($_POST['mode'] ?? '')));
    if ($mode !== 'add' && $mode !== 'update') {
        $mode = '';
    }
    $rawItem = (string) ($_POST['item'] ?? '');
    $decodedItem = json_decode($rawItem !== '' ? $rawItem : '{}', true);
    if (!is_array($decodedItem)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid item payload']);
        exit;
    }
    $body = [
        'action' => $action,
        'mode' => $mode,
        'item' => $decodedItem,
    ];
} else {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw ?: '{}', true);
    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    $action = $body['action'] ?? '';
}

// ── List ──────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    echo json_encode(['success' => true, 'items' => tooltrace_inventory_load()]);
    exit;
}

// ── Save (add or update) ──────────────────────────────────────────────────────
if ($action === 'save') {
    $item = $body['item'] ?? null;
    if (!is_array($item) || empty($item['equipment_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing equipment_id']);
        exit;
    }

    $mode = strtolower(trim((string) ($body['mode'] ?? '')));
    if ($mode !== 'add' && $mode !== 'update') {
        $mode = '';
    }

    // ===== FIXED: Store images as base64 in MySQL =====
    $savedImage = null;
    if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $tmpName = (string) ($_FILES['image']['tmp_name'] ?? '');
        $origName = (string) ($_FILES['image']['name'] ?? '');
        $mimeType = (string) ($_FILES['image']['type'] ?? 'image/jpeg');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if ($tmpName !== '' && in_array($ext, $allowed, true)) {
            // Read the file and convert to base64
            $imageData = @file_get_contents($tmpName);
            if ($imageData !== false) {
                // Create data URL with base64 encoding
                // This is stored directly in MySQL and works everywhere
                $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                $savedImage = $base64Image;
                $item['image'] = $base64Image;
                $body['item'] = $item;
            }
        }
    }

    $rawImage = isset($item['image']) ? trim((string) $item['image']) : '';
    $hasDataUrlImage = $rawImage !== '' && str_starts_with(strtolower($rawImage), 'data:');

    $equipmentId = trim((string) $item['equipment_id']);
    $pdo         = db();
    $unitsToPersist = tooltrace_inventory_build_units_from_item($item);
    $item['units'] = $unitsToPersist;

    // Check if it already exists
    $check = $pdo->prepare("SELECT equipment_id FROM equipment WHERE equipment_id = ?");
    $check->execute([$equipmentId]);
    $exists = (bool) $check->fetch();

    if ($mode === 'add' && $exists) {
        http_response_code(409);
        echo json_encode(['error' => 'Equipment ID already exists. Please try again.']);
        exit;
    }

    if ($mode === 'update' && !$exists) {
        http_response_code(404);
        echo json_encode(['error' => 'Equipment not found for update. Refresh and try again.']);
        exit;
    }

    if ($exists) {
        // Update existing
        $fields = [
            'name'        => $item['name']        ?? null,
            'brand'       => $item['brand']       ?? null,
            'description' => $item['description']  ?? null,
            'category'    => $item['category']     ?? null,
            'serial_no'   => $item['serial_no']    ?? null,
            'location'    => $item['location']     ?? null,
            'quantity'    => isset($item['quantity']) ? (int) $item['quantity'] : null,
        ];

        // Store base64 data URLs directly in the image column
        if ($hasDataUrlImage) {
            $fields['image'] = $rawImage;
        } elseif ($savedImage !== null) {
            $fields['image'] = $savedImage;
        }

        $ok = tooltrace_inventory_update($equipmentId, $fields);

        // Sync units derived from quantity/conditions
        $pdo->prepare("DELETE FROM equipment_units WHERE equipment_id = ?")->execute([$equipmentId]);
        foreach ($unitsToPersist as $unit) {
            $u = $pdo->prepare("INSERT INTO equipment_units (equipment_id, unit_number, condition_tag) VALUES (?, ?, ?)");
            $u->execute([$equipmentId, (int) $unit['unit_number'], $unit['condition_tag'] ?? 'GOOD']);
        }

        // Sync keywords if provided
        if (isset($item['keywords']) && is_array($item['keywords'])) {
            $pdo->prepare("DELETE FROM equipment_keywords WHERE equipment_id = ?")->execute([$equipmentId]);
            foreach ($item['keywords'] as $kw) {
                $k = $pdo->prepare("INSERT IGNORE INTO equipment_keywords (equipment_id, keyword) VALUES (?, ?)");
                $k->execute([$equipmentId, $kw]);
            }
        }

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update equipment']);
            exit;
        }
    } else {
        // Insert new
        // Store base64 data URLs directly in the image column
        if ($hasDataUrlImage) {
            $item['image'] = $rawImage;
        } elseif ($savedImage !== null) {
            $item['image'] = $savedImage;
        }
        
        $ok = tooltrace_inventory_add($item);
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add equipment']);
            exit;
        }
    }

    echo json_encode(['success' => true, 'image' => $savedImage]);
    exit;
}

// ── Delete ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $equipmentId = trim((string) ($body['equipment_id'] ?? $body['id'] ?? ''));
    if ($equipmentId === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing equipment_id']);
        exit;
    }

    // Safety check — don't delete if active borrows exist
    $active = db()->prepare("SELECT COUNT(*) FROM borrow_transactions WHERE equipment_id = ? AND status = 'Borrowed'");
    $active->execute([$equipmentId]);
    if ((int) $active->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Cannot delete equipment that is currently borrowed.']);
        exit;
    }

    $ok = tooltrace_inventory_delete($equipmentId);
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete equipment']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
