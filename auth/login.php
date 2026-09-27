<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if (isLoggedIn()) {
    header("Location: " . getBaseUrl() . "/admin/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Smart Academic Timetable Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-page {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top center, #1E1B4B 0%, #0B0F19 100%);
            padding: 20px;
        }
        .login-card {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: var(--radius-xl);
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow-lg), var(--shadow-glow);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-logo img {
            width: 68px;
            height: 68px;
            margin-bottom: 12px;
        }
        .demo-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px dashed rgba(59, 130, 246, 0.3);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            margin-top: 20px;
            font-size: 12.5px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <img src="../assets/images/logo.svg" alt="Department Logo">
            <h1 style="font-size: 20px; font-weight: 800; color: var(--text-white); letter-spacing: -0.5px;">SMART TIMETABLE</h1>
            <p style="font-size: 12px; color: var(--primary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">B.Voc SD & SA Department</p>
        </div>

        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label class="form-label" for="username">Admin Username or Email</label>
                <input type="text" id="username" class="form-control" placeholder="Enter username (e.g. admin)" required autofocus>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                </div>
                <input type="password" id="password" class="form-control" placeholder="Enter password" required>
            </div>

            <button type="submit" id="btnLogin" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">
                Secure Admin Sign In &rarr;
            </button>
        </form>

        <div class="demo-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <strong style="color: var(--primary);">Quick Demo Login</strong>
                <button type="button" class="btn btn-secondary btn-sm" onclick="fillDemo()" style="padding: 2px 8px; font-size: 11px;">Auto-Fill</button>
            </div>
            <div>Username: <code style="color: #60A5FA; font-family: monospace;">admin</code> | Password: <code style="color: #60A5FA; font-family: monospace;">admin123</code></div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        function fillDemo() {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
        }

        async function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerText = 'Verifying Credentials...';

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            try {
                const res = await App.api('auth.php', {
                    method: 'POST',
                    body: { action: 'login', username, password }
                });

                App.toast(res.message, 'success');
                setTimeout(() => {
                    window.location.href = res.redirect || '../admin/dashboard.php';
                }, 500);
            } catch (err) {
                App.toast(err.message, 'error');
                btn.disabled = false;
                btn.innerText = 'Secure Admin Sign In →';
            }
        }
    </script>
</body>
</html>
