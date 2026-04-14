<?php
require 'config.php';
$pageTitle  = 'Borrowing';
$activePage = 'borrowing';

$action  = $_GET['action']  ?? 'list';
$id      = (int)($_GET['id'] ?? 0);
$message = '';
$msgType = 'success';

// ── RETURN a book ─────────────────────────────────────────────
if ($action === 'return' && $id) {
    $stmt = $pdo->prepare("SELECT br.*, bk.book_id FROM borrow_records br JOIN books bk ON bk.book_id = br.book_id WHERE br.record_id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if ($rec && $rec['status'] !== 'returned') {
        $pdo->prepare("UPDATE borrow_records SET status='returned', return_date=CURDATE() WHERE record_id=?")
            ->execute([$id]);
        $pdo->prepare("UPDATE books SET available=1 WHERE book_id=?")
            ->execute([$rec['book_id']]);
        header('Location: borrowing.php?msg=returned');
        exit;
    }
}

// ── BORROW save ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'borrow') {
    $borrowerId = (int)($_POST['borrower_id'] ?? 0);
    $bookId     = (int)($_POST['book_id']     ?? 0);
    $dueDate    = trim($_POST['due_date']     ?? '');

    if (!$borrowerId || !$bookId || !$dueDate) {
        $message = 'All fields are required.';
        $msgType = 'danger';
        $action  = 'borrow';
    } else {
        // Prevent duplicate: same borrower same book not yet returned
        $dup = $pdo->prepare("SELECT record_id FROM borrow_records WHERE borrower_id=? AND book_id=? AND status != 'returned'");
        $dup->execute([$borrowerId, $bookId]);
        if ($dup->fetch()) {
            $message = 'This borrower already has this book and has not returned it yet.';
            $msgType = 'danger';
            $action  = 'borrow';
        } else {
            // Check book is available
            $avail = $pdo->prepare("SELECT available FROM books WHERE book_id=?");
            $avail->execute([$bookId]);
            $book = $avail->fetch();
            if (!$book || !$book['available']) {
                $message = 'This book is currently not available.';
                $msgType = 'danger';
                $action  = 'borrow';
            } else {
                $pdo->prepare("INSERT INTO borrow_records (borrower_id,book_id,borrow_date,due_date,status) VALUES (?,?,CURDATE(),?,'borrowed')")
                    ->execute([$borrowerId, $bookId, $dueDate]);
                $pdo->prepare("UPDATE books SET available=0 WHERE book_id=?")->execute([$bookId]);
                header('Location: borrowing.php?msg=borrowed');
                exit;
            }
        }
    }
}

// ── Flash messages ────────────────────────────────────────────
$flashMap = [
    'borrowed' => ['Book borrowed successfully.', 'success'],
    'returned' => ['Book returned successfully.', 'success'],
];
if (isset($_GET['msg'], $flashMap[$_GET['msg']])) {
    [$message, $msgType] = $flashMap[$_GET['msg']];
}

// ── Load data for borrow form ─────────────────────────────────
$borrowers    = $pdo->query("SELECT borrower_id, name FROM borrowers ORDER BY name")->fetchAll();
$availBooks   = $pdo->query("SELECT book_id, title, author FROM books WHERE available=1 ORDER BY title")->fetchAll();

// ── List records ──────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[]  = "br.status = ?";
    $params[] = $statusFilter;
}
if ($search) {
    $where[]  = "(b.name LIKE ? OR bk.title LIKE ?)";
    $params   = array_merge($params, ["%$search%", "%$search%"]);
}

$sql = "SELECT br.record_id, b.name AS borrower, bk.title, br.borrow_date, br.due_date, br.return_date, br.status
        FROM borrow_records br
        JOIN borrowers b  ON b.borrower_id = br.borrower_id
        JOIN books     bk ON bk.book_id    = br.book_id"
     . ($where ? " WHERE " . implode(' AND ', $where) : '')
     . " ORDER BY br.record_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="page-header">
  <h1>Borrowing</h1>
  <a href="borrowing.php?action=borrow" class="btn btn-primary">+ Borrow a book</a>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<?php if ($action === 'borrow'): ?>
<!-- ── Borrow form ── -->
<div class="card" style="max-width:560px;margin-bottom:1.5rem;">
  <h2 style="font-size:17px;margin-bottom:1rem;">Borrow a book</h2>
  <?php if (empty($borrowers)): ?>
    <div class="alert alert-warning">No borrowers registered. <a href="borrowers.php?action=add">Add a borrower first →</a></div>
  <?php elseif (empty($availBooks)): ?>
    <div class="alert alert-warning">No books are currently available to borrow.</div>
  <?php else: ?>
  <form method="POST">
    <input type="hidden" name="_action" value="borrow">
    <div class="form-grid">
      <div class="form-group">
        <label>Borrower *</label>
        <select name="borrower_id" required>
          <option value="">— Select borrower —</option>
          <?php foreach ($borrowers as $bwr): ?>
            <option value="<?= $bwr['borrower_id'] ?>"
              <?= ($_POST['borrower_id'] ?? '')==$bwr['borrower_id']?'selected':'' ?>>
              <?= sanitize($bwr['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Book *</label>
        <select name="book_id" required>
          <option value="">— Select available book —</option>
          <?php foreach ($availBooks as $bk): ?>
            <option value="<?= $bk['book_id'] ?>"
              <?= ($_POST['book_id'] ?? '')==$bk['book_id']?'selected':'' ?>>
              <?= sanitize($bk['title']) ?> — <?= sanitize($bk['author']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Due date *</label>
        <input type="date" name="due_date" required
               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
               value="<?= sanitize($_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days'))) ?>">
      </div>
    </div>
    <div style="display:flex;gap:.75rem;margin-top:1rem;">
      <button type="submit" class="btn btn-primary">Confirm borrow</button>
      <a href="borrowing.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Filters ── -->
<form method="GET" class="search-bar" style="flex-wrap:wrap;">
  <input type="text" name="q" placeholder="Search borrower or book…"
         value="<?= sanitize($search) ?>">
  <select name="status" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:14px;">
    <option value="all"      <?= $statusFilter==='all'?'selected':'' ?>>All records</option>
    <option value="borrowed" <?= $statusFilter==='borrowed'?'selected':'' ?>>Borrowed</option>
    <option value="overdue"  <?= $statusFilter==='overdue'?'selected':'' ?>>Overdue</option>
    <option value="returned" <?= $statusFilter==='returned'?'selected':'' ?>>Returned</option>
  </select>
  <button type="submit" class="btn btn-secondary">Filter</button>
  <?php if ($search || $statusFilter !== 'all'): ?>
    <a href="borrowing.php" class="btn btn-secondary">Clear</a>
  <?php endif; ?>
</form>

<?php if (empty($records)): ?>
  <div class="empty-state card">
    <div class="icon">🔁</div>
    <p>No records found.</p>
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
          <th>Return date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
          <td><?= $r['record_id'] ?></td>
          <td><?= sanitize($r['borrower']) ?></td>
          <td><?= sanitize($r['title']) ?></td>
          <td><?= $r['borrow_date'] ?></td>
          <td><?= $r['due_date'] ?></td>
          <td><?= $r['return_date'] ?? '—' ?></td>
          <td>
            <span class="badge badge-<?= $r['status'] ?>">
              <?= ucfirst($r['status']) ?>
            </span>
          </td>
          <td>
            <?php if ($r['status'] !== 'returned'): ?>
              <a href="borrowing.php?action=return&id=<?= $r['record_id'] ?>"
                 class="btn btn-sm btn-secondary"
                 onclick="return confirm('Mark this book as returned?')">
                ↩ Return
              </a>
            <?php else: ?>
              <span style="color:var(--text-muted);font-size:13px;">Done</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
