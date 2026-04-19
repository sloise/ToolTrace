<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_staff_session.php';
require_once __DIR__ . '/includes/inventory_store.php';

$inventory = tooltrace_inventory_load();
$maxCatalogId = '';
$maxCatalogNum = 0;
$maxCatalogDigits = 3;
$inventoryById = [];
foreach ($inventory as $row) {
    $eq_id = (string) ($row['equipment_id'] ?? '');
    if (!empty($eq_id)) {
        $inventoryById[$eq_id] = $row;
        // Track the highest numeric part for generating new IDs
        if (preg_match('/(\d+)$/', $eq_id, $m)) {
            $num = (int)$m[1];
            if (empty($maxCatalogId) || $num > (int)preg_replace('/[^0-9]/', '', $maxCatalogId)) {
                $maxCatalogId = $eq_id;
            }
            if ($num > $maxCatalogNum) {
                $maxCatalogNum = $num;
                $maxCatalogDigits = max($maxCatalogDigits, strlen((string) $m[1]));
            }
        }
    }
}

$nextCatalogId = 'EQ-' . str_pad((string) ($maxCatalogNum + 1), $maxCatalogDigits, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Staff Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        :root { 
            --navy: #2c3e50; 
            --yellow: #f1c40f; 
            --gray-bg: #f4f7f6;
        }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: var(--gray-bg); }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 32px; margin: 0; color: #333; }
        
        .search-container { display: flex; gap: 10px; align-items: center; margin-bottom: 30px; max-width: 600px; }
        .search-input { flex: 1; padding: 12px 18px; border: 1px solid #ddd; border-radius: 25px; font-size: 14px; }
        .search-input:focus { outline: none; border-color: var(--navy); box-shadow: 0 0 0 3px rgba(44,62,80,0.1); }

        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--navy); color: white; }
        .btn-primary:hover { background: #1a252f; }
        .btn-secondary { background: white; color: var(--navy); border: 1px solid #ddd; }
        .btn-secondary:hover { background: #f8f9fa; }

        .mic-btn { width: 44px; height: 44px; border-radius: 50%; background: white; border: 1px solid #ddd; padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .mic-btn.listening { background: #e74c3c; color: white; animation: pulse 1s infinite; }

        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); } 50% { box-shadow: 0 0 0 8px rgba(231, 76, 60, 0.2); } }

        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        .item-card { background: white; border-radius: 12px; padding: 25px; border: 1px solid #eee; transition: 0.3s; }
        .item-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.08); }
        .item-card.hidden { display: none; }

        .item-image-wrap {
            width: 100%;
            height: 210px;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f0f2f4;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            border: 1px solid #eee;
        }
        .item-image { width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; }
        .item-name { font-size: 18px; color: #333; margin: 0; font-weight: 700; }
        .item-brand { font-size: 13px; color: #555; margin: 4px 0 8px; font-weight: 600; }
        .item-desc { font-size: 14px; color: #666; margin-bottom: 10px; }

        .details-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; }
        .status-line { font-size: 14px; font-weight: 600; margin-bottom: 15px; }

        .units-container { background: #f9f9f9; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f0f0f0; max-height: 130px; overflow-y: auto; }
        .units-header { font-size: 11px; font-weight: 700; color: #777; padding: 10px 12px; border-bottom: 1px solid #e0e0e0; position: sticky; top: 0; background: #f9f9f9; }
        .unit-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; font-size: 13px; border-bottom: 1px solid #efefef; }
        .unit-row:last-child { border-bottom: none; }

        .card-footer { display: flex; justify-content: space-between; align-items: center; }
        .print-btn { background: var(--navy); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; transition: 0.2s; }
        .print-btn:hover { background: #1a252f; }
        .print-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .mgmt-icons { display: flex; gap: 15px; font-size: 18px; opacity: 0; transition: 0.3s; }
        .item-card:hover .mgmt-icons,
        .item-card:focus-within .mgmt-icons { opacity: 1; }

        .icon { cursor: pointer; transition: 0.2s; }
        .icon:hover { transform: scale(1.15); }
        .icon.edit { color: #f39c12; }
        .icon.delete { color: #e74c3c; }
        .icon.speak { color: #3498db; }

        .no-results { text-align: center; padding: 60px 20px; color: #999; grid-column: 1 / -1; }
        .no-results i { font-size: 48px; margin-bottom: 15px; color: #ddd; }

        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .modal-header h2 { margin: 0; color: var(--navy); font-size: 22px; }
        .modal-close { background: none; border: none; font-size: 28px; color: #aaa; cursor: pointer; padding: 0; }
        .modal-close:hover { color: #333; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
        .form-control, .form-textarea { width: 100%; padding: 11px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: inherit; }
        .form-control:focus, .form-textarea:focus { outline: none; border-color: var(--navy); box-shadow: 0 0 0 3px rgba(44,62,80,0.1); }
        .form-textarea { resize: vertical; min-height: 80px; }
        .image-preview { width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 10px; border: 1px solid #ddd; display: none; }

        .submit-btn { width: 100%; padding: 12px 20px; background: var(--navy); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 15px; margin-top: 10px; }
        .submit-btn:hover { background: #1a252f; }

        .speech-status { font-size: 12px; margin-top: 8px; color: #e74c3c; display: none; }
        .speech-status.active { display: block; }

        /* Toast */
        #uiToast { display: none; position: fixed; bottom: 26px; left: 50%; transform: translateX(-50%); background: #2c3e50; color: #fff; padding: 14px 20px; border-radius: 12px; font-size: 14px; font-weight: 700; z-index: 10000; box-shadow: 0 10px 30px rgba(0,0,0,0.25); max-width: 420px; text-align: center; }
        #uiToast.success { background: #27ae60; }
        #uiToast.warning { background: #e67e22; }
        #uiToast.error { background: #e74c3c; }

        /* Confirm modal */
        #confirmModal { display: none; position: fixed; inset: 0; z-index: 10001; align-items: center; justify-content: center; background: rgba(0,0,0,0.55); backdrop-filter: blur(2px); padding: 16px; }
        #confirmModal.show { display: flex; }
        .confirm-box { width: 100%; max-width: 420px; background: #fff; border-radius: 14px; box-shadow: 0 15px 60px rgba(0,0,0,0.28); overflow: hidden; border: 1px solid rgba(0,0,0,0.06); }
        .confirm-head { padding: 16px 18px; background: #0f172a; color: #fff; font-weight: 800; letter-spacing: 0.2px; }
        .confirm-body { padding: 16px 18px; color: #111827; font-size: 14px; line-height: 1.45; }
        .confirm-actions { display: flex; gap: 10px; justify-content: flex-end; padding: 0 18px 18px 18px; }
        .confirm-btn { border: none; border-radius: 10px; padding: 10px 14px; font-weight: 800; cursor: pointer; }
        .confirm-btn.cancel { background: #f1f5f9; color: #0f172a; }
        .confirm-btn.danger { background: #e74c3c; color: #fff; }

        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div id="uiToast" class="success" role="status" aria-live="polite"></div>

    <div id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="confirm-box">
            <div class="confirm-head" id="confirmTitle">Confirm</div>
            <div class="confirm-body" id="confirmMessage">Are you sure?</div>
            <div class="confirm-actions">
                <button type="button" class="confirm-btn cancel" id="confirmCancelBtn">Cancel</button>
                <button type="button" class="confirm-btn danger" id="confirmOkBtn">Delete</button>
            </div>
        </div>
    </div>

    <main class="main-wrapper">
        <div class="header">
            <h1>Equipment Inventory</h1>
            <div style="display: flex; gap: 12px;">
                <button class="btn btn-secondary" id="printAllBtn" onclick="printAllDatabaseQR()"><i class="fa-solid fa-print"></i> Print All QRs</button>
                <button class="btn btn-primary" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Add New Asset</button>
            </div>
        </div>

        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Search by ID, name, or description..." onkeyup="filterCards()">
            <button type="button" class="mic-btn" id="micBtn" title="Search by voice">
                <i class="fa-solid fa-microphone"></i>
            </button>
        </div>
        <div class="speech-status" id="speechStatus">
            <i class="fa-solid fa-circle-notch" style="animation: spin 1s linear infinite;"></i> Listening...
        </div>

        <div class="cards-grid" id="cardsContainer">
            <?php foreach ($inventory as $item): 
                $eq_id     = (string) ($item['equipment_id'] ?? '');
                $qty       = (int)($item['quantity'] ?? 0);
                $borrowed  = (int)($item['borrowed_count'] ?? 0);
                $available = $qty - $borrowed;

                if ($available <= 0) {
                    $status_text  = 'Out of Stock (0/' . $qty . ' available)';
                    $status_color = '#e74c3c';
                } else {
                    $status_text  = 'Available (' . $available . '/' . $qty . ' in stock)';
                    $status_color = '#2ecc71';
                }

                $item_name = htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $item_brand = htmlspecialchars($item['brand'] ?? '', ENT_QUOTES, 'UTF-8');
                $item_desc = htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8');
                $search_text = htmlspecialchars(strtolower($eq_id . ' ' . ($item['name'] ?? '') . ' ' . ($item['brand'] ?? '') . ' ' . ($item['description'] ?? '')));
            ?>
            <div class="item-card" id="card-<?php echo htmlspecialchars($eq_id); ?>" data-searchtext="<?php echo $search_text; ?>">
                <div class="item-image-wrap">
                    <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/350x180?text=No+Image'); ?>" class="item-image" alt="<?php echo $item_name; ?>" onerror="this.onerror=null;this.src='https://via.placeholder.com/350x180?text=No+Image'">
                </div>
                <div class="item-name"><?php echo $item_name; ?></div>
                <?php if (!empty($item_brand)): ?>
                <div class="item-brand">Brand: <?php echo $item_brand; ?></div>
                <?php endif; ?>
                <div class="item-desc"><?php echo $item_desc; ?></div>
                
                <div class="details-row">
                    <span><strong>Asset ID:</strong> <?php echo htmlspecialchars($eq_id); ?></span>
                    <span><strong>Total Qty:</strong> <?php echo $qty; ?></span>
                </div>

                <div class="status-line" style="color: <?php echo $status_color; ?>;">
                    Status: <?php echo $status_text; ?>
                </div>

                <div class="units-container">
                    <div class="units-header">Unit Conditions</div>
                    <?php foreach(($item['conditions'] ?? []) as $index => $cond): ?>
                        <div class="unit-row">
                            <span>Unit #<?php echo $index + 1; ?></span>
                            <span><?php echo htmlspecialchars($cond); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card-footer">
                    <button class="print-btn" 
                        data-id="<?php echo htmlspecialchars($eq_id); ?>"
                        data-name="<?php echo htmlspecialchars($item['name'] ?? '', ENT_QUOTES); ?>"
                        data-qty="<?php echo $qty; ?>"
                        data-conditions='<?php echo json_encode($item['conditions'] ?? []); ?>'
                        onclick="handlePrintBtn(this)">
                        <i class="fa-solid fa-print"></i> Print QRs
                    </button>
                    <div class="mgmt-icons">
                        <i class="fa-solid fa-volume-high icon speak" title="Hear description"
                           role="button" tabindex="0"
                           data-name="<?php echo htmlspecialchars($item['name'] ?? '', ENT_QUOTES); ?>"
                           data-desc="<?php echo htmlspecialchars($item['description'] ?? '', ENT_QUOTES); ?>"
                           onclick="speakItem(this)"
                           onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();speakItem(this);}"></i>
                        <i class="fa-solid fa-pen-to-square icon edit" title="Edit"
                           role="button" tabindex="0"
                           data-id="<?php echo htmlspecialchars($eq_id); ?>"
                           data-name="<?php echo htmlspecialchars($item['name'] ?? '', ENT_QUOTES); ?>"
                           data-brand="<?php echo htmlspecialchars($item['brand'] ?? '', ENT_QUOTES); ?>"
                           data-desc="<?php echo htmlspecialchars($item['description'] ?? '', ENT_QUOTES); ?>"
                           data-qty="<?php echo $qty; ?>"
                           data-conditions='<?php echo json_encode($item['conditions'] ?? []); ?>'
                           onclick="editItemFromBtn(this)"
                           onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();editItemFromBtn(this);}"></i>
                        <i class="fa-solid fa-trash-can icon delete" title="Delete"
                           role="button" tabindex="0"
                           onclick="deleteItem('<?php echo htmlspecialchars($eq_id); ?>')"
                           onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();deleteItem('<?php echo htmlspecialchars($eq_id); ?>');}"></i>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="no-results" id="noResults" style="display: none;">
            <i class="fa-solid fa-search"></i>
            <p>No equipment found matching your search</p>
        </div>
    </main>

    <!-- ADD/EDIT MODAL -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Equipment</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="itemForm" onsubmit="handleFormSubmit(event)">
                <input type="hidden" id="itemId">
                
                <div class="form-group">
                    <label for="itemName">Equipment Name *</label>
                    <input type="text" id="itemName" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="itemBrand">Brand</label>
                    <input type="text" id="itemBrand" class="form-control" placeholder="e.g. Epson, Canon, Sony">
                </div>

                <div class="form-group">
                    <label for="itemDesc">Description *</label>
                    <textarea id="itemDesc" class="form-textarea" required></textarea>
                </div>

                <div class="form-group">
                    <label for="itemQuantity">Quantity *</label>
                    <input type="number" id="itemQuantity" class="form-control" min="1" value="1" required onchange="updateConditionFields()">
                </div>

                <div id="conditionFields"></div>

                <div class="form-group">
                    <label for="itemImage">Upload Image</label>
                    <input type="file" id="itemImage" class="form-control" accept="image/*" onchange="previewImage()">
                    <img id="imagePreview" class="image-preview">
                </div>

                <button type="submit" class="submit-btn"><i class="fa-solid fa-save"></i> Save Equipment</button>
            </form>
        </div>
    </div>

    <script>
        function showToast(msg, type = 'success') {
            const t = document.getElementById('uiToast');
            if (!t) return;
            t.textContent = String(msg || '');
            t.className = type;
            t.style.display = 'block';
            clearTimeout(t._hideTimer);
            t._hideTimer = setTimeout(() => { t.style.display = 'none'; }, 3500);
        }

        function showConfirm(message, { title = 'Confirm', okText = 'OK', cancelText = 'Cancel', danger = false } = {}) {
            const modal = document.getElementById('confirmModal');
            const titleEl = document.getElementById('confirmTitle');
            const msgEl = document.getElementById('confirmMessage');
            const okBtn = document.getElementById('confirmOkBtn');
            const cancelBtn = document.getElementById('confirmCancelBtn');
            if (!modal || !titleEl || !msgEl || !okBtn || !cancelBtn) {
                return Promise.resolve(window.confirm(String(message || 'Are you sure?')));
            }

            titleEl.textContent = String(title || 'Confirm');
            msgEl.textContent = String(message || 'Are you sure?');
            okBtn.textContent = String(okText || 'OK');
            cancelBtn.textContent = String(cancelText || 'Cancel');
            okBtn.classList.toggle('danger', Boolean(danger));

            modal.classList.add('show');
            cancelBtn.focus();

            return new Promise((resolve) => {
                const cleanup = (result) => {
                    modal.classList.remove('show');
                    okBtn.removeEventListener('click', onOk);
                    cancelBtn.removeEventListener('click', onCancel);
                    modal.removeEventListener('click', onBackdrop);
                    document.removeEventListener('keydown', onKey);
                    resolve(result);
                };

                const onOk = () => cleanup(true);
                const onCancel = () => cleanup(false);
                const onBackdrop = (e) => { if (e.target === modal) cleanup(false); };
                const onKey = (e) => {
                    if (e.key === 'Escape') cleanup(false);
                    if (e.key === 'Enter') cleanup(true);
                };

                okBtn.addEventListener('click', onOk);
                cancelBtn.addEventListener('click', onCancel);
                modal.addEventListener('click', onBackdrop);
                document.addEventListener('keydown', onKey);
            });
        }

        // ===== TEXT TO SPEECH FUNCTION =====
        function tooltraceSpeak(text) {
            if (!window.speechSynthesis) {
                showToast('Text-to-speech is not supported in your browser.', 'warning');
                return;
            }
            
            window.speechSynthesis.cancel();
            
            if (typeof text !== 'string') {
                text = String(text);
            }
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 1;
            utterance.pitch = 1;
            utterance.volume = 1;
            
            window.speechSynthesis.speak(utterance);
        }

        function speakItem(btn) {
            const name = btn.getAttribute('data-name') || '';
            const desc = btn.getAttribute('data-desc') || '';
            tooltraceSpeak(name + '. ' + desc);
        }

        window.INVENTORY_BY_ID = <?php echo json_encode($inventoryById, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.INVENTORY_NEXT_ID = '<?php echo htmlspecialchars($nextCatalogId, ENT_QUOTES, 'UTF-8'); ?>';

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function incrementEquipmentId(id) {
            const raw = String(id || '').trim();
            const m = raw.match(/^(.*?)(\d+)$/);
            if (!m) return raw;
            const prefix = m[1];
            const digits = m[2];
            const nextNum = (parseInt(digits, 10) || 0) + 1;
            return prefix + String(nextNum).padStart(digits.length, '0');
        }

        function generateQRCodeSVG(text, size = 100) {
            const container = document.createElement('div');
            container.style.display = 'none';
            document.body.appendChild(container);
            new QRCode(container, {
                text: text,
                width: size,
                height: size,
                correctLevel: QRCode.CorrectLevel.L
            });
            const dataUrl = container.querySelector('canvas').toDataURL('image/png');
            document.body.removeChild(container);
            return dataUrl;
        }

        function previewImage() {
            const file = document.getElementById('itemImage').files[0];
            const preview = document.getElementById('imagePreview');
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        function updateConditionFields() {
            const qty = parseInt(document.getElementById('itemQuantity').value);
            const fields = document.getElementById('conditionFields');
            fields.innerHTML = '';

            if (qty === 1) {
                fields.innerHTML = `<div class="form-group">
                    <label for="itemCondition">Condition *</label>
                    <select id="itemCondition" class="form-control" required>
                        <option value="">-- Select Condition --</option>
                        <option value="NEW">New</option>
                        <option value="EXCELLENT">Excellent</option>
                        <option value="GOOD" selected>Good</option>
                        <option value="FAIR">Fair</option>
                    </select>
                </div>`;
            } else {
                for (let i = 1; i <= qty; i++) {
                    fields.innerHTML += `<div class="form-group">
                        <label for="condition${i}">Unit ${i} Condition *</label>
                        <select id="condition${i}" class="form-control" required>
                            <option value="">-- Select Condition --</option>
                            <option value="NEW">New</option>
                            <option value="EXCELLENT">Excellent</option>
                            <option value="GOOD" selected>Good</option>
                            <option value="FAIR">Fair</option>
                        </select>
                    </div>`;
                }
            }
        }

        function filterCards() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.item-card');
            let visible = 0;

            cards.forEach(card => {
                if (card.getAttribute('data-searchtext').includes(search)) {
                    card.classList.remove('hidden');
                    visible++;
                } else {
                    card.classList.add('hidden');
                }
            });

            document.getElementById('noResults').style.display = (visible === 0 && search !== '') ? 'block' : 'none';
        }

        function openAddModal() {
            document.getElementById('itemId').value = '';
            document.getElementById('modalTitle').textContent = 'Add New Equipment';
            document.getElementById('itemForm').reset();
            document.getElementById('itemQuantity').value = '1';
            updateConditionFields();
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('itemModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function editItemFromBtn(button) {
            const id = button.dataset.id;
            const name = button.dataset.name;
            const brand = button.dataset.brand || '';
            const desc = button.dataset.desc;
            const qty = parseInt(button.dataset.qty, 10);
            const conditions = JSON.parse(button.dataset.conditions || '[]');
            const card = button.closest('.item-card');
            const imageSrc = card ? card.querySelector('.item-image')?.src || '' : '';
            editItem(id, name, brand, desc, qty, conditions, imageSrc);
        }

        function editItem(id, name, brand, desc, qty, conditions, imageSrc = '') {
            document.getElementById('itemId').value = id;
            document.getElementById('modalTitle').textContent = 'Edit Equipment';
            document.getElementById('itemName').value = name;
            document.getElementById('itemBrand').value = brand;
            document.getElementById('itemDesc').value = desc;
            document.getElementById('itemQuantity').value = qty;
            updateConditionFields();
            
            if (qty === 1) {
                const cond = document.getElementById('itemCondition');
                if (cond) cond.value = conditions[0] || 'GOOD';
            } else {
                conditions.forEach((cond, idx) => {
                    const field = document.getElementById(`condition${idx + 1}`);
                    if (field) field.value = cond;
                });
            }

            const preview = document.getElementById('imagePreview');
            if (imageSrc) {
                preview.src = imageSrc;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
            
            document.getElementById('itemModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('itemModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function getStatusDisplay(available, qty) {
            if (available <= 0) {
                return { text: 'Out of Stock (0/' + qty + ' available)', color: '#e74c3c' };
            }
            return { text: 'Available (' + available + '/' + qty + ' in stock)', color: '#2ecc71' };
        }

        async function handleFormSubmit(event) {
            event.preventDefault();
            const rawId = document.getElementById('itemId').value.trim();
            const name = document.getElementById('itemName').value;
            const brand = document.getElementById('itemBrand').value.trim();
            const desc = document.getElementById('itemDesc').value;
            const qty = parseInt(document.getElementById('itemQuantity').value, 10);
            
            let conditions = [];
            if (qty === 1) {
                const cond = document.getElementById('itemCondition');
                conditions.push(cond ? cond.value : 'GOOD');
            } else {
                for (let i = 1; i <= qty; i++) {
                    const cond = document.getElementById(`condition${i}`);
                    conditions.push(cond ? cond.value : 'GOOD');
                }
            }

            const previewEl = document.getElementById('imagePreview');
            const previewSrc = (previewEl && previewEl.style.display !== 'none') ? (previewEl.src || '') : '';
            const baseImage = rawId && window.INVENTORY_BY_ID[rawId] ? (window.INVENTORY_BY_ID[rawId].image || '') : '';
            const imageSrc = (previewSrc && !String(previewSrc).startsWith('data:')) ? previewSrc : (baseImage || previewSrc || 'https://via.placeholder.com/350x180?text=No+Image');
            const base = rawId && window.INVENTORY_BY_ID[rawId] ? Object.assign({}, window.INVENTORY_BY_ID[rawId]) : {};
            const { units, ...baseWithoutUnits } = base;
            const newId = rawId || window.INVENTORY_NEXT_ID;
            const borrowedCount = rawId ? (parseInt(base.borrowed_count, 10) || 0) : 0;
            const unitPayload = Array.from({ length: qty }, (_, index) => ({
                unit_number: index + 1,
                condition_tag: conditions[index] || 'GOOD'
            }));

            const itemPayload = Object.assign({}, baseWithoutUnits, {
                equipment_id: newId,
                name: name,
                brand: brand,
                description: desc,
                quantity: qty,
                borrowed_count: borrowedCount,
                conditions: conditions,
                units: unitPayload,
                image: imageSrc,
                category: base.category || 'Equipment',
                keywords: Array.isArray(base.keywords) ? base.keywords : []
            });

            const confirmTitle = rawId ? 'Update Equipment' : 'Add Equipment';
            const confirmOkText = rawId ? 'Update' : 'Add';
            const confirmMsg = rawId
                ? `Proceed with updating ${newId}?`
                : `Proceed with adding ${newId}?`;
            const proceed = await showConfirm(confirmMsg, { title: confirmTitle, okText: confirmOkText, cancelText: 'Cancel', danger: false });
            if (!proceed) {
                return;
            }

            let saveSuccess = false;
            try {
                const fd = new FormData();
                fd.append('action', 'save');
                fd.append('mode', rawId ? 'update' : 'add');
                fd.append('item', JSON.stringify(itemPayload));
                const fileInput = document.getElementById('itemImage');
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    fd.append('image', fileInput.files[0]);
                }

                const res = await fetch('inventory_api.php', {
                    method: 'POST',
                    body: fd
                });
                const text = await res.text();
                let data = {};
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    console.error('inventory_api save response parse failed', res.status, text);
                    showToast('Server returned an invalid response while saving. Check the browser console for details.', 'error');
                    return;
                }
                if (!res.ok || !data.success) {
                    showToast(data.error || `Could not save equipment. (${res.status})`, 'error');
                    return;
                }

                if (data.image) {
                    itemPayload.image = data.image;
                }
                saveSuccess = true;
            } catch (e) {
                showToast('Network error while saving.', 'error');
                return;
            }

            if (!saveSuccess) {
                return;
            }

            window.INVENTORY_BY_ID[String(newId)] = itemPayload;

            const finalImageSrc = itemPayload.image || imageSrc;

            const available = qty - borrowedCount;
            const statusDisplay = getStatusDisplay(available, qty);

            if (rawId) {
                const card = document.getElementById(`card-${newId}`);
                if (card) {
                    card.innerHTML = createCardContent(newId, name, brand, desc, qty, finalImageSrc, conditions, statusDisplay.text, statusDisplay.color);
                    card.setAttribute('data-searchtext', `${newId} ${name} ${brand} ${desc}`.toLowerCase());
                }
                showToast('Equipment updated successfully.', 'success');
            } else {
                const cardHtml = `<div class="item-card" id="card-${escapeHtml(newId)}" data-searchtext="${String(newId).toLowerCase()} ${escapeHtml(name.toLowerCase())} ${escapeHtml(brand.toLowerCase())} ${escapeHtml(desc.toLowerCase())}">
                    ${createCardContent(newId, name, brand, desc, qty, finalImageSrc, conditions, statusDisplay.text, statusDisplay.color)}
                </div>`;
                document.getElementById('cardsContainer').insertAdjacentHTML('afterbegin', cardHtml);
                showToast(`Equipment added successfully. ID: ${newId} • Quantity: ${qty}`, 'success');

                window.INVENTORY_NEXT_ID = incrementEquipmentId(window.INVENTORY_NEXT_ID);
            }

            closeModal();
        }

        function createCardContent(id, name, brand, desc, qty, imageSrc, conditions, statusText, statusColor) {
            const brandBlock = brand ? `<div class="item-brand">Brand: ${escapeHtml(brand)}</div>` : '';
            return `
                <div class="item-image-wrap">
                    <img src="${escapeHtml(imageSrc)}" class="item-image" alt="${escapeHtml(name)}" onerror="this.src='https://via.placeholder.com/350x180?text=No+Image'">
                </div>
                <div class="item-name">${escapeHtml(name)}</div>
                ${brandBlock}
                <div class="item-desc">${escapeHtml(desc)}</div>
                <div class="details-row">
                    <span><strong>Asset ID:</strong> ${escapeHtml(id)}</span>
                    <span><strong>Total Qty:</strong> ${qty}</span>
                </div>
                <div class="status-line" style="color: ${escapeHtml(statusColor)};">Status: ${escapeHtml(statusText)}</div>
                <div class="units-container">
                    <div class="units-header">Unit Conditions</div>
                    ${conditions.map((cond, idx) => `<div class="unit-row"><span>Unit #${idx + 1}</span><span>${escapeHtml(cond)}</span></div>`).join('')}
                </div>
                <div class="card-footer">
                    <button class="print-btn"
                        data-id="${escapeHtml(id)}"
                        data-name="${escapeHtml(name)}"
                        data-qty="${qty}"
                        data-conditions='${escapeHtml(JSON.stringify(conditions))}'
                        onclick="handlePrintBtn(this)">
                        <i class="fa-solid fa-print"></i> Print QRs
                    </button>
                    <div class="mgmt-icons">
                        <i class="fa-solid fa-volume-high icon speak"
                           title="Hear Description"
                           role="button" tabindex="0"
                           data-name="${escapeHtml(name)}"
                           data-desc="${escapeHtml(desc)}"
                           onclick="speakItem(this)"
                           onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();speakItem(this);}">
                        </i>
                        <i class="fa-solid fa-pen-to-square icon edit" title="Edit"
                           role="button" tabindex="0"
                           data-id="${escapeHtml(id)}"
                           data-name="${escapeHtml(name)}"
                           data-brand="${escapeHtml(brand || '')}"
                           data-desc="${escapeHtml(desc)}"
                           data-qty="${qty}"
                           data-conditions='${escapeHtml(JSON.stringify(conditions))}'
                           onclick="editItemFromBtn(this)"
                           onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();editItemFromBtn(this);}">
                        </i>
                        <i class="fa-solid fa-trash-can icon delete" title="Delete"
                           role="button" tabindex="0"
                           onclick="deleteItem('${escapeHtml(id)}')"
                           onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();deleteItem('${escapeHtml(id)}');}">
                        </i>
                    </div>
                </div>`;
        }

        async function deleteItem(id) {
            const ok = await showConfirm(`Delete item ${escapeHtml(id)}? This cannot be undone.`, { title: 'Delete Equipment', okText: 'Delete', cancelText: 'Cancel', danger: true });
            if (!ok) return;
            let deleteSuccess = false;
            try {
                const res = await fetch('inventory_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', equipment_id: id })
                });
                const text = await res.text();
                let data = {};
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    console.error('inventory_api delete response parse failed', res.status, text);
                    showToast('Server returned an invalid response while deleting. Check the browser console for details.', 'error');
                    return;
                }
                if (!res.ok || !data.success) {
                    showToast(data.error || `Could not delete item. (${res.status})`, 'error');
                    return;
                }
                deleteSuccess = true;
            } catch (e) {
                showToast('Network error while deleting.', 'error');
                return;
            }
            if (!deleteSuccess) {
                return;
            }
            delete window.INVENTORY_BY_ID[String(id)];
            const card = document.getElementById(`card-${id}`);
            if (card) card.remove();
            showToast('Item deleted.', 'success');
        }

        function printBulkQR(id, name, qty, conditions) {
            const qrs = [];
            for (let i = 1; i <= qty; i++) {
                qrs.push({
                    id: `${id}-U${i}`,
                    name: name,
                    unit: i,
                    condition: Array.isArray(conditions) ? conditions[i-1] : 'GOOD'
                });
            }
            openPrintWindow(qrs);
        }

        function printAllDatabaseQR() {
            const qrs = [];
            <?php
            foreach ($inventory as $item) {
                $q = (int) ($item['quantity'] ?? 0);
                $nm = addslashes((string) ($item['name'] ?? ''));
                $eq_id = addslashes((string) ($item['equipment_id'] ?? ''));
                for ($i = 1; $i <= $q; $i++) {
                    $cond = isset($item['conditions'][$i - 1]) ? addslashes((string) $item['conditions'][$i - 1]) : 'GOOD';
                    echo "qrs.push({id: '{$eq_id}-U{$i}', name: '{$nm}', unit: {$i}, condition: '{$cond}'});";
                }
            }
            ?>
            openPrintWindow(qrs);
        }

        let printWin = null;

        function openPrintWindow(qrs) {
            if (printWin && !printWin.closed) {
                printWin.close();
            }

            printWin = window.open('', '_blank', 'width=1000,height=800');
            printWin.document.open();

            let html = `<!DOCTYPE html><html><head>
            <meta charset="UTF-8">
            <title>QR Codes</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial; display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; padding: 20px; background: white; }
                .qr-label { border: 2px solid #333; padding: 15px; text-align: center; border-radius: 8px; background: white; }
                .qr-img { max-width: 100px; height: auto; margin-bottom: 8px; }
                .qr-name { font-weight: bold; font-size: 12px; margin: 8px 0; word-break: break-word; }
                .qr-unit { font-size: 10px; color: #555; }
                .qr-id { background: #2c3e50; color: white; padding: 4px 2px; font-size: 9px; margin-top: 5px; border-radius: 3px; word-break: break-all; }
                #loading { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: Arial; font-size: 18px; color: #2c3e50; z-index: 999; }
            </style></head><body>
            <div id="loading">⏳ Loading QR codes... please wait</div>`;

            qrs.forEach((qr) => {
                const qrUrl = generateQRCodeSVG(qr.id, 100);
                html += `<div class="qr-label">
                    <img src="${qrUrl}" class="qr-img" alt="QR">
                    <div class="qr-name">${qr.name.substring(0, 20)}</div>
                    <div class="qr-unit">Unit ${qr.unit} | ${qr.condition}</div>
                    <div class="qr-id">${qr.id}</div>
                </div>`;
            });

            html += `<script>
                const imgs = document.querySelectorAll('.qr-label img');
                let loaded = 0;
                const total = imgs.length;
                function tryPrint() {
                    loaded++;
                    if (loaded >= total) {
                        document.getElementById('loading').style.display = 'none';
                        window.print();
                        document.title = 'QR Codes';
                        window.stop();
                    }
                }
                imgs.forEach(img => {
                    if (img.complete) tryPrint();
                    else { img.onload = tryPrint; img.onerror = tryPrint; }
                });
                if (total === 0) { window.print(); document.title = 'QR Codes'; }
            <\/script>`;

            html += `</body></html>`;
            printWin.document.write(html);
            printWin.document.close();
        }

        function handlePrintBtn(btn) {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const qty = parseInt(btn.getAttribute('data-qty'));
            const conditions = JSON.parse(btn.getAttribute('data-conditions'));
            printBulkQR(id, name, qty, conditions);
        }

        window.onclick = e => {
            const modal = document.getElementById('itemModal');
            if (e.target === modal) closeModal();
        };

        updateConditionFields();
    </script>
    <script src="assets/js/tooltrace-speech.js"></script>
    <script>
        tooltraceInitVoiceSearch({
            micId: 'micBtn',
            inputId: 'searchInput',
            statusId: 'speechStatus',
            onText: function () { filterCards(); }
        });
    </script>
</body>
</html>