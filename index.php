<?php
/* ──────────────────────────────────────────────
    ToolTrace - Full Integrated Landing Page
    Basis: Wireframe Pages 1-9 + Expanded Sections
   ────────────────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth_accounts.php';
require_once __DIR__ . '/includes/registration_requests_store.php';

$error   = '';
$success = '';
$registration_success = false;
$contact_success = '';
$contact_error = '';
$auth_error_action = '';

if (!empty($_SESSION['contact_flash_ok'])) {
    $contact_success = (string) $_SESSION['contact_flash_ok'];
    unset($_SESSION['contact_flash_ok']);
}

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'signin') {
        $orgEmail = trim($_POST['organization_email'] ?? '');
        $password = $_POST['password'] ?? '';
            if ($orgEmail === '' || $password === '') {
                $error = 'Please enter your email and password.';
            } elseif (!filter_var($orgEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $acc = tooltrace_login_account($orgEmail, $password);
                if ($acc === null) {
                    $error = 'Invalid email or password. Create an account if you are new.';
                } else {
        $stored = $acc['account_role'] ?? 'organization';
                $_SESSION['organization_name']   = $acc['organization_name'] ?? '';
                $_SESSION['organization_email'] = $acc['organization_email'] ?? '';
                $_SESSION['user_name']          = $acc['organization_name'] ?? '';
                $_SESSION['role']               = tooltrace_session_role_for_account($stored);
                $_SESSION['user_subtitle']      = match ($stored) {
                    'admin' => 'System Administrator',
                    'staff' => 'Maintenance Staff',
                    default => 'Organization',
                };
                header('Location: ' . tooltrace_redirect_for_account_role($stored), true, 302);
                exit;
            }
        }
    } elseif ($action === 'signup') {
        $orgName  = trim($_POST['organization_name'] ?? '');
        $orgEmail = trim($_POST['organization_email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        $accountRole = $_POST['account_role'] ?? 'organization';
        if (!in_array($accountRole, ['organization', 'staff', 'admin'], true)) {
            $accountRole = 'organization';
        }
        if ($orgName === '' || $orgEmail === '' || $password === '' || $confirm === '') {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($orgEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/[a-z]/', $password)
            || !preg_match('/[0-9]/', $password)
            || !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            $error = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
        } else {
            $regErr = tooltrace_submit_registration_request($orgName, $orgEmail, $password, $accountRole);
            if ($regErr !== null) {
                $error = $regErr;
            } else {
                $success = 'Your registration was submitted. An administrator will review it. You can sign in once your account is approved.';
                $registration_success = true;
            }
        }
    } elseif ($action === 'contact') {
        require_once __DIR__ . '/mailer.php';
        $csrfIn = (string) ($_POST['csrf'] ?? '');
        if (!hash_equals($_SESSION['contact_csrf'] ?? '', $csrfIn)) {
            $contact_error = 'Your session expired. Please refresh the page and try again.';
            $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
        } else {
            $name = trim((string) ($_POST['name'] ?? ''));
            $message = trim((string) ($_POST['message'] ?? ''));
            if ($name === '' || $message === '') {
                $contact_error = 'Please enter your name and message.';
            } elseif (
                (function_exists('mb_strlen') ? mb_strlen($name) : strlen($name)) > 200
                || (function_exists('mb_strlen') ? mb_strlen($message) : strlen($message)) > 8000
            ) {
                $contact_error = 'Name or message is too long.';
            } elseif (isset($_SESSION['contact_last_send']) && (time() - (int) $_SESSION['contact_last_send']) < 60) {
                $contact_error = 'Please wait a minute before sending another message.';
            } else {
                $to = tooltrace_contact_inbox_email();
                if ($to === '') {
                    $contact_error = 'The contact form is not configured yet. Please reach us by email from the address on the left.';
                } else {
                    $subject = '[ToolTrace] Website message from ' . $name;
                    $textBody = "Name: {$name}\n\nMessage:\n{$message}\n";
                    $nameSafe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $msgSafe = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
                    $htmlBody = '<!doctype html><html><body style="font-family:Segoe UI,Arial,sans-serif;line-height:1.5;">'
                        . '<p><strong>Name:</strong> ' . $nameSafe . '</p>'
                        . '<p><strong>Message:</strong></p><p>' . $msgSafe . '</p>'
                        . '</body></html>';
                    [$sent] = tooltrace_send_mail($to, $subject, $textBody, $htmlBody);
                    if ($sent) {
                        $_SESSION['contact_last_send'] = time();
                        $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
                        $_SESSION['contact_flash_ok'] = 'Thank you! We received your message and will reply soon.';
                        $redir = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
                        header('Location: ' . $redir . '#contact', true, 303);
                        exit;
                    }
                    $contact_error = 'We could not send your message right now. Please try again later or email us directly.';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== '') {
    $auth_error_action = (string) ($_POST['action'] ?? '');
}

$auth_view = 'signin';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== '' && ($_POST['action'] ?? '') === 'signup') {
    $auth_view = 'signup';
}
if (!empty($registration_success)) {
    $auth_view = 'signup';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToolTrace | Borrow Smarter, Not Harder</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --yellow: #F5C300;
            --yellow-dark: #D4A800;
            --dark: #1A1A1A;
            --dark-soft: #242424;
            --gray: #6B7280;
            --white: #FFFFFF;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; outline: none; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Barlow', sans-serif; background: var(--white); color: var(--dark); overflow-x: hidden; }

        /* ── NAVIGATION ── */
        header {
            position: fixed; top: 0; width: 100%; height: 80px;
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 60px; z-index: 1000; transition: var(--transition);
            background: rgba(255, 255, 255, 0.98); border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        header.scrolled { height: 70px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .logo { display: inline-flex; align-items: center; text-decoration: none; }
        .logo img { height: 44px; width: auto; display: block; object-fit: contain; }
        
        nav ul { display: flex; list-style: none; gap: 30px; align-items: center; }
        nav a { text-decoration: none; color: var(--gray); font-weight: 700; font-size: 13px; text-transform: uppercase; cursor: pointer; transition: var(--transition); }
        nav a:hover { color: var(--dark); }
        .btn-nav { background: var(--dark); color: var(--white) !important; padding: 10px 25px; border-radius: 50px; }

        /* ── HERO (Page 1) ── */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            box-sizing: border-box;
            padding: 96px 60px 48px;
            background: linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.75)), url('assets/images/splash_bg.png') center/cover;
            color: var(--white);
        }
        .hero-content { max-width: 1200px; margin: 0 auto; width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 40px; }
        .hero-text h1 { font-family: 'Bebas Neue', sans-serif; font-size: clamp(50px, 8vw, 100px); line-height: 0.9; margin-bottom: 20px; }
        .hero-text h1 span { color: var(--yellow); }
        .hero-text p { font-size: 18px; letter-spacing: 4px; font-weight: 700; color: rgba(255,255,255,0.6); text-transform: uppercase; }

        /* ── AUTH CARD (Page 8) ── */
        .auth-card { background: var(--white); padding: 40px; border-radius: 30px; width: 400px; max-width: 100%; color: var(--dark); box-shadow: 0 30px 60px rgba(0,0,0,0.4); }
        .auth-card h2 { font-family: 'Bebas Neue', sans-serif; font-size: 32px; text-align: center; margin-bottom: 25px; color: var(--dark); }
        .auth-panel { display: none; }
        .auth-panel.is-active { display: block; }
        .auth-switch { text-align: center; margin-top: 22px; font-size: 14px; color: var(--gray); }
        .auth-switch button.link-like {
            color: var(--yellow-dark);
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            background: none;
            font: inherit;
            padding: 0;
        }
        .auth-switch button.link-like:hover { text-decoration: underline; }
        .field { margin-bottom: 15px; }
        .field label { display: block; font-size: 11px; font-weight: 800; color: var(--gray); margin-bottom: 5px; text-transform: uppercase; }
        input, select, textarea { width: 100%; padding: 14px 20px; border: 2px solid #EEE; border-radius: 50px; font-family: inherit; transition: var(--transition); }
        select { appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: linear-gradient(45deg, transparent 50%, var(--gray) 50%), linear-gradient(135deg, var(--gray) 50%, transparent 50%); background-position: calc(100% - 24px) calc(50% - 3px), calc(100% - 18px) calc(50% - 3px); background-size: 7px 7px, 7px 7px; background-repeat: no-repeat; padding-right: 52px; }
        input:focus { border-color: var(--yellow); background: #FFF; }
        input.is-invalid { border-color: #E63946; background: #fff; }
        .field-error { margin-top: 8px; font-size: 12px; font-weight: 700; color: #E63946; line-height: 1.35; display: none; }
        .field-error.is-visible { display: block; }
        .btn-yellow { width: 100%; padding: 16px; background: var(--yellow); border: none; border-radius: 50px; font-weight: 800; cursor: pointer; transition: var(--transition); text-transform: uppercase; letter-spacing: 1px; }
        .btn-yellow:hover { background: var(--yellow-dark); transform: translateY(-2px); }

        /* ── SECTION BASICS ── */
        section { padding: 100px 60px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section-title { font-family: 'Bebas Neue', sans-serif; font-size: 56px; text-align: center; margin-bottom: 60px; letter-spacing: 1px; }
        .section-title span { color: var(--yellow); }

        /* ── PROCESS (Page 2-4) ── */
        .process-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .process-card { background: #F9FAFB; border-radius: 24px; overflow: hidden; text-align: center; padding-bottom: 35px; transition: var(--transition); border-bottom: 5px solid transparent; }
        .process-card:hover { transform: translateY(-10px); border-color: var(--yellow); box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
        .process-card img { width: 100%; height: 240px; object-fit: cover; background: #EEE; }
        .process-card h3 { font-family: 'Bebas Neue', sans-serif; font-size: 28px; margin: 20px 0 10px; }
        .process-card p { padding: 0 25px; color: var(--gray); font-size: 15px; line-height: 1.6; }

        /* ── FEATURES (Page 5-7) ── */
        .feature-row { display: flex; align-items: center; gap: 80px; margin-bottom: 100px; }
        .feature-row:nth-child(even) { flex-direction: row-reverse; }
        .feature-text { flex: 1; }
        .feature-text .section-title { margin-bottom: 18px; }
        .feature-text p { margin: 0; }
        .feature-img { flex: 1; height: 450px; background: #EEE; border-radius: 40px; border: 2px solid var(--yellow); overflow: hidden; }
        .tts-trigger { background: var(--yellow); border: none; padding: 8px 15px; border-radius: 5px; font-size: 11px; font-weight: 900; cursor: pointer; margin-top: 16px; display: inline-flex; align-items: center; gap: 8px; transition: var(--transition); }
        .tts-trigger:hover { background: var(--dark); color: var(--white); }

        /* ── REVIEWS (Expanded Section) ── */
        #reviews { background: #FFF; }
        .reviews-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
        .review-card { background: #F9FAFB; padding: 40px; border-radius: 25px; position: relative; }
        .review-card::after { content: '“'; position: absolute; top: 10px; right: 30px; font-size: 80px; color: var(--yellow); opacity: 0.2; font-family: serif; }
        .review-card p { font-style: italic; color: var(--gray); font-size: 17px; margin-bottom: 25px; line-height: 1.7; }
        .reviewer { display: flex; align-items: center; gap: 15px; }
        .rev-avatar { width: 50px; height: 50px; background: #DDD; border-radius: 50%; overflow: hidden; flex: 0 0 50px; }
        .rev-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* ── TEAM (Expanded Section) ── */
        #team { background: #F4F4F4; }
        .team-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 40px; text-align: center; }
        .team-member { width: min(320px, calc((100% - 80px) / 3)); }
        .team-member img { width: 180px; height: 180px; border-radius: 50%; transition: var(--transition); border: 5px solid var(--yellow); margin-bottom: 20px; object-fit: cover; }
        .team-member:hover img { transform: scale(1.05); }
        .team-member h4 { font-family: 'Bebas Neue'; font-size: 26px; }
        .team-member span { color: var(--yellow-dark); font-weight: 800; font-size: 12px; text-transform: uppercase; display: block; margin-top: 5px; }

        /* ── CONTACT US (Page 9) ── */
        #contact { background: var(--dark); color: var(--white); }
        .contact-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 80px; }
        .info-block { margin-bottom: 40px; }
        .info-block h4 { font-family: 'Bebas Neue', sans-serif; color: var(--yellow); font-size: 24px; margin-bottom: 10px; letter-spacing: 1px; }
        .info-block p { color: #AAA; line-height: 1.6; }
        .contact-form-box { background: var(--dark-soft); padding: 38px; border-radius: 35px; border: 1px solid rgba(255,255,255,0.08); align-self: start; }
        .contact-form-box input, .contact-form-box textarea { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); color: var(--white); border-radius: 20px; }
        .contact-form-box input:focus { border-color: var(--yellow); }
        .contact-form-box textarea { min-height: 150px; resize: vertical; border-radius: 24px; }
        .contact-form-box .field:last-of-type { margin-bottom: 18px; }
        .contact-notice { font-size: 14px; margin-top: 20px; text-align: center; font-weight: 700; line-height: 1.45; }
        .contact-notice--ok { color: var(--yellow); }
        .contact-notice--err { color: #ff8787; }

        footer { background: #111; padding: 40px; text-align: center; color: #555; font-size: 13px; border-top: 1px solid #222; }

        @media (max-width: 992px) {
            .hero-content, .process-grid, .feature-row, .reviews-grid, .team-grid, .contact-grid { grid-template-columns: 1fr; flex-direction: column; }
            header { padding: 0 25px; }
            nav { display: none; }
            .hero { text-align: center; padding-left: 25px; padding-right: 25px; }
            .auth-card { width: 100%; margin-top: 40px; }
        }
    </style>
</head>
<body>
<script>
  document.addEventListener('click', function handler() {
    document.removeEventListener('click', handler);
    if (window.tooltraceSpeak) window.tooltraceSpeak('Welcome to ToolTrace.');
  }, { once: true });
</script>

<header id="header">
    <a href="#home" class="logo" aria-label="ToolTrace">
        <img src="assets/images/tooltracelogo.png" alt="ToolTrace logo">
    </a>
    <nav>
        <ul>
            <li><a href="#process">Process</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#reviews">Reviews</a></li>
            <li><a href="#team">Team</a></li>
            <li><a href="#contact">Contact</a></li>
            <li><a href="#home" class="btn-nav">Sign In</a></li>
        </ul>
    </nav>
</header>

<section class="hero" id="home">
    <div class="hero-content">
        <div class="hero-text">
            <h1>BORROW SMARTER<br><span>NOT HARDER</span></h1>
            <p>SEARCH • REQUEST • APPROVE</p>
        </div>

        <div class="auth-card" id="authCard" data-initial-view="<?php echo htmlspecialchars($auth_view); ?>">
            <?php if (!empty($registration_success) && $success !== ''): ?>
            <div class="auth-success" style="color:#1a7f37; font-size:13px; text-align:center; margin-bottom:15px; font-weight:600; line-height:1.45;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div id="authPanelSignin" class="auth-panel<?php echo $auth_view === 'signin' ? ' is-active' : ''; ?>" role="region" aria-labelledby="authSigninHeading">
                <h2 id="authSigninHeading">Sign In</h2>
                <?php if ($error && $auth_error_action === 'signin'): ?>
                <div class="auth-error" style="color:#E63946; font-size:12px; text-align:center; margin-bottom:15px; font-weight:700;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" autocomplete="on">
                    <input type="hidden" name="action" value="signin">
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="organization_email" placeholder="you@school.edu.ph" required autocomplete="email">
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn-yellow">Sign In</button>
                </form>
                <p class="auth-switch">Don't have an account? <button type="button" class="link-like" id="showSignupBtn">Sign Up</button></p>
            </div>

            <div id="authPanelSignup" class="auth-panel<?php echo $auth_view === 'signup' ? ' is-active' : ''; ?>" role="region" aria-labelledby="authSignupHeading">
                <h2 id="authSignupHeading">Create Account</h2>
                <?php if ($error && $auth_error_action === 'signup'): ?>
                <div class="auth-error" style="color:#E63946; font-size:12px; text-align:center; margin-bottom:15px; font-weight:700;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" autocomplete="on">
                    <input type="hidden" name="action" value="signup">
                    <div class="field">
                        <label>Account type</label>
                        <select name="account_role" required>
                            <option value="organization">Organization</option>
                            <option value="staff">Maintenance staff</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Display name</label>
                        <input type="text" name="organization_name" placeholder="Organization or your full name" required autocomplete="name">
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="organization_email" placeholder="you@school.edu.ph" required autocomplete="email">
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Min 8 chars, upper/lower/number/special" required autocomplete="new-password" minlength="8">
                    </div>
                    <div class="field">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirm" placeholder="Repeat password" required autocomplete="new-password" minlength="8">
                        <div id="signupPasswordError" class="field-error" role="status" aria-live="polite"></div>
                    </div>
                    <button type="submit" class="btn-yellow">Create Account</button>
                </form>
                <p class="auth-switch">Already have an account? <button type="button" class="link-like" id="showSigninBtn">Sign In</button></p>
            </div>
        </div>
    </div>
</section>

<section id="process">
    <div class="container">
        <h2 class="section-title">The <span>Process</span></h2>
        <div class="process-grid">
            <div class="process-card">
                <img src="assets/images/how1.png" alt="Search">
                <h3>1. Search</h3>
                <p>Locate equipment instantly using Voice UI or by scanning designated QR codes.</p>
            </div>
            <div class="process-card">
                <img src="assets/images/how2.png" alt="Request">
                <h3>2. Request</h3>
                <p>Fill out the digital borrowing form. No paper trails, no manual logbooks.</p>
            </div>
            <div class="process-card">
                <img src="assets/images/how3.png" alt="Borrow">
                <h3>3. Link</h3>
                <p>Present your auto-generated digital gate pass for instant equipment release.</p>
            </div>
        </div>
    </div>
</section>

<section id="features" style="background: #FAFAFA;">
    <div class="container">
        <div class="feature-row">
            <div class="feature-text">
                <h2 class="section-title" style="text-align:left;">Voice <span>Search</span></h2>
    <p id="feature-voice-desc">ToolTrace integrates high-accuracy speech-to-text allowing students to find specific equipment or navigate the inventory hands-free during busy production setups.</p>
    <button class="tts-trigger" onclick="tooltraceSpeak(document.getElementById('feature-voice-desc').textContent)">🔊 READ ALOUD</button>
            </div>
            <div class="feature-img">
                <img src="assets/images/voicesearch.jpg" style="width:100%; height:100%; object-fit:cover;" alt="Voice Search UI">
            </div>
        </div>

        <div class="feature-row">
    <div class="feature-text">
        <h2 class="section-title" style="text-align:left;">Digital <span>Pass</span></h2>
        <p id="feature-digital-desc">Each approved request generates a secure, unique QR code. Staff scan your phone to record the transaction, ensuring 100% accuracy in inventory tracking.</p>
        <button class="tts-trigger" onclick="tooltraceSpeak(document.getElementById('feature-digital-desc').textContent)">🔊 READ ALOUD</button>
    </div>
    <div class="feature-img">
        <img src="assets/images/digitalpass.jpg" style="width:100%; height:100%; object-fit:cover;" alt="Digital Gate Pass">
    </div>
</div>
    </div>
</section>

<section id="reviews">
    <div class="container">
        <h2 class="section-title">User <span>Reviews</span></h2>
        <div class="reviews-grid">
            <div class="review-card">
                <p>The digital pass system is incredibly efficient. We used it for our theater production and the pickup was instant.</p>
                <div class="reviewer">
                    <div class="rev-avatar"><img src="assets/images/marcov.jpg" alt="Marco V."></div>
                    <div><strong>Marco V.</strong><br><small>Student Org President</small></div>
                </div>
            </div>
            <div class="review-card">
                <p>Finally, a system that doesn't require five different signatures on paper. The Voice search is a huge bonus.</p>
                <div class="reviewer">
                    <div class="rev-avatar"><img src="assets/images/lizak.jpg" alt="Liza K."></div>
                    <div><strong>Liza K.</strong><br><small>Student Org Secretary</small></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="team">
    <div class="container">
        <h2 class="section-title">The <span>Team</span></h2>
        <div class="team-grid">
            <div class="team-member">
                <img src="assets/images/Arbolente.jpg" alt="Arbolente">
                <h4>Anne Patrice Arbolente</h4>
                <span>Developer</span>
            </div>
            <div class="team-member">
                <img src="assets/images/Catindig.png" alt="Catindig">
                <h4>Raven Jeanne Catindig</h4>
                <span>Developer</span>
            </div>
            <div class="team-member">
                <img src="assets/images/Diaz.png" alt="Diaz">
                <h4>Francheska Eunice Diaz</h4>
                <span>Developer</span>
            </div>
            <div class="team-member">
                <img src="assets/images/Espino.JPG" alt="Espino">
                <h4>Rose Carmen Espino</h4>
                <span>Developer</span>
            </div>
            <div class="team-member">
                <img src="assets/images/Magpayo.png" alt="Magpayo">
                <h4>Shofia Loise Magpayo</h4>
                <span>Developer</span>
            </div>
        </div>
    </div>
</section>

<section id="contact">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <h2 class="section-title" style="text-align:left; color:white;">GET IN <span>TOUCH</span></h2>
                <div class="info-block">
                    <h4>📍 LOCATION</h4>
                    <p>1338 Arlegui St., Quiapo, Manila, 1001, Metro Manila, Philippines</p>
                </div>
                <div class="info-block">
                    <h4>📧 EMAIL</h4>
                    <p>tooltraceofficial@gmail.com</p>
                </div>
                <div class="info-block">
                    <h4>📞 HOTLINE</h4>
                    <p>+63 966-740-3386<br>Mon-Fri | 8AM - 5PM</p>
                </div>
            </div>

            <div class="contact-form-box">
                <form method="POST" action="#contact">
                    <input type="hidden" name="action" value="contact">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['contact_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field">
                        <label style="color:var(--yellow)">Full Name</label>
                        <input type="text" name="name" placeholder="Juan Dela Cruz" required maxlength="200" autocomplete="name" value="<?php echo htmlspecialchars((string) ($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="field">
                        <label style="color:var(--yellow)">Message</label>
                        <textarea name="message" rows="5" placeholder="How can we help you today?" required maxlength="8000"><?php echo htmlspecialchars((string) ($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <button type="submit" class="btn-yellow">Send Message</button>
                </form>
                <?php if ($contact_error !== ''): ?>
                <p class="contact-notice contact-notice--err"><?php echo htmlspecialchars($contact_error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php elseif ($contact_success !== ''): ?>
                <p class="contact-notice contact-notice--ok"><?php echo htmlspecialchars($contact_success, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<footer>
    &copy; 2026 ToolTrace Management System | All Rights Reserved.
</footer>

<script src="assets/js/tooltrace-tts.js"></script>
<script>
    // ── HEADER SCROLL EFFECT ──
    window.addEventListener('scroll', () => {
        const header = document.getElementById('header');
        if (window.scrollY > 50) header.classList.add('scrolled');
        else header.classList.remove('scrolled');
    });

    (function () {
        const card = document.getElementById('authCard');
        const signInPanel = document.getElementById('authPanelSignin');
        const signUpPanel = document.getElementById('authPanelSignup');
        const showSignupBtn = document.getElementById('showSignupBtn');
        const showSigninBtn = document.getElementById('showSigninBtn');
        if (!card || !signInPanel || !signUpPanel || !showSignupBtn || !showSigninBtn) return;

        function showAuth(view) {
            const isSignin = view === 'signin';
            signInPanel.classList.toggle('is-active', isSignin);
            signUpPanel.classList.toggle('is-active', !isSignin);
        }

        showAuth(card.getAttribute('data-initial-view') === 'signup' ? 'signup' : 'signin');

        showSignupBtn.addEventListener('click', function () { showAuth('signup'); });
        showSigninBtn.addEventListener('click', function () { showAuth('signin'); });
    })();

    (function () {
        const panel = document.getElementById('authPanelSignup');
        if (!panel) return;
        const form = panel.querySelector('form');
        if (!form) return;

        const pwd = form.querySelector('input[name="password"]');
        const pwd2 = form.querySelector('input[name="password_confirm"]');
        const errEl = document.getElementById('signupPasswordError');
        if (!pwd || !pwd2) return;

        function isStrongPassword(p) {
            if (!p || p.length < 8) return false;
            if (!/[A-Z]/.test(p)) return false;
            if (!/[a-z]/.test(p)) return false;
            if (!/[0-9]/.test(p)) return false;
            if (!/[^A-Za-z0-9]/.test(p)) return false;
            return true;
        }

        function setError(msg) {
            if (errEl) {
                errEl.textContent = msg;
                errEl.classList.toggle('is-visible', Boolean(msg));
            }
        }

        function setInvalid(el, on) {
            if (!el) return;
            el.classList.toggle('is-invalid', on);
        }

        function clearUiErrors() {
            setError('');
            setInvalid(pwd, false);
            setInvalid(pwd2, false);
        }

        pwd.addEventListener('input', clearUiErrors);
        pwd2.addEventListener('input', clearUiErrors);

        form.addEventListener('submit', function (e) {
            const p1 = String(pwd.value || '');
            const p2 = String(pwd2.value || '');
            if (p1 !== p2) {
                e.preventDefault();
                setInvalid(pwd2, true);
                setError('Passwords do not match.');
                pwd2.focus();
                return;
            }
            if (!isStrongPassword(p1)) {
                e.preventDefault();
                setInvalid(pwd, true);
                setError('Use at least 8 characters with uppercase, lowercase, number, and special character.');
                pwd.focus();
            }
        });
    })();
</script>

</body>
</html>