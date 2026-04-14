<?php
// includes/header.php — shared sidebar + HTML head
// $pageTitle and $activePage must be set before including this file.
requireLogin();
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($pageTitle ?? 'LibTrack') ?> — LibTrack</title>
  <link rel="stylesheet" href="<?= $root ?? '' ?>assets/css/style.css">
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <svg viewBox="0 0 28 28" fill="none">
          <rect x="3"  y="4" width="5" height="18" rx="1" fill="#fff" opacity=".9"/>
          <rect x="10" y="4" width="5" height="18" rx="1" fill="#fff" opacity=".7"/>
          <rect x="17" y="4" width="5" height="18" rx="1" fill="#fff" opacity=".9"/>
          <rect x="3"  y="21" width="19" height="2" rx="1" fill="#fff" opacity=".5"/>
          <path d="M10 8h5M10 11h5M10 14h3" stroke="#1a3a2a" stroke-width="1.2" stroke-linecap="round"/>
        </svg>
      </div>
      <div>
        <div class="logo-text">LibTrack</div>
        <div class="logo-sub">Library System</div>
      </div>
    </div>

    <nav class="nav">
      <span class="nav-section">Main</span>
      <a href="<?= $root ?? '' ?>dashboard.php" class="<?= $activePage==='dashboard'?'active':'' ?>">
        <span class="nav-icon">📊</span> Dashboard
      </a>

      <span class="nav-section">Manage</span>
      <a href="<?= $root ?? '' ?>borrowers.php" class="<?= $activePage==='borrowers'?'active':'' ?>">
        <span class="nav-icon">👤</span> Borrowers
      </a>
      <a href="<?= $root ?? '' ?>books.php" class="<?= $activePage==='books'?'active':'' ?>">
        <span class="nav-icon">📚</span> Books
      </a>
      <a href="<?= $root ?? '' ?>borrowing.php" class="<?= $activePage==='borrowing'?'active':'' ?>">
        <span class="nav-icon">🔁</span> Borrowing
      </a>

      <span class="nav-section">Reports</span>
      <a href="<?= $root ?? '' ?>reports.php" class="<?= $activePage==='reports'?'active':'' ?>">
        <span class="nav-icon">📄</span> Reports
      </a>
    </nav>

    <div class="sidebar-footer">
      <span style="color:rgba(255,255,255,.4);font-size:12px;">
        Logged in as <strong style="color:rgba(255,255,255,.7)"><?= sanitize($_SESSION['admin_name'] ?? '') ?></strong>
      </span>
      <a href="<?= $root ?? '' ?>logout.php" style="margin-top:8px;">
        <span>🚪</span> Logout
      </a>
    </div>
  </aside>

  <!-- Main content -->
  <main class="main">
