<?php
/**
 * ToolTrace Admin - Inventory Management
 * Features: PHP Search, Voice Search, TTS, and QR Code Generation
 */

$admin = ['name' => 'Maintenance Staff', 'initials' => 'MS'];

// Dataset based on Wireframe Page 23/24
$inventory = [
    ['id' => 'EQ-PRJ-001', 'name' => 'Epson EB-2250U Projector', 'category' => 'Visual', 'stock' => 5, 'location' => 'Cabinet A'],
    ['id' => 'EQ-CAM-005', 'name' => 'Canon EOS R5 Camera', 'category' => 'Photography', 'stock' => 2, 'location' => 'Vault 1'],
    ['id' => 'EQ-CAB-012', 'name' => 'HDMI Cable (1.8m)', 'category' => 'Accessories', 'stock' => 15, 'location' => 'Bin 3'],
    ['id' => 'EQ-MIC-002', 'name' => 'Sony Wireless Mic', 'category' => 'Audio', 'stock' => 4, 'location' => 'Cabinet B'],
];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filtered_inventory = [];

foreach ($inventory as $item) {
    if ($search === '' || stripos($item['name'], $search) !== false || stripos($item['id'], $search) !== false) {
        $filtered_inventory[] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ToolTrace | Inventory Management</title>
    <style>
        :root { --primary: #2c3e50; --accent: #f1c40f; --bg: #f4f7f6; --header-h: 70px; --sidebar-w: 240px; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: var(--bg); }

        /* Controls */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-add { background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer; }
        
        .search-container { display: flex; background: white; border: 1px solid #ddd; border-radius: 25px; padding: 5px 15px; width: 350px; align-items: center; }
        .search-container input { border: none; padding: 8px; flex: 1; outline: none; }

        /* Table */
        .inventory-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #fafafa; font-size: 11px; color: #95a5a6; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #f9f9f9; font-size: 14px; }

        .btn-qr { background: #eee; border: 1px solid #ccc; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-qr:hover { background: var(--accent); }

        /* Modal for QR */
        #qrModal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; text-align: center; max-width: 300px; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="main-wrapper">
        <div class="page-header">
            <h1>Equipment Inventory</h1>
            <button class="btn-add">+ Add New Item</button>
        </div>

        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <form action="" method="GET" id="searchForm" class="search-container">
                <input type="text" name="search" id="searchInput" placeholder="Search inventory..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="button" id="micBtn" title="Voice search" style="background:none; border:none; cursor:pointer; font-size:1.2rem;">🎤</button>
            </form>
        </div>

        <div class="inventory-card">
            <table>
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Equipment Name</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Location</th>
                        <th>Tracking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($filtered_inventory as $item): ?>
                    <tr>
                        <td style="font-family:monospace;"><?php echo $item['id']; ?></td>
                        <td><strong><?php echo $item['name']; ?></strong></td>
                        <td><?php echo $item['category']; ?></td>
                        <td><?php echo $item['stock']; ?> units</td>
                        <td><?php echo $item['location']; ?></td>
                        <td>
                            <button class="btn-qr" onclick="generateQR('<?php echo $item['id']; ?>', '<?php echo $item['name']; ?>')">Generate QR</button>
                            <button type="button" onclick="tooltraceSpeak(<?php echo json_encode($item['name'] . ' located in ' . $item['location'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)" style="border:none; background:none; cursor:pointer;">🔊</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="qrModal">
        <div class="modal-content">
            <h3 id="qrTitle"></h3>
            <div id="qrCodeContainer"></div>
            <p id="qrSubtitle" style="font-size:12px; color:#7f8c8d; margin-top:10px;"></p>
            <button onclick="closeModal()" style="margin-top:15px; padding:8px 20px; cursor:pointer;">Close</button>
        </div>
    </div>

    <script>
        // --- 1. QR Code Generation ---
        function generateQR(id, name) {
            const modal = document.getElementById('qrModal');
            const container = document.getElementById('qrCodeContainer');
            const title = document.getElementById('qrTitle');
            const subtitle = document.getElementById('qrSubtitle');

            title.innerText = "Equipment QR Code";
            subtitle.innerText = "ID: " + id + "\n" + name;
            
            // Use Google Chart / QRServer API to generate image
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${id}`;
            container.innerHTML = `<img src="${qrUrl}" alt="QR Code" style="width:200px; height:200px;">`;
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

    </script>
    <script src="assets/js/tooltrace-tts.js"></script>
    <script src="assets/js/tooltrace-speech.js"></script>
    <script>
        tooltraceInitVoiceSearch({ micId: 'micBtn', inputId: 'searchInput', formId: 'searchForm' });
    </script>
</body>
</html>