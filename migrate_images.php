<?php
/**
 * Migration Script: Clear broken image paths from existing equipment
 * Run this once to reset all image columns to NULL or placeholder
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/inventory_store.php';

$pdo = db();

echo "🔄 Fixing broken image paths in equipment...\n\n";

try {
    // Get all equipment with file-based images
    $stmt = $pdo->query("SELECT equipment_id, image FROM equipment WHERE image IS NOT NULL AND image != ''");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fixed = 0;
    $kept = 0;
    
    foreach ($items as $row) {
        $image = trim((string) $row['image']);
        $eq_id = $row['equipment_id'];
        
        // If it's already a data URL (base64), keep it
        if (str_starts_with(strtolower($image), 'data:')) {
            echo "✓ {$eq_id}: Already base64 (keeping)\n";
            $kept++;
            continue;
        }
        
        // If it's a file path like 'assets/images/uploads/...'
        if (str_contains($image, 'assets/images/') || str_contains($image, 'uploads/')) {
            echo "✗ {$eq_id}: Broken file path → clearing\n";
            $upd = $pdo->prepare("UPDATE equipment SET image = NULL WHERE equipment_id = ?");
            $upd->execute([$eq_id]);
            $fixed++;
        } else {
            // Keep external URLs (like placeholders or CDN links)
            echo "→ {$eq_id}: External URL (keeping)\n";
            $kept++;
        }
    }
    
    echo "\n✅ Migration complete!\n";
    echo "   • Fixed (cleared): {$fixed}\n";
    echo "   • Kept: {$kept}\n";
    echo "\n💡 Next: Upload images again with the new inventory.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
