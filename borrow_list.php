<?php
/**
 * ToolTrace - Expanded Equipment Catalog
 * Fixed: Working camera scan with improved image recognition
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$account_org_email = isset($_SESSION['organization_email']) ? trim((string) $_SESSION['organization_email']) : '';
$account_org_name  = isset($_SESSION['organization_name']) ? trim((string) $_SESSION['organization_name']) : '';

$user = [
    'name' => $_SESSION['user_name'] ?? 'Anne Arbolente',
    'initials' => 'AA',
];

require_once __DIR__ . '/includes/inventory_store.php';

$all_equipment = [];
foreach (tooltrace_inventory_load() as $row) {
    $all_equipment[] = tooltrace_inventory_row_to_catalog($row);
}

// --- SEARCH LOGIC ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filtered_list = [];
$exact_matches = [];

if ($search_query !== '') {
    $search_lower = strtolower($search_query);
    foreach ($all_equipment as $item) {
        $match = false;
        
        // Check name (exact match gets highest priority)
        if (strcasecmp($item['name'], $search_query) === 0) {
            $match = true;
            $exact_matches[] = $item;
            continue;
        }
        // Partial name match
        if (stripos($item['name'], $search_query) !== false) {
            $match = true;
        }
        // Check category
        elseif (stripos($item['category'], $search_query) !== false) {
            $match = true;
        }
        // Check keywords
        else {
            foreach ($item['keywords'] as $keyword) {
                if (stripos($keyword, $search_query) !== false || stripos($search_lower, $keyword) !== false) {
                    $match = true;
                    break;
                }
            }
        }
        
        if ($match) {
            $filtered_list[] = $item;
        }
    }
    // Show exact name matches first, then other fuzzy matches
    if (!empty($exact_matches)) {
        $filtered_list = array_merge($exact_matches, $filtered_list);
    }
} else {
    $filtered_list = $all_equipment;
}

$equipment_image_by_id = [];
foreach ($all_equipment as $eq) {
    $equipment_image_by_id[$eq['id']] = $eq['image'];
}

// Create a JSON array of all equipment for client-side search
$equipment_json = json_encode($all_equipment);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Browse & Borrow Equipment</title>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@0.8/dist/teachablemachine-image.min.js"></script>

    <script>
        // Dataset hook: edit this JSON file to plug your own labels/keywords.
        window.TOOLTRACE_BORROW_DATASET_URL = "datasets/borrow_labels.json";
    </script>
    <style>
        :root { 
            --primary: #2c3e50; 
            --accent: #f1c40f; 
            --bg: #f4f7f6; 
            --cart-red: #ff522f; 
        }
        
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: var(--bg); color: var(--primary); }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin: 0 0 14px 0;
        }

        .page-title {
            font-weight: 700;
            font-size: 2.2rem;
            margin: 0;
            line-height: 1.1;
        }

        .catalog-cart-wrap {
            flex-shrink: 0;
        }

        .catalog-cart-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            background: var(--primary);
            color: #fff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            padding: 10px 16px;
            border-radius: 999px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(44, 62, 80, 0.12);
            transition: opacity 0.2s;
        }

        .catalog-cart-btn:hover {
            opacity: 0.9;
        }

        .catalog-cart-btn .cart-icon {
            font-size: 22px;
            line-height: 1;
        }

        .catalog-cart-btn .cart-pill-badge {
            position: absolute;
            top: -8px;
            right: -4px;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: var(--cart-red);
            font-size: 12px;
            font-weight: 800;
            border-radius: 999px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .search-wrapper { max-width: 650px; display: flex; gap: 10px; background: white; padding: 5px; border-radius: 35px; border: 1px solid #ddd; align-items: center; margin-bottom: 30px; }
        .search-input { flex: 1; border: none; padding: 10px 20px; font-size: 16px; outline: none; border-radius: 30px; }
        .icon-btn { background: none; border: none; cursor: pointer; font-size: 18px; color: #7f8c8d; padding: 0 10px; transition: 0.2s; }
        .icon-btn:hover { color: var(--primary); }
        .btn-search { background: var(--primary); color: white; border: none; padding: 10px 25px; border-radius: 30px; cursor: pointer; font-weight: bold; }

        .equipment-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; }
        .item-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); position: relative; border: 1px solid transparent; transition: 0.3s; display: flex; flex-direction: column; }
        .item-thumb-wrap {
            height: 170px;
            background: #f4f6f8;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            border-bottom: 1px solid #eef0f2;
        }
        .item-thumb-wrap img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
        .item-card:hover { border-color: var(--accent); transform: translateY(-3px); }
        .item-details { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; }
        .item-name { font-size: 15px; font-weight: 600; color: var(--primary); margin: 5px 0; }
        .item-desc { font-size: 12px; color: #7f8c8d; margin-bottom: 10px; line-height: 1.4; height: 34px; overflow: hidden; }
        
        .status-tag { font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: bold; margin-bottom: 12px; display: inline-block; width: fit-content; }
        .available { background: #e8f5e9; color: #2e7d32; }
        .unavailable { background: #ffebee; color: #c62828; }

        .stock-availability {
            font-size: 12px;
            color: #7f8c8d;
            margin: -6px 0 12px 0;
        }

        .btn-add-cart { width: 100%; margin-top: auto; background: #f8f9fa; border: 1px solid #ddd; padding: 10px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--primary); transition: 0.2s; }
        .btn-add-cart:hover:not(:disabled) { background: var(--primary); color: white; }
        .btn-add-cart:disabled { opacity: 0.5; cursor: not-allowed; }

        .bag-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 3000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(44, 62, 80, 0.35);
            backdrop-filter: blur(4px);
        }
        .bag-modal.is-open { display: flex; }
        .bag-modal-panel {
            width: 100%;
            max-width: 560px;
            max-height: min(90vh, 720px);
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .bag-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 22px;
            background: #fff;
            border-bottom: 1px solid #eee;
            flex-shrink: 0;
        }
        .bag-modal-title {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.02em;
        }
        .bag-modal-close {
            background: #f8f9fa;
            border: 1px solid #ddd;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            line-height: 1;
            color: #7f8c8d;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .bag-modal-close:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .bag-modal-body {
            overflow-y: auto;
            padding: 18px 22px 22px;
            flex: 1;
            min-height: 0;
            background: var(--bg);
        }
        .bag-item-row {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 14px 0;
            border-bottom: 1px solid #eee;
        }
        .bag-item-row:last-child { border-bottom: none; }
        .bag-item-thumb-wrap {
            width: 72px;
            height: 72px;
            flex-shrink: 0;
            border-radius: 8px;
            background: #f9f9f9;
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .bag-item-thumb {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }
        .bag-item-thumb-ph {
            width: 100%;
            height: 100%;
            background: #f0f0f0;
        }
        .bag-item-main {
            flex: 1;
            min-width: 0;
        }
        .bag-item-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 4px 0;
            line-height: 1.35;
        }
        .bag-item-cat {
            font-size: 10px;
            font-weight: bold;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0 0 6px 0;
        }
        .bag-item-meta {
            font-size: 12px;
            color: #7f8c8d;
            margin: 0 0 10px 0;
            line-height: 1.4;
        }
        .bag-item-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px 14px;
        }
        .bag-item-actions .bag-qty-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
        }
        .bag-modal .bag-qty-controls {
            display: inline-flex;
            align-items: center;
            background: #f1f1f1;
            padding: 4px 12px;
            border-radius: 25px;
            gap: 12px;
            border: 1px solid #e8e8e8;
        }
        .bag-modal .bag-qty-btn {
            border: none;
            background: none;
            font-size: 18px;
            cursor: pointer;
            color: var(--primary);
            line-height: 1;
            padding: 0 4px;
        }
        .bag-modal .bag-qty-btn:disabled { opacity: 0.35; cursor: not-allowed; }
        .bag-item-remove {
            margin-left: auto;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            font-size: 12px;
            font-weight: 600;
            color: #c62828;
            cursor: pointer;
            font-family: inherit;
            transition: 0.2s;
        }
        .bag-item-remove:hover {
            background: #ffebee;
            border-color: #ffcdd2;
        }
        .bag-empty-msg {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0 0 8px 0;
            line-height: 1.5;
        }
        .bag-form-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #eee;
        }
        @media (max-width: 520px) {
            .bag-form-split { grid-template-columns: 1fr; }
            .bag-item-actions { margin-left: 0; }
            .bag-item-remove { margin-left: 0; }
        }
        .bag-field-box {
            background: #fafbfb;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 14px 16px;
        }
        .bag-field-box .bag-field-label {
            display: block;
            font-size: 10px;
            font-weight: bold;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 10px 0;
        }
        .bag-field-box input[type="text"] {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: var(--primary);
            background: #fff;
        }
        .bag-field-box input[type="text"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(241, 196, 15, 0.25);
        }
        .bag-date-field {
            margin-bottom: 12px;
        }
        .bag-date-field:last-child { margin-bottom: 0; }
        .bag-date-field label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #7f8c8d;
            margin: 0 0 6px 0;
        }
        .bag-date-field input[type="date"] {
            width: 100%;
            box-sizing: border-box;
            padding: 9px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            color: var(--primary);
            background: #fff;
        }
        .bag-date-field input[type="date"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(241, 196, 15, 0.25);
        }
        .bag-field-box--full {
            margin-top: 16px;
        }
        .bag-borrow-details.is-disabled {
            opacity: 0.45;
            pointer-events: none;
        }
        .bag-modal-footer {
            padding: 18px 22px 22px;
            background: #fff;
            border-top: 1px solid #eee;
            flex-shrink: 0;
        }
        .bag-modal-footer .btn-group {
            margin-top: 0;
            display: flex;
            gap: 12px;
        }
        .bag-modal .btn-submit {
            background: var(--accent);
            color: var(--primary);
            border: none;
            padding: 12px 22px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            flex: 1;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(241, 196, 15, 0.35);
            transition: 0.2s;
        }
        .bag-modal .btn-submit:hover:not(:disabled) {
            filter: brightness(0.98);
            box-shadow: 0 4px 12px rgba(241, 196, 15, 0.45);
        }
        .bag-modal .btn-submit:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            box-shadow: none;
        }
        .bag-field-box input[type="email"] {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: var(--primary);
            background: #fff;
        }
        .bag-field-box input[type="email"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(241, 196, 15, 0.25);
        }

        /* QR Success Screen — when visible, hide .bag-modal-body so flex:1 on the body
           does not leave a huge empty strip between this block and the footer */
        .bag-modal-panel:has(.qr-success-screen.is-visible) .bag-modal-body {
            display: none;
        }
        .qr-success-screen {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 20px 22px 12px;
            text-align: center;
            gap: 10px;
            flex: 0 1 auto;
        }
        .qr-success-screen.is-visible { display: flex; }
        .qr-success-screen h3 {
            margin: 0 0 4px 0;
            font-size: 1.15rem;
            color: var(--primary);
            font-weight: 700;
        }
        .qr-success-screen p {
            margin: 0;
            font-size: 13px;
            color: #7f8c8d;
            line-height: 1.5;
        }
        .qr-success-screen .qr-req-id {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.04em;
            margin-top: 6px;
        }
        .qr-success-screen img {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 10px;
            background: #fafafa;
        }
        .btn-print-qr {
            margin-top: 2px;
            margin-bottom: 0;
            background: none;
            border: 1px solid #ddd;
            padding: 8px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-family: inherit;
            color: var(--primary);
            font-weight: 600;
            transition: 0.2s;
            align-self: center;
        }
        .btn-print-qr:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        .borrow-alert-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 4500;
            background: rgba(0,0,0,0.45);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .borrow-alert-overlay.is-open { display: flex; }
        .borrow-alert-panel {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
            border: 1px solid #eee;
            padding: 20px;
        }
        .borrow-alert-title {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
        }
        .borrow-alert-message {
            margin: 10px 0 0 0;
            font-size: 13px;
            color: #566573;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        .borrow-alert-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }
        .borrow-alert-actions button {
            border: 1px solid #ddd;
            background: var(--primary);
            color: #fff;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
        }
        .borrow-alert-actions button:hover { opacity: 0.92; }

        .bag-modal .btn-back {
            background: #f8f9fa;
            color: var(--primary);
            border: 1px solid #ddd;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            flex: 1;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }
        .bag-modal .btn-back:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        /* Camera Modal */
        .camera-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 4000;
            background: rgba(0,0,0,0.9);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .camera-modal-overlay.is-open {
            display: flex;
        }
        .camera-modal-panel {
            background: white;
            border-radius: 12px;
            padding: 20px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }
        .camera-modal-panel h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 1.35rem;
        }
        #cameraStream {
            width: 100%;
            max-height: 400px;
            background: #000;
            border-radius: 8px;
            margin-bottom: 15px;
            display: block;
            filter: brightness(1.3) contrast(1.2);
            object-fit: cover;
        }
        #captureCanvas {
            display: none;
        }
        .camera-controls {
            display: flex;
            gap: 10px;
        }
        .camera-controls button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: 0.2s;
        }
        #captureBtn {
            background: var(--primary);
            color: white;
        }
        #captureBtn:hover:not(:disabled) {
            opacity: 0.9;
        }
        #captureBtn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        #closeCameraBtn {
            background: #f8f9fa;
            color: var(--primary);
            border: 1px solid #ddd;
        }
        #closeCameraBtn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        #cameraStatus {
            text-align: center;
            color: #7f8c8d;
            margin-top: 10px;
            font-size: 12px;
            min-height: 20px;
        }

        button.catalog-cart-btn {
            font: inherit;
        }

        @media (max-width: 600px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .catalog-cart-wrap {
                width: 100%;
            }
            .catalog-cart-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>
    <style>
        /* After navbar so horizontal padding aligns with catalog grid without fighting sidebar offset */
        .main-wrapper {
            padding-left: clamp(24px, 4vw, 48px);
            padding-right: clamp(24px, 4vw, 48px);
        }
    </style>

    <main class="main-wrapper">
        <div class="page-header">
            <h1 class="page-title">Equipment Catalog</h1>
            <div class="catalog-cart-wrap">
                <button type="button" class="catalog-cart-btn" id="cartBtn" title="View borrow list" aria-label="View borrow list" aria-haspopup="dialog" aria-controls="bagModal">
                    <span class="cart-icon" aria-hidden="true">🛒</span>
                    <span class="cart-pill-badge" id="cartCount">0</span>
                </button>
            </div>
        </div>

        <div class="search-container">
            <form action="" method="GET" id="searchForm" class="search-wrapper">
                <input type="text" name="search" id="searchInput" class="search-input" placeholder="Search equipment..." value="<?php echo htmlspecialchars($search_query); ?>">
                
                <button type="button" class="icon-btn" id="cameraBtn" title="Scan Equipment with Camera" aria-label="Search by scanning equipment with camera">📷</button>
                <button type="button" class="icon-btn" id="micBtn" title="Voice Search" aria-label="Search by voice">🎤</button>
                
                <button type="submit" class="btn-search">Search</button>
            </form>
        </div>

        <!-- Camera Modal -->
        <div class="camera-modal-overlay" id="cameraModal">
            <div class="camera-modal-panel">
                <h3>📸 Scan Equipment</h3>
                <video id="cameraStream" playsinline autoplay muted></video>
                <canvas id="captureCanvas"></canvas>
                <div class="camera-controls">
                    <button type="button" id="captureBtn">📸 Capture Photo</button>
                    <button type="button" id="closeCameraBtn">Cancel</button>
                </div>
                <p id="cameraStatus"></p>
            </div>
        </div>

        <div class="equipment-grid">
            <?php 
$first_item = true;
foreach($filtered_list as $item): 
?>
            <div class="item-card">
                <div class="item-thumb-wrap">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                </div>
                <div class="item-details">
                    <div style="font-size:10px; color:#95a5a6; font-weight:bold;"><?php echo strtoupper($item['category']); ?></div>
                    <div class="item-name"><?php echo $item['name']; ?></div>
                    <div class="item-desc"><?php echo $item['desc']; ?></div>
                    
                    <div>
                        <?php
                            $availableCount = (int) ($item['available'] ?? 0);
                            $totalCount = (int) ($item['quantity'] ?? 0);
                            $isAvailable = $availableCount > 0;
                            $displayStatus = $isAvailable
                                ? 'Available'
                                : 'Out of Stock';
                        ?>
                        <span class="status-tag <?php echo $isAvailable ? 'available' : 'unavailable'; ?>">
                            <?php echo htmlspecialchars($displayStatus); ?>
                        </span>
                    </div>

                    <div class="stock-availability">
                        <?php echo (int) $availableCount; ?>/<?php echo (int) $totalCount; ?> items available
                    </div>

                    <button 
                        class="btn-add-cart" 
                        data-id="<?php echo htmlspecialchars((string) $item['id'], ENT_QUOTES); ?>"
                        data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>"
                        data-image="<?php echo htmlspecialchars($item['image'], ENT_QUOTES); ?>"
                        data-category="<?php echo htmlspecialchars($item['category'], ENT_QUOTES); ?>"
                        <?php echo (!$isAvailable) ? 'disabled' : ''; ?>>
                        <?php echo ($isAvailable) ? 'Add to Borrow List' : 'Not Available'; ?>
                    </button>
                </div>
            </div>
            <?php 
$first_item = false;
endforeach; 
?>
        </div>
    </main>

    <div class="bag-modal" id="bagModal" role="dialog" aria-modal="true" aria-labelledby="bagModalTitle">
        <div class="bag-modal-panel">
            <div class="bag-modal-header">
                <h2 class="bag-modal-title" id="bagModalTitle">Borrow equipment</h2>
                <button type="button" class="bag-modal-close" id="bagModalClose" aria-label="Close">&times;</button>
            </div>
            <div class="bag-modal-body">
                <p class="bag-empty-msg" id="bagModalEmptyMsg" hidden>Your bag is empty. Add equipment from the catalog first.</p>
                <div id="bagModalLineItems"></div>

                <div class="bag-borrow-details is-disabled" id="bagBorrowDetails">
                    <div class="bag-form-split">
                        <div class="bag-field-box">
                            <label class="bag-field-label" for="borrowPurpose">Purpose &amp; Location</label>
                            <input type="text" id="borrowPurpose" name="purpose" placeholder="Purpose / title of the activity" autocomplete="off">
                            <label for="borrowLocation" style="display:block; font-size:11px; font-weight:600; color:#7f8c8d; margin:12px 0 6px 0;">Location</label>
                            <input type="text" id="borrowLocation" name="location" placeholder="Where will the equipment be used?" autocomplete="off">
                        </div>
                        <div class="bag-field-box">
                            <span class="bag-field-label">Schedule</span>
                            <div class="bag-date-field">
                                <label for="borrowDateNeeded">Date needed</label>
                                <input type="date" id="borrowDateNeeded" name="date_needed" autocomplete="off">
                            </div>
                            <div class="bag-date-field">
                                <label for="borrowReturnDate">Return date</label>
                                <input type="date" id="borrowReturnDate" name="return_date" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="bag-field-box bag-field-box--full">
                        <label class="bag-field-label" for="borrowOfficer">Officer in charge of claiming</label>
                        <input type="text" id="borrowOfficer" name="officer" placeholder="Full name" autocomplete="name">
                    </div>
                    <div class="bag-field-box bag-field-box--full" style="margin-top:14px;">
                        <label class="bag-field-label" for="borrowOfficerId">Upload valid ID of Officer-in-Charge</label>
                        <input type="file" id="borrowOfficerId" name="officer_id" accept="image/jpeg,image/png,application/pdf" required>
                    </div>
                    <div class="bag-field-box bag-field-box--full" style="margin-top:14px;">
                        <label class="bag-field-label" for="borrowOrgName">Organization Name</label>
                        <input type="text" id="borrowOrgName" name="organization_name" placeholder="e.g. ICS Student Council" autocomplete="organization" value="<?php echo htmlspecialchars($account_org_name); ?>">
                    </div>
                </div>
            </div>
            <!-- QR Success Screen (shown after confirm) -->
            <div class="qr-success-screen" id="qrSuccessScreen">
                <div style="font-size:36px;">🎉</div>
                <h3>Request Submitted!</h3>
                <p>Present this QR code to the Maintenance Office upon pickup.</p>
                <div id="qrImageWrap"></div>
                <div class="qr-req-id" id="qrReqIdLabel"></div>
                <p id="qrSummaryItems" style="font-size:12px; color:#95a5a6; max-width:260px;"></p>
                <button class="btn-print-qr" onclick="window.print()">Download / Print</button>
            </div>

            <div class="bag-modal-footer" id="bagModalFooter">
                <div class="btn-group">
                    <button type="button" class="btn-back" id="bagModalCancel">Cancel</button>
                    <button type="button" class="btn-submit" id="bagModalConfirm" disabled>CONFIRM REQUEST</button>
                </div>
            </div>
        </div>
    </div>

    <div class="borrow-alert-overlay" id="borrowAlertModal" role="dialog" aria-modal="true" aria-labelledby="borrowAlertTitle">
        <div class="borrow-alert-panel">
            <h3 class="borrow-alert-title" id="borrowAlertTitle">Notice</h3>
            <p class="borrow-alert-message" id="borrowAlertMessage"></p>
            <div class="borrow-alert-actions">
                <button type="button" id="borrowAlertOk">OK</button>
            </div>
        </div>
    </div>

    <script>
        // === EQUIPMENT DATA ===
        const ALL_EQUIPMENT = <?php echo $equipment_json; ?>;

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-add-cart');
            if (!btn || btn.disabled) return;
            addToCart(btn.dataset.id, btn.dataset.name, btn.dataset.image, btn.dataset.category);
        });
        function showBorrowAlert(message) {
            const overlay = document.getElementById('borrowAlertModal');
            const msgEl = document.getElementById('borrowAlertMessage');
            if (!overlay || !msgEl) return;
            msgEl.textContent = String(message || '');
            overlay.classList.add('is-open');
            const ok = document.getElementById('borrowAlertOk');
            if (ok) ok.focus();
        }

        function closeBorrowAlert() {
            const overlay = document.getElementById('borrowAlertModal');
            if (!overlay) return;
            overlay.classList.remove('is-open');
        }

        (function initBorrowAlertModal() {
            const overlay = document.getElementById('borrowAlertModal');
            const ok = document.getElementById('borrowAlertOk');
            if (ok) ok.addEventListener('click', closeBorrowAlert);
            if (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) closeBorrowAlert();
                });
            }
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeBorrowAlert();
            });
        })();

        // === CAMERA SCANNING WITH IMAGE RECOGNITION ===
        let stream = null;
        const cameraModal = document.getElementById('cameraModal');
        const cameraBtn = document.getElementById('cameraBtn');
        const closeCameraBtn = document.getElementById('closeCameraBtn');
        const captureBtn = document.getElementById('captureBtn');
        const cameraStream = document.getElementById('cameraStream');
        const captureCanvas = document.getElementById('captureCanvas');
        const cameraStatus = document.getElementById('cameraStatus');

        // On-device image recognition model (Teachable Machine Image).
        let recognitionModel = null;
        let modelLoadPromise = null;
        const TM_MODEL_URL = "datasets/tm-model/model.json";
        const TM_METADATA_URL = "datasets/tm-model/metadata.json";

        // Your dataset (JSON) used to map model labels -> equipment.
        let borrowDataset = null;
        let datasetLoadPromise = null;

        async function ensureRecognitionModelLoaded() {
            if (recognitionModel) return recognitionModel;
            if (modelLoadPromise) return modelLoadPromise;

            modelLoadPromise = (async () => {
                recognitionModel = await tmImage.load(TM_MODEL_URL, TM_METADATA_URL);
                return recognitionModel;
            })();

            return modelLoadPromise;
        }

        async function ensureBorrowDatasetLoaded() {
            if (borrowDataset) return borrowDataset;
            if (datasetLoadPromise) return datasetLoadPromise;

            datasetLoadPromise = (async () => {
                const url = window.TOOLTRACE_BORROW_DATASET_URL;
                const res = await fetch(url, { cache: 'no-cache' });
                if (!res.ok) throw new Error(`Dataset load failed (${res.status})`);
                borrowDataset = await res.json();
                return borrowDataset;
            })();

            return datasetLoadPromise;
        }

        cameraBtn.addEventListener('click', async () => {
            try {
                cameraStatus.textContent = '⏳ Accessing camera...';
                captureBtn.disabled = false;
                
                const constraints = {
                    video: {
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                };
                
                stream = await navigator.mediaDevices.getUserMedia(constraints);
                cameraStream.srcObject = stream;
                
                // Ensure video plays
                cameraStream.onloadedmetadata = () => {
                    cameraStream.play().catch(err => {
                        console.error('Play error:', err);
                    });
                };
                
                cameraModal.classList.add('is-open');
                cameraStatus.textContent = '✅ Camera ready. Click Capture Photo to recognize the equipment.';

                // Warm up model in the background to reduce the wait after capture.
                ensureRecognitionModelLoaded().catch((e) => {
                    console.warn('Model load failed:', e);
                });
                ensureBorrowDatasetLoaded().catch((e) => {
                    console.warn('Dataset load failed:', e);
                });
            } catch (err) {
                let errorMsg = '❌ Camera error: ';
                if (err.name === 'NotAllowedError') {
                    errorMsg += 'Permission denied. Allow camera access in settings.';
                } else if (err.name === 'NotFoundError') {
                    errorMsg += 'No camera found on this device.';
                } else {
                    errorMsg += err.message;
                }
                cameraStatus.textContent = errorMsg;
                console.error('Camera error:', err);
            }
        });

        closeCameraBtn.addEventListener('click', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            cameraModal.classList.remove('is-open');
            cameraStatus.textContent = '';
            captureBtn.disabled = false;
        });

        captureBtn.addEventListener('click', async () => {
            cameraStatus.textContent = '⏳ Analyzing image...';
            captureBtn.disabled = true;
            
            const ctx = captureCanvas.getContext('2d');
            captureCanvas.width = cameraStream.videoWidth;
            captureCanvas.height = cameraStream.videoHeight;
            ctx.drawImage(cameraStream, 0, 0);

            try {
                cameraStatus.textContent = '🤖 Recognizing objects (Teachable Machine)...';
                const model = await ensureRecognitionModelLoaded();
                const tmPredictions = await model.predict(captureCanvas);
                const predictions = (tmPredictions || [])
                    .map((p) => ({
                        className: p.className,
                        probability: Number(p.probability) || 0
                    }))
                    .sort((a, b) => b.probability - a.probability)
                    .slice(0, 5);
                console.log('Recognition predictions:', predictions);

                const topPredText = (predictions || [])
                    .slice(0, 3)
                    .map(p => `${p.className} (${Math.round((p.probability || 0) * 100)}%)`)
                    .join(', ');

                const ds = await ensureBorrowDatasetLoaded().catch(() => ({}));
                const match = matchEquipmentByRecognition(predictions, ds);

                cameraStatus.textContent = match && match.equipment
                    ? `✅ Found: ${match.equipment.name}. Top predictions: ${topPredText}`
                    : `⚠️ No clear match. Top predictions: ${topPredText}. Try a closer photo or type the item name.`;

                if (match && match.equipment) {
                    document.getElementById('searchInput').value = match.equipment.name;
                    setTimeout(() => {
                        closeCameraBtn.click();
                        document.getElementById('searchForm').submit();
                    }, 650);
                } else {
                    captureBtn.disabled = false;
                }
            } catch (error) {
                cameraStatus.textContent = '❌ Recognition failed. Please try again.';
                console.error('Recognition error:', error);
                captureBtn.disabled = false;
            }
        });

        // Turn model labels into a best equipment match.
        function matchEquipmentByRecognition(predictions, dataset) {
            if (!Array.isArray(predictions) || predictions.length === 0) return null;

            const predicted = predictions
                .map(p => ({
                    label: String(p.className || '').toLowerCase(),
                    prob: Number(p.probability) || 0
                }))
                .filter(p => p.label && p.prob > 0);

            if (predicted.length === 0) return null;

            const topProb = Math.max(...predicted.map(p => p.prob));

            const datasetEntries = [];
            if (dataset && typeof dataset === 'object') {
                for (const [labelName, keywords] of Object.entries(dataset)) {
                    if (!Array.isArray(keywords)) continue;
                    datasetEntries.push({
                        label: String(labelName || '').toLowerCase(),
                        keywords: keywords.map(k => String(k || '').toLowerCase()).filter(Boolean)
                    });
                }
            }

            let best = null;
            let bestScore = 0;
            let secondBestScore = 0;

            for (const equipment of ALL_EQUIPMENT) {
                const keywords = (equipment.keywords || []).map(k => String(k).toLowerCase());
                const name = String(equipment.name || '').toLowerCase();

                let score = 0;
                for (const p of predicted) {
                    for (const kw of keywords) {
                        if (!kw || kw.length < 3) continue;
                        if (p.label.includes(kw) || kw.includes(p.label)) {
                            score += 120 * p.prob;
                        }
                    }

                    for (const entry of datasetEntries) {
                        if (!entry.label) continue;
                        const classMatched = (p.label.includes(entry.label) || entry.label.includes(p.label));
                        if (!classMatched) continue;

                        const equipmentHasClass =
                            name.includes(entry.label) ||
                            keywords.some(ekw => ekw === entry.label || ekw.includes(entry.label) || entry.label.includes(ekw));

                        if (!equipmentHasClass) continue;

                        score += 160 * p.prob;
                        for (const dkw of entry.keywords) {
                            if (!dkw || dkw.length < 3) continue;
                            for (const ekw of keywords) {
                                if (ekw === dkw) {
                                    score += 120 * p.prob;
                                } else if (ekw.includes(dkw) || dkw.includes(ekw)) {
                                    score += 45 * p.prob;
                                }
                            }
                            if (name.includes(dkw)) score += 50 * p.prob;
                        }
                    }

                    const nameWords = name.split(/[^a-z0-9]+/).filter(Boolean);
                    for (const w of nameWords) {
                        if (w.length < 4) continue;
                        if (p.label.includes(w)) score += 60 * p.prob;
                    }
                }

                if (score > bestScore) {
                    secondBestScore = bestScore;
                    bestScore = score;
                    best = equipment;
                } else if (score > secondBestScore) {
                    secondBestScore = score;
                }
            }

            const minProb = 0.60;
            const minBestScore = 70;
            const minSeparation = 20;

            if (topProb < minProb) return null;
            if (bestScore < minBestScore) return null;
            if ((bestScore - secondBestScore) < minSeparation) return null;

            return { equipment: best, bestScore, secondBestScore, topProb };
        }

        function findClosestEquipment(searchText) {
            let bestMatch = null;
            let bestDistance = Infinity;
            
            for (const equipment of ALL_EQUIPMENT) {
                const distance = levenshteinDistance(searchText, equipment.name.toLowerCase());
                if (distance < bestDistance && distance < 8) {
                    bestDistance = distance;
                    bestMatch = equipment;
                }
                for (const keyword of equipment.keywords) {
                    const keyDistance = levenshteinDistance(searchText, keyword.toLowerCase());
                    if (keyDistance < bestDistance && keyDistance < 6) {
                        bestDistance = keyDistance;
                        bestMatch = equipment;
                    }
                }
            }
            return bestMatch;
        }

        function levenshteinDistance(str1, str2) {
            const len1 = str1.length;
            const len2 = str2.length;
            const d = [];
            for (let i = 0; i <= len1; i++) d[i] = [i];
            for (let j = 0; j <= len2; j++) d[0][j] = j;
            for (let i = 1; i <= len1; i++) {
                for (let j = 1; j <= len2; j++) {
                    const cost = str1[i - 1] === str2[j - 1] ? 0 : 1;
                    d[i][j] = Math.min(d[i-1][j]+1, d[i][j-1]+1, d[i-1][j-1]+cost);
                }
            }
            return d[len1][len2];
        }

        // === CART MANAGEMENT ===
        const BORROW_DRAFT_KEY = 'tooltrace_borrow_draft';
        const LEGACY_BORROW_DRAFT_KEY = 'equilink_borrow_draft';
        const CART_KEY = 'tooltrace_cart';
        const LEGACY_CART_KEY = 'equilink_cart';
        const EQUIPMENT_IMAGE_BY_ID = <?php echo json_encode($equipment_image_by_id, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function resolveEquipmentImageUrl(item) {
            const fromCart = item.image && String(item.image).trim();
            if (fromCart) return fromCart;
            const fromCatalog = EQUIPMENT_IMAGE_BY_ID[item.id];
            return fromCatalog && String(fromCatalog).trim() ? fromCatalog : '';
        }

        function normalizeCart() {
            let raw = localStorage.getItem(CART_KEY);
            if (raw == null) raw = localStorage.getItem(LEGACY_CART_KEY);
            let c = [];
            try {
                c = raw ? JSON.parse(raw) : [];
            } catch (e) {
                console.error('Error parsing cart data:', e);
                localStorage.removeItem(CART_KEY);
                localStorage.removeItem(LEGACY_CART_KEY);
                c = [];
            }
            let changed = false;
            if (localStorage.getItem(LEGACY_CART_KEY) && !localStorage.getItem(CART_KEY)) changed = true;
            c.forEach((item) => {
                if (item.qty == null || item.qty < 1) { item.qty = 1; changed = true; }
                if (item.image === undefined) { item.image = ''; changed = true; }
                const catalogUrl = EQUIPMENT_IMAGE_BY_ID[item.id];
                if (catalogUrl && (!String(item.image).trim())) { item.image = catalogUrl; changed = true; }
                if (item.category === undefined) { item.category = ''; changed = true; }
            });
            if (changed) {
                localStorage.setItem(CART_KEY, JSON.stringify(c));
                localStorage.removeItem(LEGACY_CART_KEY);
            }
            return c;
        }

        let cart = normalizeCart();
        updateCartCount();

        function addToCart(id, name, image, category) {
            try {
                cart = normalizeCart();
                const idKey = String(id);
                const existing = cart.find(i => String(i.id) === idKey);
                if (existing) {
                    existing.qty = (Number(existing.qty) || 1) + 1;
                } else {
                    const img = (image && String(image).trim()) || EQUIPMENT_IMAGE_BY_ID[idKey] || '';
                    cart.push({ id: idKey, name, image: img, category: category || '', qty: 1 });
                }
                localStorage.setItem(CART_KEY, JSON.stringify(cart));
                localStorage.removeItem(LEGACY_CART_KEY);
                updateCartCount();
                const badge = document.getElementById('cartCount');
                badge.style.transform = 'scale(1.3)';
                setTimeout(() => { badge.style.transform = 'scale(1)'; }, 200);
            } catch (error) {
                console.error('Error in addToCart:', error);
                alert('Error: ' + error.message);
            }
        }

        function updateCartCount() {
            try {
                let raw = localStorage.getItem(CART_KEY);
                if (raw == null) raw = localStorage.getItem(LEGACY_CART_KEY);
                cart = raw ? JSON.parse(raw) : [];
                const n = cart.reduce((sum, i) => sum + (Number(i.qty) || 1), 0);
                const countElement = document.getElementById('cartCount');
                if (countElement) countElement.innerText = n;
            } catch (error) {
                console.error('Error updating cart count:', error);
                const countElement = document.getElementById('cartCount');
                if (countElement) countElement.innerText = '0';
            }
        }


        // === BAG MODAL ===
        const bagModal = document.getElementById('bagModal');
        const ACCOUNT_ORG_EMAIL = <?php echo json_encode($account_org_email); ?>;
        const ACCOUNT_ORG_NAME_DEFAULT = <?php echo json_encode($account_org_name); ?>;

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function saveBorrowDraft() {
            sessionStorage.setItem(BORROW_DRAFT_KEY, JSON.stringify({
                dateNeeded: document.getElementById('borrowDateNeeded').value,
                returnDate: document.getElementById('borrowReturnDate').value,
                purpose: document.getElementById('borrowPurpose').value,
                officer: document.getElementById('borrowOfficer').value,
                orgName: document.getElementById('borrowOrgName').value,
                location: document.getElementById('borrowLocation').value
            }));
        }

        function loadBorrowDraft() {
            try {
                let raw = sessionStorage.getItem(BORROW_DRAFT_KEY);
                if (raw == null) raw = sessionStorage.getItem(LEGACY_BORROW_DRAFT_KEY);
                const d = JSON.parse(raw || '{}');
                if (d.dateNeeded) document.getElementById('borrowDateNeeded').value = d.dateNeeded;
                if (d.returnDate) document.getElementById('borrowReturnDate').value = d.returnDate;
                if (d.purpose != null) document.getElementById('borrowPurpose').value = d.purpose;
                if (d.officer != null) document.getElementById('borrowOfficer').value = d.officer;
                if (d.orgName != null) document.getElementById('borrowOrgName').value = d.orgName;
                else if (ACCOUNT_ORG_NAME_DEFAULT) document.getElementById('borrowOrgName').value = ACCOUNT_ORG_NAME_DEFAULT;
                if (d.location != null) document.getElementById('borrowLocation').value = d.location;
            } catch (e) { /* ignore */ }
        }

        ['borrowDateNeeded', 'borrowReturnDate', 'borrowPurpose', 'borrowOfficer', 'borrowOrgName', 'borrowLocation'].forEach((id) => {
            const el = document.getElementById(id);
            el.addEventListener('input', saveBorrowDraft);
            el.addEventListener('change', saveBorrowDraft);
        });

        function openBagModal() {
            bagModal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            loadBorrowDraft();
            const orgField = document.getElementById('borrowOrgName');
            if (orgField && !orgField.value.trim() && ACCOUNT_ORG_NAME_DEFAULT) {
                orgField.value = ACCOUNT_ORG_NAME_DEFAULT;
            }
            renderBagLineItems();
            document.getElementById('bagModalClose').focus();
        }

        function closeBagModal() {
            saveBorrowDraft();
            bagModal.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        function renderBagLineItems() {
            cart = normalizeCart();
            const wrap = document.getElementById('bagModalLineItems');
            const emptyMsg = document.getElementById('bagModalEmptyMsg');
            const details = document.getElementById('bagBorrowDetails');
            const confirmBtn = document.getElementById('bagModalConfirm');

            if (cart.length === 0) {
                wrap.innerHTML = '';
                emptyMsg.hidden = false;
                details.classList.add('is-disabled');
                confirmBtn.disabled = true;
                return;
            }

            emptyMsg.hidden = true;
            details.classList.remove('is-disabled');
            confirmBtn.disabled = false;

            wrap.innerHTML = cart.map((item, index) => {
                const imgUrl = resolveEquipmentImageUrl(item);
                const thumb = imgUrl
                    ? `<img class="bag-item-thumb" src="${escapeHtml(imgUrl)}" alt="" loading="lazy">`
                    : '<div class="bag-item-thumb-ph" aria-hidden="true"></div>';
                const nameSafe = escapeHtml(item.name);
                const catSafe = escapeHtml(item.category || 'Equipment');
                const idSafe = escapeHtml(String(item.id));
                const q = Number(item.qty) || 1;

                const eq = Array.isArray(ALL_EQUIPMENT) ? ALL_EQUIPMENT.find(x => String(x.id) === String(item.id)) : null;
                const units = eq && Array.isArray(eq.units) ? eq.units : [];
                const unavail = eq && Array.isArray(eq.unavailable_unit_ids) ? eq.unavailable_unit_ids.map(String) : [];
                const availableUnits = units.filter(u => u && !unavail.includes(String(u.unit_id)));

                const selected = Array.isArray(item.selected_unit_ids) ? item.selected_unit_ids.map(String) : [];
                const unitOptions = availableUnits.map(u => {
                    const uid = String(u.unit_id);
                    const checked = selected.includes(uid) ? 'checked' : '';
                    const label = `Unit ${escapeHtml(String(u.unit_number))}`;
                    return `
                        <label style="display:inline-flex; align-items:center; gap:6px; margin:4px 10px 0 0; font-size:12px; color:#2c3e50;">
                            <input type="checkbox" data-unit-id="${escapeHtml(uid)}" ${checked} onchange="toggleUnitSelection(${index}, this)">
                            <span>${label}</span>
                        </label>
                    `;
                }).join('');

                return `
                    <div class="bag-item-row">
                        <div class="bag-item-thumb-wrap">${thumb}</div>
                        <div class="bag-item-main">
                            <p class="bag-item-name">${nameSafe}</p>
                            <p class="bag-item-cat">${catSafe}</p>
                            <p class="bag-item-meta">Equipment ID · ${idSafe}</p>
                            <div class="bag-item-actions">
                                <span class="bag-qty-label">Qty</span>
                                <div class="bag-qty-controls">
                                    <button type="button" class="bag-qty-btn" onclick="changeBagQty(${index}, -1)" aria-label="Decrease quantity">−</button>
                                    <strong>${q}</strong>
                                    <button type="button" class="bag-qty-btn" onclick="changeBagQty(${index}, 1)" aria-label="Increase quantity">+</button>
                                </div>
                                <button type="button" class="bag-item-remove" onclick="removeFromBag(${index})">Remove</button>
                            </div>
                            <div style="margin-top:10px; font-size:12px; color:#7f8c8d;">
                                Select unit(s) (${Math.min(q, availableUnits.length)}/${availableUnits.length} available)
                            </div>
                            <div style="margin-top:6px; display:flex; flex-wrap:wrap;">
                                ${unitOptions || '<span style="font-size:12px; color:#e74c3c;">No units available</span>'}
                            </div>
                        </div>
                    </div>`;
            }).join('');
        }

        function toggleUnitSelection(index, checkbox) {
            cart = normalizeCart();
            if (!cart[index]) return;
            const unitId = String(checkbox.getAttribute('data-unit-id') || '');
            if (!unitId) return;
            const selected = Array.isArray(cart[index].selected_unit_ids) ? cart[index].selected_unit_ids.map(String) : [];
            const qty = Number(cart[index].qty) || 1;

            if (checkbox.checked) {
                if (!selected.includes(unitId)) {
                    if (selected.length >= qty) {
                        checkbox.checked = false;
                        showBorrowAlert('You already selected the required number of units for this item.');
                        return;
                    }
                    selected.push(unitId);
                }
            } else {
                const idx = selected.indexOf(unitId);
                if (idx >= 0) selected.splice(idx, 1);
            }

            cart[index].selected_unit_ids = selected;
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
            localStorage.removeItem(LEGACY_CART_KEY);
        }

        function changeBagQty(index, delta) {
            cart = normalizeCart();
            if (!cart[index]) return;
            const next = Math.max(1, (Number(cart[index].qty) || 1) + delta);
            cart[index].qty = next;

            const selected = Array.isArray(cart[index].selected_unit_ids) ? cart[index].selected_unit_ids : [];
            if (selected.length > next) {
                cart[index].selected_unit_ids = selected.slice(0, next);
            }

            localStorage.setItem(CART_KEY, JSON.stringify(cart));
            localStorage.removeItem(LEGACY_CART_KEY);
            updateCartCount();
            renderBagLineItems();
        }

        function removeFromBag(index) {
            cart = normalizeCart();
            cart.splice(index, 1);
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
            localStorage.removeItem(LEGACY_CART_KEY);
            updateCartCount();
            renderBagLineItems();
        }

        async function confirmBorrowing() {
            cart = normalizeCart();
            if (cart.length === 0) {
                showBorrowAlert('Your bag is empty. Add equipment from the catalog first.');
                return;
            }
            const dateNeeded   = document.getElementById('borrowDateNeeded').value;
            const returnDate   = document.getElementById('borrowReturnDate').value;
            const purpose      = document.getElementById('borrowPurpose').value.trim();
            const officer      = document.getElementById('borrowOfficer').value.trim();
            const officerIdEl  = document.getElementById('borrowOfficerId');
            const orgName      = document.getElementById('borrowOrgName').value.trim();
            const location     = document.getElementById('borrowLocation').value.trim();
            const orgEmail     = String(ACCOUNT_ORG_EMAIL || '').trim();

            if (!dateNeeded || !returnDate || !purpose || !officer) {
                showBorrowAlert('Please fill in all borrowing details: dates, purpose, and officer in charge.');
                return;
            }
            if (!officerIdEl || !officerIdEl.files || !officerIdEl.files[0]) {
                showBorrowAlert('Please upload a valid ID of the Officer-in-Charge.');
                return;
            }
            const officerIdFile = officerIdEl.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(String(officerIdFile.type || ''))) {
                showBorrowAlert('Invalid file type. Please upload a JPG, PNG, or PDF.');
                return;
            }
            if (!orgName) { showBorrowAlert('Please enter the organization name.'); return; }
            if (!orgEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(orgEmail)) {
                showBorrowAlert('Your organization email is missing. Sign in from the home page with your organization account first.');
                return;
            }
            if (returnDate < dateNeeded) { showBorrowAlert('Return date must be on or after the date needed.'); return; }

            for (const item of cart) {
                const eq = Array.isArray(ALL_EQUIPMENT) ? ALL_EQUIPMENT.find(x => String(x.id) === String(item.id)) : null;
                const available = eq ? Number(eq.available) || 0 : 0;
                const qty = Number(item.qty) || 1;
                if (qty > available) {
                    showBorrowAlert(`Only ${available} unit(s) available. You cannot borrow more than the available stock.`);
                    return;
                }
                const selected = Array.isArray(item.selected_unit_ids) ? item.selected_unit_ids : [];
                if (selected.length !== qty) {
                    showBorrowAlert(`Please select exactly ${qty} unit(s) for ${item.name}.`);
                    return;
                }
            }

            const confirmBtn = document.getElementById('bagModalConfirm');
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Submitting…';

            const payload = {
                student_name:       '<?php echo addslashes($user["name"]); ?>',
                student_email:      orgEmail,
                organization_name:  orgName,
                organization_email: orgEmail,
                officer_in_charge:  officer,
                location:           location,
                items: cart.map(i => ({ id: i.id, name: i.name, category: i.category, qty: Number(i.qty) || 1, unit_ids: i.selected_unit_ids })),
                purpose:            purpose,
                date_needed:        dateNeeded,
                return_date:        returnDate,
                date_requested:     new Date().toISOString().slice(0, 10),
                returned:           false,
                reminders_sent: {
                    one_day_before: false,
                    on_due_date: false,
                    organization_on_due_date: false
                }
            };

            let requestId = null;
            try {
                const form = new FormData();
                form.append('payload', JSON.stringify(payload));
                form.append('oic_id_file', officerIdFile);

                const res  = await fetch('save_request.php', {
                    method:  'POST',
                    body:    form
                });
                let json = null;
                let rawText = '';
                try {
                    json = await res.json();
                } catch (e) {
                    try { rawText = await res.text(); } catch (e2) { rawText = ''; }
                }
                if (!res.ok || !json || !json.success) {
                    const msg = (json && json.error)
                        ? json.error
                        : (rawText ? rawText : 'Could not save your request. Please try again.');
                    showBorrowAlert(msg);
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'CONFIRM REQUEST';
                    return;
                }
                requestId = json.request_id;
            } catch (err) {
                showBorrowAlert('Network error. Please check your connection and try again.');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'CONFIRM REQUEST';
                return;
            }

            const qrPayload = JSON.stringify({ type: 'PICKUP', requestId, orgName, dateNeeded, returnDate });
            const qrData    = encodeURIComponent(qrPayload);

            document.getElementById('qrImageWrap').innerHTML =
            `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrData}" alt="Pickup QR Code" width="200" height="200">`;
            document.getElementById('qrReqIdLabel').textContent = requestId;
            document.getElementById('qrSummaryItems').textContent =
                cart.map(i => `${i.name} ×${Number(i.qty)||1}`).join(', ');

            document.getElementById('bagModalLineItems').style.display  = 'none';
            document.getElementById('bagBorrowDetails').style.display   = 'none';
            document.getElementById('bagModalEmptyMsg').hidden          = true;
            document.getElementById('qrSuccessScreen').classList.add('is-visible');

            const footer = document.getElementById('bagModalFooter');
            footer.innerHTML = `<div class="btn-group"><button type="button" class="btn-submit" style="flex:1" id="bagDoneBtn">Done</button></div>`;
            document.getElementById('bagDoneBtn').onclick = closeAfterQr;

            sessionStorage.removeItem(BORROW_DRAFT_KEY);
            sessionStorage.removeItem(LEGACY_BORROW_DRAFT_KEY);
            localStorage.removeItem(CART_KEY);
            localStorage.removeItem(LEGACY_CART_KEY);
            cart = [];
            updateCartCount();
        }

        function closeAfterQr() {
            closeBagModal();
            document.getElementById('bagModalLineItems').style.display  = '';
            document.getElementById('bagBorrowDetails').style.display   = '';
            document.getElementById('qrSuccessScreen').classList.remove('is-visible');

            const footer = document.getElementById('bagModalFooter');
            footer.innerHTML = `
                <div class="btn-group">
                    <button type="button" class="btn-back"   id="bagModalCancel">Cancel</button>
                    <button type="button" class="btn-submit" id="bagModalConfirm" disabled>CONFIRM REQUEST</button>
                </div>`;
            document.getElementById('bagModalCancel').onclick  = closeBagModal;
            document.getElementById('bagModalConfirm').onclick = confirmBorrowing;
        }

        document.getElementById('cartBtn').addEventListener('click', openBagModal);
        document.getElementById('bagModalClose').addEventListener('click', closeBagModal);
        document.getElementById('bagModalCancel').onclick  = closeBagModal;
        document.getElementById('bagModalConfirm').onclick = confirmBorrowing;
        bagModal.addEventListener('click', (e) => { if (e.target === bagModal) closeBagModal(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && bagModal.classList.contains('is-open')) closeBagModal();
        });

        // Auto-search on typing (Google-like): updates even when the input becomes empty.
        (function initLiveSearch() {
            const input = document.getElementById('searchInput');
            const form = document.getElementById('searchForm');
            if (!input || !form) return;

            const focusFlagKey = 'tooltraceBorrowListSearchFocus';
            const restoreFocus = () => {
                if (window.sessionStorage.getItem(focusFlagKey) !== '1') return;
                window.sessionStorage.removeItem(focusFlagKey);
                window.requestAnimationFrame(() => {
                    input.focus();
                    const end = input.value.length;
                    if (typeof input.setSelectionRange === 'function') {
                        input.setSelectionRange(end, end);
                    }
                });
            };
            restoreFocus();

            let t = null;
            const submitNow = () => {
                const params = new URLSearchParams(window.location.search);
                const v = String(input.value || '').trim();
                if (v) params.set('search', v);
                else params.delete('search');
                window.sessionStorage.setItem(focusFlagKey, '1');
                const next = `${window.location.pathname}?${params.toString()}`;
                window.location.assign(next);
            };

            input.addEventListener('input', () => {
                if (t) window.clearTimeout(t);
                t = window.setTimeout(submitNow, 250);
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') e.preventDefault();
            });
        })();
    </script>
    <script src="assets/js/tooltrace-speech.js"></script>
    <script>
        tooltraceInitVoiceSearch({ micId: 'micBtn', inputId: 'searchInput', formId: 'searchForm' });
    </script>
</body>
</html>
