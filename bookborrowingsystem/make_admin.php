<?php
/**
 * make_admin.php — Run this file ONCE in your browser to create your admin account.
 * After running it, DELETE this file immediately.
 *
 * HOW TO USE:
 *   1. Edit $username, $password, and $fullName below with your own values.
 *   2. Open http://localhost/libtrack/make_admin.php in your browser.
 *   3. DELETE this file from your project folder right away!
 */

require 'config.php';

// ── EDIT THESE THREE LINES ────────────────────────────────────
$username = 'your_username';      // ← change this
$password = 'YourStrongPassword'; // ← change this (use letters + numbers + symbols)
$fullName = 'Your Full Name';     // ← change this
// ─────────────────────────────────────────────────────────────

// Basic validation
if ($username === 'your_username' || $password === 'YourStrongPassword') {
    die('<p style="font-family:sans-serif;color:red;padding:2rem;">
         ⚠️ Please open <strong>make_admin.php</strong> in VS Code and change the username,
         password, and full name before running this file.</p>');
}

$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hash, $fullName]);
    echo '<div style="font-family:sans-serif;padding:2rem;max-width:500px;">
            <h2 style="color:#1a3a2a;">✅ Admin account created!</h2>
            <p><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
            <p><strong>Full name:</strong> ' . htmlspecialchars($fullName) . '</p>
            <p style="margin-top:1rem;padding:.75rem;background:#fde8e8;border-radius:8px;color:#c0392b;">
              ⚠️ <strong>Delete this file (make_admin.php) now!</strong>
              Leaving it on the server is a security risk.
            </p>
            <p style="margin-top:1rem;">
              <a href="index.php" style="color:#1a3a2a;font-weight:600;">→ Go to login page</a>
            </p>
          </div>';
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        echo '<p style="font-family:sans-serif;color:red;padding:2rem;">
              ⚠️ That username already exists. Choose a different username in make_admin.php.</p>';
    } else {
        echo '<p style="font-family:sans-serif;color:red;padding:2rem;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
