<?php
require 'config.php';

// Already logged in → go to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — LibTrack</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">

    <div class="login-logo">
      <div class="login-logo-circle">
        <svg viewBox="0 0 28 28" fill="none">
          <rect x="3"  y="4" width="5" height="18" rx="1" fill="#fff" opacity=".9"/>
          <rect x="10" y="4" width="5" height="18" rx="1" fill="#fff" opacity=".7"/>
          <rect x="17" y="4" width="5" height="18" rx="1" fill="#fff" opacity=".9"/>
          <rect x="3"  y="21" width="19" height="2" rx="1" fill="#fff" opacity=".5"/>
          <path d="M10 8h5M10 11h5M10 14h3" stroke="#1a3a2a" stroke-width="1.2" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="login-title">LibTrack</div>
      <div class="login-sub">Book Borrowing Management System</div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username"
                 placeholder="Enter your username"
                 value="<?= sanitize($_POST['username'] ?? '') ?>"
                 autocomplete="username" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="Enter your password"
                 autocomplete="current-password" required>
        </div>
        <button type="submit" class="login-btn">Sign In</button>
      </div>
    </form>

    <p style="text-align:center;font-size:12px;color:#6b7d72;margin-top:1.2rem;">
      Authorized personnel only
    </p>
  </div>
</div>
</body>
</html>
