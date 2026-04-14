<?php
require 'config.php';
$pageTitle  = 'Books';
$activePage = 'books';

$action  = $_GET['action']  ?? 'list';
$id      = (int)($_GET['id'] ?? 0);
$message = '';
$msgType = 'success';

// ── DELETE ────────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $active = $pdo->prepare("SELECT COUNT(*) FROM borrow_records WHERE book_id = ? AND status != 'returned'");
    $active->execute([$id]);
    if ($active->fetchColumn() > 0) {
        $message = 'Cannot delete — this book is currently borrowed.';
        $msgType = 'danger';
        $action  = 'list';
    } else {
        $pdo->prepare("DELETE FROM books WHERE book_id = ?")->execute([$id]);
        header('Location: books.php?msg=deleted');
        exit;
    }
}

// ── ADD / EDIT SAVE ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title  = trim($_POST['title']  ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn   = trim($_POST['isbn']   ?? '');
    $editId = (int)($_POST['edit_id'] ?? 0);

    if ($title === '' || $author === '') {
        $message = 'Title and Author are required.';
        $msgType = 'danger';
    } else {
        // Check duplicate ISBN
        if ($isbn !== '') {
            $check = $pdo->prepare("SELECT book_id FROM books WHERE isbn = ? AND book_id != ?");
            $check->execute([$isbn, $editId]);
            if ($check->fetch()) {
                $message = 'A book with this ISBN already exists.';
                $msgType = 'danger';
                goto renderPage;
            }
        }

        if ($editId) {
            $pdo->prepare("UPDATE books SET title=?,author=?,isbn=? WHERE book_id=?")
                ->execute([$title,$author,$isbn ?: null,$editId]);
            header('Location: books.php?msg=updated');
        } else {
            $pdo->prepare("INSERT INTO books (title,author,isbn) VALUES (?,?,?)")
                ->execute([$title,$author,$isbn ?: null]);
            header('Location: books.php?msg=added');
        }
        exit;
    }
}

// ── Flash messages ────────────────────────────────────────────
$flashMap = [
    'added'   => ['Book added successfully.', 'success'],
    'updated' => ['Book updated successfully.', 'success'],
    'deleted' => ['Book deleted.', 'success'],
];
if (isset($_GET['msg'], $flashMap[$_GET['msg']])) {
    [$message, $msgType] = $flashMap[$_GET['msg']];
}

// ── Load edit record ──────────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
}

// ── List ──────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$where = [];
$params = [];

if ($search) {
    $where[]  = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $params   = array_merge($params, ["%$search%","%$search%","%$search%"]);
}
if ($filter === 'available') { $where[] = "available = 1"; }
if ($filter === 'borrowed')  { $where[] = "available = 0"; }

$sql = "SELECT * FROM books" . ($where ? " WHERE " . implode(' AND ', $where) : '') . " ORDER BY title";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

renderPage:
require 'includes/header.php';
?>

<div class="page-header">
  <h1>Books</h1>
  <a href="books.php?action=add" class="btn btn-primary">+ Add book</a>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="card" style="max-width:540px;margin-bottom:1.5rem;">
  <h2 style="font-size:17px;margin-bottom:1rem;">
    <?= $action === 'edit' ? 'Edit book' : 'Add new book' ?>
  </h2>
  <form method="POST">
    <?php if ($editing): ?>
      <input type="hidden" name="edit_id" value="<?= $editing['book_id'] ?>">
    <?php endif; ?>
    <div class="form-grid">
      <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" required
               value="<?= sanitize($editing['title'] ?? '') ?>"
               placeholder="Book title">
      </div>
      <div class="form-group">
        <label>Author *</label>
        <input type="text" name="author" required
               value="<?= sanitize($editing['author'] ?? '') ?>"
               placeholder="Author name">
      </div>
      <div class="form-group">
        <label>ISBN</label>
        <input type="text" name="isbn"
               value="<?= sanitize($editing['isbn'] ?? '') ?>"
               placeholder="e.g. 978-0-06-112008-4">
      </div>
    </div>
    <div style="display:flex;gap:.75rem;margin-top:1rem;">
      <button type="submit" class="btn btn-primary">
        <?= $action === 'edit' ? 'Save changes' : 'Add book' ?>
      </button>
      <a href="books.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Search & filter -->
<form method="GET" class="search-bar" style="flex-wrap:wrap;">
  <input type="text" name="q" placeholder="Search title, author, ISBN…"
         value="<?= sanitize($search) ?>">
  <select name="filter" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:14px;">
    <option value="all"       <?= $filter==='all'?'selected':'' ?>>All books</option>
    <option value="available" <?= $filter==='available'?'selected':'' ?>>Available only</option>
    <option value="borrowed"  <?= $filter==='borrowed'?'selected':'' ?>>Currently borrowed</option>
  </select>
  <button type="submit" class="btn btn-secondary">Filter</button>
  <?php if ($search || $filter !== 'all'): ?>
    <a href="books.php" class="btn btn-secondary">Clear</a>
  <?php endif; ?>
</form>

<?php if (empty($books)): ?>
  <div class="empty-state card">
    <div class="icon">📚</div>
    <p><?= $search ? 'No books match your search.' : 'No books yet. Add one above.' ?></p>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Author</th>
          <th>ISBN</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($books as $b): ?>
        <tr>
          <td><?= $b['book_id'] ?></td>
          <td><?= sanitize($b['title']) ?></td>
          <td><?= sanitize($b['author']) ?></td>
          <td><?= sanitize($b['isbn']) ?: '—' ?></td>
          <td>
            <span class="badge <?= $b['available'] ? 'badge-returned' : 'badge-borrowed' ?>">
              <?= $b['available'] ? 'Available' : 'Borrowed' ?>
            </span>
          </td>
          <td>
            <a href="books.php?action=edit&id=<?= $b['book_id'] ?>"
               class="btn btn-sm btn-secondary">Edit</a>
            <?php if ($b['available']): ?>
            <a href="books.php?action=delete&id=<?= $b['book_id'] ?>"
               class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this book?')">Delete</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
