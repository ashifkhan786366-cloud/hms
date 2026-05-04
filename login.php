<?php
require_once 'config/db.php';

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['username']  = $user['username'];

            // Remember Me - store cookie for 30 days
            if (!empty($_POST['remember_me'])) {
                $token = bin2hex(random_bytes(32));
                setcookie('hms_remember', $token, time() + (30 * 24 * 3600), '/');
            }

            // Log login time (optional - best effort)
            try {
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            } catch (Exception $e) { /* column may not exist */ }

            header("Location: index.php");
            exit();
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}

// Get hospital name for branding
$hospitalName = defined('APP_NAME') ? APP_NAME : 'SANKHLA HOSPITAL';
$hospitalShort = defined('APP_SHORT_NAME') ? APP_SHORT_NAME : 'HMS';
$hospitalLogo = defined('APP_LOGO') ? APP_LOGO : 'assets/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; <?php echo htmlspecialchars($hospitalName); ?></title>
    <meta name="description" content="Secure login portal for <?php echo htmlspecialchars($hospitalName); ?> Hospital Management System.">

    <!-- Bootstrap 5 (Offline) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- FontAwesome (Offline) -->
    <link rel="stylesheet" href="assets/css/all.min.css">

    <style>
        /* =============================================
           HMS LOGIN PAGE - PREMIUM DESIGN
           ============================================= */

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary:       #0ea5e9;
            --primary-dark:  #0284c7;
            --primary-glow:  rgba(14, 165, 233, 0.35);
            --accent:        #6366f1;
            --accent-dark:   #4f46e5;
            --success:       #10b981;
            --danger:        #ef4444;
            --danger-bg:     rgba(239, 68, 68, 0.12);
            --glass-bg:      rgba(255, 255, 255, 0.06);
            --glass-border:  rgba(255, 255, 255, 0.14);
            --text-primary:  #f0f9ff;
            --text-muted:    rgba(240, 249, 255, 0.55);
            --card-radius:   20px;
            --transition:    0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html, body {
            height: 100%;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            overflow: hidden;
        }

        /* ---- Animated Gradient Background ---- */
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #0c4a6e 100%);
            background-size: 400% 400%;
            animation: gradientShift 12s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            position: relative;
        }

        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* ---- Floating Orbs (Background Decoration) ---- */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.18;
            pointer-events: none;
            animation: orbFloat 8s ease-in-out infinite;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #0ea5e9, transparent);
            top: -120px; left: -100px;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #6366f1, transparent);
            bottom: -80px; right: -80px;
            animation-delay: -4s;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, #10b981, transparent);
            top: 50%; left: 60%;
            animation-delay: -2s;
        }
        @keyframes orbFloat {
            0%, 100% { transform: translateY(0px) scale(1); }
            50%       { transform: translateY(-30px) scale(1.05); }
        }

        /* ---- Particle Grid ---- */
        .grid-overlay {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(14, 165, 233, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(14, 165, 233, 0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        /* ---- Login Wrapper ---- */
        .login-wrapper {
            width: 100%;
            max-width: 460px;
            padding: 20px;
            position: relative;
            z-index: 10;
            animation: slideUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ---- Glassmorphism Card ---- */
        .login-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--card-radius);
            padding: 48px 44px;
            box-shadow:
                0 25px 60px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255,255,255,0.05) inset,
                0 1px 0 rgba(255,255,255,0.1) inset;
        }

        /* ---- Hospital Branding ---- */
        .brand-section {
            text-align: center;
            margin-bottom: 36px;
        }

        .brand-logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px; height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 22px;
            margin-bottom: 16px;
            box-shadow: 0 8px 32px var(--primary-glow);
            animation: logoPulse 3s ease-in-out infinite;
        }
        @keyframes logoPulse {
            0%, 100% { box-shadow: 0 8px 32px var(--primary-glow); }
            50%       { box-shadow: 0 8px 48px rgba(14, 165, 233, 0.6); }
        }

        .brand-logo-img {
            max-width: 56px;
            max-height: 56px;
            object-fit: contain;
            border-radius: 12px;
        }

        .brand-logo-icon {
            font-size: 32px;
            color: white;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: 0.5px;
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .brand-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 400;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .brand-divider {
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 2px;
            margin: 12px auto 0;
        }

        /* ---- Section Heading ---- */
        .section-heading {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-heading::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--glass-border);
        }

        /* ---- Form Fields ---- */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 15px;
            transition: color var(--transition);
            pointer-events: none;
        }

        .form-control-custom {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 13px 16px 13px 44px;
            font-size: 14.5px;
            color: var(--text-primary);
            font-family: inherit;
            outline: none;
            transition: all var(--transition);
            -webkit-appearance: none;
        }

        .form-control-custom::placeholder {
            color: rgba(255,255,255,0.25);
        }

        .form-control-custom:focus {
            border-color: var(--primary);
            background: rgba(14, 165, 233, 0.08);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .form-control-custom:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: var(--primary);
        }

        /* Password toggle */
        .pwd-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 15px;
            cursor: pointer;
            padding: 4px;
            transition: color var(--transition);
            line-height: 1;
        }
        .pwd-toggle:hover { color: var(--primary); }

        /* ---- Remember Me ---- */
        .remember-row {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }

        .custom-check {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 13px;
            color: var(--text-muted);
            user-select: none;
        }

        .custom-check input[type="checkbox"] {
            appearance: none;
            width: 18px; height: 18px;
            border: 1.5px solid rgba(255,255,255,0.2);
            border-radius: 5px;
            background: rgba(255,255,255,0.05);
            cursor: pointer;
            transition: all var(--transition);
            position: relative;
            flex-shrink: 0;
        }

        .custom-check input[type="checkbox"]:checked {
            background: var(--primary);
            border-color: var(--primary);
        }

        .custom-check input[type="checkbox"]:checked::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 10px;
            color: white;
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }

        /* ---- Login Button ---- */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            letter-spacing: 0.5px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all var(--transition);
            box-shadow: 0 4px 20px var(--primary-glow);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before { left: 100%; }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(14, 165, 233, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login .btn-spinner {
            display: none;
        }
        .btn-login.loading .btn-text  { display: none; }
        .btn-login.loading .btn-spinner { display: inline-block; }

        /* ---- Alert Messages ---- */
        .alert-custom {
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            animation: alertShake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        .alert-error {
            background: var(--danger-bg);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        @keyframes alertShake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-6px); }
            20%, 40%, 60%, 80% { transform: translateX(6px); }
        }

        /* ---- Footer Hint ---- */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid var(--glass-border);
        }

        .login-footer p {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .role-badges {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }

        .role-badge {
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            background: var(--glass-bg);
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* ---- Copyright Bar ---- */
        .copyright-bar {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            letter-spacing: 0.5px;
        }

        /* ---- Responsive ---- */
        @media (max-width: 500px) {
            .login-card { padding: 36px 28px; }
        }
    </style>
</head>
<body>

    <!-- Background Decorations -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-overlay"></div>

    <!-- Login Wrapper -->
    <div class="login-wrapper">
        <div class="login-card">

            <!-- Hospital Branding -->
            <div class="brand-section">
                <div class="brand-logo-wrap">
                    <img src="<?php echo htmlspecialchars($hospitalLogo); ?>"
                         alt="Hospital Logo"
                         class="brand-logo-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <i class="fas fa-hospital brand-logo-icon" style="display:none;"></i>
                </div>
                <div class="brand-title"><?php echo htmlspecialchars($hospitalName); ?></div>
                <div class="brand-subtitle">Hospital Management System</div>
                <div class="brand-divider"></div>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="alert-custom alert-error" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="section-heading">
                <i class="fas fa-lock" style="color: var(--primary); font-size:13px;"></i>
                Secure Sign In
            </div>

            <form method="POST" id="loginForm" autocomplete="on">

                <!-- Username -->
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control-custom"
                            placeholder="Enter your username"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            required
                            autocomplete="username"
                            autofocus>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control-custom"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                            style="padding-right: 48px;">
                        <button type="button" class="pwd-toggle" id="pwdToggle" title="Show/Hide Password">
                            <i class="fas fa-eye" id="pwdIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="remember-row">
                    <label class="custom-check">
                        <input type="checkbox" name="remember_me" id="rememberMe">
                        <span>Remember me for 30 days</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </span>
                    <span class="btn-spinner">
                        <i class="fas fa-circle-notch fa-spin me-2"></i>Authenticating...
                    </span>
                </button>

            </form>

            <!-- Footer Info -->
            <div class="login-footer">
                <p><i class="fas fa-shield-alt me-1" style="color: var(--primary);"></i> Authorized personnel only</p>
                <p>Contact admin if you cannot log in.</p>
                <div class="role-badges">
                    <span class="role-badge"><i class="fas fa-user-shield me-1"></i>Admin</span>
                    <span class="role-badge"><i class="fas fa-stethoscope me-1"></i>Doctor</span>
                    <span class="role-badge"><i class="fas fa-user-nurse me-1"></i>Nurse</span>
                    <span class="role-badge"><i class="fas fa-flask me-1"></i>Lab Tech</span>
                    <span class="role-badge"><i class="fas fa-cash-register me-1"></i>Receptionist</span>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="copyright-bar">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($hospitalName); ?> &mdash; All rights reserved
        </div>
    </div>

    <!-- Bootstrap JS (Offline) -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script>
        // ---- Password Toggle ----
        const pwdToggle = document.getElementById('pwdToggle');
        const pwdInput  = document.getElementById('password');
        const pwdIcon   = document.getElementById('pwdIcon');

        pwdToggle.addEventListener('click', function () {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            pwdIcon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        });

        // ---- Loading State on Submit ----
        const loginForm = document.getElementById('loginForm');
        const loginBtn  = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', function () {
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        });

        // ---- Auto-dismiss alert after 5 seconds ----
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            setTimeout(function () {
                errorAlert.style.transition = 'opacity 0.5s ease';
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 500);
            }, 5000);
        }

        // ---- Keyboard shortcut: Enter on username -> focus password ----
        document.getElementById('username').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });
    </script>

</body>
</html>