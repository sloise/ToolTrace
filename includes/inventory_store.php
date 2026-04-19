<?php
/**
 * Equipment inventory — PDO-backed (MySQL via XAMPP).
 * Replaces the old JSON-based inventory_store.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function tooltrace_inventory_normalize_image_path(string $image): string
{
    $image = trim($image);
    if ($image === '') {
        return '';
    }

    $tooltraceWebRoot = function (): string {
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $script = trim(str_replace('\\', '/', $script));
        $script = trim($script, '/');
        if ($script === '') {
            return '';
        }
        $parts = explode('/', $script);
        if (count($parts) <= 1) {
            return '';
        }
        $first = $parts[0] ?? '';
        if ($first === '' || str_contains($first, '.')) {
            return '';
        }
        return '/' . $first;
    };

    $lowerRaw = strtolower($image);
    if (str_starts_with($lowerRaw, 'data:')) {
        return $image;
    }

    // Normalize Windows paths to web paths
    $image = str_replace('\\', '/', $image);
    $image = preg_replace('#/+#', '/', $image) ?: $image;

    // Remove leading ./
    if (str_starts_with($image, './')) {
        $image = substr($image, 2);
    }

    $lower = strtolower($image);

    // If an absolute file path was stored (e.g. C:/xampp/htdocs/ToolTrace/assets/images/foo.jpg),
    // convert it to a web path.
    $posAssets = strpos($lower, 'assets/images/');
    if ($posAssets !== false) {
        $rel = substr($image, $posAssets);
        if ($rel === '') {
            return '';
        }
        if (!str_starts_with($rel, '/')) {
            $rel = '/' . $rel;
        }
        return $tooltraceWebRoot() . $rel;
    }

    // If the path looks like a Windows drive path but does not include assets/images,
    // fall back to the filename.
    if (preg_match('#^[a-z]:/#i', $image) === 1) {
        $base = basename($image);
        if ($base === '') {
            return '';
        }
        return $tooltraceWebRoot() . '/assets/images/' . $base;
    }

    if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://') || str_starts_with($image, '/')) {
        return $image;
    }

    // If already an assets path, keep it
    if (str_starts_with($lower, 'assets/images/')) {
        return $tooltraceWebRoot() . '/' . $image;
    }

    if (str_contains($image, '/')) {
        return $image;
    }

    return $tooltraceWebRoot() . '/assets/images/' . ltrim($image, '/');
}

// ── Load all equipment with their units ──────────────────────────────────────

function tooltrace_inventory_load(): array
{
    $pdo = db();

    // Get all equipment
    $stmt = $pdo->query("SELECT * FROM equipment ORDER BY name ASC");
    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        $item['image'] = tooltrace_inventory_normalize_image_path((string) ($item['image'] ?? ''));

        // Attach units
        $u = $pdo->prepare("SELECT unit_id, unit_number, condition_tag FROM equipment_units WHERE equipment_id = ? ORDER BY unit_number ASC");
        $u->execute([$item['equipment_id']]);
        $units = $u->fetchAll();

        $unavail = $pdo->prepare("
            SELECT DISTINCT unit_id
            FROM borrow_transactions
            WHERE equipment_id = ?
              AND unit_id IS NOT NULL
              AND status <> 'Returned'
              AND approval_status <> 'Rejected'
        ");
        $unavail->execute([$item['equipment_id']]);
        $unavailableUnitIds = array_map('intval', array_column($unavail->fetchAll(), 'unit_id'));

        // Attach keywords
        $k = $pdo->prepare("SELECT keyword FROM equipment_keywords WHERE equipment_id = ?");
        $k->execute([$item['equipment_id']]);
        $keywords = array_column($k->fetchAll(), 'keyword');

        // Count how many units are currently unavailable (picked up or reserved via approval)
        $b = $pdo->prepare("
            SELECT COUNT(DISTINCT unit_id)
            FROM borrow_transactions
            WHERE equipment_id = ?
              AND unit_id IS NOT NULL
              AND status <> 'Returned'
              AND approval_status <> 'Rejected'
        ");
        $b->execute([$item['equipment_id']]);
        $borrowedCount = (int) $b->fetchColumn();

        $item['units']         = $units;
        $item['unavailable_unit_ids'] = $unavailableUnitIds;
        $item['conditions']    = array_column($units, 'condition_tag');
        $item['keywords']      = $keywords;
        $item['borrowed_count'] = $borrowedCount;
    }
    unset($item);

    return $items;
}

// ── Map one DB row to the borrow catalog format ──────────────────────────────

function tooltrace_inventory_row_to_catalog(array $row): array
{
    $qty      = (int) ($row['quantity'] ?? 0);
    $borrowed = (int) ($row['borrowed_count'] ?? 0);
    $available = max(0, $qty - $borrowed);
    $status   = $available > 0 ? 'Available' : 'Out of Stock';

    $keywords = $row['keywords'] ?? [];
    $brand    = trim((string) ($row['brand'] ?? ''));
    if ($brand !== '') {
        $keywords[] = $brand;
    }
    $keywords = array_values(array_unique(array_filter(array_map('strval', $keywords))));

    return [
        'id'       => (string) ($row['equipment_id'] ?? ''),
        'name'     => (string) ($row['name'] ?? ''),
        'category' => (string) ($row['category'] ?? 'Equipment'),
        'status'   => $status,
        'quantity' => $qty,
        'borrowed' => $borrowed,
        'available' => $available,
        'units'    => isset($row['units']) && is_array($row['units']) ? $row['units'] : [],
        'unavailable_unit_ids' => isset($row['unavailable_unit_ids']) && is_array($row['unavailable_unit_ids']) ? $row['unavailable_unit_ids'] : [],
        'desc'     => (string) ($row['description'] ?? ''),
        'image'    => tooltrace_inventory_normalize_image_path((string) ($row['image'] ?? '')),
        'location' => (string) ($row['location'] ?? ''),
        'keywords' => $keywords,
    ];
}

// ── Save (used by staff_inventory.php when editing) ──────────────────────────

function tooltrace_inventory_save(array $items): bool
{
    // No-op: DB is source of truth; individual update functions handle writes.
    return true;
}

// ── Add a new equipment item ──────────────────────────────────────────────────

function tooltrace_inventory_add(array $item): bool
{
    $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO equipment (equipment_id, name, brand, description, category, serial_no, location, quantity, image)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ok = $stmt->execute([
        $item['equipment_id'],
        $item['name'],
        $item['brand'] ?? null,
        $item['description'] ?? null,
        $item['category'],
        $item['serial_no'] ?? null,
        $item['location'] ?? null,
        (int) ($item['quantity'] ?? 1),
        $item['image'] ?? null,
    ]);

    if ($ok && !empty($item['units'])) {
        foreach ($item['units'] as $unit) {
            $u = $pdo->prepare("INSERT INTO equipment_units (equipment_id, unit_number, condition_tag) VALUES (?, ?, ?)");
            $u->execute([$item['equipment_id'], $unit['unit_number'], $unit['condition_tag'] ?? 'GOOD']);
        }
    }

    if ($ok && !empty($item['keywords'])) {
        foreach ($item['keywords'] as $kw) {
            $k = $pdo->prepare("INSERT IGNORE INTO equipment_keywords (equipment_id, keyword) VALUES (?, ?)");
            $k->execute([$item['equipment_id'], $kw]);
        }
    }

    return $ok;
}

// ── Update an existing equipment item ────────────────────────────────────────

function tooltrace_inventory_update(string $equipmentId, array $fields): bool
{
    $pdo = db();
    $allowed = ['name', 'brand', 'description', 'category', 'serial_no', 'location', 'quantity', 'image'];
    $sets = [];
    $vals = [];
    foreach ($allowed as $col) {
        if (array_key_exists($col, $fields)) {
            $sets[] = "`$col` = ?";
            $vals[] = $fields[$col];
        }
    }
    if (empty($sets)) {
        return false;
    }
    $vals[] = $equipmentId;
    $stmt = $pdo->prepare("UPDATE equipment SET " . implode(', ', $sets) . " WHERE equipment_id = ?");
    return $stmt->execute($vals);
}

// ── Delete equipment ──────────────────────────────────────────────────────────

function tooltrace_inventory_delete(string $equipmentId): bool
{
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
    return $stmt->execute([$equipmentId]);
}