<?php
require 'config.php';
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require 'includes/header.php';

// ── Stats ────────────────────────────────────────────────────
$totalBooks     = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$availableBooks = $pdo->query("SELECT COUNT(*) FROM books WHERE available = 1")->fetchColumn();
$totalBorrowers = $pdo->query("SELECT COUNT(*) FROM borrowers")->fetchColumn();
$borrowed       = $pdo->query("SELECT COUNT(*) FROM borrow_records WHERE status = 'borrowed'")->fetchColumn();
$overdue        = $pdo->query("SELECT COUNT(*) FROM borrow_records WHERE status = 'overdue'")->fetchColumn();
$returnedToday  = $pdo->query("SELECT COUNT(*) FROM borrow_records WHERE status = 'returned' AND return_date = CURDATE()")->fetchColumn();

// ── Recent borrowing activity ─────────────────────────────────
$recent = $pdo->query("
    SELECT br.record_id, b.name AS borrower, bk.title, br.borrow_date, br.due_date, br.status
    FROM borrow_records br
    JOIN borrowers b  ON b.borrower_id = br.borrower_id
    JOIN books     bk ON bk.book_id    = br.book_id
    ORDER BY br.record_id DESC
    LIMIT 8
")->fetchAll();
?>

<div class="page-header">
  <h1>Dashboard</h1>
  <span style="font-size:13px;color:var(--text-muted);">
    <?= date('l, F j, Y') ?>
  </span>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📚</div>
    <div class="stat-num"><?= $totalBooks ?></div>
    <div class="stat-lbl">Total books</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div class="stat-num"><?= $availableBooks ?></div>
    <div class="stat-lbl">Available</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🔁</div>
    <div class="stat-num"><?= $borrowed ?></div>
    <div class="stat-lbl">Currently borrowed</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">👤</div>
    <div class="stat-num"><?= $totalBorrowers ?></div>
    <div class="stat-lbl">Borrowers</div>
  </div>
  <div class="stat-card <?= $overdue > 0 ? 'danger' : '' ?>">
    <div class="stat-icon">⚠️</div>
    <div class="stat-num"><?= $overdue ?></div>
    <div class="stat-lbl">Overdue</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">↩️</div>
    <div class="stat-num"><?= $returnedToday ?></div>
    <div class="stat-lbl">Returned today</div>
  </div>
</div>

<!-- Quick actions -->
<div style="display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
  <a href="borrowing.php?action=borrow" class="btn btn-primary">+ Borrow a book</a>
  <a href="borrowers.php?action=add"    class="btn btn-secondary">+ Add borrower</a>
  <a href="books.php?action=add"        class="btn btn-secondary">+ Add book</a>
  <a href="reports.php"                 class="btn btn-secondary">📄 Reports</a>
</div>

<!-- Recent activity -->
<div class="page-header" style="margin-bottom:.75rem;">
  <h2 style="font-size:17px;font-family:'Playfair Display',serif;color:var(--green-dark);">Recent borrowing activity</h2>
  <a href="borrowing.php" class="btn btn-sm btn-secondary">View all</a>
</div>

<?php if (empty($recent)): ?>
  <div class="empty-state card">
    <div class="icon">📖</div>
    <p>No borrowing records yet. <a href="borrowing.php?action=borrow">Borrow the first book →</a></p>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Borrower</th>
          <th>Book</th>
          <th>Borrow date</th>
          <th>Due date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= $r['record_id'] ?></td>
          <td><?= sanitize($r['borrower']) ?></td>
          <td><?= sanitize($r['title']) ?></td>
          <td><?= $r['borrow_date'] ?></td>
          <td><?= $r['due_date'] ?></td>
          <td>
            <span class="badge badge-<?= $r['status'] ?>">
              <?= ucfirst($r['status']) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
