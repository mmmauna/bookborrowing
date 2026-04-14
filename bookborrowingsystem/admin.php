<?php
require 'config.php';

$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$fullname = 'Administrator';

$stmt = $pdo->prepare("INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)");
$stmt->execute([$username, $password, $fullname]);

echo "Admin account created successfully!";