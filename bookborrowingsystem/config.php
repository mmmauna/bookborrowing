<?php
// ── Database connection ───────────────────────────────────────
// Edit these values to match your WAMP setup.
// Default WAMP: host=localhost, user=root, pass='' (empty)

define('DB_HOST', 'fdb1033.awardspace.net');
define('DB_NAME', '4692567_bookborrowing');
define('DB_USER', '4692567_bookborrowing');
define('DB_PASS', 'BookBorrowing456');          // WAMP default is empty string

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
            <h2>Database connection failed</h2>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <p>Check your credentials in <code>config.php</code> and make sure WAMP is running.</p>
         </div>');
}

// ── Session helper ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: index.php');
        exit;
    }
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Auto-mark overdue records
$pdo->exec("UPDATE borrow_records
            SET status = 'overdue'
            WHERE status = 'borrowed'
              AND due_date < CURDATE()");
