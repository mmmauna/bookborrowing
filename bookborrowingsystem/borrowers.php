<?php
require 'config.php';
$pageTitle  = 'Borrowers';
$activePage = 'borrowers';

$action  = $_GET['action']  ?? 'list';
$id      = (int)($_GET['id'] ?? 0);
$message = '';
$msgType = 'success';

// ── DELETE ────────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    // Check for active borrows first
    $active = $pdo->prepare("SELECT COUNT(*) FROM borrow_records WHERE borrower_id = ? AND status != 'returned'");
    $active->execute([$id]);
    if ($active->fetchColumn() > 0) {
        $message = 'Cannot delete — this borrower has unreturned books.';
        $msgType = 'danger';
        $action  = 'list';
    } else {
        $pdo->prepare("DELETE FROM borrowers WHERE borrower_id = ?")->execute([$id]);
        header('Location: borrowers.php?msg=deleted');
        exit;
    }
}

// ── ADD / EDIT SAVE ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email   = trim($_POST['email']   ?? '');
    $editId  = (int)($_POST['edit_id'] ?? 0);

    if ($name === '') {
        $message = 'Name is required.';
        $msgType = 'danger';
    } else {
        if ($editId) {
            $pdo->prepare("UPDATE borrowers SET name=?,contact=?,address=?,email=? WHERE borrower_id=?")
                ->execute([$name,$contact,$address,$email,$editId]);
            header('Location: borrowers.php?msg=updated');
        } else {
            $pdo->prepare("INSERT INTO borrowers (name,contact,address,email) VALUES (?,?,?,?)")
                ->execute([$name,$contact,$address,$email]);
            header('Location: borrowers.php?msg=added');
        }
        exit;
    }
}

// ── Flash messages ────────────────────────────────────────────
$flashMap = [
    'added'   => ['Borrower added successfully.', 'success'],
    'updated' => ['Borrower updated successfully.', 'success'],
    'deleted' => ['Borrower deleted.', 'success'],
];
if (isset($_GET['msg'], $flashMap[$_GET['msg']])) {
    [$message, $msgType] = $flashMap[$_GET['msg']];
}

// ── Load edit record ──────────────────────────────────────────
$editing = null;
if ($action === 'edit' && $id) {
    $editing = $pdo->prepare("SELECT * FROM borrowers WHERE borrower_id = ?");
    $editing->execute([$id]);
    $editing = $editing->fetch();
}

// ── List ──────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM borrowers WHERE name LIKE ? OR email LIKE ? ORDER BY name");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM borrowers ORDER BY name");
}
$borrowers = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="page-header">
  <h1>Borrowers</h1>
  <a href="borrowers.php?action=add" class="btn btn-primary">+ Add borrower</a>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- ── Form ── -->
<div class="card" style="max-width:600px;margin-bottom:1.5rem;">
  <h2 style="font-size:17px;margin-bottom:1rem;">
    <?= $action === 'edit' ? 'Edit borrower' : 'Add new borrower' ?>
  </h2>
  <form method="POST">
    <?php if ($editing): ?>
      <input type="hidden" name="edit_id" value="<?= $editing['borrower_id'] ?>">
    <?php endif; ?>
    <div class="form-grid" style="grid-template-columns:1fr 1fr;">
      <div class="form-group" style="grid-column:1/-1;">
        <label>Full name *</label>
        <input type="text" name="name" required
               value="<?= sanitize($editing['name'] ?? ($_POST['name'] ?? '')) ?>"
               placeholder="e.g. Maria Santos">
      </div>
      <div class="form-group">
        <label>Contact number</label>
        <input type="text" name="contact"
               value="<?= sanitize($editing['contact'] ?? '') ?>"
               placeholder="09XXXXXXXXX">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email"
               value="<?= sanitize($editing['email'] ?? '') ?>"
               placeholder="name@example.com">
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <label>Address</label>
        <input type="text" name="address"
               value="<?= sanitize($editing['address'] ?? '') ?>"
               placeholder="Street, Barangay, City">
      </div>
    </div>
    <div style="display:flex;gap:.75rem;margin-top:1rem;">
      <button type="submit" class="btn btn-primary">
        <?= $action === 'edit' ? 'Save changes' : 'Add borrower' ?>
      </button>
      <a href="borrowers.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- ── Search + table ── -->
<form method="GET" class="search-bar">
  <input type="hidden" name="action" value="list">
  <input type="text" name="q" placeholder="Search by name or email…"
         value="<?= sanitize($search) ?>">
  <button type="submit" class="btn btn-secondary">Search</button>
  <?php if ($search): ?>
    <a href="borrowers.php" class="btn btn-secondary">Clear</a>
  <?php endif; ?>
</form>

<?php if (empty($borrowers)): ?>
  <div class="empty-state card">
    <div class="icon">👤</div>
    <p><?= $search ? 'No borrowers found matching your search.' : 'No borrowers yet. Add one above.' ?></p>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Contact</th>
          <th>Email</th>
          <th>Address</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($borrowers as $b): ?>
        <tr>
          <td><?= $b['borrower_id'] ?></td>
          <td><?= sanitize($b['name']) ?></td>
          <td><?= sanitize($b['contact']) ?: '—' ?></td>
          <td><?= sanitize($b['email']) ?: '—' ?></td>
          <td><?= sanitize($b['address']) ?: '—' ?></td>
          <td>
            <a href="borrowers.php?action=edit&id=<?= $b['borrower_id'] ?>"
               class="btn btn-sm btn-secondary">Edit</a>
            <a href="borrowers.php?action=delete&id=<?= $b['borrower_id'] ?>"
               class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this borrower?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
