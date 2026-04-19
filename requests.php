<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ToolTrace | User Requests</title>
    <style>
        :root { --primary: #2c3e50; --accent: #f1c40f; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg); margin: 0; color: var(--primary); }
        .main-wrapper { padding: 40px; }

        h1 { font-size: 2.2rem; margin: 0 0 24px 0; line-height: 1.1; }
        h2 { color: #7f8c8d; border-bottom: 3px solid var(--accent); display: inline-block; margin: 24px 0 16px; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 0.04em; }


        .request-card { background: white; padding: 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 14px; border: 1px solid #eef0f2; gap: 12px; }
        .btn-approve { background: #2ecc71; color: white; border: none; padding: 10px 20px; border-radius: 30px; font-weight: 700; cursor: pointer; font-size: 12px; }
        .btn-reject { background: #EEE; color: #333; border: none; padding: 10px 20px; border-radius: 30px; font-weight: 700; cursor: pointer; font-size: 12px; margin-left: 8px; }
        @media (max-width: 860px) {
            .request-card { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="main-wrapper">
        <h1>Registration Requests</h1>
       
        <h2>Staff</h2>
        <div class="request-card">
            <div><b style="font-size: 18px;">Cheska Diaz</b><br><small style="color: #888;">chesska@school.edu.ph | 02/22/2026</small></div>
            <div><button class="btn-approve">Approve</button><button class="btn-reject">Reject</button></div>
        </div>


        <h2>Organizations</h2>
        <div class="request-card">
            <div><b style="font-size: 18px;">CCDA Organization</b><br><small style="color: #888;">accda@school.edu.ph | 03/01/2026</small></div>
            <div><button class="btn-approve">Approve</button><button class="btn-reject">Reject</button></div>
        </div>
    </div>
</body>
</html>

