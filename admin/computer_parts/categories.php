<?php
require_once __DIR__ . '/../../includes/functions.php';
$user = require_login('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); $a = $_POST['action'] ?? ''; $name = trim($_POST['name'] ?? '');
    try {
        if ($a === 'add' && $name !== '') { db()->prepare('INSERT INTO computer_categories (name,description) VALUES (?,?)')->execute([$name, trim($_POST['description'] ?? '')]); flash('success', 'Category added.'); }
        elseif ($a === 'edit' && $name !== '') { db()->prepare('UPDATE computer_categories SET name=?,description=? WHERE id=?')->execute([$name, trim($_POST['description'] ?? ''), (int)$_POST['id']]); flash('success', 'Category updated.'); }
        elseif ($a === 'delete') { db()->prepare('DELETE FROM computer_categories WHERE id=?')->execute([(int)$_POST['id']]); flash('success', 'Category deleted.'); }
    } catch (PDOException $x) { flash('error', 'Cannot save: the name exists or parts still use this category.'); }
    redirect('admin/computer_parts/categories.php');
}
$rows = db()->query('SELECT c.*, (SELECT COUNT(*) FROM computer_parts p WHERE p.category_id=c.id) n FROM computer_categories c ORDER BY name')->fetchAll();
$pageTitle = 'Part Categories'; $activeNav = 'parts';
include __DIR__ . '/../../includes/header.php';
?>
<div class="card p-3 mb-3"><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="add"><div class="col-md-4"><input name="name" class="form-control" placeholder="Category name" required></div><div class="col-md-6"><input name="description" class="form-control" placeholder="Description"></div><div class="col-md-2"><button class="btn btn-success w-100">Add</button></div></form></div>
<div class="card p-3"><?php foreach ($rows as $r): ?><form method="post" class="row g-2 mb-2 align-items-center"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
<div class="col-md-3"><input name="name" class="form-control" value="<?= e($r['name']) ?>"></div><div class="col-md-5"><input name="description" class="form-control" value="<?= e($r['description']) ?>"></div><div class="col-md-1"><span class="badge text-bg-light"><?= $r['n'] ?> parts</span></div>
<div class="col-md-3"><button name="action" value="edit" class="btn btn-outline-primary btn-sm">Save</button> <button name="action" value="delete" class="btn btn-outline-danger btn-sm" data-confirm="Delete category?">Delete</button></div></form><?php endforeach; ?></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
