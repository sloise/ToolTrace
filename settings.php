<?php
/**
 * ToolTrace - Account Settings
 * Features: Profile Management, STT (Voice Input), and TTS (Read Profile)
 */

$user = [
    'name' => 'Anne Arbolente',
    'email' => 'anne.arbolente@school.edu.ph',
    'id_number' => '2023-10042',
    'role' => 'Student',
    'initials' => 'AA',
    'organization' => 'Tech Club'
];

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = "Settings updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ToolTrace | Settings</title>
    <style>
        :root { --primary: #2c3e50; --accent: #f1c40f; --bg: #f4f7f6; --header-h: 70px; --sidebar-w: 240px; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: var(--bg); color: var(--primary); }

        /* Layout */
        .top-header { height: var(--header-h); background: #fff; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; position: fixed; top: 0; width: 100%; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.05); box-sizing: border-box; }
        .sidebar { width: var(--sidebar-w); background: var(--primary); height: 100vh; position: fixed; top: var(--header-h); left: 0; padding: 20px 0; }
        .sidebar a { color: #bdc3c7; text-decoration: none; display: block; padding: 15px 25px; font-size: 14px; }
        .sidebar .active { color: var(--accent); font-weight: bold; border-left: 4px solid var(--accent); }
        .main-content { margin-left: var(--sidebar-w); margin-top: var(--header-h); padding: 40px; max-width: 800px; }

        /* Settings Design */
        .settings-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .big-avatar { width: 80px; height: 80px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: bold; }
        
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #7f8c8d; margin-bottom: 8px; }
        .input-wrapper { display: flex; align-items: center; gap: 10px; }
        .form-control { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: var(--accent); }

        .btn-mic { background: none; border: none; cursor: pointer; font-size: 18px; color: #95a5a6; transition: 0.2s; }
        .btn-mic:hover { color: #e74c3c; }

        .btn-save { background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-listen-profile { background: #eee; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 12px; }

        .alert-success { background: #ecfaf0; color: #2ecc71; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #d4edda; }

        /* Toast */
        #uiToast { display:none; position: fixed; bottom: 26px; left: 50%; transform: translateX(-50%); background: #2c3e50; color: #fff; padding: 14px 20px; border-radius: 12px; font-size: 14px; font-weight: 800; z-index: 10000; box-shadow: 0 10px 30px rgba(0,0,0,0.25); max-width: 520px; text-align: center; }
        #uiToast.success { background: #27ae60; }
        #uiToast.warning { background: #e67e22; }
        #uiToast.error { background: #e74c3c; }
    </style>
</head>
<body>

    <div id="uiToast" class="success" role="status" aria-live="polite"></div>

    <header class="top-header">
        <div style="font-size:22px; font-weight:800;">TOOL<span style="color:var(--accent);">TRACE</span></div>
        <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-size:14px; font-weight:600;"><?php echo $user['name']; ?></span>
            <div style="width:35px;height:35px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;"><?php echo $user['initials']; ?></div>
        </div>
    </header>

    <nav class="sidebar">
        <a href="#">Dashboard</a>
        <a href="#">Browse Equipments</a>
        <a href="#">Borrowing History</a>
        <a href="?" class="active">Settings</a>
    </nav>

    <main class="main-content">
        <h1>Account Settings</h1>
        
        <?php if($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="settings-card">
            <div class="profile-header">
                <div class="big-avatar"><?php echo $user['initials']; ?></div>
                <div>
                    <h2 style="margin:0;"><?php echo $user['name']; ?></h2>
                    <p style="margin:5px 0; color:#7f8c8d;"><?php echo $user['role']; ?> | <?php echo $user['organization']; ?></p>
                    <button class="btn-listen-profile" onclick="readProfile()">🔊 Listen to Profile</button>
                </div>
            </div>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <input type="text" id="nameInput" name="name" class="form-control" value="<?php echo $user['name']; ?>">
                        <button type="button" class="btn-mic" onclick="startVoice('nameInput')">🎤</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" id="emailInput" name="email" class="form-control" value="<?php echo $user['email']; ?>">
                        <button type="button" class="btn-mic" onclick="startVoice('emailInput')">🎤</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Student ID Number</label>
                    <input type="text" class="form-control" value="<?php echo $user['id_number']; ?>" readonly style="background:#f9f9f9;">
                </div>

                <div style="margin-top:30px; padding-top:20px; border-top:1px solid #eee;">
                    <h3 style="font-size:16px;">Security</h3>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                    </div>
                </div>

                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </main>

    <script src="assets/js/tooltrace-tts.js"></script>
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

        // --- Speech to Text (STT) for Form Fields ---
        function startVoice(fieldId) {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) {
                showToast('Voice input not supported in this browser.', 'warning');
                return;
            }
            const recognition = new SR();
            recognition.lang = 'en-US';
            recognition.continuous = false;
            recognition.interimResults = false;

            const targetInput = document.getElementById(fieldId);
            const originalPlaceholder = targetInput.placeholder;

            targetInput.placeholder = "Listening...";
            try {
                recognition.start();
            } catch (e) {
                targetInput.placeholder = originalPlaceholder;
                showToast('Could not start voice input. Try again.', 'error');
                return;
            }

            recognition.onresult = (event) => {
                let text = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    text += event.results[i][0].transcript;
                }
                targetInput.value = text.trim();
            };

            recognition.onerror = (event) => {
                if (event.error === 'not-allowed') {
                    showToast('Microphone is blocked. Allow microphone for this site in the browser address bar.', 'warning');
                }
            };

            recognition.onend = () => {
                targetInput.placeholder = originalPlaceholder;
            };
        }

        // --- Text to Speech (TTS) for Profile ---
        function readProfile() {
            tooltraceSpeak(<?php echo json_encode(
                'Profile for ' . $user['name'] . '. Your email is ' . $user['email'] . '. Your role is ' . $user['role'] . ' within the ' . $user['organization'] . '.',
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ); ?>);
        }
    </script>
</body>
</html>