<?php
require 'config.php';
$pageTitle  = 'Reports';
$activePage = 'reports';

$report = $_GET['report'] ?? 'borrowed';

// ── Borrowed books ────────────────────────────────────────────
$borrowed = $pdo->query("
    SELECT br.record_id, b.name AS borrower, b.contact, bk.title, bk.author,
           br.borrow_date, br.due_date, br.status
    FROM borrow_records br
    JOIN borrowers b  ON b.borrower_id = br.borrower_id
    JOIN books     bk ON bk.book_id    = br.book_id
    WHERE br.status IN ('borrowed','overdue')
    ORDER BY br.status DESC, br.due_date ASC
")->fetchAll();

// ── Available books ───────────────────────────────────────────
$available = $pdo->query("
    SELECT book_id, title, author, isbn FROM books WHERE available=1 ORDER BY title
")->fetchAll();

// ── Overdue ───────────────────────────────────────────────────
$overdue = $pdo->query("
    SELECT br.record_id, b.name AS borrower, b.contact, bk.title,
           br.borrow_date, br.due_date,
           DATEDIFF(CURDATE(), br.due_date) AS days_overdue
    FROM borrow_records br
    JOIN borrowers b  ON b.borrower_id = br.borrower_id
    JOIN books     bk ON bk.book_id    = br.book_id
    WHERE br.status = 'overdue'
    ORDER BY br.due_date ASC
")->fetchAll();

// ── Transaction history ───────────────────────────────────────
$history = $pdo->query("
    SELECT br.record_id, b.name AS borrower, bk.title,
           br.borrow_date, br.due_date, br.return_date, br.status
    FROM borrow_records br
    JOIN borrowers b  ON b.borrower_id = br.borrower_id
    JOIN books     bk ON bk.book_id    = br.book_id
    ORDER BY br.record_id DESC
    LIMIT 100
")->fetchAll();

require 'includes/header.php';
?>

<div class="page-header">
  <h1>Reports</h1>
  <span style="font-size:13px;color:var(--text-muted);">Generated <?= date('M j, Y g:i A') ?></span>
</div>

<!-- Report tabs -->
<div style="display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap;">
  <?php
  $tabs = [
    'borrowed'  => ['🔁 Borrowed books',   count($borrowed)],
    'available' => ['✅ Available books',   count($available)],
    'overdue'   => ['⚠️ Overdue books',    count($overdue)],
    'history'   => ['📋 Transaction history', count($history)],
  ];
  foreach ($tabs as $key => [$label, $count]):
  ?>
    <a href="reports.php?report=<?= $key ?>"
       class="btn <?= $report===$key ? 'btn-primary' : 'btn-secondary' ?>">
      <?= $label ?>
      <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:12px;font-size:12px;margin-left:4px;">
        <?= $count ?>
      </span>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($report === 'borrowed'): ?>
  <h2 style="font-size:16px;margin-bottom:.75rem;color:var(--green-dark);">Currently borrowed books</h2>
  <?php if (empty($borrowed)): ?>
    <div class="empty-state card"><div class="icon">🎉</div><p>All books are currently available!</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Borrower</th><th>Contact</th><th>Book</th><th>Author</th><th>Borrow date</th><th>Due date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($borrowed as $r): ?>
        <tr>
          <td><?= $r['record_id'] ?></td>
          <td><?= sanitize($r['borrower']) ?></td>
          <td><?= sanitize($r['contact']) ?: '—' ?></td>
          <td><?= sanitize($r['title']) ?></td>
          <td><?= sanitize($r['author']) ?></td>
          <td><?= $r['borrow_date'] ?></td>
          <td><?= $r['due_date'] ?></td>
          <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

<?php elseif ($report === 'available'): ?>
  <h2 style="font-size:16px;margin-bottom:.75rem;color:var(--green-dark);">Available books</h2>
  <?php if (empty($available)): ?>
    <div class="empty-state card"><div class="icon">📚</div><p>No books are currently available.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Author</th><th>ISBN</th></tr></thead>
      <tbody>
        <?php foreach ($available as $b): ?>
        <tr>
          <td><?= $b['book_id'] ?></td>
          <td><?= sanitize($b['title']) ?></td>
          <td><?= sanitize($b['author']) ?></td>
          <td><?= sanitize($b['isbn']) ?: '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

<?php elseif ($report === 'overdue'): ?>
  <h2 style="font-size:16px;margin-bottom:.75rem;color:var(--danger);">⚠️ Overdue books</h2>
  <?php if (empty($overdue)): ?>
    <div class="empty-state card"><div class="icon">🎉</div><p>No overdue books — great!</p></div>
  <?php else: ?>
  <div class="alert alert-warning">
    There are <strong><?= count($overdue) ?></strong> overdue book(s). Please contact the borrowers immediately.
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Borrower</th><th>Contact</th><th>Book</th><th>Borrow date</th><th>Due date</th><th>Days overdue</th></tr></thead>
      <tbody>
        <?php foreach ($overdue as $r): ?>
        <tr style="background:#fff5f5;">
          <td><?= $r['record_id'] ?></td>
          <td><?= sanitize($r['borrower']) ?></td>
          <td><?= sanitize($r['contact']) ?: '—' ?></td>
          <td><?= sanitize($r['title']) ?></td>
          <td><?= $r['borrow_date'] ?></td>
          <td><?= $r['due_date'] ?></td>
          <td><strong style="color:var(--danger);"><?= $r['days_overdue'] ?> day(s)</strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

<?php elseif ($report === 'history'): ?>
  <h2 style="font-size:16px;margin-bottom:.75rem;color:var(--green-dark);">Transaction history <span style="font-size:13px;color:var(--text-muted);font-weight:400;">(last 100 records)</span></h2>
  <?php if (empty($history)): ?>
    <div class="empty-state card"><div class="icon">📋</div><p>No transactions yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Borrower</th><th>Book</th><th>Borrow date</th><th>Due date</th><th>Return date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($history as $r): ?>
        <tr>
          <td><?= $r['record_id'] ?></td>
          <td><?= sanitize($r['borrower']) ?></td>
          <td><?= sanitize($r['title']) ?></td>
          <td><?= $r['borrow_date'] ?></td>
          <td><?= $r['due_date'] ?></td>
          <td><?= $r['return_date'] ?? '—' ?></td>
          <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
