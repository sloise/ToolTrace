<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/require_staff_session.php';
require_once __DIR__ . '/../includes/config.php';

try {
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    if ($q === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing q']);
        exit;
    }

    $equipmentId = '';
    $unitNumber  = null;
    $unitId      = null;

    if (preg_match('/^(.+?)-U(\d+)$/i', $q, $m) === 1) {
        $equipmentId = (string) $m[1];
        $unitNumber  = (int) $m[2];
    } elseif (preg_match('/^UNIT:(\d+)$/i', $q, $m) === 1) {
        $unitId = (int) $m[1];
    } elseif (ctype_digit($q)) {
        $unitId = (int) $q;
    } else {
        $equipmentId = $q;
    }

    $pdo = db();

    $lookupByUnitId = static function (PDO $pdo, int $lookupUnitId): ?array {
        $stmt = $pdo->prepare(
            "SELECT e.equipment_id, e.name, e.description, e.category, e.location, e.quantity, eu.unit_id, eu.unit_number, eu.condition_tag\n"
            . "FROM equipment_units eu\n"
            . "JOIN equipment e ON eu.equipment_id = e.equipment_id\n"
            . "WHERE eu.unit_id = ?\n"
            . "LIMIT 1"
        );
        $stmt->execute([$lookupUnitId]);
        $row = $stmt->fetch();
        return $row ?: null;
    };

    $lookupByEquipmentAndUnit = static function (PDO $pdo, string $lookupEquipmentId, int $lookupUnitNumber): ?array {
        $stmt = $pdo->prepare(
            "SELECT e.equipment_id, e.name, e.description, e.category, e.location, e.quantity, eu.unit_id, eu.unit_number, eu.condition_tag\n"
            . "FROM equipment_units eu\n"
            . "JOIN equipment e ON eu.equipment_id = e.equipment_id\n"
            . "WHERE eu.equipment_id = ? AND eu.unit_number = ?\n"
            . "LIMIT 1"
        );
        $stmt->execute([$lookupEquipmentId, $lookupUnitNumber]);
        $row = $stmt->fetch();
        return $row ?: null;
    };

    if ($unitId !== null) {
        $row = $lookupByUnitId($pdo, $unitId);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Unit not found']);
            exit;
        }
    } elseif ($equipmentId !== '' && $unitNumber !== null) {
        $row = $lookupByEquipmentAndUnit($pdo, $equipmentId, $unitNumber);
        if (!$row) {
            $row = $lookupByUnitId($pdo, $unitNumber);
        }
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Item unit not found']);
            exit;
        }
    } else {
        $stmt = $pdo->prepare("SELECT equipment_id, name, description, category, location, quantity FROM equipment WHERE equipment_id = ? LIMIT 1");
        $stmt->execute([$equipmentId]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Item not found']);
            exit;
        }
        $row['unit_id'] = null;
        $row['unit_number'] = null;
        $row['condition_tag'] = null;
    }

    $borrowedStmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT unit_id)\n"
        . "FROM borrow_transactions\n"
        . "WHERE equipment_id = ?\n"
        . "  AND unit_id IS NOT NULL\n"
        . "  AND status <> 'Returned'\n"
        . "  AND approval_status <> 'Rejected'"
    );
    $borrowedStmt->execute([(string) ($row['equipment_id'] ?? $equipmentId)]);
    $borrowedCount = (int) $borrowedStmt->fetchColumn();

    $qty = (int) ($row['quantity'] ?? 0);
    $available = max(0, $qty - $borrowedCount);

    echo json_encode([
        'success' => true,
        'data' => [
            'equipment_id' => (string) ($row['equipment_id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'category' => (string) ($row['category'] ?? ''),
            'location' => (string) ($row['location'] ?? ''),
            'quantity' => $qty,
            'borrowed_count' => $borrowedCount,
            'available_count' => $available,
            'unit_id' => isset($row['unit_id']) ? (int) $row['unit_id'] : null,
            'unit_number' => isset($row['unit_number']) ? (int) $row['unit_number'] : null,
            'condition_tag' => (string) ($row['condition_tag'] ?? ''),
            'qr_value' => $q,
        ],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
